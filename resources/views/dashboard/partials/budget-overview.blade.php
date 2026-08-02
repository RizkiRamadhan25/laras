@php
    $budgetSummary = $budgetOverview[
        'summary'
    ];

    $budgetAttentionItems = $budgetOverview[
        'attention_items'
    ]->take(3);

    $budgetCurrency = $budgetOverview[
        'currency_code'
    ];

    $formatBudgetMoney = static fn (
        string $amount
    ): string => number_format(
        (float) $amount,
        0,
        ',',
        '.'
    );
@endphp

<section class="mt-6 grid gap-6 xl:grid-cols-[0.85fr_1.15fr]">
    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras sm:p-6">
        <header class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-laras-700">
                    Anggaran bulan berjalan
                </p>

                <h2 class="mt-2 text-xl font-semibold text-slate-950">
                    Kendali batas pengeluaran
                </h2>

                <p class="mt-2 text-sm leading-6 text-slate-500">
                    Ringkasan penggunaan seluruh anggaran aktif.
                </p>
            </div>

            <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-laras-50 text-laras-700">
                <i
                    data-lucide="piggy-bank"
                    class="size-5"
                ></i>
            </span>
        </header>

        @if ($budgetSummary['active'] === 0)
            <div class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center">
                <p class="font-semibold text-slate-800">
                    Belum ada anggaran aktif
                </p>

                <p class="mt-2 text-sm leading-6 text-slate-500">
                    Buat batas pengeluaran untuk memantau kategori yang penting.
                </p>

                <a
                    href="{{ route('budgets.create') }}"
                    class="mt-5 inline-flex items-center justify-center gap-2 rounded-xl bg-laras-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-laras-800"
                >
                    <i
                        data-lucide="plus"
                        class="size-4"
                    ></i>

                    Tambah anggaran
                </a>
            </div>
        @else
            <div class="mt-6 grid grid-cols-2 gap-3">
                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-medium text-slate-400">
                        Total batas
                    </p>

                    <p class="mt-2 font-semibold text-slate-900">
                        <span class="text-xs text-slate-400">
                            {{ $budgetCurrency }}
                        </span>

                        {{ $formatBudgetMoney(
                            $budgetSummary[
                                'total_limit'
                            ]
                        ) }}
                    </p>
                </div>

                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-medium text-slate-400">
                        Sudah digunakan
                    </p>

                    <p class="mt-2 font-semibold text-slate-900">
                        <span class="text-xs text-slate-400">
                            {{ $budgetCurrency }}
                        </span>

                        {{ $formatBudgetMoney(
                            $budgetSummary[
                                'total_used'
                            ]
                        ) }}
                    </p>
                </div>

                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-medium text-slate-400">
                        Aman
                    </p>

                    <p class="mt-2 text-2xl font-semibold text-emerald-700">
                        {{ $budgetSummary['safe'] }}
                    </p>
                </div>

                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-medium text-slate-400">
                        Perlu perhatian
                    </p>

                    <p class="mt-2 text-2xl font-semibold text-amber-700">
                        {{ $budgetSummary['warning']
                            + $budgetSummary['exceeded'] }}
                    </p>
                </div>
            </div>

            <a
                href="{{ route('budgets.index') }}"
                class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-laras-700 transition hover:text-laras-900"
            >
                Lihat semua anggaran

                <span aria-hidden="true">
                    →
                </span>
            </a>
        @endif
    </article>

    <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-laras">
        <header class="flex flex-col justify-between gap-3 border-b border-slate-100 px-5 py-5 sm:flex-row sm:items-center sm:px-6">
            <div>
                <h2 class="font-semibold text-slate-950">
                    Anggaran perlu perhatian
                </h2>

                <p class="mt-1 text-sm text-slate-400">
                    Prioritas berdasarkan tingkat penggunaan tertinggi.
                </p>
            </div>

            @if ($budgetSummary['exceeded'] > 0)
                <span class="inline-flex w-fit items-center gap-2 rounded-full bg-rose-100 px-3 py-1.5 text-xs font-semibold text-rose-700">
                    <span class="size-2 rounded-full bg-rose-500"></span>

                    {{ $budgetSummary['exceeded'] }} terlampaui
                </span>
            @elseif ($budgetSummary['warning'] > 0)
                <span class="inline-flex w-fit items-center gap-2 rounded-full bg-amber-100 px-3 py-1.5 text-xs font-semibold text-amber-700">
                    <span class="size-2 rounded-full bg-amber-500"></span>

                    {{ $budgetSummary['warning'] }} mendekati batas
                </span>
            @endif
        </header>

        @if ($budgetSummary['active'] === 0)
            <div class="px-6 py-12 text-center">
                <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                    <i
                        data-lucide="piggy-bank"
                        class="size-6"
                    ></i>
                </span>

                <p class="mt-4 font-semibold text-slate-800">
                    Belum ada data anggaran
                </p>
            </div>
        @elseif ($budgetAttentionItems->isEmpty())
            <div class="px-6 py-12 text-center">
                <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
                    <i
                        data-lucide="circle-check"
                        class="size-6"
                    ></i>
                </span>

                <h3 class="mt-4 font-semibold text-slate-900">
                    Semua anggaran masih aman
                </h3>

                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">
                    Belum ada penggunaan yang mencapai ambang peringatan.
                </p>
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($budgetAttentionItems as $item)
                    @php
                        $budget = $item['budget'];
                        $period = $item['period'];
                        $level = $item['alert_level'];

                        $progressClass = match (
                            $level->value
                        ) {
                            'exceeded' =>
                                'bg-rose-500',

                            'warning' =>
                                'bg-amber-500',

                            default =>
                                'bg-emerald-500',
                        };
                    @endphp

                    <a
                        href="{{ route(
                            'budgets.show',
                            [
                                'budget' => $budget,
                                'period' => $period?->id,
                            ]
                        ) }}"
                        class="block px-5 py-5 transition hover:bg-slate-50 sm:px-6"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="truncate font-semibold text-slate-900">
                                        {{ $budget->name }}
                                    </h3>

                                    <span class="rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ $level->colorClass() }}">
                                        {{ $level->label() }}
                                    </span>
                                </div>

                                <p class="mt-1 text-xs text-slate-400">
                                    {{ $budget->financeCategory?->name
                                        ?? 'Tanpa kategori' }}
                                </p>
                            </div>

                            <span class="shrink-0 text-sm font-semibold text-slate-800">
                                {{ $item['usage_percent'] }}%
                            </span>
                        </div>

                        <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-slate-100">
                            <div
                                class="h-full rounded-full {{ $progressClass }}"
                                style="width: {{ $item['progress_width'] }}%"
                            ></div>
                        </div>

                        <div class="mt-3 flex flex-wrap justify-between gap-2 text-xs text-slate-400">
                            <span>
                                Terpakai {{ $budgetCurrency }}
                                {{ $formatBudgetMoney(
                                    $item['used_amount']
                                ) }}
                            </span>

                            <span>
                                Batas {{ $budgetCurrency }}
                                {{ $formatBudgetMoney(
                                    $item['budget_amount']
                                ) }}
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </article>
</section>
