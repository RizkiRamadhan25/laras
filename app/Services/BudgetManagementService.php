<?php

namespace App\Services;

use App\Enums\BudgetPeriodStatus;
use App\Enums\FinanceFlowType;
use App\Models\Budget;
use App\Models\FinanceCategory;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BudgetManagementService
{
    public function __construct(
        private readonly BudgetPeriodService $periodService,
        private readonly BudgetUsageSyncService $usageSyncService,
        private readonly BudgetAlertService $alertService
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
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
                'name.required' =>
                    'Nama anggaran wajib diisi.',

                'amount.required' =>
                    'Batas anggaran wajib diisi.',

                'amount.gt' =>
                    'Batas anggaran harus lebih dari nol.',

                'warning_threshold_percent.between' =>
                    'Ambang peringatan harus berada antara 1 sampai 100 persen.',
            ]
        )->validate();

        return DB::transaction(
            function () use (
                $budget,
                $validated
            ): Budget {
                $budget->forceFill([
                    'name' =>
                        $validated['name'],

                    'amount' =>
                        $validated['amount'],

                    'warning_threshold_percent' =>
                        $validated[
                            'warning_threshold_percent'
                        ],
                ]);

                $budget->save();

                $periods = $budget
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

                return $budget
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

        if (! $budget->is_active) {
            return $budget;
        }

        $budget->forceFill([
            'is_active' => false,
        ])->save();

        return $budget->refresh();
    }

    public function activate(
        User $user,
        Budget $budget
    ): Budget {
        $this->ensureOwnership(
            $user,
            $budget
        );

        $budget->loadMissing(
            'financeCategory'
        );

        $this->ensureCategoryAvailable(
            $user,
            $budget->financeCategory
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

        if (
            $budget->end_date !== null
            && $budget->end_date->lt(
                $today
            )
        ) {
            throw ValidationException::withMessages([
                'is_active' =>
                    'Anggaran yang sudah berakhir tidak dapat diaktifkan kembali.',
            ]);
        }

        $duplicateExists = Budget::query()
            ->where(
                'user_id',
                $user->id
            )
            ->where(
                'finance_category_id',
                $budget->finance_category_id
            )
            ->where(
                'is_active',
                true
            )
            ->whereKeyNot(
                $budget->id
            )
            ->exists();

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'finance_category_id' =>
                    'Kategori ini sudah memiliki anggaran aktif.',
            ]);
        }

        $budget->forceFill([
            'is_active' => true,
        ])->save();

        $this->usageSyncService
            ->syncBudgetForDate(
                $budget,
                $today
            );

        return $budget->refresh();
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
            'budget' =>
                'Anggaran tidak dimiliki oleh pengguna ini.',
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
                'finance_category_id' =>
                    'Kategori tidak dimiliki oleh pengguna ini.',
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
                'finance_category_id' =>
                    'Anggaran hanya dapat menggunakan kategori pengeluaran.',
            ]);
        }

        $isDeleted =
            method_exists(
                $category,
                'trashed'
            )
            && $category->trashed();

        if (
            ! $category->is_active
            || $isDeleted
        ) {
            throw ValidationException::withMessages([
                'finance_category_id' =>
                    'Kategori anggaran sudah tidak aktif.',
            ]);
        }
    }
}
