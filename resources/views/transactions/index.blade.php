@extends('layouts.app')

@section('title', 'Transaksi — Laras')
@section('page-title', 'Transaksi')
@section(
    'page-description',
    'Pantau pemasukan, pengeluaran, dan transfer antar-rekening.'
)

@section('content')
    @php
        $currencyCode = $user->preference?->currency_code
            ?? 'IDR';

        $timezone = $user->preference?->timezone
            ?? config('laras.defaults.timezone');
    @endphp

    <section>
        <div class="flex flex-col justify-between gap-5 xl:flex-row xl:items-end">
            <div>
                <p class="text-sm font-semibold text-laras-700">
                    Riwayat keuangan
                </p>

                <h1 class="mt-2 text-3xl font-semibold tracking-tight">
                    Transaksi
                </h1>

                <p class="mt-3 max-w-2xl leading-7 text-slate-500">
                    Setiap transaksi tercatat memiliki ledger yang dapat
                    ditelusuri kembali.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route(
                        'transactions.create',
                        ['type' => 'income']
                    ) }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100"
                >
                    <i
                        data-lucide="arrow-down-left"
                        class="size-4"
                    ></i>
                    Pemasukan
                </a>

                <a
                    href="{{ route(
                        'transactions.create',
                        ['type' => 'expense']
                    ) }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 transition hover:bg-rose-100"
                >
                    <i
                        data-lucide="arrow-up-right"
                        class="size-4"
                    ></i>
                    Pengeluaran
                </a>

                <a
                    href="{{ route(
                        'transactions.create',
                        ['type' => 'transfer']
                    ) }}"
                    class="inline-flex items-center gap-2 rounded-xl bg-laras-700 px-4 py-3 text-sm font-semibold text-white transition hover:bg-laras-800"
                >
                    <i
                        data-lucide="arrow-left-right"
                        class="size-4"
                    ></i>
                    Transfer
                </a>
            </div>
        </div>
    </section>

    <div
        data-finance-browser="transactions"
        class="relative"
        aria-live="polite"
        aria-busy="false"
    >
    <section class="mt-7 rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
        <form
            method="GET"
            action="{{ route('transactions.index') }}"
            data-finance-filter-form
            class="grid gap-4 md:grid-cols-2 xl:grid-cols-6"
        >
            <div class="xl:col-span-2">
                <label
                    for="search"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Pencarian
                </label>

                <input
                    id="search"
                    name="search"
                    type="search"
                    value="{{ $filters['search'] ?? '' }}"
                    maxlength="100"
                    data-finance-search
                    autocomplete="off"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
                    placeholder="Deskripsi, pihak, referensi..."
                >
            </div>

            <div>
                <label
                    for="type"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Jenis
                </label>

                <select
                    id="type"
                    name="type"
                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none"
                >
                    <option value="">Semua</option>
                    <option
                        value="income"
                        @selected(
                            ($filters['type'] ?? '') === 'income'
                        )
                    >
                        Pemasukan
                    </option>
                    <option
                        value="expense"
                        @selected(
                            ($filters['type'] ?? '') === 'expense'
                        )
                    >
                        Pengeluaran
                    </option>
                    <option
                        value="transfer"
                        @selected(
                            ($filters['type'] ?? '') === 'transfer'
                        )
                    >
                        Transfer
                    </option>
                </select>
            </div>

            <div>
                <label
                    for="status"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Status
                </label>

                <select
                    id="status"
                    name="status"
                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none"
                >
                    <option value="">Semua</option>

                    @foreach (
                        \App\Enums\TransactionStatus::cases()
                        as $status
                    )
                        <option
                            value="{{ $status->value }}"
                            @selected(
                                ($filters['status'] ?? '')
                                    === $status->value
                            )
                        >
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label
                    for="account_id"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Rekening
                </label>

                <select
                    id="account_id"
                    name="account_id"
                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none"
                >
                    <option value="">Semua</option>

                    @foreach ($accounts as $account)
                        <option
                            value="{{ $account->id }}"
                            @selected(
                                (int) ($filters['account_id'] ?? 0)
                                    === $account->id
                            )
                        >
                            {{ $account->name }}
                            {{ $account->trashed() ? '(Arsip)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label
                    for="date_from"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Mulai
                </label>

                <input
                    id="date_from"
                    name="date_from"
                    type="date"
                    value="{{ $filters['date_from'] ?? '' }}"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none"
                >
            </div>

            <div>
                <label
                    for="date_to"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Sampai
                </label>

                <input
                    id="date_to"
                    name="date_to"
                    type="date"
                    value="{{ $filters['date_to'] ?? '' }}"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none"
                >
            </div>

            <div class="flex items-end gap-2 md:col-span-2 xl:col-span-6">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
                >
                    Terapkan filter
                </button>

                <a
                    href="{{ route('transactions.index') }}"
                    data-finance-reset
                    class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                >
                    Reset
                </a>
            </div>
        </form>
    </section>

    <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-laras">
        @if ($transactions->isEmpty())
            <div class="px-6 py-16 text-center">
                <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                    <i
                        data-lucide="receipt-text"
                        class="size-6"
                    ></i>
                </span>

                <h2 class="mt-4 font-semibold text-slate-900">
                    Belum ada transaksi
                </h2>

                <p class="mt-2 text-sm text-slate-500">
                    Catat pemasukan, pengeluaran, atau transfer pertamamu.
                </p>
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($transactions as $transaction)
                    @php
                        $amount = $transaction->displayAmount();

                        $typeStyle = match (
                            $transaction->type
                        ) {
                            \App\Enums\TransactionType::Income => [
                                'icon' => 'arrow-down-left',
                                'iconClass' =>
                                    'bg-emerald-100 text-emerald-700',
                                'amountClass' => 'text-emerald-700',
                                'prefix' => '+',
                            ],
                            \App\Enums\TransactionType::Expense => [
                                'icon' => 'arrow-up-right',
                                'iconClass' =>
                                    'bg-rose-100 text-rose-700',
                                'amountClass' => 'text-rose-700',
                                'prefix' => '-',
                            ],
                            default => [
                                'icon' => 'arrow-left-right',
                                'iconClass' =>
                                    'bg-blue-100 text-blue-700',
                                'amountClass' => 'text-slate-900',
                                'prefix' => '',
                            ],
                        };

                        $statusClass = match (
                            $transaction->status
                        ) {
                            \App\Enums\TransactionStatus::Posted =>
                                'bg-emerald-50 text-emerald-700',
                            \App\Enums\TransactionStatus::Cancelled =>
                                'bg-slate-100 text-slate-500',
                            \App\Enums\TransactionStatus::Draft =>
                                'bg-amber-50 text-amber-700',
                            \App\Enums\TransactionStatus::Pending =>
                                'bg-blue-50 text-blue-700',
                            default =>
                                'bg-rose-50 text-rose-700',
                        };

                        $principalEntries = $transaction->entries
                            ->where(
                                'role',
                                \App\Enums\TransactionEntryRole::Principal
                            );

                        $sourceEntry = $principalEntries->first(
                            fn ($entry): bool =>
                                bccomp(
                                    $entry->amount,
                                    '0.00',
                                    2
                                ) < 0
                        );

                        $destinationEntry = $principalEntries->first(
                            fn ($entry): bool =>
                                bccomp(
                                    $entry->amount,
                                    '0.00',
                                    2
                                ) > 0
                        );

                        $singleEntry = $principalEntries->first();
                    @endphp

                    <a
                        href="{{ route(
                            'transactions.show',
                            $transaction->id
                        ) }}"
                        class="flex flex-col gap-4 px-5 py-5 transition hover:bg-slate-50 sm:flex-row sm:items-center sm:px-6"
                    >
                        <span
                            class="flex size-12 shrink-0 items-center justify-center rounded-2xl {{ $typeStyle['iconClass'] }}"
                        >
                            <i
                                data-lucide="{{ $typeStyle['icon'] }}"
                                class="size-5"
                            ></i>
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="truncate font-semibold text-slate-900">
                                    {{ $transaction->description
                                        ?? $transaction->type->label() }}
                                </p>

                                <span
                                    class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $statusClass }}"
                                >
                                    {{ $transaction->status->label() }}
                                </span>
                            </div>

                            <p class="mt-1 truncate text-sm text-slate-400">
                                @if (
                                    $transaction->type
                                    === \App\Enums\TransactionType::Transfer
                                )
                                    {{ $sourceEntry?->account?->name
                                        ?? 'Rekening sumber' }}
                                    →
                                    {{ $destinationEntry?->account?->name
                                        ?? 'Rekening tujuan' }}
                                @else
                                    {{ $singleEntry?->account?->name
                                        ?? 'Rekening' }}

                                    @if ($singleEntry?->financeCategory)
                                        •
                                        {{ $singleEntry->financeCategory->name }}
                                    @endif
                                @endif
                            </p>
                        </div>

                        <div class="shrink-0 sm:text-right">
                            <p
                                class="font-semibold {{ $typeStyle['amountClass'] }}"
                            >
                                {{ $typeStyle['prefix'] }}
                                {{ $currencyCode }}
                                {{ number_format(
                                    (float) $amount,
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </p>

                            <p class="mt-1 text-xs text-slate-400">
                                {{ $transaction->occurred_at
                                    ->timezone($timezone)
                                    ->translatedFormat(
                                        'd M Y, H:i'
                                    ) }}
                            </p>
                        </div>

                        <span class="hidden text-slate-300 sm:block">
                            <i
                                data-lucide="eye"
                                class="size-4"
                            ></i>
                        </span>
                    </a>
                @endforeach
            </div>

            <div
                data-finance-pagination
                class="border-t border-slate-100 px-5 py-4 sm:px-6"
            >
                {{ $transactions->withQueryString()->links() }}
            </div>
        @endif
    </section>
    </div>
@endsection
