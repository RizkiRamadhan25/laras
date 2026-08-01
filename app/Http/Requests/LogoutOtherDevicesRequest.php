<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LogoutOtherDevicesRequest extends FormRequest
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
            'logout_current_password' => [
                'required',
                'string',
                'current_password',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'logout_current_password.required' =>
                'Kata sandi saat ini wajib diisi.',

            'logout_current_password.current_password' =>
                'Kata sandi saat ini tidak sesuai.',
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('settings.index')
            .'#security';
    }
}
