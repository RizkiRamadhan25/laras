<?php

namespace App\Services;

use App\Enums\BudgetPeriodStatus;
use App\Models\Budget;
use App\Models\BudgetPeriod;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;

class BudgetIndexQueryService
{
    /**
     * @param array<string, mixed> $input
     * @return array{
     *     q: string,
     *     status: string,
     *     condition: string,
     *     sort: string
     * }
     */
    public function normalize(array $input): array
    {
        $status = $this->stringValue(
            $input['status'] ?? 'all'
        );

        $condition = $this->stringValue(
            $input['condition'] ?? 'all'
        );

        $sort = $this->stringValue(
            $input['sort'] ?? 'priority'
        );

        return [
            'q' => mb_substr(
                $this->stringValue(
                    $input['q'] ?? ''
                ),
                0,
                100
            ),

            'status' => in_array(
                $status,
                [
                    'all',
                    'active',
                    'inactive',
                ],
                true
            )
                ? $status
                : 'all',

            'condition' => in_array(
                $condition,
                [
                    'all',
                    'safe',
                    'warning',
                    'exceeded',
                    'no_period',
                ],
                true
            )
                ? $condition
                : 'all',

            'sort' => in_array(
                $sort,
                [
                    'priority',
                    'recent',
                    'usage_desc',
                    'limit_desc',
                    'name_asc',
                ],
                true
            )
                ? $sort
                : 'priority',
        ];
    }

    /**
     * @param array{
     *     q: string,
     *     status: string,
     *     condition: string,
     *     sort: string
     * } $filters
     */
    public function paginate(
        User $user,
        array $filters
    ): LengthAwarePaginator {
        $query = Budget::query()
            ->select('budgets.*')
            ->where(
                'budgets.user_id',
                $user->id
            )
            ->leftJoinSub(
                $this->activePeriodIds(),
                'active_period_ids',
                function (
                    JoinClause $join
                ): void {
                    $join->on(
                        'active_period_ids.budget_id',
                        '=',
                        'budgets.id'
                    );
                }
            )
            ->leftJoin(
                'budget_periods as active_period',
                'active_period.id',
                '=',
                'active_period_ids.id'
            )
            ->with([
                'financeCategory',
                'activePeriod',
                'latestPeriod',
            ]);

        $this->applySearch(
            $query,
            $filters['q']
        );

        $this->applyStatus(
            $query,
            $filters['status']
        );

        $this->applyCondition(
            $query,
            $filters['condition']
        );

        $this->applySort(
            $query,
            $filters['sort']
        );

        return $query
            ->paginate(9)
            ->withQueryString();
    }

    /**
     * @return array{
     *     total: int,
     *     active: int,
     *     active_limit: string,
     *     attention: int
     * }
     */
    public function summary(
        User $user
    ): array {
        $totals = Budget::query()
            ->where(
                'user_id',
                $user->id
            )
            ->selectRaw(
                'COUNT(*) as total'
            )
            ->selectRaw(
                'SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active'
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN is_active = 1 THEN amount ELSE 0 END), 0) as active_limit'
            )
            ->first();

        $attention = Budget::query()
            ->select('budgets.id')
            ->where(
                'budgets.user_id',
                $user->id
            )
            ->where(
                'budgets.is_active',
                true
            )
            ->leftJoinSub(
                $this->activePeriodIds(),
                'active_period_ids',
                function (
                    JoinClause $join
                ): void {
                    $join->on(
                        'active_period_ids.budget_id',
                        '=',
                        'budgets.id'
                    );
                }
            )
            ->leftJoin(
                'budget_periods as active_period',
                'active_period.id',
                '=',
                'active_period_ids.id'
            )
            ->whereNotNull(
                'active_period.id'
            )
            ->where(
                function (
                    Builder $query
                ): void {
                    $query
                        ->where(
                            'active_period.usage_percent',
                            '>=',
                            100
                        )
                        ->orWhereColumn(
                            'active_period.usage_percent',
                            '>=',
                            'budgets.warning_threshold_percent'
                        );
                }
            )
            ->count('budgets.id');

        return [
            'total' => (int) (
                $totals?->total ?? 0
            ),

            'active' => (int) (
                $totals?->active ?? 0
            ),

            'active_limit' => number_format(
                (float) (
                    $totals?->active_limit ?? 0
                ),
                2,
                '.',
                ''
            ),

            'attention' => $attention,
        ];
    }

    private function activePeriodIds(): Builder
    {
        return BudgetPeriod::query()
            ->selectRaw(
                'MAX(id) as id, budget_id'
            )
            ->where(
                'status',
                BudgetPeriodStatus::Active->value
            )
            ->groupBy('budget_id');
    }

    private function applySearch(
        Builder $query,
        string $search
    ): void {
        if ($search === '') {
            return;
        }

        $query->where(
            function (
                Builder $nested
            ) use ($search): void {
                $nested
                    ->where(
                        'budgets.name',
                        'like',
                        '%'.$search.'%'
                    )
                    ->orWhereHas(
                        'financeCategory',
                        function (
                            Builder $categoryQuery
                        ) use ($search): void {
                            $categoryQuery->where(
                                'name',
                                'like',
                                '%'.$search.'%'
                            );
                        }
                    );
            }
        );
    }

    private function applyStatus(
        Builder $query,
        string $status
    ): void {
        if ($status === 'active') {
            $query->where(
                'budgets.is_active',
                true
            );
        }

        if ($status === 'inactive') {
            $query->where(
                'budgets.is_active',
                false
            );
        }
    }

    private function applyCondition(
        Builder $query,
        string $condition
    ): void {
        if ($condition === 'safe') {
            $query
                ->whereNotNull(
                    'active_period.id'
                )
                ->whereColumn(
                    'active_period.usage_percent',
                    '<',
                    'budgets.warning_threshold_percent'
                )
                ->where(
                    'active_period.usage_percent',
                    '<',
                    100
                );
        }

        if ($condition === 'warning') {
            $query
                ->whereNotNull(
                    'active_period.id'
                )
                ->whereColumn(
                    'active_period.usage_percent',
                    '>=',
                    'budgets.warning_threshold_percent'
                )
                ->where(
                    'active_period.usage_percent',
                    '<',
                    100
                );
        }

        if ($condition === 'exceeded') {
            $query
                ->whereNotNull(
                    'active_period.id'
                )
                ->where(
                    'active_period.usage_percent',
                    '>=',
                    100
                );
        }

        if ($condition === 'no_period') {
            $query->whereNull(
                'active_period.id'
            );
        }
    }

    private function applySort(
        Builder $query,
        string $sort
    ): void {
        if ($sort === 'recent') {
            $query->orderByDesc(
                'budgets.id'
            );

            return;
        }

        if ($sort === 'usage_desc') {
            $query
                ->orderByRaw(
                    'CASE WHEN active_period.id IS NULL THEN 1 ELSE 0 END ASC'
                )
                ->orderByDesc(
                    'active_period.usage_percent'
                )
                ->orderByDesc(
                    'budgets.id'
                );

            return;
        }

        if ($sort === 'limit_desc') {
            $query
                ->orderByDesc(
                    'budgets.amount'
                )
                ->orderByDesc(
                    'budgets.id'
                );

            return;
        }

        if ($sort === 'name_asc') {
            $query
                ->orderBy(
                    'budgets.name'
                )
                ->orderByDesc(
                    'budgets.id'
                );

            return;
        }

        $query
            ->orderByRaw(
                'CASE
                    WHEN active_period.usage_percent >= 100 THEN 0
                    WHEN active_period.id IS NOT NULL
                        AND active_period.usage_percent >= budgets.warning_threshold_percent THEN 1
                    WHEN active_period.id IS NULL THEN 3
                    ELSE 2
                END ASC'
            )
            ->orderByDesc(
                'active_period.usage_percent'
            )
            ->orderByDesc(
                'budgets.is_active'
            )
            ->orderByDesc(
                'budgets.id'
            );
    }

    private function stringValue(
        mixed $value
    ): string {
        if (! is_scalar($value)) {
            return '';
        }

        return trim(
            (string) $value
        );
    }
}
