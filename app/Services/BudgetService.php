<?php

namespace App\Services;

use App\Enums\BudgetPeriodType;
use App\Models\Budget;
use App\Models\FinanceCategory;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use BackedEnum;

class BudgetService
{
    public function __construct(
        private readonly BudgetPeriodService $periodService
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(
        User $user,
        FinanceCategory $category,
        array $attributes
    ): Budget {
        $this->ensureCategoryOwnership(
            $user,
            $category
        );

        $this->ensureActiveCategory(
            $category
        );

        $this->ensureExpenseCategory(
            $category
        );

        $periodTypeValue =
            $attributes['period_type']
            ?? BudgetPeriodType::Monthly->value;

        if (
            $periodTypeValue
            instanceof BudgetPeriodType
        ) {
            $periodTypeValue =
                $periodTypeValue->value;
        }

        $payload = array_merge(
            $attributes,
            [
                'period_type' =>
                    $periodTypeValue,
            ]
        );

        $validator = Validator::make(
            $payload,
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

                'period_type' => [
                    'required',
                    Rule::enum(
                        BudgetPeriodType::class
                    ),
                ],

                'warning_threshold_percent' => [
                    'required',
                    'numeric',
                    'decimal:0,2',
                    'between:1,100',
                ],

                'start_date' => [
                    'required',
                    'date',
                ],

                'end_date' => [
                    Rule::requiredIf(
                        $periodTypeValue
                        === BudgetPeriodType::Custom
                            ->value
                    ),
                    'nullable',
                    'date',
                    'after_or_equal:start_date',
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

                'end_date.required' =>
                    'Tanggal selesai wajib diisi untuk periode khusus.',

                'end_date.after_or_equal' =>
                    'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            ]
        );

        $validated =
            $validator->validate();

        $this->ensureNoDuplicateActiveBudget(
            $user,
            $category
        );

        $periodType =
            BudgetPeriodType::from(
                $validated['period_type']
            );

        return DB::transaction(
            function () use (
                $user,
                $category,
                $validated,
                $periodType
            ): Budget {
                $budget = Budget::query()
                    ->create([
                        'user_id' =>
                            $user->id,

                        'finance_category_id' =>
                            $category->id,

                        'name' =>
                            $validated['name'],

                        'amount' =>
                            $validated['amount'],

                        'period_type' =>
                            $periodType,

                        'warning_threshold_percent' =>
                            $validated[
                                'warning_threshold_percent'
                            ],

                        'start_date' =>
                            $validated[
                                'start_date'
                            ],

                        'end_date' =>
                            $validated[
                                'end_date'
                            ] ?? null,

                        'is_recurring' =>
                            $periodType
                                ->isRecurring(),

                        'is_active' =>
                            true,
                    ]);

                $this
                    ->periodService
                    ->sync(
                        $budget,
                        '0.00',
                        CarbonImmutable::parse(
                            $validated[
                                'start_date'
                            ]
                        )
                    );

                return $budget
                    ->load([
                        'financeCategory',
                        'periods',
                    ]);
            },
            3
        );
    }

    private function ensureCategoryOwnership(
        User $user,
        FinanceCategory $category
    ): void {
        if (
            (int) $category->user_id
            === (int) $user->id
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'finance_category_id' =>
                'Kategori tidak dimiliki oleh pengguna ini.',
        ]);
    }

    private function ensureActiveCategory(
        FinanceCategory $category
    ): void {
        $isDeleted =
            method_exists($category, 'trashed')
            && $category->trashed();

        if (
            (bool) $category->is_active
            && ! $isDeleted
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'finance_category_id' =>
                'Anggaran hanya dapat menggunakan kategori yang masih aktif.',
        ]);
    }

    private function ensureExpenseCategory(
        FinanceCategory $category
    ): void {
        $flowType = $category->flow_type;

        if ($flowType instanceof BackedEnum) {
            $flowType = $flowType->value;
        }

        if ((string) $flowType === 'expense') {
            return;
        }

        throw ValidationException::withMessages([
            'finance_category_id' =>
                'Anggaran hanya dapat menggunakan kategori pengeluaran.',
        ]);
    }

    private function ensureNoDuplicateActiveBudget(
        User $user,
        FinanceCategory $category
    ): void {
        $exists = Budget::query()
            ->where(
                'user_id',
                $user->id
            )
            ->where(
                'finance_category_id',
                $category->id
            )
            ->where(
                'is_active',
                true
            )
            ->exists();

        if (! $exists) {
            return;
        }

        throw ValidationException::withMessages([
            'finance_category_id' =>
                'Kategori ini sudah memiliki anggaran aktif.',
        ]);
    }
}
