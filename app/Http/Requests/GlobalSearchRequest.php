<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GlobalSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'q' => [
                'required',
                'string',
                'min:2',
                'max:80',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'q.required' => 'Masukkan kata yang ingin dicari.',
            'q.min' => 'Masukkan minimal 2 karakter.',
            'q.max' => 'Pencarian maksimal 80 karakter.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $query = preg_replace(
            '/\s+/u',
            ' ',
            trim((string) $this->input('q', ''))
        );

        $this->merge([
            'q' => $query ?? '',
        ]);
    }
}
