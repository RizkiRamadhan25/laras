@php
    $editing = isset($account);

    $selectedType = old(
        'type',
        $editing ? $account->type->value : 'bank'
    );

    $currencyCode = $user->preference?->currency_code ?? 'IDR';
@endphp

<div
    data-modern-account-form
    class="grid gap-6 lg:grid-cols-2"
>
    <x-ui.floating-input
        name="name"
        label="Nama rekening"
        :value="$account->name ?? ''"
        :required="true"
        hint="Gunakan nama yang mudah dikenali, misalnya BCA Utama."
        maxlength="100"
        autofocus
    />

    <x-ui.floating-select
        name="type"
        label="Tipe rekening"
        :required="true"
    >
        @foreach ($accountTypes as $type)
            <option
                value="{{ $type->value }}"
                @selected($selectedType === $type->value)
            >
                {{ $type->label() }}
            </option>
        @endforeach
    </x-ui.floating-select>

    <x-ui.floating-input
        name="institution"
        label="Institusi"
        :value="$account->institution ?? ''"
        hint="Opsional. Contoh: BCA, Mandiri, SeaBank, atau GoPay."
        maxlength="100"
    />

    <x-ui.floating-input
        name="account_number_last_four"
        label="Empat digit terakhir"
        :value="$account->account_number_last_four ?? ''"
        hint="Opsional. Hanya digunakan sebagai penanda rekening."
        maxlength="4"
        inputmode="numeric"
        pattern="[0-9]{4}"
    />

    <x-ui.floating-input
        name="initial_balance"
        label="Saldo awal"
        type="number"
        :value="$account->initial_balance ?? '0'"
        :required="true"
        :prefix="$currencyCode"
        :hint="$editing
            ? 'Mengubah saldo awal akan menyesuaikan saldo terkini sebesar selisihnya.'
            : 'Masukkan nominal tanpa titik pemisah ribuan.'"
        min="0"
        max="9999999999999999.99"
        step="0.01"
        inputmode="decimal"
    />

    <div>
        <p class="mb-2 text-sm font-semibold text-slate-800">
            Warna penanda
        </p>

        <label class="flex min-h-[64px] cursor-pointer items-center gap-4 rounded-2xl border border-slate-300 bg-white px-4 transition hover:border-slate-400 focus-within:border-laras-600 focus-within:ring-4 focus-within:ring-laras-100">
            <input
                id="color"
                name="color"
                type="color"
                value="{{ old(
                    'color',
                    $account->color ?? '#2563EB'
                ) }}"
                required
                class="size-10 cursor-pointer border-0 bg-transparent p-0"
            >

            <span>
                <span class="block text-sm font-semibold text-slate-800">
                    Pilih warna rekening
                </span>

                <span class="mt-0.5 block text-xs text-slate-400">
                    Digunakan pada kartu, grafik, dan ringkasan saldo.
                </span>
            </span>
        </label>

        @error('color')
            <p
                class="laras-field__message laras-field__message--error"
                role="alert"
            >
                {{ $message }}
            </p>
        @enderror
    </div>
</div>

@if ($editing)
    <div class="mt-6 rounded-2xl bg-slate-50 p-5">
        <p class="text-sm text-slate-500">
            Saldo terkini
        </p>

        <p class="mt-2 text-xl font-semibold text-slate-900">
            {{ $account->currency_code }}
            {{ number_format(
                (float) $account->cached_balance,
                0,
                ',',
                '.'
            ) }}
        </p>
    </div>
@endif

<div class="mt-8 flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:justify-end">
    <a
        href="{{ route('accounts.index') }}"
        class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
    >
        Batal
    </a>

    <button
        type="submit"
        class="inline-flex items-center justify-center rounded-xl bg-laras-700 px-6 py-3 text-sm font-semibold text-white transition hover:bg-laras-800 focus:outline-none focus:ring-4 focus:ring-laras-200"
    >
        {{ $editing ? 'Simpan perubahan' : 'Tambah rekening' }}
    </button>
</div>
