<?php

namespace App\Http\Requests;

use App\Enums\FinanceFlowType;
use App\Enums\TransactionType;
use App\Models\FinanceCategory;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'type' => trim((string) $this->input('type')),
            'amount' => trim((string) $this->input('amount')),
            'admin_fee' => $this->normalizeOptionalMoney(
                $this->input('admin_fee')
            ),
            'description' => $this->nullableString(
                $this->input('description')
            ),
            'counterparty' => $this->nullableString(
                $this->input('counterparty')
            ),
            'reference_number' => $this->nullableString(
                $this->input('reference_number')
            ),
            'notes' => $this->nullableString(
                $this->input('notes')
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;

        $ownedActiveAccount = Rule::exists('accounts', 'id')
            ->where(
                fn (Builder $query): Builder => $query
                    ->where('user_id', $userId)
                    ->where('is_active', true)
                    ->whereNull('deleted_at')
            );

        $ownedActiveCategory = Rule::exists(
            'finance_categories',
            'id'
        )->where(
            fn (Builder $query): Builder => $query
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->whereNull('deleted_at')
        );

        return [
            'type' => [
                'required',
                Rule::in([
                    TransactionType::Income->value,
                    TransactionType::Expense->value,
                    TransactionType::Transfer->value,
                ]),
            ],

            'account_id' => [
                'required',
                'integer',
                $ownedActiveAccount,
            ],

            'destination_account_id' => [
                'exclude_unless:type,transfer',
                'required',
                'integer',
                'different:account_id',
                $ownedActiveAccount,
            ],

            'category_id' => [
                'exclude_if:type,transfer',
                'required',
                'integer',
                $ownedActiveCategory,
            ],

            'amount' => [
                'required',
                'numeric',
                'decimal:0,2',
                'gt:0',
                'max:9999999999999999.99',
            ],

            'admin_fee' => [
                'exclude_unless:type,transfer',
                'nullable',
                'numeric',
                'decimal:0,2',
                'min:0',
                'max:9999999999999999.99',
            ],

            'occurred_at' => [
                'required',
                'date_format:Y-m-d\TH:i',
            ],

            'description' => [
                'nullable',
                'string',
                'max:160',
            ],

            'counterparty' => [
                'nullable',
                'string',
                'max:120',
            ],

            'reference_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (
            Validator $validator
        ): void {
            if (
                $validator->errors()->has('type')
                || $validator->errors()->has('category_id')
            ) {
                return;
            }

            $type = $this->input('type');

            if ($type === TransactionType::Transfer->value) {
                return;
            }

            $category = FinanceCategory::query()
                ->where('user_id', $this->user()->id)
                ->where('is_active', true)
                ->find($this->integer('category_id'));

            if ($category === null) {
                return;
            }

            $requiredFlow = $type === TransactionType::Income->value
                ? FinanceFlowType::Income
                : FinanceFlowType::Expense;

            if (! in_array(
                $category->flow_type,
                [
                    $requiredFlow,
                    FinanceFlowType::Both,
                ],
                true
            )) {
                $validator->errors()->add(
                    'category_id',
                    'Kategori tidak sesuai dengan jenis transaksi.'
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Jenis transaksi wajib dipilih.',
            'type.in' => 'Jenis transaksi tidak tersedia.',

            'account_id.required' => 'Rekening wajib dipilih.',
            'account_id.exists' => 'Rekening yang dipilih tidak tersedia.',

            'destination_account_id.required' => 'Rekening tujuan wajib dipilih.',
            'destination_account_id.different' => 'Rekening sumber dan tujuan harus berbeda.',
            'destination_account_id.exists' => 'Rekening tujuan tidak tersedia.',

            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.exists' => 'Kategori yang dipilih tidak tersedia.',

            'amount.required' => 'Nominal transaksi wajib diisi.',
            'amount.numeric' => 'Nominal transaksi harus berupa angka.',
            'amount.decimal' => 'Nominal maksimal memiliki dua angka desimal.',
            'amount.gt' => 'Nominal transaksi harus lebih besar dari nol.',
            'amount.max' => 'Nominal transaksi melebihi batas.',

            'admin_fee.numeric' => 'Biaya admin harus berupa angka.',
            'admin_fee.decimal' => 'Biaya admin maksimal memiliki dua angka desimal.',
            'admin_fee.min' => 'Biaya admin tidak boleh kurang dari nol.',
            'admin_fee.max' => 'Biaya admin melebihi batas.',

            'occurred_at.required' => 'Tanggal dan waktu transaksi wajib diisi.',
            'occurred_at.date_format' => 'Format tanggal dan waktu transaksi tidak valid.',

            'description.max' => 'Deskripsi maksimal 160 karakter.',
            'counterparty.max' => 'Nama pihak terkait maksimal 120 karakter.',
            'reference_number.max' => 'Nomor referensi maksimal 100 karakter.',
            'notes.max' => 'Catatan maksimal 2.000 karakter.',
        ];
    }

    private function normalizeOptionalMoney(
        mixed $value
    ): string {
        $normalized = trim((string) $value);

        return $normalized === '' ? '0' : $normalized;
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
