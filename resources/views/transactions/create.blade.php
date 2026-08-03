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


        @if ($errors->any())
            <div
                class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4"
                role="alert"
            >
                <p class="text-sm font-semibold text-rose-800">
                    Periksa kembali data transaksi.
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
            action="{{ route('transactions.store') }}"
            class="rounded-2xl border border-slate-200 bg-white p-6 shadow-laras sm:p-8"
        >
            @csrf

            <section>
                <h2 class="text-base font-semibold text-slate-900">
                    Jenis transaksi
                </h2>

                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    <label
                        class="cursor-pointer rounded-2xl border p-4 transition"
                        x-bind:class="
                            type === 'income'
                                ? 'border-emerald-500 bg-emerald-50 ring-4 ring-emerald-100'
                                : 'border-slate-200 hover:border-slate-300'
                        "
                    >
                        <input
                            type="radio"
                            name="type"
                            value="income"
                            x-model="type"
                            class="sr-only"
                        >

                        <span class="flex items-center gap-3">
                            <span class="flex size-11 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                                <i
                                    data-lucide="arrow-down-left"
                                    class="size-5"
                                ></i>
                            </span>

                            <span>
                                <span class="block text-sm font-semibold text-slate-900">
                                    Pemasukan
                                </span>

                                <span class="mt-1 block text-xs text-slate-500">
                                    Saldo bertambah
                                </span>
                            </span>
                        </span>
                    </label>

                    <label
                        class="cursor-pointer rounded-2xl border p-4 transition"
                        x-bind:class="
                            type === 'expense'
                                ? 'border-rose-500 bg-rose-50 ring-4 ring-rose-100'
                                : 'border-slate-200 hover:border-slate-300'
                        "
                    >
                        <input
                            type="radio"
                            name="type"
                            value="expense"
                            x-model="type"
                            class="sr-only"
                        >

                        <span class="flex items-center gap-3">
                            <span class="flex size-11 items-center justify-center rounded-xl bg-rose-100 text-rose-700">
                                <i
                                    data-lucide="arrow-up-right"
                                    class="size-5"
                                ></i>
                            </span>

                            <span>
                                <span class="block text-sm font-semibold text-slate-900">
                                    Pengeluaran
                                </span>

                                <span class="mt-1 block text-xs text-slate-500">
                                    Saldo berkurang
                                </span>
                            </span>
                        </span>
                    </label>

                    <label
                        class="cursor-pointer rounded-2xl border p-4 transition"
                        x-bind:class="
                            type === 'transfer'
                                ? 'border-blue-500 bg-blue-50 ring-4 ring-blue-100'
                                : 'border-slate-200 hover:border-slate-300'
                        "
                    >
                        <input
                            type="radio"
                            name="type"
                            value="transfer"
                            x-model="type"
                            class="sr-only"
                        >

                        <span class="flex items-center gap-3">
                            <span class="flex size-11 items-center justify-center rounded-xl bg-blue-100 text-blue-700">
                                <i
                                    data-lucide="arrow-left-right"
                                    class="size-5"
                                ></i>
                            </span>

                            <span>
                                <span class="block text-sm font-semibold text-slate-900">
                                    Transfer
                                </span>

                                <span class="mt-1 block text-xs text-slate-500">
                                    Antar-rekening
                                </span>
                            </span>
                        </span>
                    </label>
                </div>
            </section>

            <hr class="my-7 border-slate-200">

            <section class="grid gap-6 lg:grid-cols-2">
                <div>
                    <label
                        for="account_id"
                        class="mb-2 block text-sm font-medium text-slate-700"
                        x-text="
                            type === 'transfer'
                                ? 'Rekening sumber'
                                : 'Rekening'
                        "
                    ></label>

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
                    </select>

                    @error('account_id')
                        <p class="mt-2 text-sm text-rose-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div
                    x-cloak
                    x-show="type === 'transfer'"
                >
                    <label
                        for="destination_account_id"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Rekening tujuan
                    </label>

                    <select
                        id="destination_account_id"
                        name="destination_account_id"
                        x-bind:disabled="type !== 'transfer'"
                        x-bind:required="type === 'transfer'"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
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
                    </select>

                    @error('destination_account_id')
                        <p class="mt-2 text-sm text-rose-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div
                    x-cloak
                    x-show="type === 'income'"
                >
                    <label
                        for="income_category_id"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Kategori pemasukan
                    </label>

                    <select
                        id="income_category_id"
                        name="category_id"
                        x-bind:disabled="type !== 'income'"
                        x-bind:required="type === 'income'"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
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
                    </select>
                </div>

                <div
                    x-cloak
                    x-show="type === 'expense'"
                >
                    <label
                        for="expense_category_id"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Kategori pengeluaran
                    </label>

                    <select
                        id="expense_category_id"
                        name="category_id"
                        x-bind:disabled="type !== 'expense'"
                        x-bind:required="type === 'expense'"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
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
                    </select>
                </div>

                <div>
                    <label
                        for="amount"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Nominal
                    </label>

                    <div class="flex rounded-xl border border-slate-300 bg-white focus-within:border-laras-600 focus-within:ring-4 focus-within:ring-laras-100">
                        <span class="flex items-center border-r border-slate-200 px-4 text-sm font-semibold text-slate-500">
                            {{ $currencyCode }}
                        </span>

                        <input
                            id="amount"
                            name="amount"
                            type="number"
                            value="{{ old('amount') }}"
                            min="0.01"
                            max="9999999999999999.99"
                            step="0.01"
                            inputmode="decimal"
                            required
                            class="min-w-0 flex-1 rounded-r-xl px-4 py-3 text-sm outline-none"
                            placeholder="0"
                        >
                    </div>

                    @error('amount')
                        <p class="mt-2 text-sm text-rose-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div
                    x-cloak
                    x-show="type === 'transfer'"
                >
                    <label
                        for="admin_fee"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Biaya admin
                        <span class="font-normal text-slate-400">
                            (opsional)
                        </span>
                    </label>

                    <div class="flex rounded-xl border border-slate-300 bg-white focus-within:border-laras-600 focus-within:ring-4 focus-within:ring-laras-100">
                        <span class="flex items-center border-r border-slate-200 px-4 text-sm font-semibold text-slate-500">
                            {{ $currencyCode }}
                        </span>

                        <input
                            id="admin_fee"
                            name="admin_fee"
                            type="number"
                            value="{{ old('admin_fee', '0') }}"
                            min="0"
                            max="9999999999999999.99"
                            step="0.01"
                            inputmode="decimal"
                            x-bind:disabled="type !== 'transfer'"
                            class="min-w-0 flex-1 rounded-r-xl px-4 py-3 text-sm outline-none"
                        >
                    </div>

                    @error('admin_fee')
                        <p class="mt-2 text-sm text-rose-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label
                        for="occurred_at"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Tanggal dan waktu
                    </label>

                    <input
                        id="occurred_at"
                        name="occurred_at"
                        type="datetime-local"
                        value="{{ old(
                            'occurred_at',
                            $defaultOccurredAt
                        ) }}"
                        required
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
                    >

                    @error('occurred_at')
                        <p class="mt-2 text-sm text-rose-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label
                        for="counterparty"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Pihak terkait
                        <span class="font-normal text-slate-400">
                            (opsional)
                        </span>
                    </label>

                    <input
                        id="counterparty"
                        name="counterparty"
                        type="text"
                        value="{{ old('counterparty') }}"
                        maxlength="120"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
                        placeholder="Contoh: FamilyMart, Klien"
                    >
                </div>

                <div class="lg:col-span-2">
                    <label
                        for="description"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Deskripsi
                        <span class="font-normal text-slate-400">
                            (opsional)
                        </span>
                    </label>

                    <input
                        id="description"
                        name="description"
                        type="text"
                        value="{{ old('description') }}"
                        maxlength="160"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
                        placeholder="Contoh: Makan siang bersama teman"
                    >
                </div>

                <div>
                    <label
                        for="reference_number"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Nomor referensi
                        <span class="font-normal text-slate-400">
                            (opsional)
                        </span>
                    </label>

                    <input
                        id="reference_number"
                        name="reference_number"
                        type="text"
                        value="{{ old('reference_number') }}"
                        maxlength="100"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
                    >
                </div>

                <div class="lg:col-span-2">
                    <label
                        for="notes"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Catatan
                        <span class="font-normal text-slate-400">
                            (opsional)
                        </span>
                    </label>

                    <textarea
                        id="notes"
                        name="notes"
                        rows="4"
                        maxlength="2000"
                        class="w-full resize-y rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
                    >{{ old('notes') }}</textarea>
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
