<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOnboardingPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'currency_code' => strtoupper(
                trim((string) $this->input('currency_code'))
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
            'locale' => [
                'required',
                Rule::in(['id']),
            ],
            'currency_code' => [
                'required',
                Rule::in(['IDR', 'USD', 'SGD']),
            ],
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
                    'Y-m-d',
                    'd M Y',
                ]),
            ],
            'time_format' => [
                'required',
                Rule::in([
                    'H:i',
                    'h:i A',
                ]),
            ],
            'week_starts_on' => [
                'required',
                'integer',
                Rule::in([1, 7]),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'name.max' => 'Nama maksimal 100 karakter.',
            'locale.required' => 'Bahasa wajib dipilih.',
            'locale.in' => 'Bahasa yang dipilih tidak tersedia.',
            'currency_code.required' => 'Mata uang wajib dipilih.',
            'currency_code.in' => 'Mata uang yang dipilih tidak tersedia.',
            'timezone.required' => 'Zona waktu wajib dipilih.',
            'timezone.in' => 'Zona waktu yang dipilih tidak tersedia.',
            'date_format.required' => 'Format tanggal wajib dipilih.',
            'date_format.in' => 'Format tanggal yang dipilih tidak tersedia.',
            'time_format.required' => 'Format waktu wajib dipilih.',
            'time_format.in' => 'Format waktu yang dipilih tidak tersedia.',
            'week_starts_on.required' => 'Awal minggu wajib dipilih.',
            'week_starts_on.in' => 'Pilihan awal minggu tidak tersedia.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'locale' => 'bahasa',
            'currency_code' => 'mata uang',
            'timezone' => 'zona waktu',
            'date_format' => 'format tanggal',
            'time_format' => 'format waktu',
            'week_starts_on' => 'awal minggu',
        ];
    }
}
