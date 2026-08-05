<?php

namespace App\Http\Requests;

use App\Enums\DataDeletionScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurgeRecommendationInteractionsRequest extends FormRequest
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
            'scope' => [
                'required',
                Rule::in([
                    DataDeletionScope::All->value,
                    DataDeletionScope::Selected->value,
                    DataDeletionScope::Older->value,
                ]),
            ],

            'interaction_ids' => [
                'required_if:scope,selected',
                'array',
                'min:1',
                'max:100',
            ],

            'interaction_ids.*' => [
                'required',
                'integer',
                'distinct',
            ],

            'older_than_days' => [
                'required_if:scope,older',
                'integer',
                'min:1',
                'max:3650',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'scope.required' => 'Cakupan penghapusan riwayat rekomendasi wajib dipilih.',
            'scope.in' => 'Cakupan penghapusan riwayat rekomendasi tidak valid.',
            'interaction_ids.required_if' => 'Pilih minimal satu riwayat rekomendasi yang akan dihapus.',
            'interaction_ids.array' => 'Daftar riwayat rekomendasi tidak valid.',
            'interaction_ids.min' => 'Pilih minimal satu riwayat rekomendasi yang akan dihapus.',
            'interaction_ids.max' => 'Maksimal 100 riwayat dapat diproses sekaligus.',
            'interaction_ids.*.integer' => 'Identitas riwayat rekomendasi tidak valid.',
            'interaction_ids.*.distinct' => 'Daftar riwayat tidak boleh berisi data yang sama.',
            'older_than_days.required_if' => 'Umur data wajib ditentukan untuk penghapusan riwayat lama.',
            'older_than_days.integer' => 'Umur data harus berupa angka hari.',
            'older_than_days.min' => 'Umur data minimal 1 hari.',
            'older_than_days.max' => 'Umur data maksimal 3650 hari.',
        ];
    }
}
