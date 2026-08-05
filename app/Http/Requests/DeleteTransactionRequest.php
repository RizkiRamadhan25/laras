<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'confirmation' => trim(
                (string) $this->input(
                    'confirmation'
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
            'delete_current_password' => [
                'required',
                'string',
                'current_password',
            ],

            'confirmation' => [
                'required',
                'string',
                Rule::in([
                    'HAPUS TRANSAKSI',
                ]),
            ],

            'acknowledge_permanent_deletion' => [
                'accepted',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'delete_current_password.required' => 'Kata sandi saat ini wajib diisi.',

            'delete_current_password.current_password' => 'Kata sandi saat ini tidak sesuai.',

            'confirmation.required' => 'Konfirmasi penghapusan wajib diisi.',

            'confirmation.in' => 'Ketik HAPUS TRANSAKSI untuk mengonfirmasi penghapusan.',

            'acknowledge_permanent_deletion.accepted' => 'Pernyataan penghapusan permanen wajib disetujui.',
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route(
            'transactions.show',
            (int) $this->route('transaction')
        );
    }
}
