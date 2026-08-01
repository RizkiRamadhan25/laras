<?php

namespace App\Http\Requests;

use App\Enums\RecommendationInteractionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecommendationFeedbackRequest extends FormRequest
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
            'interaction_type' => [
                'required',

                Rule::in([
                    RecommendationInteractionType
                        ::FollowedUp->value,

                    RecommendationInteractionType
                        ::Dismissed->value,

                    RecommendationInteractionType
                        ::Irrelevant->value,
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
            'interaction_type.required' =>
                'Jenis feedback wajib dipilih.',

            'interaction_type.in' =>
                'Jenis feedback rekomendasi tidak valid.',
        ];
    }
}
