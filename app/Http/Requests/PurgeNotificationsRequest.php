<?php

namespace App\Http\Requests;

use App\Enums\DataDeletionScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurgeNotificationsRequest extends FormRequest
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
                Rule::enum(DataDeletionScope::class),
            ],

            'notification_ids' => [
                'required_if:scope,selected',
                'array',
                'min:1',
                'max:100',
            ],

            'notification_ids.*' => [
                'required',
                'uuid',
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
            'scope.required' => 'Cakupan penghapusan notifikasi wajib dipilih.',
            'scope.enum' => 'Cakupan penghapusan notifikasi tidak valid.',
            'notification_ids.required_if' => 'Pilih minimal satu notifikasi yang akan dihapus.',
            'notification_ids.array' => 'Daftar notifikasi tidak valid.',
            'notification_ids.min' => 'Pilih minimal satu notifikasi yang akan dihapus.',
            'notification_ids.max' => 'Maksimal 100 notifikasi dapat diproses sekaligus.',
            'notification_ids.*.uuid' => 'Identitas notifikasi tidak valid.',
            'notification_ids.*.distinct' => 'Daftar notifikasi tidak boleh berisi data yang sama.',
            'older_than_days.required_if' => 'Umur data wajib ditentukan untuk penghapusan notifikasi lama.',
            'older_than_days.integer' => 'Umur data harus berupa angka hari.',
            'older_than_days.min' => 'Umur data minimal 1 hari.',
            'older_than_days.max' => 'Umur data maksimal 3650 hari.',
        ];
    }
}
