<?php

namespace App\Http\Requests;

use App\Models\Budget;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        $budget =
            $this->route('budget');

        return $this->user() !== null
            && $budget instanceof Budget
            && (int) $budget->user_id
                === (int) $this->user()->id;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim(
                (string) $this->input(
                    'name'
                )
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
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
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' =>
                'Nama anggaran wajib diisi.',

            'name.min' =>
                'Nama anggaran minimal 2 karakter.',

            'amount.required' =>
                'Batas anggaran wajib diisi.',

            'amount.gt' =>
                'Batas anggaran harus lebih dari nol.',

            'warning_threshold_percent.between' =>
                'Ambang peringatan harus berada antara 1 sampai 100 persen.',
        ];
    }

    protected function getRedirectUrl(): string
    {
        $budget =
            $this->route('budget');

        return route(
            'budgets.edit',
            $budget
        );
    }
}
