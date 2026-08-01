<?php

namespace App\Http\Requests;

use App\Enums\SubscriptionIntervalUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $reminderDays = $this->input(
            'reminder_days',
            []
        );

        if (! is_array($reminderDays)) {
            $reminderDays = [
                $reminderDays,
            ];
        }

        $this->merge([
            'name' => trim(
                (string) $this->input('name')
            ),

            'provider' => $this->nullableString(
                $this->input('provider')
            ),

            'amount' => $this->normalizeMoney(
                $this->input('amount')
            ),

            'started_on' => $this->nullableString(
                $this->input('started_on')
            ),

            'next_billing_on' => $this->nullableString(
                $this->input('next_billing_on')
            ),

            'end_on' => $this->nullableString(
                $this->input('end_on')
            ),

            'billing_time' => $this->nullableString(
                $this->input('billing_time')
            ),

            'auto_post' => $this->boolean(
                'auto_post'
            ),

            'reminder_days' => array_values(
                array_unique(
                    array_map(
                        'intval',
                        $reminderDays
                    )
                )
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'account_id' => [
                'required',
                'integer',
                'min:1',
            ],

            'finance_category_id' => [
                'required',
                'integer',
                'min:1',
            ],

            'name' => [
                'required',
                'string',
                'max:160',
            ],

            'provider' => [
                'nullable',
                'string',
                'max:120',
            ],

            'amount' => [
                'required',
                'decimal:0,2',
                'gt:0',
                'max:9999999999999999.99',
            ],

            'interval_unit' => [
                'required',
                Rule::enum(
                    SubscriptionIntervalUnit::class
                ),
            ],

            'interval_count' => [
                'required',
                'integer',
                'min:1',
                'max:365',
            ],

            'started_on' => [
                'required',
                'date_format:Y-m-d',
            ],

            'next_billing_on' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:started_on',
            ],

            'end_on' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:started_on',
                'after_or_equal:next_billing_on',
            ],

            'billing_time' => [
                'required',
                'date_format:H:i',
            ],

            'auto_post' => [
                'required',
                'boolean',
            ],

            'reminder_days' => [
                'required',
                'array',
                'min:1',
            ],

            'reminder_days.*' => [
                'required',
                'integer',
                Rule::in([
                    7,
                    3,
                    1,
                    0,
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
            'account_id.required' =>
                'Rekening pembayaran wajib dipilih.',

            'account_id.integer' =>
                'Rekening pembayaran tidak valid.',

            'finance_category_id.required' =>
                'Kategori pengeluaran wajib dipilih.',

            'finance_category_id.integer' =>
                'Kategori pengeluaran tidak valid.',

            'name.required' =>
                'Nama langganan wajib diisi.',

            'name.max' =>
                'Nama langganan maksimal 160 karakter.',

            'provider.max' =>
                'Nama penyedia maksimal 120 karakter.',

            'amount.required' =>
                'Nominal langganan wajib diisi.',

            'amount.decimal' =>
                'Nominal langganan tidak valid.',

            'amount.gt' =>
                'Nominal harus lebih besar dari nol.',

            'interval_unit.required' =>
                'Satuan siklus wajib dipilih.',

            'interval_unit.enum' =>
                'Satuan siklus tidak tersedia.',

            'interval_count.required' =>
                'Interval langganan wajib diisi.',

            'interval_count.integer' =>
                'Interval langganan harus berupa angka bulat.',

            'interval_count.min' =>
                'Interval langganan minimal satu.',

            'interval_count.max' =>
                'Interval langganan maksimal 365.',

            'started_on.required' =>
                'Tanggal mulai wajib diisi.',

            'started_on.date_format' =>
                'Tanggal mulai tidak valid.',

            'next_billing_on.required' =>
                'Tanggal tagihan berikutnya wajib diisi.',

            'next_billing_on.after_or_equal' =>
                'Tanggal tagihan tidak boleh mendahului tanggal mulai.',

            'end_on.after_or_equal' =>
                'Tanggal berakhir tidak boleh mendahului jadwal langganan.',

            'billing_time.required' =>
                'Waktu penagihan wajib diisi.',

            'billing_time.date_format' =>
                'Waktu penagihan tidak valid.',

            'reminder_days.required' =>
                'Pilih setidaknya satu waktu pengingat.',

            'reminder_days.min' =>
                'Pilih setidaknya satu waktu pengingat.',

            'reminder_days.*.in' =>
                'Pilihan waktu pengingat tidak valid.',
        ];
    }

    private function nullableString(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $normalized = trim(
            (string) $value
        );

        return $normalized === ''
            ? null
            : $normalized;
    }

    private function normalizeMoney(
        mixed $value
    ): string {
        $normalized = trim(
            (string) $value
        );

        /*
         * Input browser dapat berisi pemisah ribuan
         * dari hasil penyalinan.
         */
        return str_replace(
            [
                ' ',
                ',',
            ],
            [
                '',
                '.',
            ],
            $normalized
        );
    }
}
