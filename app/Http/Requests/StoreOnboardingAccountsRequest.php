<?php

namespace App\Http\Requests;

use App\Enums\AccountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOnboardingAccountsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $submittedAccounts = $this->input('accounts');

        if (! is_array($submittedAccounts)) {
            return;
        }

        $accounts = collect($submittedAccounts)
            ->filter(fn (mixed $account): bool => is_array($account))
            ->map(function (array $account): array {
                $balance = trim(
                    (string) ($account['initial_balance'] ?? '')
                );

                return [
                    'name' => trim(
                        (string) ($account['name'] ?? '')
                    ),
                    'type' => trim(
                        (string) ($account['type'] ?? '')
                    ),
                    'institution' => $this->nullableString(
                        $account['institution'] ?? null
                    ),
                    'initial_balance' => $balance === ''
                        ? '0'
                        : $balance,
                    'account_number_last_four' => $this->nullableString(
                        $account['account_number_last_four'] ?? null
                    ),
                    'color' => strtoupper(
                        trim((string) ($account['color'] ?? ''))
                    ),
                ];
            })
            ->values()
            ->all();

        $this->merge([
            'accounts' => $accounts,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $accountTypes = array_map(
            static fn (AccountType $type): string => $type->value,
            AccountType::cases(),
        );

        return [
            'accounts' => [
                'required',
                'array',
                'min:1',
                'max:12',
            ],

            'accounts.*' => [
                'required',
                'array',
            ],

            'accounts.*.name' => [
                'required',
                'string',
                'max:100',
                'distinct:ignore_case',
            ],

            'accounts.*.type' => [
                'required',
                Rule::in($accountTypes),
            ],

            'accounts.*.institution' => [
                'nullable',
                'string',
                'max:100',
            ],

            'accounts.*.initial_balance' => [
                'required',
                'numeric',
                'decimal:0,2',
                'min:0',
                'max:9999999999999999.99',
            ],

            'accounts.*.account_number_last_four' => [
                'nullable',
                'regex:/^\d{4}$/',
            ],

            'accounts.*.color' => [
                'required',
                'regex:/^#[0-9A-F]{6}$/',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'accounts.required' => 'Tambahkan minimal satu rekening.',
            'accounts.array' => 'Format data rekening tidak valid.',
            'accounts.min' => 'Tambahkan minimal satu rekening.',
            'accounts.max' => 'Maksimal 12 rekening dapat ditambahkan saat onboarding.',

            'accounts.*.name.required' => 'Nama rekening wajib diisi.',
            'accounts.*.name.max' => 'Nama rekening maksimal 100 karakter.',
            'accounts.*.name.distinct' => 'Nama setiap rekening harus berbeda.',

            'accounts.*.type.required' => 'Tipe rekening wajib dipilih.',
            'accounts.*.type.in' => 'Tipe rekening yang dipilih tidak tersedia.',

            'accounts.*.institution.max' => 'Nama institusi maksimal 100 karakter.',

            'accounts.*.initial_balance.required' => 'Saldo awal wajib diisi.',
            'accounts.*.initial_balance.numeric' => 'Saldo awal harus berupa angka.',
            'accounts.*.initial_balance.decimal' => 'Saldo awal maksimal memiliki dua angka desimal.',
            'accounts.*.initial_balance.min' => 'Saldo awal tidak boleh kurang dari nol.',
            'accounts.*.initial_balance.max' => 'Saldo awal melebihi batas yang diperbolehkan.',

            'accounts.*.account_number_last_four.regex' => 'Empat digit rekening harus berisi tepat empat angka.',

            'accounts.*.color.required' => 'Warna rekening wajib dipilih.',
            'accounts.*.color.regex' => 'Format warna rekening tidak valid.',
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
