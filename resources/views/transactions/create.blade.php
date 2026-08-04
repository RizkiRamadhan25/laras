@extends('layouts.app')

@section('title', 'Tambah Transaksi — Laras')
@section('page-title', 'Tambah transaksi')
@section(
    'page-description',
    'Catat pemasukan, pengeluaran, atau transfer antar-rekening.'
)

@section('content')
    @php
        $currencyCode = $user->preference?->currency_code
            ?? 'IDR';

        $oldType = old('type', $selectedType);
    @endphp

    <div
        x-data="{
            type: @js($oldType),
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
                            'description' => 'Antar-rekening Laras',
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

            <hr class="my-7 border-slate-200">

            <section class="grid gap-6 lg:grid-cols-2">
                <x-ui.floating-select
                    name="account_id"
                    label="Rekening"
                    :required="true"
                    hint="Rekening yang menerima atau mengeluarkan dana."
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
                    x-show="type === 'transfer'"
                >
                    <x-ui.floating-select
                        name="destination_account_id"
                        label="Rekening tujuan"
                        x-bind:disabled="type !== 'transfer'"
                        x-bind:required="type === 'transfer'"
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
