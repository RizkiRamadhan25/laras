<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'current_password' => [
                'required',
                'string',
                'current_password',
            ],

            'password' => [
                'required',
                'string',
                'different:current_password',
                'confirmed',

                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' =>
                'Kata sandi saat ini wajib diisi.',

            'current_password.current_password' =>
                'Kata sandi saat ini tidak sesuai.',

            'password.required' =>
                'Kata sandi baru wajib diisi.',

            'password.different' =>
                'Kata sandi baru harus berbeda dari kata sandi saat ini.',

            'password.confirmed' =>
                'Konfirmasi kata sandi baru tidak cocok.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'current_password' =>
                'kata sandi saat ini',

            'password' =>
                'kata sandi baru',
        ];
    }
}
