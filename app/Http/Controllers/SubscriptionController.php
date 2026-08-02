<?php

namespace App\Http\Controllers;

use App\Enums\FinanceFlowType;
use App\Enums\SubscriptionBillingStatus;
use App\Enums\SubscriptionIntervalUnit;
use App\Enums\SubscriptionStatus;
use App\Http\Requests\FilterSubscriptionsRequest;
use App\Http\Requests\StoreSubscriptionRequest;
use App\Models\FinanceCategory;
use App\Models\Subscription;
use App\Models\SubscriptionBilling;
use App\Services\SubscriptionService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService
    ) {}

    public function index(
        FilterSubscriptionsRequest $request
    ): View {
        $user = $request->user()->load(
            'preference'
        );

        $filters = $request->validated();

        $query = Subscription::query()
            ->where('user_id', $user->id)
            ->with([
                'account',
                'financeCategory',
            ]);

        $query->when(
            $filters['search'] ?? null,
            function (
                Builder $query,
                string $search
            ): Builder {
                $escaped = addcslashes(
                    $search,
                    '%_\\'
                );

                $pattern = '%'.$escaped.'%';

                return $query->where(
                    function (
                        Builder $searchQuery
                    ) use ($pattern): void {
                        $searchQuery
                            ->where(
                                'name',
                                'like',
                                $pattern
                            )
                            ->orWhere(
                                'provider',
                                'like',
                                $pattern
                            );
                    }
                );
            }
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

        $query->when(
            $filters['account_id'] ?? null,
            fn (
                Builder $query,
                int|string $accountId
            ): Builder => $query->where(
                'account_id',
                (int) $accountId
            )
        );

        $query->when(
            $filters[
                'finance_category_id'
            ] ?? null,
            fn (
                Builder $query,
                int|string $categoryId
            ): Builder => $query->where(
                'finance_category_id',
                (int) $categoryId
            )
        );

        $subscriptions = $query
            ->orderByRaw(
                <<<'SQL'
                CASE status
                    WHEN 'active' THEN 1
                    WHEN 'paused' THEN 2
                    WHEN 'expired' THEN 3
                    WHEN 'cancelled' THEN 4
                    ELSE 5
                END
                SQL
            )
            ->orderByRaw(
                <<<'SQL'
                CASE
                    WHEN next_billing_on IS NULL
                    THEN 1
                    ELSE 0
                END
                SQL
            )
            ->orderBy('next_billing_on')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $activeSubscriptions = $user
            ->subscriptions()
            ->active()
            ->get();

        $monthlyEquivalent = $activeSubscriptions
            ->reduce(
                fn (
                    string $total,
                    Subscription $subscription
                ): string => bcadd(
                    $total,
                    $subscription
                        ->monthlyEquivalent(),
                    2
                ),
                '0.00'
            );

        $timezone = $user
            ->preference?->timezone
                ?? config(
                    'laras.defaults.timezone',
                    'Asia/Jakarta'
                );

        $today = CarbonImmutable::today(
            $timezone
        );

        return view('subscriptions.index', [
            'user' => $user,
            'subscriptions' => $subscriptions,
            'filters' => $filters,

            'accounts' => $user->accounts()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),

            'categories' => $this->expenseCategories(
                $user->id
            ),

            'statuses' => SubscriptionStatus::cases(),

            'summary' => [
            'active' => $activeSubscriptions->count(),

            'paused' => $user
                ->subscriptions()
                ->where(
                    'status',
                    SubscriptionStatus::Paused->value
                )
                ->count(),

            'monthly' => $monthlyEquivalent,

            'yearly' => bcmul(
                $monthlyEquivalent,
                '12',
                2
            ),

            'due_soon' => $user
                ->subscriptions()
                ->active()
                ->whereBetween(
                    'next_billing_on',
                    [
                        $today->toDateString(),
                        $today
                            ->addDays(7)
                            ->toDateString(),
                    ]
                )
                ->count(),
            ],

            'timezone' => $timezone,
            'today' => $today,
        ]);
    }

    public function create(
        Request $request
    ): View {
        $user = $request->user()->load(
            'preference'
        );

        return view('subscriptions.create', [
            'user' => $user,

            'accounts' => $user->accounts()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),

            'categories' => $this->expenseCategories(
                $user->id
            ),

            'intervalUnits' => SubscriptionIntervalUnit::cases(),
        ]);
    }

    public function store(
        StoreSubscriptionRequest $request
    ): RedirectResponse {
        try {
            $subscription =
                $this->subscriptionService->create(
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
                'subscriptions.show',
                $subscription->id
            )
            ->with(
                'status',
                'Langganan berhasil ditambahkan.'
            );
    }

    public function show(
        Request $request,
        int $subscription
    ): View {
        $user = $request->user()->load(
            'preference'
        );

        $ownedSubscription = Subscription::query()
            ->where('user_id', $user->id)
            ->with([
                'account',
                'financeCategory',
            ])
            ->findOrFail($subscription);

        $baseBillingQuery =
            SubscriptionBilling::query()
                ->where(
                    'subscription_id',
                    $ownedSubscription->id
                )
                ->where(
                    'user_id',
                    $user->id
                );

        $billingSummary = [
            'total' => (
                clone $baseBillingQuery
            )->count(),

            'posted' => (
                clone $baseBillingQuery
            )
                ->where(
                    'status',
                    SubscriptionBillingStatus::Posted->value
                )
                ->count(),

            'failed' => (
                clone $baseBillingQuery
            )
                ->where(
                    'status',
                    SubscriptionBillingStatus::Failed->value
                )
                ->count(),

            'scheduled' => (
                clone $baseBillingQuery
            )
                ->where(
                    'status',
                    SubscriptionBillingStatus::Scheduled->value
                )
                ->count(),

            'total_paid' => (
                clone $baseBillingQuery
            )
                ->where(
                    'status',
                    SubscriptionBillingStatus::Posted->value
                )
                ->sum('amount'),
        ];

        $billings = (
            clone $baseBillingQuery
        )
            ->with('transaction')
            ->orderByDesc('scheduled_for')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('subscriptions.show', [
            'user' => $user,

            'subscription' => $ownedSubscription,

            'billings' => $billings,

            'billingSummary' => $billingSummary,
        ]);
    }

    public function edit(
        Request $request,
        int $subscription
    ): View {
        $ownedSubscription = $request->user()
            ->subscriptions()
            ->findOrFail($subscription);

        $user = $request->user()->load(
            'preference'
        );

        return view('subscriptions.edit', [
            'user' => $user,
            'subscription' => $ownedSubscription,

            'accounts' => $user->accounts()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),

            'categories' => $this->expenseCategories(
                $user->id
            ),

            'intervalUnits' => SubscriptionIntervalUnit::cases(),
        ]);
    }

    public function update(
        StoreSubscriptionRequest $request,
        int $subscription
    ): RedirectResponse {
        try {
            $updated =
                $this->subscriptionService->update(
                    user: $request->user(),
                    subscriptionId: $subscription,
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
                'subscriptions.show',
                $updated->id
            )
            ->with(
                'status',
                'Langganan berhasil diperbarui.'
            );
    }

    public function pause(
        Request $request,
        int $subscription
    ): RedirectResponse {
        return $this->statusAction(
            callback: fn (): Subscription => $this->subscriptionService
                ->pause(
                    $request->user(),
                    $subscription
                ),

            message: 'Langganan berhasil dijeda.'
        );
    }

    public function resume(
        Request $request,
        int $subscription
    ): RedirectResponse {
        return $this->statusAction(
            callback: fn (): Subscription => $this->subscriptionService
                ->resume(
                    $request->user(),
                    $subscription
                ),

            message: 'Langganan berhasil diaktifkan kembali.'
        );
    }

    public function cancel(
        Request $request,
        int $subscription
    ): RedirectResponse {
        return $this->statusAction(
            callback: fn (): Subscription => $this->subscriptionService
                ->cancel(
                    $request->user(),
                    $subscription
                ),

            message: 'Langganan berhasil dihentikan.'
        );
    }

    private function expenseCategories(
        int $userId
    ) {
        return FinanceCategory::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->whereIn(
                'flow_type',
                [
                    FinanceFlowType::Expense->value,

                    FinanceFlowType::Both->value,
                ]
            )
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  callable(): Subscription  $callback
     */
    private function statusAction(
        callable $callback,
        string $message
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
            $message
        );
    }
}
