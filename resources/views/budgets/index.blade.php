@extends('layouts.app')

@section('title', 'Anggaran')

@section('content')
    @php
        $formatMoney = static fn (
            string|int|float $amount
        ): string => number_format(
            (float) $amount,
            0,
            ',',
            '.'
        );
    @endphp

    <div class="mx-auto max-w-7xl">
        <header class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-semibold text-laras-700">
                    Keuangan
                </p>

                <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">
                    Anggaran
                </h1>

                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                    Pantau batas pengeluaran, cari anggaran,
                    dan dahulukan kategori yang perlu perhatian.
                </p>
            </div>

            <a
                href="{{ route(
                    'budgets.create'
                ) }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-laras-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-laras-800 focus:outline-none focus:ring-4 focus:ring-laras-100"
            >
                <i
                    data-lucide="plus"
                    class="size-4"
                ></i>

                Tambah anggaran
            </a>
        </header>


        <section
            aria-label="Ringkasan anggaran"
            class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
        >
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                <p class="text-sm text-slate-500">
                    Total anggaran
                </p>

                <p class="mt-2 text-3xl font-semibold text-slate-950">
                    {{ $summary['total'] }}
                </p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                <p class="text-sm text-slate-500">
                    Anggaran aktif
                </p>

                <p class="mt-2 text-3xl font-semibold text-slate-950">
                    {{ $summary['active'] }}
                </p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                <p class="text-sm text-slate-500">
                    Perlu perhatian
                </p>

                <p
                    @class([
                        'mt-2 text-3xl font-semibold',
                        'text-amber-700' =>
                            $summary['attention'] > 0,
                        'text-slate-950' =>
                            $summary['attention'] === 0,
                    ])
                >
                    {{ $summary['attention'] }}
                </p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                <p class="text-sm text-slate-500">
                    Total batas aktif
                </p>

                <p class="mt-2 text-2xl font-semibold text-slate-950">
                    Rp{{ $formatMoney(
                        $summary['active_limit']
                    ) }}
                </p>
            </article>
        </section>

        <section
            aria-labelledby="budget-filter-title"
            class="mt-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-laras sm:p-6"
        >
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <div>
                    <h2
                        id="budget-filter-title"
                        class="font-semibold text-slate-950"
                    >
                        Cari dan saring
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Tampilkan anggaran berdasarkan status dan kondisi penggunaan.
                    </p>
                </div>

                @if ($hasCustomControls)
                    <a
                        href="{{ route(
                            'budgets.index'
                        ) }}"
                        class="inline-flex items-center gap-2 self-start rounded-xl px-3 py-2 text-sm font-semibold text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-4 focus:ring-slate-100 sm:self-auto"
                    >
                        <i
                            data-lucide="rotate-ccw"
                            class="size-4"
                        ></i>

                        Atur ulang
                    </a>
                @endif
            </div>

            <form
                method="GET"
                action="{{ route(
                    'budgets.index'
                ) }}"
                class="mt-5 grid gap-4 lg:grid-cols-[minmax(0,1.4fr)_repeat(3,minmax(0,0.75fr))_auto] lg:items-end"
            >
                <div>
                    <label
                        for="budget-search"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Nama atau kategori
                    </label>

                    <div class="relative">
                        <i
                            data-lucide="search"
                            class="pointer-events-none absolute left-4 top-1/2 size-4 -translate-y-1/2 text-slate-400"
                        ></i>

                        <input
                            id="budget-search"
                            name="q"
                            type="search"
                            maxlength="100"
                            value="{{ $filters['q'] }}"
                            placeholder="Contoh: Makanan"
                            class="w-full rounded-xl border border-slate-300 bg-white py-3 pl-11 pr-4 text-sm outline-none transition focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
                        >
                    </div>
                </div>

                <div>
                    <label
                        for="budget-status"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Status
                    </label>

                    <select
                        id="budget-status"
                        name="status"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
                    >
                        <option
                            value="all"
                            @selected(
                                $filters['status'] === 'all'
                            )
                        >
                            Semua status
                        </option>

                        <option
                            value="active"
                            @selected(
                                $filters['status'] === 'active'
                            )
                        >
                            Aktif
                        </option>

                        <option
                            value="inactive"
                            @selected(
                                $filters['status'] === 'inactive'
                            )
                        >
                            Tidak aktif
                        </option>
                    </select>
                </div>

                <div>
                    <label
                        for="budget-condition"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Kondisi
                    </label>

                    <select
                        id="budget-condition"
                        name="condition"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
                    >
                        <option
                            value="all"
                            @selected(
                                $filters['condition'] === 'all'
                            )
                        >
                            Semua kondisi
                        </option>

                        <option
                            value="safe"
                            @selected(
                                $filters['condition'] === 'safe'
                            )
                        >
                            Aman
                        </option>

                        <option
                            value="warning"
                            @selected(
                                $filters['condition'] === 'warning'
                            )
                        >
                            Mendekati batas
                        </option>

                        <option
                            value="exceeded"
                            @selected(
                                $filters['condition'] === 'exceeded'
                            )
                        >
                            Terlampaui
                        </option>

                        <option
                            value="no_period"
                            @selected(
                                $filters['condition'] === 'no_period'
                            )
                        >
                            Tanpa periode aktif
                        </option>
                    </select>
                </div>

                <div>
                    <label
                        for="budget-sort"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Urutkan
                    </label>

                    <select
                        id="budget-sort"
                        name="sort"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
                    >
                        <option
                            value="priority"
                            @selected(
                                $filters['sort'] === 'priority'
                            )
                        >
                            Prioritas kondisi
                        </option>

                        <option
                            value="recent"
                            @selected(
                                $filters['sort'] === 'recent'
                            )
                        >
                            Terbaru dibuat
                        </option>

                        <option
                            value="usage_desc"
                            @selected(
                                $filters['sort'] === 'usage_desc'
                            )
                        >
                            Penggunaan tertinggi
                        </option>

                        <option
                            value="limit_desc"
                            @selected(
                                $filters['sort'] === 'limit_desc'
                            )
                        >
                            Batas terbesar
                        </option>

                        <option
                            value="name_asc"
                            @selected(
                                $filters['sort'] === 'name_asc'
                            )
                        >
                            Nama A–Z
                        </option>
                    </select>
                </div>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-200"
                >
                    <i
                        data-lucide="sliders-horizontal"
                        class="size-4"
                    ></i>

                    Terapkan
                </button>
            </form>
        </section>

        <section
            aria-labelledby="budget-list-title"
            class="mt-8"
        >
            <div class="mb-4 flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                <div>
                    <h2
                        id="budget-list-title"
                        class="font-semibold text-slate-950"
                    >
                        Daftar anggaran
                    </h2>

                    <p
                        class="mt-1 text-sm text-slate-500"
                        aria-live="polite"
                    >
                        Menampilkan {{ $budgets->count() }} dari
                        {{ $budgets->total() }} anggaran yang cocok.
                    </p>
                </div>

                @if ($hasFilters)
                    <p class="text-xs font-medium text-laras-700">
                        Filter sedang diterapkan
                    </p>
                @endif
            </div>

            @forelse ($budgets as $budget)
                @php
                    $period = $budget->activePeriod
                        ?? $budget->latestPeriod;

                    $isActivePeriod =
                        $budget->activePeriod !== null;

                    $alertLevel =
                        $alertLevels[
                            $budget->id
                        ] ?? null;

                    $usage = $period
                        ? (float) $period
                            ->usage_percent
                        : 0;

                    $progressWidth = min(
                        100,
                        max(0, $usage)
                    );

                    $ariaValue = (int) min(
                        100,
                        max(
                            0,
                            round($usage)
                        )
                    );

                    $progressClass = match (
                        $alertLevel?->value
                    ) {
                        'warning' =>
                            'bg-amber-500',

                        'exceeded' =>
                            'bg-rose-500',

                        default =>
                            'bg-emerald-500',
                    };
                @endphp

                <article
                    aria-labelledby="budget-title-{{ $budget->id }}"
                    class="mb-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-laras"
                >
                    <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-start">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-laras-50 px-3 py-1 text-xs font-semibold text-laras-700">
                                    {{ $budget
                                        ->financeCategory
                                        ->name }}
                                </span>

                                <span
                                    class="rounded-full px-3 py-1 text-xs font-semibold {{ $budget->is_active
                                        ? 'bg-emerald-50 text-emerald-700'
                                        : 'bg-slate-100 text-slate-500' }}"
                                >
                                    {{ $budget->is_active
                                        ? 'Aktif'
                                        : 'Tidak aktif' }}
                                </span>

                                @if ($alertLevel)
                                    <span class="rounded-full border px-3 py-1 text-xs font-semibold {{ $alertLevel->colorClass() }}">
                                        {{ $alertLevel->label() }}
                                    </span>
                                @endif

                                @if ($period && ! $isActivePeriod)
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">
                                        Periode terakhir
                                    </span>
                                @endif
                            </div>

                            <h3
                                id="budget-title-{{ $budget->id }}"
                                class="mt-4 text-xl font-semibold text-slate-950"
                            >
                                {{ $budget->name }}
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                {{ $budget
                                    ->period_type
                                    ->label() }}

                                · Mulai
                                {{ $budget
                                    ->start_date
                                    ->format('d/m/Y') }}
                            </p>

                            @if ($period)
                                <div class="mt-6">
                                    <div class="flex items-center justify-between gap-4 text-sm">
                                        <span class="text-slate-500">
                                            {{ $isActivePeriod
                                                ? 'Penggunaan periode aktif'
                                                : 'Penggunaan periode terakhir' }}
                                        </span>

                                        <span class="font-semibold text-slate-800">
                                            {{ number_format(
                                                $usage,
                                                2,
                                                ',',
                                                '.'
                                            ) }}%
                                        </span>
                                    </div>

                                    <div
                                        role="progressbar"
                                        aria-label="Penggunaan {{ $budget->name }}"
                                        aria-valuemin="0"
                                        aria-valuemax="100"
                                        aria-valuenow="{{ $ariaValue }}"
                                        aria-valuetext="{{ number_format(
                                            $usage,
                                            2,
                                            ',',
                                            '.'
                                        ) }} persen; terpakai Rp{{ $formatMoney(
                                            $period->used_amount
                                        ) }} dari batas Rp{{ $formatMoney(
                                            $period->budget_amount
                                        ) }}"
                                        class="mt-2 h-2.5 overflow-hidden rounded-full bg-slate-100"
                                    >
                                        <div
                                            class="h-full rounded-full {{ $progressClass }}"
                                            style="width: {{ $progressWidth }}%"
                                        ></div>
                                    </div>

                                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                        <div>
                                            <p class="text-xs text-slate-400">
                                                Terpakai
                                            </p>

                                            <p class="mt-1 font-semibold text-slate-800">
                                                Rp{{ $formatMoney(
                                                    $period
                                                        ->used_amount
                                                ) }}
                                            </p>
                                        </div>

                                        <div>
                                            <p class="text-xs text-slate-400">
                                                Sisa
                                            </p>

                                            <p
                                                @class([
                                                    'mt-1 font-semibold',
                                                    'text-rose-700' =>
                                                        (float) $period->remaining_amount < 0,
                                                    'text-slate-800' =>
                                                        (float) $period->remaining_amount >= 0,
                                                ])
                                            >
                                                Rp{{ $formatMoney(
                                                    $period
                                                        ->remaining_amount
                                                ) }}
                                            </p>
                                        </div>

                                        <div>
                                            <p class="text-xs text-slate-400">
                                                Batas
                                            </p>

                                            <p class="mt-1 font-semibold text-slate-800">
                                                Rp{{ $formatMoney(
                                                    $period
                                                        ->budget_amount
                                                ) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="mt-6 rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-500">
                                    Belum ada periode yang dapat ditampilkan.
                                    Sinkronisasi akan membuat periode saat anggaran mulai berlaku.
                                </div>
                            @endif
                        </div>

                        <div class="flex shrink-0 flex-wrap gap-2">
                            <a
                                href="{{ route(
                                    'budgets.show',
                                    $budget
                                ) }}"
                                class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 focus:outline-none focus:ring-4 focus:ring-slate-100"
                            >
                                Detail
                            </a>

                            <a
                                href="{{ route(
                                    'budgets.edit',
                                    $budget
                                ) }}"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-laras-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-laras-800 focus:outline-none focus:ring-4 focus:ring-laras-100"
                            >
                                <i
                                    data-lucide="pencil"
                                    class="size-4"
                                ></i>

                                Edit
                            </a>
                        </div>
                    </div>
                </article>
            @empty
                @if ($hasFilters)
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                        <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                            <i
                                data-lucide="search"
                                class="size-6"
                            ></i>
                        </span>

                        <h3 class="mt-5 text-lg font-semibold text-slate-900">
                            Tidak ada anggaran yang cocok
                        </h3>

                        <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">
                            Ubah kata pencarian atau longgarkan filter untuk menampilkan hasil lain.
                        </p>

                        <a
                            href="{{ route(
                                'budgets.index'
                            ) }}"
                            class="mt-6 inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
                        >
                            <i
                                data-lucide="rotate-ccw"
                                class="size-4"
                            ></i>

                            Hapus filter
                        </a>
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                        <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-laras-50 text-laras-700">
                            <i
                                data-lucide="piggy-bank"
                                class="size-6"
                            ></i>
                        </span>

                        <h3 class="mt-5 text-lg font-semibold text-slate-900">
                            Belum ada anggaran
                        </h3>

                        <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">
                            Buat anggaran pertama untuk mulai mengendalikan pengeluaran.
                        </p>

                        <a
                            href="{{ route(
                                'budgets.create'
                            ) }}"
                            class="mt-6 inline-flex items-center justify-center gap-2 rounded-xl bg-laras-700 px-5 py-3 text-sm font-semibold text-white"
                        >
                            <i
                                data-lucide="plus"
                                class="size-4"
                            ></i>

                            Tambah anggaran
                        </a>
                    </div>
                @endif
            @endforelse
        </section>

        @if ($budgets->hasPages())
            <nav
                aria-label="Paginasi anggaran"
                class="mt-7"
            >
                {{ $budgets->links() }}
            </nav>
        @endif
    </div>
@endsection
