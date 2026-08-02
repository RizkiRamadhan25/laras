<?php

namespace App\Http\Controllers;

use App\Enums\BudgetPeriodStatus;
use App\Enums\FinanceFlowType;
use App\Http\Requests\StoreBudgetRequest;
use App\Http\Requests\UpdateBudgetRequest;
use App\Models\Budget;
use App\Models\FinanceCategory;
use App\Services\BudgetIndexQueryService;
use App\Services\BudgetManagementService;
use App\Services\BudgetPeriodService;
use App\Services\BudgetService;
use App\Services\BudgetTransactionQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BudgetController extends Controller
{
    public function __construct(
        private readonly BudgetService $budgetService,
        private readonly BudgetIndexQueryService $indexQueryService,
        private readonly BudgetManagementService $managementService,
        private readonly BudgetPeriodService $periodService,
        private readonly BudgetTransactionQueryService $transactionQueryService
    ) {
    }

    public function index(
        Request $request
    ): View {
        $user = $request->user();

        $filters = $this
            ->indexQueryService
            ->normalize(
                $request->query()
            );

        $budgets = $this
            ->indexQueryService
            ->paginate(
                $user,
                $filters
            );

        $alertLevels = [];

        foreach ($budgets as $budget) {
            $period = $budget->activePeriod
                ?? $budget->latestPeriod;

            if ($period === null) {
                $alertLevels[$budget->id] = null;

                continue;
            }

            /*
             * Hindari query tambahan dari
             * BudgetPeriodService::alertLevel().
             */
            $period->setRelation(
                'budget',
                $budget
            );

            $alertLevels[$budget->id] =
                $this
                    ->periodService
                    ->alertLevel(
                        $period
                    );
        }

        $summary = $this
            ->indexQueryService
            ->summary($user);

        $hasFilters = $filters['q'] !== ''
            || $filters['status'] !== 'all'
            || $filters['condition'] !== 'all';

        $hasCustomControls = $hasFilters
            || $filters['sort'] !== 'priority';

        return view(
            'budgets.index',
            [
                'budgets' => $budgets,
                'alertLevels' =>
                    $alertLevels,
                'summary' => $summary,
                'filters' => $filters,
                'hasFilters' =>
                    $hasFilters,
                'hasCustomControls' =>
                    $hasCustomControls,
            ]
        );
    }

    public function create(
        Request $request
    ): View {
        $categories =
            $this->expenseCategories(
                $request
            );

        return view(
            'budgets.create',
            [
                'categories' =>
                    $categories,
            ]
        );
    }

    public function store(
        StoreBudgetRequest $request
    ): RedirectResponse {
        $validated =
            $request->validated();

        $category = FinanceCategory::query()
            ->where(
                'user_id',
                $request->user()->id
            )
            ->whereIn(
                'flow_type',
                [
                    FinanceFlowType::Expense->value,
                    FinanceFlowType::Both->value,
                ]
            )
            ->where(
                'is_active',
                true
            )
            ->findOrFail(
                $validated[
                    'finance_category_id'
                ]
            );

        unset(
            $validated[
                'finance_category_id'
            ]
        );

        $budget = $this
            ->budgetService
            ->create(
                $request->user(),
                $category,
                $validated
            );

        return redirect()
            ->route(
                'budgets.show',
                $budget
            )
            ->with(
                'status',
                'Anggaran berhasil dibuat.'
            );
    }

    public function show(
        Request $request,
        Budget $budget
    ): View {
        $this->ensureOwned(
            $request,
            $budget
        );

        $user = $request
            ->user()
            ->loadMissing(
                'preference'
            );

        $budget->load([
            'user.preference',
            'financeCategory',

            'periods' =>
                function ($query): void {
                    $query
                        ->orderByDesc(
                            'period_start'
                        )
                        ->orderByDesc('id');
                },
        ]);

        $periodAlerts = [];

        foreach (
            $budget->periods
            as $period
        ) {
            $periodAlerts[$period->id] =
                $this
                    ->periodService
                    ->alertLevel(
                        $period
                    );
        }

        $requestedPeriodId = (int) $request
            ->query(
                'period',
                0
            );

        $selectedPeriod = null;

        if ($requestedPeriodId > 0) {
            $selectedPeriod = $budget
                ->periods
                ->firstWhere(
                    'id',
                    $requestedPeriodId
                );

            abort_if(
                $selectedPeriod === null,
                404
            );
        }

        if ($selectedPeriod === null) {
            $selectedPeriod = $budget
                ->periods
                ->first(
                    static fn ($period): bool =>
                        $period->status
                        === BudgetPeriodStatus::Active
                )
                ?? $budget->periods->first();
        }

        $usageEntries = $selectedPeriod !== null
            ? $this
                ->transactionQueryService
                ->paginateForPeriod(
                    $budget,
                    $selectedPeriod
                )
            : null;

        $timezone = $user
            ->preference?->timezone
            ?? config(
                'laras.defaults.timezone',
                'Asia/Jakarta'
            );

        return view(
            'budgets.show',
            [
                'budget' => $budget,
                'periodAlerts' =>
                    $periodAlerts,
                'selectedPeriod' =>
                    $selectedPeriod,
                'usageEntries' =>
                    $usageEntries,
                'timezone' => $timezone,
            ]
        );
    }

    public function edit(
        Request $request,
        Budget $budget
    ): View {
        $this->ensureOwned(
            $request,
            $budget
        );

        $budget->load(
            'financeCategory'
        );

        return view(
            'budgets.edit',
            [
                'budget' => $budget,
            ]
        );
    }

    public function update(
        UpdateBudgetRequest $request,
        Budget $budget
    ): RedirectResponse {
        $this
            ->managementService
            ->update(
                $request->user(),
                $budget,
                $request->validated()
            );

        return redirect()
            ->route(
                'budgets.show',
                $budget
            )
            ->with(
                'status',
                'Anggaran berhasil diperbarui.'
            );
    }

    public function deactivate(
        Request $request,
        Budget $budget
    ): RedirectResponse {
        $this->ensureOwned(
            $request,
            $budget
        );

        $this
            ->managementService
            ->deactivate(
                $request->user(),
                $budget
            );

        return redirect()
            ->route(
                'budgets.show',
                $budget
            )
            ->with(
                'status',
                'Anggaran berhasil dinonaktifkan.'
            );
    }

    public function activate(
        Request $request,
        Budget $budget
    ): RedirectResponse {
        $this->ensureOwned(
            $request,
            $budget
        );

        $this
            ->managementService
            ->activate(
                $request->user(),
                $budget
            );

        return redirect()
            ->route(
                'budgets.show',
                $budget
            )
            ->with(
                'status',
                'Anggaran berhasil diaktifkan.'
            );
    }

    private function ensureOwned(
        Request $request,
        Budget $budget
    ): void {
        abort_unless(
            (int) $budget->user_id
            === (int) $request
                ->user()
                ->id,
            404
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, FinanceCategory>
     */
    private function expenseCategories(
        Request $request
    ) {
        return FinanceCategory::query()
            ->where(
                'user_id',
                $request->user()->id
            )
            ->whereIn(
                'flow_type',
                [
                    FinanceFlowType::Expense->value,
                    FinanceFlowType::Both->value,
                ]
            )
            ->where(
                'is_active',
                true
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
