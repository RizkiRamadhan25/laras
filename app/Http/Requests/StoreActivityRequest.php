<?php

namespace App\Http\Requests;

use App\Enums\ActivityPriority;
use App\Enums\ActivityType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => trim(
                (string) $this->input('title')
            ),

            'description' => $this->nullableString(
                $this->input('description')
            ),

            'location' => $this->nullableString(
                $this->input('location')
            ),

            'starts_at' => $this->nullableString(
                $this->input('starts_at')
            ),

            'ends_at' => $this->nullableString(
                $this->input('ends_at')
            ),

            'due_at' => $this->nullableString(
                $this->input('due_at')
            ),

            'estimated_minutes' => $this->nullableString(
                $this->input('estimated_minutes')
            ),

            'all_day' => $this->boolean('all_day'),

            'is_flexible' => $this->boolean(
                'is_flexible'
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
            'title' => [
                'required',
                'string',
                'max:160',
            ],

            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'type' => [
                'required',
                Rule::enum(ActivityType::class),
            ],

            'priority' => [
                'required',
                Rule::enum(ActivityPriority::class),
            ],

            'starts_at' => [
                'required_if:type,event',
                'nullable',
                'date_format:Y-m-d\TH:i',
            ],

            'ends_at' => [
                'nullable',
                'date_format:Y-m-d\TH:i',
                'after_or_equal:starts_at',
            ],

            'due_at' => [
                'required_if:type,deadline',
                'nullable',
                'date_format:Y-m-d\TH:i',
                'after_or_equal:starts_at',
            ],

            'all_day' => [
                'required',
                'boolean',
            ],

            'estimated_minutes' => [
                'nullable',
                'integer',
                'min:5',
                'max:1440',
            ],

            'is_flexible' => [
                'required',
                'boolean',
            ],

            'location' => [
                'nullable',
                'string',
                'max:160',
            ],

            'color' => [
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
            'title.required' => 'Judul aktivitas wajib diisi.',

            'title.max' => 'Judul aktivitas maksimal 160 karakter.',

            'description.max' => 'Deskripsi maksimal 5.000 karakter.',

            'type.required' => 'Jenis aktivitas wajib dipilih.',

            'type.enum' => 'Jenis aktivitas tidak tersedia.',

            'priority.required' => 'Prioritas wajib dipilih.',

            'priority.enum' => 'Prioritas yang dipilih tidak tersedia.',

            'starts_at.required_if' => 'Acara wajib memiliki waktu mulai.',

            'starts_at.date_format' => 'Format waktu mulai tidak valid.',

            'ends_at.date_format' => 'Format waktu selesai tidak valid.',

            'ends_at.after_or_equal' => 'Waktu selesai tidak boleh mendahului waktu mulai.',

            'due_at.required_if' => 'Aktivitas deadline wajib memiliki tenggat.',

            'due_at.date_format' => 'Format tenggat tidak valid.',

            'due_at.after_or_equal' => 'Tenggat tidak boleh mendahului waktu mulai.',

            'estimated_minutes.integer' => 'Estimasi durasi harus berupa angka bulat.',

            'estimated_minutes.min' => 'Estimasi durasi minimal lima menit.',

            'estimated_minutes.max' => 'Estimasi durasi maksimal 1.440 menit.',

            'location.max' => 'Lokasi maksimal 160 karakter.',

            'color.required' => 'Warna aktivitas wajib dipilih.',

            'color.regex' => 'Format warna aktivitas tidak valid.',
        ];
    }

    private function nullableString(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === ''
            ? null
            : $normalized;
    }
}
