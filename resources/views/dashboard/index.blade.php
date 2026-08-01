@extends('layouts.app')

@section('title', 'Dashboard — Laras')
@section('page-title', 'Dashboard')
@section(
    'page-description',
    'Ringkasan aktivitas dan kondisi keuanganmu.'
)

@section('content')
    @php
        $formatMoney = static fn (
            string $amount
        ): string => number_format(
            (float) $amount,
            0,
            ',',
            '.'
        );

        $positiveNetCashFlow = bccomp(
            $netCashFlow,
            '0.00',
            2
        ) >= 0;
    @endphp

    <section>
        <div class="flex flex-col justify-between gap-5 xl:flex-row xl:items-end">
            <div>
                <p class="text-sm font-semibold text-laras-700">
                    {{ $currentDate }}
                </p>

                <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">
                    {{ $greeting }}, {{ $user->name }}.
                </h1>

                <p class="mt-3 max-w-2xl leading-7 text-slate-500">
                    Berikut ringkasan keuanganmu untuk
                    {{ $monthLabel }}.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route(
                        'transactions.create',
                        ['type' => 'income']
                    ) }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100"
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
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-laras-700 px-4 py-3 text-sm font-semibold text-white transition hover:bg-laras-800"
                >
                    <i
                        data-lucide="plus"
                        class="size-4"
                    ></i>
                    Tambah transaksi
                </a>
            </div>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                <span class="flex size-11 items-center justify-center rounded-2xl bg-laras-50 text-laras-700">
                    <i
                        data-lucide="wallet-cards"
                        class="size-5"
                    ></i>
                </span>

                <p class="mt-5 text-sm font-medium text-slate-500">
                    Total saldo
                </p>

                <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">
                    <span class="text-sm font-medium text-slate-400">
                        {{ $currencyCode }}
                    </span>

                    {{ $formatMoney($totalBalance) }}
                </p>

                <p class="mt-2 text-xs text-slate-400">
                    {{ $accounts->count() }} rekening aktif
                </p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                <span class="flex size-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                    <i
                        data-lucide="arrow-down-left"
                        class="size-5"
                    ></i>
                </span>

                <p class="mt-5 text-sm font-medium text-slate-500">
                    Pemasukan bulan ini
                </p>

                <p class="mt-2 text-2xl font-semibold tracking-tight text-emerald-700">
                    <span class="text-sm font-medium text-emerald-500">
                        {{ $currencyCode }}
                    </span>

                    {{ $formatMoney($monthlyIncome) }}
                </p>

                <p class="mt-2 text-xs text-slate-400">
                    Transaksi berstatus tercatat
                </p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                <span class="flex size-11 items-center justify-center rounded-2xl bg-rose-50 text-rose-700">
                    <i
                        data-lucide="arrow-up-right"
                        class="size-5"
                    ></i>
                </span>

                <p class="mt-5 text-sm font-medium text-slate-500">
                    Pengeluaran bulan ini
                </p>

                <p class="mt-2 text-2xl font-semibold tracking-tight text-rose-700">
                    <span class="text-sm font-medium text-rose-500">
                        {{ $currencyCode }}
                    </span>

                    {{ $formatMoney($monthlyExpense) }}
                </p>

                <p class="mt-2 text-xs text-slate-400">
                    Termasuk biaya admin transfer
                </p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                <span
                    @class([
                        'flex size-11 items-center justify-center rounded-2xl',
                        'bg-sky-50 text-sky-700' =>
                            $positiveNetCashFlow,
                        'bg-amber-50 text-amber-700' =>
                            ! $positiveNetCashFlow,
                    ])
                >
                    <i
                        data-lucide="chart-no-axes-combined"
                        class="size-5"
                    ></i>
                </span>

                <p class="mt-5 text-sm font-medium text-slate-500">
                    Arus kas bersih
                </p>

                <p
                    @class([
                        'mt-2 text-2xl font-semibold tracking-tight',
                        'text-sky-700' => $positiveNetCashFlow,
                        'text-amber-700' => ! $positiveNetCashFlow,
                    ])
                >
                    {{ $positiveNetCashFlow ? '' : '-' }}

                    <span class="text-sm font-medium opacity-70">
                        {{ $currencyCode }}
                    </span>

                    {{ $formatMoney(
                        $positiveNetCashFlow
                            ? $netCashFlow
                            : bcsub(
                                '0.00',
                                $netCashFlow,
                                2
                            )
                    ) }}
                </p>

                <p class="mt-2 text-xs text-slate-400">
                    Dari {{ $postedTransactionCount }}
                    transaksi bulan berjalan
                </p>
            </article>
        </div>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1.45fr_0.75fr]">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras sm:p-6">
            <header>
                <h2 class="font-semibold text-slate-950">
                    Arus kas bulan berjalan
                </h2>

                <p class="mt-1 text-sm text-slate-400">
                    Perbandingan pemasukan dan pengeluaran harian.
                </p>
            </header>

            <div class="mt-6 h-80">
                <canvas
                    id="dashboard-cash-flow-chart"
                    aria-label="Grafik arus kas bulan berjalan"
                ></canvas>
            </div>

            <script
                id="dashboard-cash-flow-data"
                type="application/json"
            >{!! json_encode(
                array_merge(
                    $cashFlowChart,
                    ['currency' => $currencyCode]
                ),
                JSON_HEX_TAG
                | JSON_HEX_APOS
                | JSON_HEX_AMP
                | JSON_HEX_QUOT
            ) !!}</script>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras sm:p-6">
            <header>
                <h2 class="font-semibold text-slate-950">
                    Distribusi pengeluaran
                </h2>

                <p class="mt-1 text-sm text-slate-400">
                    Kategori terbesar pada bulan berjalan.
                </p>
            </header>

            @if ($categoryBreakdown->isEmpty())
                <div class="flex h-80 flex-col items-center justify-center text-center">
                    <span class="flex size-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                        <i
                            data-lucide="chart-no-axes-combined"
                            class="size-6"
                        ></i>
                    </span>

                    <p class="mt-4 font-semibold text-slate-800">
                        Belum ada pengeluaran
                    </p>

                    <p class="mt-2 text-sm text-slate-400">
                        Distribusi kategori akan muncul setelah transaksi dicatat.
                    </p>
                </div>
            @else
                <div class="mt-6 h-52">
                    <canvas
                        id="dashboard-category-chart"
                        aria-label="Grafik distribusi pengeluaran"
                    ></canvas>
                </div>

                <script
                    id="dashboard-category-data"
                    type="application/json"
                >{!! json_encode(
                    array_merge(
                        $categoryChart,
                        ['currency' => $currencyCode]
                    ),
                    JSON_HEX_TAG
                    | JSON_HEX_APOS
                    | JSON_HEX_AMP
                    | JSON_HEX_QUOT
                ) !!}</script>

                <div class="mt-6 space-y-3">
                    @foreach ($categoryBreakdown as $category)
                        <div class="flex items-center gap-3">
                            <span
                                class="size-3 shrink-0 rounded-full"
                                style="
                                    background-color:
                                    {{ $category['color'] }}
                                "
                            ></span>

                            <p class="min-w-0 flex-1 truncate text-sm font-medium text-slate-700">
                                {{ $category['name'] }}
                            </p>

                            <p class="shrink-0 text-sm font-semibold text-slate-900">
                                {{ $currencyCode }}
                                {{ $formatMoney(
                                    $category['amount']
                                ) }}
                            </p>
                        </div>
                    @endforeach
                </div>
            @endif
        </article>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1.35fr_0.85fr]">
        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-laras">
            <header class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-5 sm:px-6">
                <div>
                    <h2 class="font-semibold text-slate-950">
                        Transaksi terbaru
                    </h2>

                    <p class="mt-1 text-sm text-slate-400">
                        Aktivitas keuangan paling baru.
                    </p>
                </div>

                <a
                    href="{{ route('transactions.index') }}"
                    class="text-sm font-semibold text-laras-700 hover:text-laras-900"
                >
                    Lihat semua
                </a>
            </header>

            @if ($recentTransactions->isEmpty())
                <div class="px-6 py-12 text-center">
                    <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                        <i
                            data-lucide="receipt-text"
                            class="size-6"
                        ></i>
                    </span>

                    <p class="mt-4 font-semibold text-slate-900">
                        Belum ada transaksi
                    </p>
                </div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach ($recentTransactions as $transaction)
                        @php
                            $amount = $transaction->displayAmount();

                            $typeStyle = match (
                                $transaction->type
                            ) {
                                \App\Enums\TransactionType::Income => [
                                    'icon' => 'arrow-down-left',
                                    'background' =>
                                        'bg-emerald-100 text-emerald-700',
                                    'amount' =>
                                        'text-emerald-700',
                                    'prefix' => '+',
                                ],

                                \App\Enums\TransactionType::Expense => [
                                    'icon' => 'arrow-up-right',
                                    'background' =>
                                        'bg-rose-100 text-rose-700',
                                    'amount' =>
                                        'text-rose-700',
                                    'prefix' => '-',
                                ],

                                default => [
                                    'icon' => 'arrow-left-right',
                                    'background' =>
                                        'bg-blue-100 text-blue-700',
                                    'amount' =>
                                        'text-slate-900',
                                    'prefix' => '',
                                ],
                            };
                        @endphp

                        <a
                            href="{{ route(
                                'transactions.show',
                                $transaction->id
                            ) }}"
                            class="flex items-center gap-4 px-5 py-4 transition hover:bg-slate-50 sm:px-6"
                        >
                            <span
                                class="flex size-11 shrink-0 items-center justify-center rounded-2xl {{ $typeStyle['background'] }}"
                            >
                                <i
                                    data-lucide="{{ $typeStyle['icon'] }}"
                                    class="size-5"
                                ></i>
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-900">
                                    {{ $transaction->description
                                        ?? $transaction->type->label() }}
                                </p>

                                <p class="mt-1 truncate text-xs text-slate-400">
                                    {{ $transaction->occurred_at
                                        ->timezone($timezone)
                                        ->translatedFormat(
                                            'd M Y, H:i'
                                        ) }}

                                    • {{ $transaction->status->label() }}
                                </p>
                            </div>

                            <p
                                class="shrink-0 text-sm font-semibold {{ $typeStyle['amount'] }}"
                            >
                                {{ $typeStyle['prefix'] }}
                                {{ $currencyCode }}
                                {{ $formatMoney($amount) }}
                            </p>
                        </a>
                    @endforeach
                </div>
            @endif
        </article>

        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-laras">
            <header class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-5 sm:px-6">
                <div>
                    <h2 class="font-semibold text-slate-950">
                        Ringkasan rekening
                    </h2>

                    <p class="mt-1 text-sm text-slate-400">
                        Saldo rekening aktif.
                    </p>
                </div>

                <a
                    href="{{ route('accounts.index') }}"
                    class="text-sm font-semibold text-laras-700 hover:text-laras-900"
                >
                    Kelola
                </a>
            </header>

            <div class="divide-y divide-slate-100">
                @foreach ($accounts->take(6) as $account)
                    <div class="flex items-center gap-4 px-5 py-4 sm:px-6">
                        <span
                            class="size-3 shrink-0 rounded-full"
                            style="
                                background-color:
                                {{ $account->color ?? '#2563EB' }}
                            "
                        ></span>

                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                            <i
                                data-lucide="{{ $account->icon ?? 'wallet' }}"
                                class="size-5"
                            ></i>
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-900">
                                {{ $account->name }}
                            </p>

                            <p class="mt-1 truncate text-xs text-slate-400">
                                {{ $account->institution
                                    ?? $account->type->label() }}
                            </p>
                        </div>

                        <p class="shrink-0 text-sm font-semibold text-slate-900">
                            {{ $account->currency_code }}
                            {{ $formatMoney(
                                $account->cached_balance
                            ) }}
                        </p>
                    </div>
                @endforeach
            </div>
        </article>
    </section>
@endsection
