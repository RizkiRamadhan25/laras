<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Rekening Awal — Laras</title>

    <meta
        name="description"
        content="Siapkan rekening dan saldo awal untuk Laras."
    >

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-50 text-slate-950 antialiased">
    @php
        $rows = old('accounts', $accountRows);
        $currencyCode = $user->preference->currency_code;
    @endphp

    <main class="mx-auto min-h-screen max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <header class="border-b border-slate-200 px-6 py-7 sm:px-10">
                <div class="flex items-center gap-3">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-sm font-semibold text-white">
                        ✓
                    </span>

                    <div class="h-1 flex-1 overflow-hidden rounded-full bg-slate-200">
                        <div class="h-full w-full rounded-full bg-blue-700"></div>
                    </div>

                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-blue-700 text-sm font-semibold text-white">
                        2
                    </span>
                </div>

                <div class="mt-7 flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
                    <div>
                        <p class="text-sm font-semibold text-blue-700">
                            Langkah 2 dari 2
                        </p>

                        <h1 class="mt-2 text-3xl font-semibold tracking-tight">
                            Siapkan rekening awal
                        </h1>

                        <p class="mt-3 max-w-3xl leading-7 text-slate-500">
                            Tambahkan rekening bank, dompet digital, investasi,
                            dan uang tunai yang ingin dikelola melalui Laras.
                        </p>
                    </div>

                    <div class="rounded-2xl bg-slate-50 px-5 py-4 lg:min-w-52">
                        <p class="text-xs font-medium uppercase tracking-wider text-slate-400">
                            Mata uang utama
                        </p>

                        <p class="mt-1 text-lg font-semibold text-slate-900">
                            {{ $currencyCode }}
                        </p>
                    </div>
                </div>
            </header>

            <div class="px-6 py-7 sm:px-10 sm:py-9">
                @if (session('status'))
                    <div
                        class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800"
                        role="status"
                    >
                        {{ session('status') }}
                    </div>
                @endif

                @if (session('warning'))
                    <div
                        class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900"
                        role="alert"
                    >
                        {{ session('warning') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div
                        class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4"
                        role="alert"
                    >
                        <p class="text-sm font-semibold text-rose-800">
                            Beberapa data rekening perlu diperbaiki.
                        </p>

                        <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-rose-700">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form
                    method="POST"
                    action="{{ route('onboarding.accounts.store') }}"
                    id="accounts-form"
                >
                    @csrf

                    <div
                        id="account-list"
                        class="space-y-5"
                    >
                        @foreach ($rows as $index => $account)
                            <article
                                class="account-row rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"
                                data-account-row
                                data-index="{{ $index }}"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-medium uppercase tracking-wider text-slate-400">
                                            Akun keuangan
                                        </p>

                                        <h2
                                            class="account-row-title mt-1 text-lg font-semibold"
                                        >
                                            Rekening {{ $loop->iteration }}
                                        </h2>
                                    </div>

                                    <button
                                        type="button"
                                        data-remove-account
                                        class="rounded-lg px-3 py-2 text-sm font-medium text-rose-600 transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-40"
                                    >
                                        Hapus
                                    </button>
                                </div>

                                <div class="mt-5 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                                    <div>
                                        <label
                                            for="accounts_{{ $index }}_name"
                                            class="mb-2 block text-sm font-medium text-slate-700"
                                        >
                                            Nama rekening
                                        </label>

                                        <input
                                            id="accounts_{{ $index }}_name"
                                            name="accounts[{{ $index }}][name]"
                                            type="text"
                                            value="{{ $account['name'] ?? '' }}"
                                            maxlength="100"
                                            required
                                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-100"
                                            placeholder="Contoh: BCA Utama"
                                        >

                                        @error("accounts.$index.name")
                                            <p class="mt-2 text-sm text-rose-600">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label
                                            for="accounts_{{ $index }}_type"
                                            class="mb-2 block text-sm font-medium text-slate-700"
                                        >
                                            Tipe
                                        </label>

                                        <select
                                            id="accounts_{{ $index }}_type"
                                            name="accounts[{{ $index }}][type]"
                                            required
                                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100"
                                        >
                                            @foreach ($accountTypes as $type)
                                                <option
                                                    value="{{ $type->value }}"
                                                    @selected(
                                                        ($account['type'] ?? '')
                                                            === $type->value
                                                    )
                                                >
                                                    {{ $type->label() }}
                                                </option>
                                            @endforeach
                                        </select>

                                        @error("accounts.$index.type")
                                            <p class="mt-2 text-sm text-rose-600">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label
                                            for="accounts_{{ $index }}_institution"
                                            class="mb-2 block text-sm font-medium text-slate-700"
                                        >
                                            Institusi
                                            <span class="font-normal text-slate-400">
                                                (opsional)
                                            </span>
                                        </label>

                                        <input
                                            id="accounts_{{ $index }}_institution"
                                            name="accounts[{{ $index }}][institution]"
                                            type="text"
                                            value="{{ $account['institution'] ?? '' }}"
                                            maxlength="100"
                                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-100"
                                            placeholder="Contoh: BCA, GoPay"
                                        >

                                        @error("accounts.$index.institution")
                                            <p class="mt-2 text-sm text-rose-600">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label
                                            for="accounts_{{ $index }}_balance"
                                            class="mb-2 block text-sm font-medium text-slate-700"
                                        >
                                            Saldo awal
                                        </label>

                                        <div class="flex rounded-xl border border-slate-300 bg-white focus-within:border-blue-600 focus-within:ring-4 focus-within:ring-blue-100">
                                            <span class="flex items-center border-r border-slate-200 px-4 text-sm font-semibold text-slate-500">
                                                {{ $currencyCode }}
                                            </span>

                                            <input
                                                id="accounts_{{ $index }}_balance"
                                                name="accounts[{{ $index }}][initial_balance]"
                                                type="number"
                                                value="{{ $account['initial_balance'] ?? '0' }}"
                                                min="0"
                                                max="9999999999999999.99"
                                                step="0.01"
                                                inputmode="decimal"
                                                required
                                                class="min-w-0 flex-1 rounded-r-xl px-4 py-3 text-sm outline-none"
                                                placeholder="0"
                                            >
                                        </div>

                                        <p class="mt-2 text-xs text-slate-400">
                                            Masukkan tanpa titik pemisah ribuan.
                                        </p>

                                        @error("accounts.$index.initial_balance")
                                            <p class="mt-2 text-sm text-rose-600">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label
                                            for="accounts_{{ $index }}_last_four"
                                            class="mb-2 block text-sm font-medium text-slate-700"
                                        >
                                            Empat digit terakhir
                                            <span class="font-normal text-slate-400">
                                                (opsional)
                                            </span>
                                        </label>

                                        <input
                                            id="accounts_{{ $index }}_last_four"
                                            name="accounts[{{ $index }}][account_number_last_four]"
                                            type="text"
                                            value="{{ $account['account_number_last_four'] ?? '' }}"
                                            maxlength="4"
                                            inputmode="numeric"
                                            pattern="[0-9]{4}"
                                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-100"
                                            placeholder="1234"
                                        >

                                        @error("accounts.$index.account_number_last_four")
                                            <p class="mt-2 text-sm text-rose-600">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label
                                            for="accounts_{{ $index }}_color"
                                            class="mb-2 block text-sm font-medium text-slate-700"
                                        >
                                            Warna penanda
                                        </label>

                                        <div class="flex items-center gap-3 rounded-xl border border-slate-300 px-4 py-2">
                                            <input
                                                id="accounts_{{ $index }}_color"
                                                name="accounts[{{ $index }}][color]"
                                                type="color"
                                                value="{{ $account['color'] ?? '#2563EB' }}"
                                                required
                                                class="size-9 cursor-pointer rounded border-0 bg-transparent p-0"
                                            >

                                            <span class="text-sm text-slate-500">
                                                Digunakan pada kartu dan grafik
                                            </span>
                                        </div>

                                        @error("accounts.$index.color")
                                            <p class="mt-2 text-sm text-rose-600">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="mt-6">
                        <button
                            id="add-account-button"
                            type="button"
                            class="inline-flex items-center rounded-xl border border-dashed border-blue-300 bg-blue-50 px-5 py-3 text-sm font-semibold text-blue-700 transition hover:border-blue-400 hover:bg-blue-100"
                        >
                            <span class="mr-2 text-lg leading-none">+</span>
                            Tambah rekening
                        </button>

                        <p
                            id="account-limit-message"
                            class="mt-2 hidden text-sm text-amber-700"
                        >
                            Maksimal 12 rekening dapat ditambahkan.
                        </p>
                    </div>

                    <div class="mt-9 flex flex-col-reverse gap-3 border-t border-slate-200 pt-7 sm:flex-row sm:items-center sm:justify-between">
                        <a
                            href="{{ route('onboarding.preferences.edit') }}"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                        >
                            ← Ubah preferensi
                        </a>

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-xl bg-blue-700 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-200 active:translate-y-px"
                        >
                            Selesaikan pengaturan
                            <span class="ml-2" aria-hidden="true">→</span>
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <template id="account-row-template">
        <article
            class="account-row rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"
            data-account-row
            data-index="__INDEX__"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-slate-400">
                        Akun keuangan
                    </p>

                    <h2 class="account-row-title mt-1 text-lg font-semibold">
                        Rekening
                    </h2>
                </div>

                <button
                    type="button"
                    data-remove-account
                    class="rounded-lg px-3 py-2 text-sm font-medium text-rose-600 transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-40"
                >
                    Hapus
                </button>
            </div>

            <div class="mt-5 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label
                        for="accounts___INDEX___name"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Nama rekening
                    </label>

                    <input
                        id="accounts___INDEX___name"
                        name="accounts[__INDEX__][name]"
                        type="text"
                        maxlength="100"
                        required
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-100"
                        placeholder="Contoh: GoPay"
                    >
                </div>

                <div>
                    <label
                        for="accounts___INDEX___type"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Tipe
                    </label>

                    <select
                        id="accounts___INDEX___type"
                        name="accounts[__INDEX__][type]"
                        required
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100"
                    >
                        @foreach ($accountTypes as $type)
                            <option value="{{ $type->value }}">
                                {{ $type->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label
                        for="accounts___INDEX___institution"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Institusi
                        <span class="font-normal text-slate-400">
                            (opsional)
                        </span>
                    </label>

                    <input
                        id="accounts___INDEX___institution"
                        name="accounts[__INDEX__][institution]"
                        type="text"
                        maxlength="100"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-100"
                        placeholder="Contoh: GoPay"
                    >
                </div>

                <div>
                    <label
                        for="accounts___INDEX___balance"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Saldo awal
                    </label>

                    <div class="flex rounded-xl border border-slate-300 bg-white focus-within:border-blue-600 focus-within:ring-4 focus-within:ring-blue-100">
                        <span class="flex items-center border-r border-slate-200 px-4 text-sm font-semibold text-slate-500">
                            {{ $currencyCode }}
                        </span>

                        <input
                            id="accounts___INDEX___balance"
                            name="accounts[__INDEX__][initial_balance]"
                            type="number"
                            value="0"
                            min="0"
                            max="9999999999999999.99"
                            step="0.01"
                            inputmode="decimal"
                            required
                            class="min-w-0 flex-1 rounded-r-xl px-4 py-3 text-sm outline-none"
                        >
                    </div>

                    <p class="mt-2 text-xs text-slate-400">
                        Masukkan tanpa titik pemisah ribuan.
                    </p>
                </div>

                <div>
                    <label
                        for="accounts___INDEX___last_four"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Empat digit terakhir
                        <span class="font-normal text-slate-400">
                            (opsional)
                        </span>
                    </label>

                    <input
                        id="accounts___INDEX___last_four"
                        name="accounts[__INDEX__][account_number_last_four]"
                        type="text"
                        maxlength="4"
                        inputmode="numeric"
                        pattern="[0-9]{4}"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-100"
                        placeholder="1234"
                    >
                </div>

                <div>
                    <label
                        for="accounts___INDEX___color"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Warna penanda
                    </label>

                    <div class="flex items-center gap-3 rounded-xl border border-slate-300 px-4 py-2">
                        <input
                            id="accounts___INDEX___color"
                            name="accounts[__INDEX__][color]"
                            type="color"
                            value="#2563EB"
                            required
                            class="size-9 cursor-pointer rounded border-0 bg-transparent p-0"
                        >

                        <span class="text-sm text-slate-500">
                            Digunakan pada kartu dan grafik
                        </span>
                    </div>
                </div>
            </div>
        </article>
    </template>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const accountList = document.getElementById('account-list');
            const addButton = document.getElementById('add-account-button');
            const template = document.getElementById('account-row-template');
            const limitMessage = document.getElementById(
                'account-limit-message'
            );

            const maximumAccounts = 12;

            const existingIndexes = [...accountList.querySelectorAll(
                '[data-account-row]'
            )].map((row) => Number(row.dataset.index));

            let nextIndex = existingIndexes.length > 0
                ? Math.max(...existingIndexes) + 1
                : 0;

            function rows() {
                return [
                    ...accountList.querySelectorAll('[data-account-row]')
                ];
            }

            function updateRows() {
                const currentRows = rows();

                currentRows.forEach((row, position) => {
                    const title = row.querySelector('.account-row-title');
                    const removeButton = row.querySelector(
                        '[data-remove-account]'
                    );

                    title.textContent = `Rekening ${position + 1}`;
                    removeButton.disabled = currentRows.length === 1;
                });

                const limitReached =
                    currentRows.length >= maximumAccounts;

                addButton.disabled = limitReached;

                addButton.classList.toggle(
                    'cursor-not-allowed',
                    limitReached
                );

                addButton.classList.toggle(
                    'opacity-50',
                    limitReached
                );

                limitMessage.classList.toggle(
                    'hidden',
                    ! limitReached
                );
            }

            addButton.addEventListener('click', () => {
                if (rows().length >= maximumAccounts) {
                    return;
                }

                const content = template.innerHTML.replaceAll(
                    '__INDEX__',
                    String(nextIndex)
                );

                accountList.insertAdjacentHTML('beforeend', content);

                nextIndex += 1;
                updateRows();

                const newRow = rows().at(-1);
                const nameInput = newRow?.querySelector(
                    'input[name$="[name]"]'
                );

                nameInput?.focus();
            });

            accountList.addEventListener('click', (event) => {
                const removeButton = event.target.closest(
                    '[data-remove-account]'
                );

                if (! removeButton || rows().length === 1) {
                    return;
                }

                removeButton.closest('[data-account-row]')?.remove();

                updateRows();
            });

            updateRows();
        });
    </script>
</body>
</html>
