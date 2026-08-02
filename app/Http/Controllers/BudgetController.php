<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBudgetRequest;
use App\Http\Requests\UpdateBudgetRequest;
use App\Models\Budget;
use App\Models\FinanceCategory;
use App\Services\BudgetManagementService;
use App\Services\BudgetPeriodService;
use App\Services\BudgetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BudgetController extends Controller
{
    public function __construct(
        private readonly BudgetService $budgetService,
        private readonly BudgetManagementService $managementService,
        private readonly BudgetPeriodService $periodService
    ) {
    }

    public function index(
        Request $request
    ): View {
        $user = $request->user();

        $budgets = Budget::query()
            ->where(
                'user_id',
                $user->id
            )
            ->with([
                'financeCategory',

                'periods' =>
                    function ($query): void {
                        $query
                            ->orderByDesc(
                                'period_start'
                            )
                            ->orderByDesc('id');
                    },
            ])
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->paginate(9)
            ->withQueryString();

        $alertLevels = [];

        foreach ($budgets as $budget) {
            $period =
                $budget->periods->first();

            $alertLevels[$budget->id] =
                $period !== null
                    ? $this
                        ->periodService
                        ->alertLevel(
                            $period
                        )
                    : null;
        }

        $summary = [
            'total' => Budget::query()
                ->where(
                    'user_id',
                    $user->id
                )
                ->count(),

            'active' => Budget::query()
                ->where(
                    'user_id',
                    $user->id
                )
                ->where(
                    'is_active',
                    true
                )
                ->count(),

            'active_limit' => Budget::query()
                ->where(
                    'user_id',
                    $user->id
                )
                ->where(
                    'is_active',
                    true
                )
                ->sum('amount'),
        ];

        return view(
            'budgets.index',
            [
                'budgets' => $budgets,
                'alertLevels' =>
                    $alertLevels,
                'summary' => $summary,
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
            ->where(
                'flow_type',
                'expense'
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

        $budget->load([
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

        return view(
            'budgets.show',
            [
                'budget' => $budget,
                'periodAlerts' =>
                    $periodAlerts,
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
            ->where(
                'flow_type',
                'expense'
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
