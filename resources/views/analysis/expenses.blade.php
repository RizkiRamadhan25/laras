@extends('layouts.app')

@section('title', 'Analisis Pengeluaran — Laras')
@section('page-title', 'Analisis')
@section(
    'page-description',
    'Evaluasi pengeluaran berdasarkan kategori dan periode.'
)

@section('content')
    @php
        $selectedPeriod =
            $analysis['selected_period'];

        $selectedPeriodData =
            $analysis['periods'][
                $selectedPeriod
            ];

        $summary = $analysis['summary'];

        $money = static fn (
            string|int|float $amount
        ): string => number_format(
            (float) $amount,
            0,
            ',',
            '.'
        );

        $changePercent =
            $summary['change_percent'];

        $trendStyle = match (
            $summary['trend']
        ) {
            'up' => [
                'class' =>
                    'bg-rose-100 text-rose-700',

                'icon' => 'arrow-up',

                'text' => 'Naik',
            ],

            'down' => [
                'class' =>
                    'bg-emerald-100 text-emerald-700',

                'icon' => 'arrow-down',

                'text' => 'Turun',
            ],

            'new' => [
                'class' =>
                    'bg-blue-100 text-blue-700',

                'icon' => 'sparkles',

                'text' => 'Pengeluaran baru',
            ],

            default => [
                'class' =>
                    'bg-slate-100 text-slate-600',

                'icon' => 'arrow-left-right',

                'text' => 'Tetap',
            ],
        };
    @endphp

    <section>
        <div class="flex flex-col justify-between gap-5 xl:flex-row xl:items-end">
            <div>
                <p class="text-sm font-semibold text-laras-700">
                    Evaluasi keuangan
                </p>

                <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">
                    Pahami pola pengeluaranmu.
                </h1>

                <p class="mt-3 max-w-2xl leading-7 text-slate-500">
                    Lihat kategori yang paling banyak menggunakan
                    saldo selama satu minggu, satu bulan, dan satu tahun.
                </p>
            </div>

            <nav class="flex w-fit gap-2 rounded-2xl border border-slate-200 bg-white p-2 shadow-laras">
                @foreach (
                    [
                        'week' => '7 Hari',
                        'month' => 'Bulan',
                        'year' => 'Tahun',
                    ]
                    as $periodValue => $label
                )
                    <a
                        href="{{ route(
                            'analysis.index',
                            [
                                'period' =>
                                    $periodValue,
                            ]
                        ) }}"
                        @class([
                            'rounded-xl px-4 py-2.5 text-sm font-semibold transition',

                            'bg-laras-700 text-white shadow-sm' =>
                                $selectedPeriod
                                    === $periodValue,

                            'text-slate-500 hover:bg-slate-100 hover:text-slate-900' =>
                                $selectedPeriod
                                    !== $periodValue,
                        ])
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                <span class="flex size-11 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                    <i
                        data-lucide="wallet"
                        class="size-5"
                    ></i>
                </span>

                <p class="mt-5 text-sm text-slate-500">
                    {{ $selectedPeriodData[
                        'label'
                    ] }}
                </p>

                <p class="mt-2 text-2xl font-semibold text-slate-950">
                    IDR
                    {{ $money(
                        $summary[
                            'selected_total'
                        ]
                    ) }}
                </p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                <span class="flex size-11 items-center justify-center rounded-2xl bg-slate-100 text-slate-600">
                    <i
                        data-lucide="history"
                        class="size-5"
                    ></i>
                </span>

                <p class="mt-5 text-sm text-slate-500">
                    {{ $selectedPeriodData[
                        'previous_label'
                    ] }}
                </p>

                <p class="mt-2 text-2xl font-semibold text-slate-950">
                    IDR
                    {{ $money(
                        $summary[
                            'previous_total'
                        ]
                    ) }}
                </p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                <span class="flex size-11 items-center justify-center rounded-2xl {{ $trendStyle['class'] }}">
                    <i
                        data-lucide="{{ $trendStyle['icon'] }}"
                        class="size-5"
                    ></i>
                </span>

                <p class="mt-5 text-sm text-slate-500">
                    Perubahan pengeluaran
                </p>

                <div class="mt-2 flex items-end gap-2">
                    <p class="text-2xl font-semibold text-slate-950">
                        @if (
                            $changePercent === null
                        )
                            Baru
                        @else
                            {{ number_format(
                                abs($changePercent),
                                1,
                                ',',
                                '.'
                            ) }}%
                        @endif
                    </p>

                    <span class="pb-0.5 text-sm font-medium text-slate-500">
                        {{ $trendStyle['text'] }}
                    </span>
                </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                <span class="flex size-11 items-center justify-center rounded-2xl bg-violet-50 text-violet-700">
                    <i
                        data-lucide="calendar-days"
                        class="size-5"
                    ></i>
                </span>

                <p class="mt-5 text-sm text-slate-500">
                    Rata-rata per hari
                </p>

                <p class="mt-2 text-2xl font-semibold text-slate-950">
                    IDR
                    {{ $money(
                        $summary[
                            'average_daily'
                        ]
                    ) }}
                </p>

                <p class="mt-1 text-xs text-slate-400">
                    Berdasarkan
                    {{ $summary['days_count'] }}
                    hari
                </p>
            </article>
        </div>
    </section>

    <section class="mt-6 rounded-2xl border border-laras-200 bg-laras-50 p-5 sm:p-6">
        <div class="flex items-start gap-4">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-laras-700 text-white">
                <i
                    data-lucide="lightbulb"
                    class="size-5"
                ></i>
            </span>

            <div>
                <h2 class="font-semibold text-laras-950">
                    Evaluasi periode ini
                </h2>

                @if (
                    $summary['top_category']
                    === null
                )
                    <p class="mt-2 text-sm leading-6 text-laras-700">
                        Belum ada pengeluaran yang tercatat pada
                        {{ strtolower(
                            $selectedPeriodData[
                                'label'
                            ]
                        ) }}.
                    </p>
                @else
                    <p class="mt-2 text-sm leading-6 text-laras-700">
                        Pengeluaran terbesar berasal dari kategori

                        <strong>
                            {{ $summary[
                                'top_category'
                            ]['name'] }}
                        </strong>

                        sebesar

                        <strong>
                            IDR
                            {{ $money(
                                $summary[
                                    'top_category'
                                ]['selected']
                            ) }}
                        </strong>

                        atau

                        <strong>
                            {{ number_format(
                                $summary[
                                    'top_category'
                                ]['share'],
                                1,
                                ',',
                                '.'
                            ) }}%
                        </strong>

                        dari total pengeluaran periode ini.
                    </p>
                @endif
            </div>
        </div>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-2">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras sm:p-6">
            <header>
                <h2 class="font-semibold text-slate-950">
                    Pengeluaran per kategori
                </h2>

                <p class="mt-1 text-sm text-slate-400">
                    Distribusi untuk
                    {{ strtolower(
                        $selectedPeriodData[
                            'label'
                        ]
                    ) }}.
                </p>
            </header>

            <div class="mt-6 h-[330px]">
                @if (
                    bccomp(
                        $summary[
                            'selected_total'
                        ],
                        '0.00',
                        2
                    ) === 0
                )
                    <div class="flex h-full items-center justify-center rounded-2xl bg-slate-50 text-center">
                        <div>
                            <i
                                data-lucide="chart-no-axes-combined"
                                class="mx-auto size-8 text-slate-400"
                            ></i>

                            <p class="mt-3 text-sm font-semibold text-slate-700">
                                Belum ada data grafik
                            </p>
                        </div>
                    </div>
                @else
                    <canvas
                        id="expense-category-chart"
                        aria-label="Grafik pengeluaran per kategori"
                    ></canvas>
                @endif
            </div>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras sm:p-6">
            <header>
                <h2 class="font-semibold text-slate-950">
                    Tren 12 bulan
                </h2>

                <p class="mt-1 text-sm text-slate-400">
                    Total pengeluaran setiap bulan.
                </p>
            </header>

            <div class="mt-6 h-[330px]">
                <canvas
                    id="expense-monthly-chart"
                    aria-label="Grafik tren pengeluaran bulanan"
                ></canvas>
            </div>
        </article>
    </section>

    <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-laras">
        <header class="border-b border-slate-100 px-5 py-5 sm:px-6">
            <h2 class="font-semibold text-slate-950">
                Rincian setiap kategori
            </h2>

            <p class="mt-1 text-sm text-slate-400">
                Perbandingan pengeluaran selama satu minggu,
                satu bulan, dan satu tahun.
            </p>
        </header>

        @if (
            empty($analysis['categories'])
        )
            <div class="px-6 py-14 text-center">
                <i
                    data-lucide="chart-no-axes-combined"
                    class="mx-auto size-8 text-slate-400"
                ></i>

                <p class="mt-4 font-semibold text-slate-800">
                    Belum ada kategori pengeluaran
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1050px] text-left">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-6 py-4">
                                Kategori
                            </th>

                            <th class="px-5 py-4 text-right">
                                7 hari
                            </th>

                            <th class="px-5 py-4 text-right">
                                Bulan
                            </th>

                            <th class="px-5 py-4 text-right">
                                Tahun
                            </th>

                            <th class="px-5 py-4 text-right">
                                Porsi
                            </th>

                            <th class="px-6 py-4">
                                Perubahan
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @foreach (
                            $analysis['categories']
                            as $category
                        )
                            @php
                                $categoryTrend =
                                    match (
                                        $category['trend']
                                    ) {
                                        'up' => [
                                            'class' =>
                                                'bg-rose-100 text-rose-700',

                                            'text' => 'Naik',
                                        ],

                                        'down' => [
                                            'class' =>
                                                'bg-emerald-100 text-emerald-700',

                                            'text' => 'Turun',
                                        ],

                                        'new' => [
                                            'class' =>
                                                'bg-blue-100 text-blue-700',

                                            'text' => 'Baru',
                                        ],

                                        default => [
                                            'class' =>
                                                'bg-slate-100 text-slate-600',

                                            'text' => 'Tetap',
                                        ],
                                    };
                            @endphp

                            <tr class="transition hover:bg-slate-50">
                                <td class="px-6 py-5">
                                    <div class="flex items-center gap-3">
                                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                                            <i
                                                data-lucide="receipt-text"
                                                class="size-4"
                                            ></i>
                                        </span>

                                        <div>
                                            <p class="font-semibold text-slate-900">
                                                {{ $category[
                                                    'name'
                                                ] }}
                                            </p>

                                            @if (
                                                $category[
                                                    'archived'
                                                ]
                                            )
                                                <p class="mt-1 text-xs text-slate-400">
                                                    Kategori diarsipkan
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <td class="px-5 py-5 text-right text-sm font-semibold text-slate-700">
                                    IDR
                                    {{ $money(
                                        $category[
                                            'week'
                                        ]
                                    ) }}
                                </td>

                                <td class="px-5 py-5 text-right text-sm font-semibold text-slate-700">
                                    IDR
                                    {{ $money(
                                        $category[
                                            'month'
                                        ]
                                    ) }}
                                </td>

                                <td class="px-5 py-5 text-right text-sm font-semibold text-slate-700">
                                    IDR
                                    {{ $money(
                                        $category[
                                            'year'
                                        ]
                                    ) }}
                                </td>

                                <td class="px-5 py-5 text-right">
                                    <span class="text-sm font-semibold text-slate-900">
                                        {{ number_format(
                                            $category[
                                                'share'
                                            ],
                                            1,
                                            ',',
                                            '.'
                                        ) }}%
                                    </span>
                                </td>

                                <td class="px-6 py-5">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $categoryTrend['class'] }}">
                                        @if (
                                            $category[
                                                'change_percent'
                                            ] === null
                                        )
                                            {{ $categoryTrend[
                                                'text'
                                            ] }}
                                        @else
                                            {{ $categoryTrend[
                                                'text'
                                            ] }}

                                            {{ number_format(
                                                abs(
                                                    $category[
                                                        'change_percent'
                                                    ]
                                                ),
                                                1,
                                                ',',
                                                '.'
                                            ) }}%
                                        @endif
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot class="border-t-2 border-slate-200 bg-slate-50">
                        <tr>
                            <td class="px-6 py-5 font-semibold text-slate-900">
                                Total pengeluaran
                            </td>

                            <td class="px-5 py-5 text-right font-semibold text-slate-900">
                                IDR
                                {{ $money(
                                    $summary[
                                        'week_total'
                                    ]
                                ) }}
                            </td>

                            <td class="px-5 py-5 text-right font-semibold text-slate-900">
                                IDR
                                {{ $money(
                                    $summary[
                                        'month_total'
                                    ]
                                ) }}
                            </td>

                            <td class="px-5 py-5 text-right font-semibold text-slate-900">
                                IDR
                                {{ $money(
                                    $summary[
                                        'year_total'
                                    ]
                                ) }}
                            </td>

                            <td class="px-5 py-5 text-right font-semibold text-slate-900">
                                {{ bccomp(
                                    $summary[
                                        'selected_total'
                                    ],
                                    '0.00',
                                    2
                                ) > 0
                                    ? '100%'
                                    : '0%' }}
                            </td>

                            <td class="px-6 py-5"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </section>

    <script
        id="expense-analysis-chart-data"
        type="application/json"
    >{!! json_encode(
        $analysis['chart_data'],
        JSON_HEX_TAG
        | JSON_HEX_APOS
        | JSON_HEX_AMP
        | JSON_HEX_QUOT
    ) !!}</script>
@endsection
