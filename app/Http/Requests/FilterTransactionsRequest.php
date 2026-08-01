<?php

namespace App\Http\Requests;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterTransactionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => $this->nullableString(
                $this->input('search')
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'type' => [
                'nullable',
                Rule::in([
                    TransactionType::Income->value,
                    TransactionType::Expense->value,
                    TransactionType::Transfer->value,
                    TransactionType::Adjustment->value,
                ]),
            ],

            'status' => [
                'nullable',
                Rule::enum(TransactionStatus::class),
            ],

            'account_id' => [
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')->where(
                    fn (Builder $query): Builder => $query
                        ->where(
                            'user_id',
                            $this->user()->id
                        )
                ),
            ],

            'date_from' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'date_to' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:date_from',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'search.max' =>
                'Kata pencarian maksimal 100 karakter.',
            'type.in' =>
                'Jenis transaksi tidak valid.',
            'status.enum' =>
                'Status transaksi tidak valid.',
            'account_id.exists' =>
                'Rekening filter tidak tersedia.',
            'date_from.date_format' =>
                'Format tanggal mulai tidak valid.',
            'date_to.date_format' =>
                'Format tanggal akhir tidak valid.',
            'date_to.after_or_equal' =>
                'Tanggal akhir tidak boleh mendahului tanggal mulai.',
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
