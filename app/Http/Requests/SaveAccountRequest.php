<?php

namespace App\Http\Requests;

use App\Enums\AccountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'institution' => $this->nullableString(
                $this->input('institution')
            ),
            'account_number_last_four' => $this->nullableString(
                $this->input('account_number_last_four')
            ),
            'initial_balance' => trim(
                (string) $this->input('initial_balance')
            ),
            'color' => strtoupper(
                trim((string) $this->input('color'))
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
                'max:100',
            ],
            'type' => [
                'required',
                Rule::enum(AccountType::class),
            ],
            'institution' => [
                'nullable',
                'string',
                'max:100',
            ],
            'initial_balance' => [
                'required',
                'numeric',
                'decimal:0,2',
                'min:0',
                'max:9999999999999999.99',
            ],
            'account_number_last_four' => [
                'nullable',
                'regex:/^\d{4}$/',
            ],
            'color' => [
                'required',
                'regex:/^#[0-9A-F]{6}$/',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('name')) {
                return;
            }

            $currentAccountId = $this->route('account') !== null
                ? (int) $this->route('account')
                : null;

            $normalizedName = mb_strtolower(
                trim((string) $this->input('name'))
            );

            $duplicateExists = $this->user()
                ->accounts()
                ->withTrashed()
                ->get(['id', 'name'])
                ->contains(
                    fn ($account): bool =>
                        $account->id !== $currentAccountId
                        && mb_strtolower(trim($account->name))
                            === $normalizedName
                );

            if ($duplicateExists) {
                $validator->errors()->add(
                    'name',
                    'Nama rekening sudah digunakan.'
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
            'name.required' => 'Nama rekening wajib diisi.',
            'name.max' =>
                'Nama rekening maksimal 100 karakter.',
            'type.required' => 'Tipe rekening wajib dipilih.',
            'type.enum' =>
                'Tipe rekening yang dipilih tidak tersedia.',
            'institution.max' =>
                'Nama institusi maksimal 100 karakter.',
            'initial_balance.required' =>
                'Saldo awal wajib diisi.',
            'initial_balance.numeric' =>
                'Saldo awal harus berupa angka.',
            'initial_balance.decimal' =>
                'Saldo awal maksimal memiliki dua angka desimal.',
            'initial_balance.min' =>
                'Saldo awal tidak boleh kurang dari nol.',
            'initial_balance.max' =>
                'Saldo awal melebihi batas yang diperbolehkan.',
            'account_number_last_four.regex' =>
                'Empat digit terakhir harus berisi tepat empat angka.',
            'color.required' =>
                'Warna penanda wajib dipilih.',
            'color.regex' =>
                'Format warna penanda tidak valid.',
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
