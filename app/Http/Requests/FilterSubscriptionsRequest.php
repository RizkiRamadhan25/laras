<?php

namespace App\Http\Requests;

use App\Enums\SubscriptionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterSubscriptionsRequest extends FormRequest
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
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'status' => [
                'nullable',
                Rule::enum(
                    SubscriptionStatus::class
                ),
            ],

            'account_id' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'finance_category_id' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'search.max' => 'Pencarian maksimal 100 karakter.',

            'status.enum' => 'Status langganan tidak valid.',

            'account_id.integer' => 'Filter rekening tidak valid.',

            'finance_category_id.integer' => 'Filter kategori tidak valid.',
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
