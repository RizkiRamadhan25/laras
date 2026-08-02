<?php

namespace App\Http\Controllers;

use App\Enums\ActivityPriority;
use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Http\Requests\FilterActivitiesRequest;
use App\Http\Requests\StoreActivityRequest;
use App\Models\Activity;
use App\Services\ActivityRecommendationService;
use App\Services\ActivityService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function __construct(
        private readonly ActivityService $activityService,
        private readonly ActivityRecommendationService $recommendationService
    ) {}

    public function index(
        FilterActivitiesRequest $request
    ): View {
        $filters = $request->validated();

        $selectedView = $filters['view']
            ?? 'open';

        return $this->renderIndex(
            request: $request,
            filters: $filters,
            selectedView: $selectedView,
            priorityPage: false
        );
    }

    public function priorities(
        FilterActivitiesRequest $request
    ): View {
        $filters = $request->validated();

        $filters['view'] = 'priority';

        return $this->renderIndex(
            request: $request,
            filters: $filters,
            selectedView: 'priority',
            priorityPage: true
        );
    }

    public function create(
        Request $request
    ): View {
        $user = $request->user()->load(
            'preference'
        );

        $selectedType = ActivityType::tryFrom(
            (string) $request->query(
                'type',
                ActivityType::Task->value
            )
        ) ?? ActivityType::Task;

        return view('activities.create', [
            'user' => $user,
            'activityTypes' => ActivityType::cases(),
            'priorities' => ActivityPriority::cases(),
            'selectedType' => $selectedType,
        ]);
    }

    public function store(
        StoreActivityRequest $request
    ): RedirectResponse {
        try {
            $activity = $this->activityService->create(
                user: $request->user(),
                data: $request->validated()
            );
        } catch (DomainException $exception) {
            return back()
                ->withInput()
                ->with(
                    'warning',
                    $exception->getMessage()
                );
        }

        return redirect()
            ->route(
                'activities.edit',
                $activity->id
            )
            ->with(
                'status',
                'Aktivitas berhasil ditambahkan.'
            );
    }

    public function edit(
        Request $request,
        int $activity
    ): View {
        $ownedActivity = $request->user()
            ->activities()
            ->findOrFail($activity);

        return view('activities.edit', [
            'user' => $request->user()->load(
                'preference'
            ),
            'activity' => $ownedActivity,
            'activityTypes' => ActivityType::cases(),
            'priorities' => ActivityPriority::cases(),
            'selectedType' => $ownedActivity->type,
        ]);
    }

    public function update(
        StoreActivityRequest $request,
        int $activity
    ): RedirectResponse {
        try {
            $updatedActivity =
                $this->activityService->update(
                    user: $request->user(),
                    activityId: $activity,
                    data: $request->validated()
                );
        } catch (DomainException $exception) {
            return back()
                ->withInput()
                ->with(
                    'warning',
                    $exception->getMessage()
                );
        }

        return redirect()
            ->route(
                'activities.edit',
                $updatedActivity->id
            )
            ->with(
                'status',
                'Aktivitas berhasil diperbarui.'
            );
    }

    public function start(
        Request $request,
        int $activity
    ): RedirectResponse {
        return $this->runStatusAction(
            callback: fn (): Activity => $this->activityService->start(
                $request->user(),
                $activity
            ),

            successMessage: 'Aktivitas mulai dikerjakan.'
        );
    }

    public function complete(
        Request $request,
        int $activity
    ): RedirectResponse {
        return $this->runStatusAction(
            callback: fn (): Activity => $this->activityService->complete(
                $request->user(),
                $activity
            ),

            successMessage: 'Aktivitas berhasil diselesaikan.'
        );
    }

    public function cancel(
        Request $request,
        int $activity
    ): RedirectResponse {
        return $this->runStatusAction(
            callback: fn (): Activity => $this->activityService->cancel(
                $request->user(),
                $activity
            ),

            successMessage: 'Aktivitas berhasil dibatalkan.'
        );
    }

    public function reopen(
        Request $request,
        int $activity
    ): RedirectResponse {
        return $this->runStatusAction(
            callback: fn (): Activity => $this->activityService->reopen(
                $request->user(),
                $activity
            ),

            successMessage: 'Aktivitas dibuka kembali.'
        );
    }

    public function destroy(
        Request $request,
        int $activity
    ): RedirectResponse {
        $this->activityService->archive(
            user: $request->user(),
            activityId: $activity
        );

        return back()->with(
            'status',
            'Aktivitas berhasil diarsipkan.'
        );
    }

    public function restore(
        Request $request,
        int $activity
    ): RedirectResponse {
        $this->activityService->restore(
            user: $request->user(),
            activityId: $activity
        );

        return back()->with(
            'status',
            'Aktivitas berhasil dipulihkan.'
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function renderIndex(
        FilterActivitiesRequest $request,
        array $filters,
        string $selectedView,
        bool $priorityPage
    ): View {
        $user = $request->user()->load(
            'preference'
        );

        $timezone = $user->preference?->timezone
            ?? config('laras.defaults.timezone');

        $locale = $user->preference?->locale
            ?? config('laras.defaults.locale');

        $now = CarbonImmutable::now(
            $timezone
        )->locale($locale);

        $databaseNow = $now->setTimezone(
            config('app.timezone')
        );

        $todayStart = $now
            ->startOfDay()
            ->setTimezone(config('app.timezone'));

        $todayEnd = $now
            ->endOfDay()
            ->setTimezone(config('app.timezone'));

        $query = Activity::query()
            ->where('user_id', $user->id);

        if ($selectedView === 'archived') {
            $query->onlyTrashed();
        }

        $this->applyView(
            query: $query,
            selectedView: $selectedView,
            todayStart: $todayStart,
            todayEnd: $todayEnd
        );

        $query->when(
            $filters['search'] ?? null,
            function (
                Builder $query,
                string $search
            ): Builder {
                $escapedSearch = addcslashes(
                    $search,
                    '%_\\'
                );

                $pattern = '%'.$escapedSearch.'%';

                return $query->where(
                    function (
                        Builder $searchQuery
                    ) use ($pattern): void {
                        $searchQuery
                            ->where(
                                'title',
                                'like',
                                $pattern
                            )
                            ->orWhere(
                                'description',
                                'like',
                                $pattern
                            )
                            ->orWhere(
                                'location',
                                'like',
                                $pattern
                            );
                    }
                );
            }
        );

        $query->when(
            $filters['type'] ?? null,
            fn (
                Builder $query,
                string $type
            ): Builder => $query->where(
                'type',
                $type
            )
        );

        $query->when(
            $filters['priority'] ?? null,
            fn (
                Builder $query,
                string $priority
            ): Builder => $query->where(
                'priority',
                $priority
            )
        );

        $query->when(
            $filters['status'] ?? null,
            fn (
                Builder $query,
                string $status
            ): Builder => $query->where(
                'status',
                $status
            )
        );

        $dateFrom = isset($filters['date_from'])
            ? CarbonImmutable::createFromFormat(
                'Y-m-d',
                $filters['date_from'],
                $timezone
            )
                ->startOfDay()
                ->setTimezone(config('app.timezone'))
            : null;

        $dateTo = isset($filters['date_to'])
            ? CarbonImmutable::createFromFormat(
                'Y-m-d',
                $filters['date_to'],
                $timezone
            )
                ->endOfDay()
                ->setTimezone(config('app.timezone'))
            : null;

        $this->applyDateFilter(
            query: $query,
            dateFrom: $dateFrom,
            dateTo: $dateTo
        );

        if ($selectedView === 'archived') {
            $query
                ->orderByDesc('deleted_at')
                ->orderByDesc('id');
        } elseif ($selectedView === 'priority') {
            $query
                ->orderByRaw(
                    <<<'SQL'
                    CASE priority
                        WHEN 'urgent' THEN 4
                        WHEN 'high' THEN 3
                        WHEN 'medium' THEN 2
                        WHEN 'low' THEN 1
                        ELSE 0
                    END DESC
                    SQL
                )
                ->orderByRaw(
                    <<<'SQL'
                    CASE
                        WHEN due_at IS NULL
                            AND starts_at IS NULL
                        THEN 1
                        ELSE 0
                    END
                    SQL
                )
                ->orderByRaw(
                    'COALESCE(due_at, starts_at)'
                )
                ->orderBy('id');
        } else {
            $query
                ->orderByRaw(
                    <<<'SQL'
                    CASE
                        WHEN due_at IS NULL
                            AND starts_at IS NULL
                        THEN 1
                        ELSE 0
                    END
                    SQL
                )
                ->orderByRaw(
                    'COALESCE(due_at, starts_at)'
                )
                ->orderByRaw(
                    <<<'SQL'
                    CASE priority
                        WHEN 'urgent' THEN 4
                        WHEN 'high' THEN 3
                        WHEN 'medium' THEN 2
                        WHEN 'low' THEN 1
                        ELSE 0
                    END DESC
                    SQL
                )
                ->orderBy('id');
        }

        $activities = $query
            ->paginate(12)
            ->withQueryString();

        $todayActivities = $user->activities()
            ->open()
            ->where(function (
                Builder $query
            ) use (
                $todayStart,
                $todayEnd
            ): void {
                $query
                    ->whereBetween(
                        'starts_at',
                        [
                            $todayStart,
                            $todayEnd,
                        ]
                    )
                    ->orWhereBetween(
                        'due_at',
                        [
                            $todayStart,
                            $todayEnd,
                        ]
                    );
            })
            ->orderByRaw(
                'COALESCE(starts_at, due_at)'
            )
            ->limit(6)
            ->get();

        $recommendations =
            $this->recommendationService->rank(
                user: $user,
                reference: $databaseNow,
                limit: 5
            );

        $summary = [
            'open' => $user->activities()
                ->open()
                ->count(),

            'today' => $user->activities()
                ->open()
                ->where(function (
                    Builder $query
                ) use (
                    $todayStart,
                    $todayEnd
                ): void {
                    $query
                        ->whereBetween(
                            'starts_at',
                            [
                                $todayStart,
                                $todayEnd,
                            ]
                        )
                        ->orWhereBetween(
                            'due_at',
                            [
                                $todayStart,
                                $todayEnd,
                            ]
                        );
                })
                ->count(),

            'overdue' => $user->activities()
                ->open()
                ->whereNotNull('due_at')
                ->where(
                    'due_at',
                    '<',
                    $databaseNow
                )
                ->count(),

            'completed_month' => $user->activities()
                ->completed()
                ->whereBetween(
                    'completed_at',
                    [
                        $now
                            ->startOfMonth()
                            ->setTimezone(
                                config(
                                    'app.timezone'
                                )
                            ),

                        $now
                            ->endOfMonth()
                            ->setTimezone(
                                config(
                                    'app.timezone'
                                )
                            ),
                    ]
                )
                ->count(),
        ];

        return view('activities.index', [
            'user' => $user,
            'activities' => $activities,
            'todayActivities' => $todayActivities,
            'recommendations' => $recommendations,
            'summary' => $summary,

            'filters' => $filters,
            'selectedView' => $selectedView,
            'priorityPage' => $priorityPage,

            'activityTypes' => ActivityType::cases(),

            'priorities' => ActivityPriority::cases(),

            'statuses' => ActivityStatus::cases(),

            'timezone' => $timezone,
            'currentDate' => $now->translatedFormat(
                'l, d F Y'
            ),
        ]);
    }

    private function applyView(
        Builder $query,
        string $selectedView,
        CarbonImmutable $todayStart,
        CarbonImmutable $todayEnd
    ): void {
        match ($selectedView) {
            'open',
            'priority' => $query->open(),

            'today' => $query
                ->open()
                ->where(function (
                    Builder $query
                ) use (
                    $todayStart,
                    $todayEnd
                ): void {
                    $query
                        ->whereBetween(
                            'starts_at',
                            [
                                $todayStart,
                                $todayEnd,
                            ]
                        )
                        ->orWhereBetween(
                            'due_at',
                            [
                                $todayStart,
                                $todayEnd,
                            ]
                        );
                }),

            'completed' => $query->where(
                'status',
                ActivityStatus::Completed->value
            ),

            'cancelled' => $query->where(
                'status',
                ActivityStatus::Cancelled->value
            ),

            'archived' => null,

            default => $query->open(),
        };
    }

    private function applyDateFilter(
        Builder $query,
        ?CarbonImmutable $dateFrom,
        ?CarbonImmutable $dateTo
    ): void {
        if (
            $dateFrom === null
            && $dateTo === null
        ) {
            return;
        }

        $query->where(function (
            Builder $dateQuery
        ) use (
            $dateFrom,
            $dateTo
        ): void {
            $dateQuery
                ->where(function (
                    Builder $startQuery
                ) use (
                    $dateFrom,
                    $dateTo
                ): void {
                    $startQuery->whereNotNull(
                        'starts_at'
                    );

                    if ($dateFrom !== null) {
                        $startQuery->where(
                            'starts_at',
                            '>=',
                            $dateFrom
                        );
                    }

                    if ($dateTo !== null) {
                        $startQuery->where(
                            'starts_at',
                            '<=',
                            $dateTo
                        );
                    }
                })
                ->orWhere(function (
                    Builder $dueQuery
                ) use (
                    $dateFrom,
                    $dateTo
                ): void {
                    $dueQuery->whereNotNull(
                        'due_at'
                    );

                    if ($dateFrom !== null) {
                        $dueQuery->where(
                            'due_at',
                            '>=',
                            $dateFrom
                        );
                    }

                    if ($dateTo !== null) {
                        $dueQuery->where(
                            'due_at',
                            '<=',
                            $dateTo
                        );
                    }
                });
        });
    }

    /**
     * @param  callable(): Activity  $callback
     */
    private function runStatusAction(
        callable $callback,
        string $successMessage
    ): RedirectResponse {
        try {
            $callback();
        } catch (DomainException $exception) {
            return back()->with(
                'warning',
                $exception->getMessage()
            );
        }

        return back()->with(
            'status',
            $successMessage
        );
    }
}
