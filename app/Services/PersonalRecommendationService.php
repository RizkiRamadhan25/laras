<?php

namespace App\Services;

use App\Enums\ActivityPriority;
use App\Enums\ActivityStatus;
use App\Enums\SubscriptionBillingStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\SubscriptionBilling;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;

class PersonalRecommendationService
{
    public function __construct(
        private readonly ActivityRecommendationService $activityRecommendationService,
        private readonly ExpenseAnalysisService $expenseAnalysisService
    ) {
    }

    /**
     * @return array{
     *     generated_at: CarbonImmutable,
     *     items: Collection<int, array<string, mixed>>,
     *     summary: array{
     *         total: int,
     *         critical: int,
     *         attention: int,
     *         insight: int
     *     }
     * }
     */
    public function build(
        User $user,
        ?DateTimeInterface $reference = null
    ): array {
        $timezone = $this->userTimezone(
            $user
        );

        $now = $reference === null
            ? CarbonImmutable::now($timezone)
            : CarbonImmutable::instance(
                $reference
            )->setTimezone($timezone);

        $items = collect();

        $failedBillings = SubscriptionBilling::query()
            ->where('user_id', $user->id)
            ->where(
                'status',
                SubscriptionBillingStatus::Failed->value
            )
            ->whereHas(
                'subscription',
                fn ($query) => $query->where(
                    'status',
                    SubscriptionStatus::Active->value
                )
            )
            ->with([
                'subscription.account',
            ])
            ->orderByDesc('scheduled_for')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $failedSubscriptionIds = $failedBillings
            ->pluck('subscription_id')
            ->map(
                fn (mixed $id): int => (int) $id
            )
            ->all();

        foreach ($failedBillings as $billing) {
            $subscription = $billing->subscription;

            if ($subscription === null) {
                continue;
            }

            $items->push([
                'key' =>
                    'billing-failed-'.$billing->id,

                'kind' => 'billing_failed',
                'severity' => 'danger',
                'score' => 100,

                'icon' => 'circle-alert',

                'title' => sprintf(
                    'Tagihan %s gagal diproses',
                    $subscription->name
                ),

                'message' =>
                    $billing->failure_reason
                    ?? 'Tagihan belum berhasil dicatat sebagai pengeluaran.',

                'meta' => sprintf(
                    '%s %s · Tagihan %s',
                    $billing->currency_code,
                    number_format(
                        (float) $billing->amount,
                        0,
                        ',',
                        '.'
                    ),
                    $billing
                        ->scheduled_for
                        ->locale('id')
                        ->translatedFormat('d F Y')
                ),

                'action_url' => route(
                    'subscriptions.billings.show',
                    [
                        'subscription' =>
                            $subscription->id,

                        'billing' =>
                            $billing->id,
                    ]
                ),

                'action_label' =>
                    'Periksa tagihan',

                'order_at' => $billing
                    ->scheduled_for
                    ->format('Y-m-d H:i:s'),
            ]);
        }

        $activityRecommendations =
            $this->activityRecommendationService
                ->rank(
                    user: $user,

                    reference: $now->setTimezone(
                        config(
                            'app.timezone',
                            'UTC'
                        )
                    ),

                    limit: 5
                );

        foreach (
            $activityRecommendations
            as $recommendation
        ) {
            $activity =
                $recommendation['activity'];

            $dueAt = $activity->due_at
                ?->setTimezone($timezone);

            $overdue = $activity->isOpen()
                && $dueAt !== null
                && $dueAt->lessThan($now);

            $severity = match (true) {
                $overdue => 'danger',

                $activity->priority
                    === ActivityPriority::Urgent =>
                    'danger',

                $activity->priority
                    === ActivityPriority::High =>
                    'warning',

                $activity->status
                    === ActivityStatus::InProgress =>
                    'warning',

                default => 'info',
            };

            $baseScore = match ($severity) {
                'danger' => 86,
                'warning' => 72,
                default => 58,
            };

            $score = min(
                99,
                $baseScore
                    + min(
                        13,
                        (int) floor(
                            (
                                (int) $recommendation[
                                    'score'
                                ]
                            ) / 10
                        )
                    )
            );

            $metaParts = [
                $activity->priority->label(),
                $activity->status->label(),
            ];

            if ($dueAt !== null) {
                $metaParts[] = $overdue
                    ? 'Terlambat sejak '
                        .$dueAt
                            ->locale('id')
                            ->translatedFormat(
                                'd M Y, H:i'
                            )
                    : 'Tenggat '
                        .$dueAt
                            ->locale('id')
                            ->translatedFormat(
                                'd M Y, H:i'
                            );
            }

            if (
                $activity->estimated_minutes
                !== null
            ) {
                $metaParts[] =
                    $activity->estimated_minutes
                    .' menit';
            }

            $items->push([
                'key' =>
                    'activity-'.$activity->id,

                'kind' => 'activity',
                'severity' => $severity,
                'score' => $score,

                'icon' => $overdue
                    ? 'alarm-clock'
                    : $activity->type->icon(),

                'title' => $overdue
                    ? 'Selesaikan aktivitas yang terlambat'
                    : 'Prioritaskan '
                        .$activity->title,

                'message' =>
                    $recommendation['reason'],

                'meta' => implode(
                    ' · ',
                    $metaParts
                ),

                'action_url' => route(
                    'activities.edit',
                    $activity->id
                ),

                'action_label' =>
                    'Buka aktivitas',

                'order_at' => (
                    $activity->relevantAt()
                        ?? $now
                )
                    ->setTimezone($timezone)
                    ->format('Y-m-d H:i:s'),
            ]);
        }

        $upcomingSubscriptions =
            Subscription::query()
                ->where(
                    'user_id',
                    $user->id
                )
                ->where(
                    'status',
                    SubscriptionStatus::Active->value
                )
                ->whereNotNull(
                    'next_billing_on'
                )
                ->whereBetween(
                    'next_billing_on',
                    [
                        $now
                            ->startOfDay()
                            ->toDateString(),

                        $now
                            ->addDays(7)
                            ->endOfDay()
                            ->toDateString(),
                    ]
                )
                ->whereNotIn(
                    'id',
                    $failedSubscriptionIds
                )
                ->with([
                    'account',
                    'financeCategory',
                ])
                ->orderBy('next_billing_on')
                ->orderBy('id')
                ->limit(5)
                ->get();

        foreach (
            $upcomingSubscriptions
            as $subscription
        ) {
            $billingDate =
                CarbonImmutable::parse(
                    $subscription
                        ->next_billing_on
                        ->toDateString(),
                    $timezone
                )->startOfDay();

            $daysUntil = max(
                0,
                (int) $now
                    ->startOfDay()
                    ->diffInDays(
                        $billingDate,
                        false
                    )
            );

            $severity = $daysUntil <= 3
                ? 'warning'
                : 'info';

            $score = $daysUntil <= 1
                ? 88
                : (
                    $daysUntil <= 3
                        ? 78
                        : 62
                );

            $timeLabel = match ($daysUntil) {
                0 => 'hari ini',
                1 => 'besok',
                default =>
                    'dalam '.$daysUntil.' hari',
            };

            $items->push([
                'key' =>
                    'subscription-due-'
                    .$subscription->id,

                'kind' => 'subscription_due',
                'severity' => $severity,
                'score' => $score,

                'icon' => 'repeat-2',

                'title' => sprintf(
                    '%s akan ditagihkan %s',
                    $subscription->name,
                    $timeLabel
                ),

                'message' => sprintf(
                    'Pastikan saldo %s mencukupi sebelum jadwal pencatatan otomatis.',
                    $subscription->account?->name
                        ?? 'rekening pembayaran'
                ),

                'meta' => sprintf(
                    '%s %s · %s',
                    $subscription->currency_code,
                    number_format(
                        (float) $subscription->amount,
                        0,
                        ',',
                        '.'
                    ),
                    $billingDate
                        ->locale('id')
                        ->translatedFormat(
                            'd F Y'
                        )
                ),

                'action_url' => route(
                    'subscriptions.show',
                    $subscription->id
                ),

                'action_label' =>
                    'Lihat langganan',

                'order_at' =>
                    $billingDate->format(
                        'Y-m-d H:i:s'
                    ),
            ]);
        }

        $expenseAnalysis =
            $this->expenseAnalysisService
                ->build(
                    user: $user,
                    selectedPeriod: 'month',
                    reference: $now
                );

        $expenseSummary =
            $expenseAnalysis['summary'];

        $selectedTotal =
            (string) $expenseSummary[
                'selected_total'
            ];

        $changePercent =
            $expenseSummary[
                'change_percent'
            ];

        if (
            $expenseSummary['trend'] === 'up'
            && $changePercent !== null
            && $changePercent >= 10
            && bccomp(
                $selectedTotal,
                '0.00',
                2
            ) > 0
        ) {
            $severity = $changePercent >= 30
                ? 'warning'
                : 'info';

            $items->push([
                'key' =>
                    'expense-monthly-increase',

                'kind' =>
                    'expense_increase',

                'severity' => $severity,

                'score' => $changePercent >= 30
                    ? 68
                    : 56,

                'icon' =>
                    'chart-no-axes-combined',

                'title' => sprintf(
                    'Pengeluaran bulan ini naik %s%%',
                    number_format(
                        $changePercent,
                        1,
                        ',',
                        '.'
                    )
                ),

                'message' => sprintf(
                    'Total pengeluaran bulan berjalan mencapai IDR %s. Tinjau kategori yang mengalami kenaikan.',
                    number_format(
                        (float) $selectedTotal,
                        0,
                        ',',
                        '.'
                    )
                ),

                'meta' =>
                    'Dibandingkan periode setara bulan lalu',

                'action_url' => route(
                    'analysis.index',
                    [
                        'period' => 'month',
                    ]
                ),

                'action_label' =>
                    'Buka analisis',

                'order_at' =>
                    $now->format(
                        'Y-m-d H:i:s'
                    ),
            ]);
        }

        $topCategory =
            $expenseSummary['top_category'];

        if (
            is_array($topCategory)
            && (
                (float) $topCategory['share']
            ) >= 40
            && bccomp(
                (string) $topCategory[
                    'selected'
                ],
                '0.00',
                2
            ) > 0
        ) {
            $items->push([
                'key' =>
                    'dominant-category-'
                    .$topCategory['id'],

                'kind' =>
                    'dominant_category',

                'severity' => 'info',
                'score' => 52,

                'icon' => 'receipt-text',

                'title' => sprintf(
                    '%s mendominasi pengeluaran',
                    $topCategory['name']
                ),

                'message' => sprintf(
                    'Kategori ini menggunakan %s%% dari seluruh pengeluaran bulan berjalan.',
                    number_format(
                        (float) $topCategory[
                            'share'
                        ],
                        1,
                        ',',
                        '.'
                    )
                ),

                'meta' => sprintf(
                    'IDR %s bulan ini',
                    number_format(
                        (float) $topCategory[
                            'selected'
                        ],
                        0,
                        ',',
                        '.'
                    )
                ),

                'action_url' => route(
                    'analysis.index',
                    [
                        'period' => 'month',
                    ]
                ),

                'action_label' =>
                    'Evaluasi kategori',

                'order_at' =>
                    $now->format(
                        'Y-m-d H:i:s'
                    ),
            ]);
        }

        $items = $items
            ->sort(
                function (
                    array $left,
                    array $right
                ): int {
                    $scoreComparison =
                        $right['score']
                        <=> $left['score'];

                    if (
                        $scoreComparison !== 0
                    ) {
                        return $scoreComparison;
                    }

                    $dateComparison = strcmp(
                        $left['order_at'],
                        $right['order_at']
                    );

                    if (
                        $dateComparison !== 0
                    ) {
                        return $dateComparison;
                    }

                    return strcmp(
                        $left['key'],
                        $right['key']
                    );
                }
            )
            ->values();

        return [
            'generated_at' => $now,
            'items' => $items,

            'summary' => [
                'total' => $items->count(),

                'critical' => $items
                    ->where(
                        'severity',
                        'danger'
                    )
                    ->count(),

                'attention' => $items
                    ->where(
                        'severity',
                        'warning'
                    )
                    ->count(),

                'insight' => $items
                    ->where(
                        'severity',
                        'info'
                    )
                    ->count(),
            ],
        ];
    }

    private function userTimezone(
        User $user
    ): string {
        return $user->preference()
            ->value('timezone')
            ?? config(
                'laras.defaults.timezone',
                'Asia/Jakarta'
            );
    }
}
