@php
    $editing = isset($subscription);

    $selectedReminders = array_map(
        'intval',
        old(
            'reminder_days',
            $editing
                ? ($subscription->reminder_days ?? [3, 1])
                : [3, 1]
        )
    );

    $billingTime = old(
        'billing_time',
        $editing
            ? substr(
                (string) $subscription->billing_time,
                0,
                5
            )
            : '08:00'
    );

    $currencyCode = $user->preference?->currency_code
        ?? 'IDR';
@endphp

<div
    data-modern-subscription-form
    class="grid gap-6 lg:grid-cols-2"
>
    <div class="lg:col-span-2">
        <x-ui.floating-input
            name="name"
            label="Nama langganan"
            :value="$subscription->name ?? ''"
            :required="true"
            hint="Contoh: Netflix Premium atau YouTube Membership."
            maxlength="160"
            autofocus
        />
    </div>

    <x-ui.floating-input
        name="provider"
        label="Penyedia"
        :value="$subscription->provider ?? ''"
        hint="Opsional. Contoh: Netflix, Google, atau YouTube."
        maxlength="120"
    />

    <x-ui.floating-input
        name="amount"
        label="Nominal setiap tagihan"
        type="number"
        :value="$subscription->amount ?? ''"
        :required="true"
        :prefix="$currencyCode"
        min="1"
        max="9999999999999999.99"
        step="0.01"
        inputmode="decimal"
    />

    <x-ui.floating-select
        name="account_id"
        label="Rekening pembayaran"
        :required="true"
    >
        <option value="">
            Pilih rekening
        </option>

        @foreach ($accounts as $account)
            <option
                value="{{ $account->id }}"
                @selected(
                    (string) old(
                        'account_id',
                        $subscription->account_id ?? ''
                    ) === (string) $account->id
                )
            >
                {{ $account->name }}
                — {{ $account->currency_code }}
                {{ number_format(
                    (float) $account->cached_balance,
                    0,
                    ',',
                    '.'
                ) }}
            </option>
        @endforeach
    </x-ui.floating-select>

    <x-ui.floating-select
        name="finance_category_id"
        label="Kategori pengeluaran"
        :required="true"
    >
        <option value="">
            Pilih kategori
        </option>

        @foreach ($categories as $category)
            <option
                value="{{ $category->id }}"
                @selected(
                    (string) old(
                        'finance_category_id',
                        $subscription->finance_category_id ?? ''
                    ) === (string) $category->id
                )
            >
                {{ $category->name }}
            </option>
        @endforeach
    </x-ui.floating-select>

    <div>
        <p class="mb-2 text-sm font-semibold text-slate-800">
            Siklus pembayaran
        </p>

        <div class="grid grid-cols-[minmax(100px,0.65fr)_minmax(0,1.35fr)] gap-3">
            <x-ui.floating-input
                name="interval_count"
                label="Setiap"
                type="number"
                :value="$subscription->interval_count ?? 1"
                :required="true"
                min="1"
                max="365"
            />

            <x-ui.floating-select
                name="interval_unit"
                label="Satuan"
                :required="true"
            >
                @foreach ($intervalUnits as $unit)
                    <option
                        value="{{ $unit->value }}"
                        @selected(
                            old(
                                'interval_unit',
                                $subscription->interval_unit->value
                                    ?? 'month'
                            ) === $unit->value
                        )
                    >
                        {{ $unit->label() }}
                    </option>
                @endforeach
            </x-ui.floating-select>
        </div>
    </div>

    <x-ui.floating-input
        name="billing_time"
        label="Waktu pencatatan otomatis"
        type="time"
        :value="$billingTime"
        :required="true"
    />

    <x-ui.floating-input
        name="started_on"
        label="Tanggal mulai"
        type="date"
        :value="isset($subscription)
            ? $subscription->started_on->toDateString()
            : now()->toDateString()"
        :required="true"
    />

    <x-ui.floating-input
        name="next_billing_on"
        label="Tagihan berikutnya"
        type="date"
        :value="isset($subscription)
            ? $subscription->next_billing_on?->toDateString()
            : now()->toDateString()"
        :required="true"
    />

    <x-ui.floating-input
        name="end_on"
        label="Tanggal berakhir"
        type="date"
        :value="isset($subscription)
            ? $subscription->end_on?->toDateString()
            : ''"
        hint="Opsional. Kosongkan jika langganan tidak mempunyai tanggal berakhir."
    />
</div>

<section class="mt-7">
    <h2 class="text-sm font-semibold text-slate-800">
        Waktu pengingat
    </h2>

    <p class="mt-1 text-sm text-slate-500">
        Pilih kapan Laras perlu mengingatkan tagihan berikutnya.
    </p>

    <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            7 => '7 hari sebelumnya',
            3 => '3 hari sebelumnya',
            1 => '1 hari sebelumnya',
            0 => 'Pada hari tagihan',
        ] as $days => $label)
            <label
                @class([
                    'laras-choice-card flex cursor-pointer items-center gap-3 rounded-xl border p-4 transition',
                    'border-laras-400 bg-laras-50' => in_array(
                        $days,
                        $selectedReminders,
                        true
                    ),
                    'border-slate-200 hover:border-slate-300' => ! in_array(
                        $days,
                        $selectedReminders,
                        true
                    ),
                ])
            >
                <input
                    type="checkbox"
                    name="reminder_days[]"
                    value="{{ $days }}"
                    @checked(
                        in_array(
                            $days,
                            $selectedReminders,
                            true
                        )
                    )
                    class="size-4 rounded border-slate-300 text-laras-700 focus:ring-laras-500"
                >

                <span class="text-sm font-medium text-slate-700">
                    {{ $label }}
                </span>
            </label>
        @endforeach
    </div>
</section>

<section class="mt-7">
    <input
        type="hidden"
        name="auto_post"
        value="0"
    >

    <label class="laras-choice-card flex cursor-pointer items-start gap-3 rounded-2xl border border-laras-200 bg-laras-50 p-5">
        <input
            type="checkbox"
            name="auto_post"
            value="1"
            @checked(
                old(
                    'auto_post',
                    $subscription->auto_post ?? true
                )
            )
            class="mt-1 size-4 rounded border-slate-300 text-laras-700 focus:ring-laras-500"
        >

        <span>
            <span class="block text-sm font-semibold text-laras-950">
                Catat pengeluaran secara otomatis
            </span>

            <span class="mt-1 block text-xs leading-5 text-laras-700">
                Pada tanggal tagihan, Laras akan membuat transaksi
                pengeluaran dan mengurangi saldo rekening yang dipilih.
            </span>
        </span>
    </label>
</section>

<div class="mt-8 flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:justify-end">
    <a
        href="{{ route('subscriptions.index') }}"
        class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
    >
        Kembali
    </a>

    <button
        type="submit"
        class="inline-flex items-center justify-center rounded-xl bg-laras-700 px-6 py-3 text-sm font-semibold text-white transition hover:bg-laras-800"
    >
        {{ $editing
            ? 'Simpan perubahan'
            : 'Tambah langganan' }}
    </button>
</div>
