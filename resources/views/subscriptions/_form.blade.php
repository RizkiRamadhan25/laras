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
@endphp

@if ($errors->any())
    <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4">
        <p class="text-sm font-semibold text-rose-800">
            Periksa kembali data langganan.
        </p>

        <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-rose-700">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid gap-6 lg:grid-cols-2">
    <div class="lg:col-span-2">
        <label
            for="name"
            class="mb-2 block text-sm font-medium text-slate-700"
        >
            Nama langganan
        </label>

        <input
            id="name"
            name="name"
            type="text"
            maxlength="160"
            required
            autofocus
            value="{{ old(
                'name',
                $subscription->name ?? ''
            ) }}"
            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
            placeholder="Contoh: Netflix Premium"
        >
    </div>

    <div>
        <label
            for="provider"
            class="mb-2 block text-sm font-medium text-slate-700"
        >
            Penyedia
            <span class="font-normal text-slate-400">
                (opsional)
            </span>
        </label>

        <input
            id="provider"
            name="provider"
            type="text"
            maxlength="120"
            value="{{ old(
                'provider',
                $subscription->provider ?? ''
            ) }}"
            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
            placeholder="Netflix, Google, YouTube..."
        >
    </div>

    <div>
        <label
            for="amount"
            class="mb-2 block text-sm font-medium text-slate-700"
        >
            Nominal setiap tagihan
        </label>

        <div class="flex rounded-xl border border-slate-300 bg-white focus-within:border-laras-600 focus-within:ring-4 focus-within:ring-laras-100">
            <span class="flex items-center border-r border-slate-200 px-4 text-sm font-semibold text-slate-500">
                IDR
            </span>

            <input
                id="amount"
                name="amount"
                type="number"
                min="1"
                max="9999999999999999.99"
                step="0.01"
                required
                value="{{ old(
                    'amount',
                    $subscription->amount ?? ''
                ) }}"
                class="min-w-0 flex-1 rounded-r-xl px-4 py-3 text-sm outline-none"
                placeholder="186000"
            >
        </div>
    </div>

    <div>
        <label
            for="account_id"
            class="mb-2 block text-sm font-medium text-slate-700"
        >
            Rekening pembayaran
        </label>

        <select
            id="account_id"
            name="account_id"
            required
            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
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
        </select>
    </div>

    <div>
        <label
            for="finance_category_id"
            class="mb-2 block text-sm font-medium text-slate-700"
        >
            Kategori pengeluaran
        </label>

        <select
            id="finance_category_id"
            name="finance_category_id"
            required
            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
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
        </select>
    </div>

    <div>
        <label class="mb-2 block text-sm font-medium text-slate-700">
            Siklus pembayaran
        </label>

        <div class="grid grid-cols-[110px_1fr] gap-3">
            <input
                name="interval_count"
                type="number"
                min="1"
                max="365"
                required
                value="{{ old(
                    'interval_count',
                    $subscription->interval_count ?? 1
                ) }}"
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
            >

            <select
                name="interval_unit"
                required
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
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
            </select>
        </div>
    </div>

    <div>
        <label
            for="billing_time"
            class="mb-2 block text-sm font-medium text-slate-700"
        >
            Waktu pencatatan otomatis
        </label>

        <input
            id="billing_time"
            name="billing_time"
            type="time"
            required
            value="{{ $billingTime }}"
            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
        >
    </div>

    <div>
        <label
            for="started_on"
            class="mb-2 block text-sm font-medium text-slate-700"
        >
            Tanggal mulai
        </label>

        <input
            id="started_on"
            name="started_on"
            type="date"
            required
            value="{{ old(
                'started_on',
                isset($subscription)
                    ? $subscription
                        ->started_on
                        ->toDateString()
                    : now()->toDateString()
            ) }}"
            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
        >
    </div>

    <div>
        <label
            for="next_billing_on"
            class="mb-2 block text-sm font-medium text-slate-700"
        >
            Tagihan berikutnya
        </label>

        <input
            id="next_billing_on"
            name="next_billing_on"
            type="date"
            required
            value="{{ old(
                'next_billing_on',
                isset($subscription)
                    ? $subscription
                        ->next_billing_on
                        ?->toDateString()
                    : now()->toDateString()
            ) }}"
            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
        >
    </div>

    <div>
        <label
            for="end_on"
            class="mb-2 block text-sm font-medium text-slate-700"
        >
            Tanggal berakhir
            <span class="font-normal text-slate-400">
                (opsional)
            </span>
        </label>

        <input
            id="end_on"
            name="end_on"
            type="date"
            value="{{ old(
                'end_on',
                isset($subscription)
                    ? $subscription
                        ->end_on
                        ?->toDateString()
                    : ''
            ) }}"
            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
        >
    </div>
</div>

<section class="mt-7">
    <h2 class="text-sm font-semibold text-slate-800">
        Waktu pengingat
    </h2>

    <div class="mt-3 grid gap-3 sm:grid-cols-4">
        @foreach ([
            7 => '7 hari sebelumnya',
            3 => '3 hari sebelumnya',
            1 => '1 hari sebelumnya',
            0 => 'Pada hari tagihan',
        ] as $days => $label)
            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 p-4 transition hover:border-slate-300">
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

    <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-laras-200 bg-laras-50 p-5">
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
