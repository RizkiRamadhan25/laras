<?php

namespace App\Http\Requests;

use App\Enums\ActivityPriority;
use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterActivitiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => $this->nullableString(
                $this->input('search')
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'view' => [
                'nullable',
                Rule::in([
                    'open',
                    'today',
                    'priority',
                    'completed',
                    'cancelled',
                    'archived',
                ]),
            ],

            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'type' => [
                'nullable',
                Rule::enum(ActivityType::class),
            ],

            'priority' => [
                'nullable',
                Rule::enum(ActivityPriority::class),
            ],

            'status' => [
                'nullable',
                Rule::enum(ActivityStatus::class),
            ],

            'date_from' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'date_to' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:date_from',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'view.in' =>
                'Tampilan aktivitas tidak valid.',

            'search.max' =>
                'Pencarian maksimal 100 karakter.',

            'type.enum' =>
                'Jenis aktivitas tidak valid.',

            'priority.enum' =>
                'Prioritas aktivitas tidak valid.',

            'status.enum' =>
                'Status aktivitas tidak valid.',

            'date_from.date_format' =>
                'Format tanggal mulai tidak valid.',

            'date_to.date_format' =>
                'Format tanggal akhir tidak valid.',

            'date_to.after_or_equal' =>
                'Tanggal akhir tidak boleh mendahului tanggal mulai.',
        ];
    }

    private function nullableString(
        mixed $value
    ): ?string {
        $normalized = trim(
            (string) $value
        );

        return $normalized === ''
            ? null
            : $normalized;
    }
}
