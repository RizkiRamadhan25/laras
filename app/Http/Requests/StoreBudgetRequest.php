<?php

namespace App\Http\Requests;

use App\Enums\BudgetPeriodType;
use App\Enums\FinanceFlowType;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim(
                (string) $this->input(
                    'name'
                )
            ),

            'warning_threshold_percent' =>
                $this->input(
                    'warning_threshold_percent',
                    '80'
                ),

            'end_date' =>
                filled(
                    $this->input(
                        'end_date'
                    )
                )
                    ? $this->input(
                        'end_date'
                    )
                    : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'finance_category_id' => [
                'required',
                'integer',

                Rule::exists(
                    'finance_categories',
                    'id'
                )->where(
                    function (
                        Builder $query
                    ) use ($userId): void {
                        $query
                            ->where(
                                'user_id',
                                $userId
                            )
                            ->whereIn(
                                'flow_type',
                                [
                                    FinanceFlowType::Expense
                                        ->value,

                                    FinanceFlowType::Both
                                        ->value,
                                ]
                            )
                            ->where(
                                'is_active',
                                true
                            )
                            ->whereNull(
                                'deleted_at'
                            );
                    }
                ),
            ],

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
                    $this->input(
                        'period_type'
                    )
                    === BudgetPeriodType::Custom
                        ->value
                ),

                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'finance_category_id.required' =>
                'Kategori pengeluaran wajib dipilih.',

            'finance_category_id.exists' =>
                'Kategori pengeluaran tidak tersedia atau bukan milikmu.',

            'name.required' =>
                'Nama anggaran wajib diisi.',

            'name.min' =>
                'Nama anggaran minimal 2 karakter.',

            'amount.required' =>
                'Batas anggaran wajib diisi.',

            'amount.gt' =>
                'Batas anggaran harus lebih dari nol.',

            'period_type.required' =>
                'Jenis periode wajib dipilih.',

            'warning_threshold_percent.between' =>
                'Ambang peringatan harus berada antara 1 sampai 100 persen.',

            'start_date.required' =>
                'Tanggal mulai wajib diisi.',

            'end_date.required' =>
                'Tanggal selesai wajib diisi untuk periode khusus.',

            'end_date.after_or_equal' =>
                'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('budgets.create');
    }
}
