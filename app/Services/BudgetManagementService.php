<?php

namespace App\Services;

use App\Enums\BudgetPeriodStatus;
use App\Enums\FinanceFlowType;
use App\Models\Budget;
use App\Models\FinanceCategory;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BudgetManagementService
{
    public function __construct(
        private readonly BudgetPeriodService $periodService,
        private readonly BudgetUsageSyncService $usageSyncService,
        private readonly BudgetAlertService $alertService
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(
        User $user,
        Budget $budget,
        array $attributes
    ): Budget {
        $this->ensureOwnership(
            $user,
            $budget
        );

        $validated = Validator::make(
            $attributes,
            [
                'name' => [
                    'required',
                    'string',
                    'min:2',
                    'max:120',
                ],

                'amount' => [
                    'required',
                    'numeric',
                    'decimal:0,2',
                    'gt:0',
                ],

                'warning_threshold_percent' => [
                    'required',
                    'numeric',
                    'decimal:0,2',
                    'between:1,100',
                ],
            ],
            [
                'name.required' => 'Nama anggaran wajib diisi.',

                'amount.required' => 'Batas anggaran wajib diisi.',

                'amount.gt' => 'Batas anggaran harus lebih dari nol.',

                'warning_threshold_percent.between' => 'Ambang peringatan harus berada antara 1 sampai 100 persen.',
            ]
        )->validate();

        return DB::transaction(
            function () use (
                $budget,
                $validated
            ): Budget {
                $lockedBudget = Budget::query()
                    ->whereKey($budget->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedBudget->forceFill([
                    'name' => $validated['name'],

                    'amount' => $validated['amount'],

                    'warning_threshold_percent' => $validated[
                            'warning_threshold_percent'
                        ],
                ])->save();

                $periods = $lockedBudget
                    ->periods()
                    ->whereIn(
                        'status',
                        [
                            BudgetPeriodStatus::Active
                                ->value,

                            BudgetPeriodStatus::Upcoming
                                ->value,
                        ]
                    )
                    ->get();

                foreach ($periods as $period) {
                    $refreshedPeriod = $this
                        ->periodService
                        ->refreshExisting(
                            $period
                        );

                    $this->alertService
                        ->notifyForPeriod(
                            $refreshedPeriod
                        );
                }

                return $lockedBudget
                    ->refresh()
                    ->load([
                        'financeCategory',
                        'periods',
                    ]);
            },
            3
        );
    }

    public function deactivate(
        User $user,
        Budget $budget
    ): Budget {
        $this->ensureOwnership(
            $user,
            $budget
        );

        return DB::transaction(
            function () use ($budget): Budget {
                $lockedBudget = Budget::query()
                    ->whereKey($budget->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $lockedBudget->is_active) {
                    return $lockedBudget;
                }

                $lockedBudget->forceFill([
                    'is_active' => false,
                    'active_finance_category_id' => null,
                ])->save();

                return $lockedBudget->refresh();
            },
            3
        );
    }

    public function activate(
        User $user,
        Budget $budget
    ): Budget {
        $this->ensureOwnership(
            $user,
            $budget
        );

        $user->loadMissing('preference');

        $timezone = $user->preference?->timezone
            ?? config(
                'laras.defaults.timezone',
                'Asia/Jakarta'
            );

        $today = CarbonImmutable::now(
            $timezone
        )->startOfDay();

        try {
            return DB::transaction(
                function () use (
                    $user,
                    $budget,
                    $today
                ): Budget {
                    $lockedBudget = Budget::query()
                        ->whereKey($budget->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($lockedBudget->is_active) {
                        return $lockedBudget;
                    }

                    $lockedBudget->loadMissing(
                        'financeCategory'
                    );

                    $this->ensureCategoryAvailable(
                        $user,
                        $lockedBudget
                            ->financeCategory
                    );

                    if (
                        $lockedBudget->end_date !== null
                        && $lockedBudget->end_date
                            ->lt($today)
                    ) {
                        throw ValidationException::withMessages([
                            'is_active' => 'Anggaran yang sudah berakhir tidak dapat diaktifkan kembali.',
                        ]);
                    }

                    $duplicateExists = Budget::query()
                        ->where(
                            'user_id',
                            $user->id
                        )
                        ->where(
                            'active_finance_category_id',
                            $lockedBudget
                                ->finance_category_id
                        )
                        ->whereKeyNot(
                            $lockedBudget->id
                        )
                        ->exists();

                    if ($duplicateExists) {
                        throw ValidationException::withMessages([
                            'finance_category_id' => 'Kategori ini sudah memiliki anggaran aktif.',
                        ]);
                    }

                    $lockedBudget->forceFill([
                        'is_active' => true,
                        'active_finance_category_id' => $lockedBudget
                            ->finance_category_id,
                    ])->save();

                    $this->usageSyncService
                        ->syncBudgetForDate(
                            $lockedBudget,
                            $today
                        );

                    return $lockedBudget->refresh();
                },
                3
            );
        } catch (
            UniqueConstraintViolationException $exception
        ) {
            if (
                Budget::query()
                    ->where('user_id', $user->id)
                    ->where(
                        'active_finance_category_id',
                        $budget
                            ->finance_category_id
                    )
                    ->whereKeyNot($budget->id)
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'finance_category_id' => 'Kategori ini sudah memiliki anggaran aktif.',
                ]);
            }

            throw $exception;
        }
    }

    private function ensureOwnership(
        User $user,
        Budget $budget
    ): void {
        if (
            (int) $budget->user_id
            === (int) $user->id
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'budget' => 'Anggaran tidak dimiliki oleh pengguna ini.',
        ]);
    }

    private function ensureCategoryAvailable(
        User $user,
        FinanceCategory $category
    ): void {
        if (
            (int) $category->user_id
            !== (int) $user->id
        ) {
            throw ValidationException::withMessages([
                'finance_category_id' => 'Kategori tidak dimiliki oleh pengguna ini.',
            ]);
        }

        if (
            ! in_array(
                $category->flow_type,
                [
                    FinanceFlowType::Expense,
                    FinanceFlowType::Both,
                ],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'finance_category_id' => 'Anggaran hanya dapat menggunakan kategori pengeluaran.',
            ]);
        }

        $isDeleted = method_exists(
            $category,
            'trashed'
        ) && $category->trashed();

        if (
            ! $category->is_active
            || $isDeleted
        ) {
            throw ValidationException::withMessages([
                'finance_category_id' => 'Kategori anggaran sudah tidak aktif.',
            ]);
        }
    }
}
