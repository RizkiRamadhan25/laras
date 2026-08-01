@php
    $editing = isset($account);

    $selectedType = old(
        'type',
        $editing ? $account->type->value : 'bank'
    );

    $currencyCode = $user->preference?->currency_code ?? 'IDR';
@endphp

@if ($errors->any())
    <div
        class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4"
        role="alert"
    >
        <p class="text-sm font-semibold text-rose-800">
            Periksa kembali data rekening.
        </p>

        <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-rose-700">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid gap-6 lg:grid-cols-2">
    <div>
        <label
            for="name"
            class="mb-2 block text-sm font-medium text-slate-700"
        >
            Nama rekening
        </label>

        <input
            id="name"
            name="name"
            type="text"
            value="{{ old('name', $account->name ?? '') }}"
            maxlength="100"
            required
            autofocus
            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
            placeholder="Contoh: BCA Utama"
        >

        @error('name')
            <p class="mt-2 text-sm text-rose-600">
                {{ $message }}
            </p>
        @enderror
    </div>

    <div>
        <label
            for="type"
            class="mb-2 block text-sm font-medium text-slate-700"
        >
            Tipe rekening
        </label>

        <select
            id="type"
            name="type"
            required
            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
        >
            @foreach ($accountTypes as $type)
                <option
                    value="{{ $type->value }}"
                    @selected($selectedType === $type->value)
                >
                    {{ $type->label() }}
                </option>
            @endforeach
        </select>

        @error('type')
            <p class="mt-2 text-sm text-rose-600">
                {{ $message }}
            </p>
        @enderror
    </div>

    <div>
        <label
            for="institution"
            class="mb-2 block text-sm font-medium text-slate-700"
        >
            Institusi
            <span class="font-normal text-slate-400">
                (opsional)
            </span>
        </label>

        <input
            id="institution"
            name="institution"
            type="text"
            value="{{ old('institution', $account->institution ?? '') }}"
            maxlength="100"
            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
            placeholder="Contoh: BCA, GoPay"
        >

        @error('institution')
            <p class="mt-2 text-sm text-rose-600">
                {{ $message }}
            </p>
        @enderror
    </div>

    <div>
        <label
            for="account_number_last_four"
            class="mb-2 block text-sm font-medium text-slate-700"
        >
            Empat digit terakhir
            <span class="font-normal text-slate-400">
                (opsional)
            </span>
        </label>

        <input
            id="account_number_last_four"
            name="account_number_last_four"
            type="text"
            value="{{ old(
                'account_number_last_four',
                $account->account_number_last_four ?? ''
            ) }}"
            maxlength="4"
            inputmode="numeric"
            pattern="[0-9]{4}"
            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
            placeholder="1234"
        >

        @error('account_number_last_four')
            <p class="mt-2 text-sm text-rose-600">
                {{ $message }}
            </p>
        @enderror
    </div>

    <div>
        <label
            for="initial_balance"
            class="mb-2 block text-sm font-medium text-slate-700"
        >
            Saldo awal
        </label>

        <div class="flex rounded-xl border border-slate-300 bg-white focus-within:border-laras-600 focus-within:ring-4 focus-within:ring-laras-100">
            <span class="flex items-center border-r border-slate-200 px-4 text-sm font-semibold text-slate-500">
                {{ $currencyCode }}
            </span>

            <input
                id="initial_balance"
                name="initial_balance"
                type="number"
                value="{{ old(
                    'initial_balance',
                    $account->initial_balance ?? '0'
                ) }}"
                min="0"
                max="9999999999999999.99"
                step="0.01"
                required
                class="min-w-0 flex-1 rounded-r-xl px-4 py-3 text-sm outline-none"
            >
        </div>

        @if ($editing)
            <p class="mt-2 text-xs leading-5 text-amber-700">
                Mengubah saldo awal akan menggeser saldo terkini sebesar
                selisih perubahan tersebut.
            </p>
        @else
            <p class="mt-2 text-xs text-slate-400">
                Masukkan tanpa titik pemisah ribuan.
            </p>
        @endif

        @error('initial_balance')
            <p class="mt-2 text-sm text-rose-600">
                {{ $message }}
            </p>
        @enderror
    </div>

    <div>
        <label
            for="color"
            class="mb-2 block text-sm font-medium text-slate-700"
        >
            Warna penanda
        </label>

        <div class="flex h-[50px] items-center gap-4 rounded-xl border border-slate-300 px-4">
            <input
                id="color"
                name="color"
                type="color"
                value="{{ old(
                    'color',
                    $account->color ?? '#2563EB'
                ) }}"
                required
                class="size-9 cursor-pointer border-0 bg-transparent p-0"
            >

            <span class="text-sm text-slate-500">
                Digunakan pada kartu dan grafik
            </span>
        </div>

        @error('color')
            <p class="mt-2 text-sm text-rose-600">
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
