<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePreferencesRequest extends FormRequest
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
            'timezone' => [
                'required',
                Rule::in([
                    'Asia/Jakarta',
                    'Asia/Makassar',
                    'Asia/Jayapura',
                ]),
            ],

            'date_format' => [
                'required',
                Rule::in([
                    'd/m/Y',
                    'd-m-Y',
                    'Y-m-d',
                ]),
            ],

            'time_format' => [
                'required',
                Rule::in([
                    'H:i',
                    'h:i A',
                ]),
            ],

            'currency_code' => [
                'required',
                Rule::in([
                    'IDR',
                    'USD',
                    'SGD',
                    'MYR',
                    'EUR',
                ]),
            ],

            'week_starts_on' => [
                'required',
                'integer',
                Rule::in([
                    0,
                    1,
                    6,
                ]),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'timezone.required' =>
                'Zona waktu wajib dipilih.',

            'timezone.in' =>
                'Zona waktu yang dipilih tidak tersedia.',

            'date_format.required' =>
                'Format tanggal wajib dipilih.',

            'date_format.in' =>
                'Format tanggal yang dipilih tidak valid.',

            'time_format.required' =>
                'Format waktu wajib dipilih.',

            'time_format.in' =>
                'Format waktu yang dipilih tidak valid.',

            'currency_code.required' =>
                'Mata uang utama wajib dipilih.',

            'currency_code.in' =>
                'Mata uang yang dipilih tidak tersedia.',

            'week_starts_on.required' =>
                'Awal minggu wajib dipilih.',

            'week_starts_on.integer' =>
                'Awal minggu tidak valid.',

            'week_starts_on.in' =>
                'Awal minggu yang dipilih tidak tersedia.',
        ];
    }
}
