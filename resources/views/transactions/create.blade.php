@extends('layouts.app')

@section('title', 'Tambah Transaksi — Laras')
@section('page-title', 'Tambah transaksi')
@section(
    'page-description',
    'Catat pemasukan, pengeluaran, atau transfer internal dan eksternal.'
)

@section('content')
    @php
        $currencyCode = $user->preference?->currency_code
            ?? 'IDR';

        $oldType = old('type', $selectedType);

        $oldTransferKind = old(
            'transfer_kind',
            \App\Enums\TransactionTransferKind::Internal->value
        );
    @endphp

    <div
        x-data="{
            type: @js($oldType),
            transferKind: @js($oldTransferKind),
        }"
        class="mx-auto max-w-5xl"
    >
        <header class="mb-7">
            <p class="text-sm font-semibold text-laras-700">
                Keuangan
            </p>

            <h1 class="mt-2 text-3xl font-semibold tracking-tight">
                Catat transaksi
            </h1>

            <p class="mt-3 max-w-2xl leading-7 text-slate-500">
                Saldo rekening akan langsung diperbarui setelah transaksi
                berhasil dicatat.
            </p>
        </header>

        <form
            method="POST"
            action="{{ route('transactions.store') }}"
            data-modern-transaction-form
            data-transaction-transfer-form
            class="rounded-2xl border border-slate-200 bg-white p-6 shadow-laras sm:p-8"
        >
            @csrf

            <section>
                <h2 class="text-base font-semibold text-slate-900">
                    Jenis transaksi
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Pilih arus dana yang akan dicatat.
                </p>

                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    @foreach ([
                        [
                            'value' => 'income',
                            'label' => 'Pemasukan',
                            'description' => 'Saldo bertambah',
                            'icon' => 'arrow-down-left',
                            'active' => 'border-emerald-500 bg-emerald-50 ring-4 ring-emerald-100',
                            'iconClass' => 'bg-emerald-100 text-emerald-700',
                        ],
                        [
                            'value' => 'expense',
                            'label' => 'Pengeluaran',
                            'description' => 'Saldo berkurang',
                            'icon' => 'arrow-up-right',
                            'active' => 'border-rose-500 bg-rose-50 ring-4 ring-rose-100',
                            'iconClass' => 'bg-rose-100 text-rose-700',
                        ],
                        [
                            'value' => 'transfer',
                            'label' => 'Transfer',
                            'description' => 'Internal atau eksternal',
                            'icon' => 'arrow-left-right',
                            'active' => 'border-blue-500 bg-blue-50 ring-4 ring-blue-100',
                            'iconClass' => 'bg-blue-100 text-blue-700',
                        ],
                    ] as $option)
                        <label
                            class="laras-choice-card cursor-pointer rounded-2xl border p-4 transition"
                            x-bind:class="
                                type === @js($option['value'])
                                    ? @js($option['active'])
                                    : 'border-slate-200 hover:border-slate-300'
                            "
                        >
                            <input
                                type="radio"
                                name="type"
                                value="{{ $option['value'] }}"
                                x-model="type"
                                class="sr-only"
                            >

                            <span class="flex items-center gap-3">
                                <span class="flex size-11 items-center justify-center rounded-xl {{ $option['iconClass'] }}">
                                    <i
                                        data-lucide="{{ $option['icon'] }}"
                                        class="size-5"
                                    ></i>
                                </span>

                                <span>
                                    <span class="block text-sm font-semibold text-slate-900">
                                        {{ $option['label'] }}
                                    </span>

                                    <span class="mt-1 block text-xs text-slate-500">
                                        {{ $option['description'] }}
                                    </span>
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </section>

            <section
                x-cloak
                x-show="type === 'transfer'"
                data-transfer-kind-selector
                class="mt-7 rounded-2xl border border-blue-100 bg-blue-50/60 p-5"
            >
                <div class="flex items-start gap-3">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700">
                        <i
                            data-lucide="arrow-left-right"
                            class="size-5"
                        ></i>
                    </span>

                    <div>
                        <h2 class="text-base font-semibold text-slate-900">
                            Tujuan transfer
                        </h2>

                        <p class="mt-1 text-sm leading-6 text-slate-500">
                            Pilih apakah dana dipindahkan antar-rekening Laras
                            atau dikirim ke pihak di luar Laras.
                        </p>
                    </div>
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    @foreach ([
                        [
                            'value' => 'internal',
                            'label' => 'Antar-rekening Laras',
                            'description' => 'Saldo rekening sumber berkurang dan rekening tujuan Laras bertambah.',
                            'icon' => 'repeat-2',
                        ],
                        [
                            'value' => 'external',
                            'label' => 'Ke pihak luar Laras',
                            'description' => 'Dana keluar dari Laras tanpa menambah saldo rekening Laras lain.',
                            'icon' => 'arrow-up-right',
                        ],
                    ] as $option)
                        <label
                            class="cursor-pointer rounded-2xl border bg-white p-4 transition"
                            x-bind:class="
                                transferKind === @js($option['value'])
                                    ? 'border-blue-500 ring-4 ring-blue-100'
                                    : 'border-slate-200 hover:border-slate-300'
                            "
                        >
                            <input
                                type="radio"
                                name="transfer_kind"
                                value="{{ $option['value'] }}"
                                x-model="transferKind"
                                x-bind:disabled="type !== 'transfer'"
                                class="sr-only"
                            >

                            <span class="flex items-start gap-3">
                                <span
                                    class="flex size-10 shrink-0 items-center justify-center rounded-xl"
                                    x-bind:class="
                                        transferKind === @js($option['value'])
                                            ? 'bg-blue-100 text-blue-700'
                                            : 'bg-slate-100 text-slate-500'
                                    "
                                >
                                    <i
                                        data-lucide="{{ $option['icon'] }}"
                                        class="size-4"
                                    ></i>
                                </span>

                                <span>
                                    <span class="block text-sm font-semibold text-slate-900">
                                        {{ $option['label'] }}
                                    </span>

                                    <span class="mt-1 block text-xs leading-5 text-slate-500">
                                        {{ $option['description'] }}
                                    </span>
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>

                @error('transfer_kind')
                    <p class="mt-3 text-sm font-medium text-rose-600">
                        {{ $message }}
                    </p>
                @enderror
            </section>

            <hr class="my-7 border-slate-200">

            <section class="grid gap-6 lg:grid-cols-2">
                <x-ui.floating-select
                    name="account_id"
                    label="Rekening"
                    :required="true"
                    hint="Rekening yang menerima pemasukan atau menjadi sumber dana."
                >
                    <option value="">
                        Pilih rekening
                    </option>

                    @foreach ($accounts as $account)
                        <option
                            value="{{ $account->id }}"
                            @selected(
                                (int) old('account_id')
                                    === $account->id
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

                <div
                    x-cloak
                    x-show="
                        type === 'transfer'
                            && transferKind === 'internal'
                    "
                    data-transfer-internal-fields
                >
                    <x-ui.floating-select
                        name="destination_account_id"
                        label="Rekening tujuan Laras"
                        x-bind:disabled="
                            type !== 'transfer'
                                || transferKind !== 'internal'
                        "
                        x-bind:required="
                            type === 'transfer'
                                && transferKind === 'internal'
                        "
                        hint="Rekening ini akan menerima dana transfer."
                    >
                        <option value="">
                            Pilih rekening tujuan
                        </option>

                        @foreach ($accounts as $account)
                            <option
                                value="{{ $account->id }}"
                                @selected(
                                    (int) old('destination_account_id')
                                        === $account->id
                                )
                            >
                                {{ $account->name }}
                                — {{ $account->currency_code }}
                            </option>
                        @endforeach
                    </x-ui.floating-select>
                </div>

                <div
                    x-cloak
                    x-show="type === 'income'"
                >
                    <x-ui.floating-select
                        id="income_category_id"
                        name="category_id"
                        label="Kategori pemasukan"
                        x-bind:disabled="type !== 'income'"
                        x-bind:required="type === 'income'"
                    >
                        <option value="">
                            Pilih kategori
                        </option>

                        @foreach ($incomeCategories as $category)
                            <option
                                value="{{ $category->id }}"
                                @selected(
                                    (int) old('category_id')
                                        === $category->id
                                )
                            >
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </x-ui.floating-select>
                </div>

                <div
                    x-cloak
                    x-show="type === 'expense'"
                >
                    <x-ui.floating-select
                        id="expense_category_id"
                        name="category_id"
                        label="Kategori pengeluaran"
                        x-bind:disabled="type !== 'expense'"
                        x-bind:required="type === 'expense'"
                    >
                        <option value="">
                            Pilih kategori
                        </option>

                        @foreach ($expenseCategories as $category)
                            <option
                                value="{{ $category->id }}"
                                @selected(
                                    (int) old('category_id')
                                        === $category->id
                                )
                            >
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </x-ui.floating-select>
                </div>

                <div
                    x-cloak
                    x-show="
                        type === 'transfer'
                            && transferKind === 'external'
                    "
                    data-transfer-external-fields
                    class="grid gap-6 lg:col-span-2 lg:grid-cols-2"
                >
                    <x-ui.floating-input
                        name="external_destination_name"
                        label="Nama penerima atau tujuan"
                        :required="false"
                        hint="Nama penerima, merchant, organisasi, atau tujuan dana."
                        maxlength="120"
                        x-bind:disabled="
                            type !== 'transfer'
                                || transferKind !== 'external'
                        "
                        x-bind:required="
                            type === 'transfer'
                                && transferKind === 'external'
                        "
                    />

                    <x-ui.floating-input
                        name="external_destination_institution"
                        label="Bank atau institusi tujuan"
                        hint="Opsional. Contoh: BCA, Mandiri, atau nama platform."
                        maxlength="120"
                        x-bind:disabled="
                            type !== 'transfer'
                                || transferKind !== 'external'
                        "
                    />

                    <div class="lg:col-span-2">
                        <x-ui.floating-input
                            name="external_destination_account_number"
                            label="Nomor rekening atau identitas tujuan"
                            hint="Opsional. Simpan nomor rekening, nomor virtual account, atau ID penerima."
                            maxlength="100"
                            inputmode="numeric"
                            x-bind:disabled="
                                type !== 'transfer'
                                    || transferKind !== 'external'
                            "
                        />
                    </div>
                </div>

                <x-ui.floating-input
                    name="amount"
                    label="Nominal"
                    type="number"
                    :required="true"
                    :prefix="$currencyCode"
                    min="0.01"
                    max="9999999999999999.99"
                    step="0.01"
                    inputmode="decimal"
                />

                <div
                    x-cloak
                    x-show="type === 'transfer'"
                >
                    <x-ui.floating-input
                        name="admin_fee"
                        label="Biaya admin"
                        type="number"
                        value="0"
                        :prefix="$currencyCode"
                        hint="Opsional. Biaya ini ikut mengurangi saldo rekening sumber."
                        min="0"
                        max="9999999999999999.99"
                        step="0.01"
                        inputmode="decimal"
                        x-bind:disabled="type !== 'transfer'"
                    />
                </div>

                <x-ui.floating-input
                    name="occurred_at"
                    label="Tanggal dan waktu"
                    type="datetime-local"
                    :value="$defaultOccurredAt"
                    :required="true"
                />

                <x-ui.floating-input
                    name="counterparty"
                    label="Pihak terkait"
                    hint="Opsional. Contoh: FamilyMart, klien, atau penerima dana."
                    maxlength="120"
                />

                <div class="lg:col-span-2">
                    <x-ui.floating-input
                        name="description"
                        label="Deskripsi"
                        hint="Opsional. Ringkas tujuan transaksi."
                        maxlength="160"
                    />
                </div>

                <x-ui.floating-input
                    name="reference_number"
                    label="Nomor referensi"
                    hint="Opsional. Gunakan nomor dari bukti transaksi."
                    maxlength="100"
                />

                <div class="lg:col-span-2">
                    <x-ui.floating-textarea
                        name="notes"
                        label="Catatan"
                        hint="Opsional. Tambahkan informasi yang perlu disimpan."
                        rows="4"
                        maxlength="2000"
                    />
                </div>
            </section>

            <section
                x-cloak
                x-show="type === 'transfer'"
                data-transfer-impact-summary
                class="mt-7 rounded-2xl border border-slate-200 bg-slate-50 p-5"
                aria-live="polite"
            >
                <div class="flex items-start gap-3">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white text-slate-600 shadow-sm">
                        <i
                            data-lucide="arrow-left-right"
                            class="size-4"
                        ></i>
                    </span>

                    <div>
                        <p class="text-sm font-semibold text-slate-900">
                            Dampak pada saldo
                        </p>

                        <p
                            x-show="transferKind === 'internal'"
                            class="mt-1 text-sm leading-6 text-slate-500"
                        >
                            Nominal dan biaya admin mengurangi rekening sumber.
                            Nominal transfer menambah rekening tujuan Laras.
                        </p>

                        <p
                            x-show="transferKind === 'external'"
                            class="mt-1 text-sm leading-6 text-slate-500"
                        >
                            Nominal dan biaya admin mengurangi rekening sumber.
                            Tidak ada rekening Laras lain yang menerima saldo.
                        </p>
                    </div>
                </div>
            </section>

            <div class="mt-8 flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:justify-end">
                <a
                    href="{{ route('transactions.index') }}"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-xl bg-laras-700 px-6 py-3 text-sm font-semibold text-white transition hover:bg-laras-800 focus:outline-none focus:ring-4 focus:ring-laras-200"
                >
                    Catat transaksi
                </button>
            </div>
        </form>
    </div>
@endsection
