@extends('layouts.app')

@section('title', 'Anggaran')

@section('content')
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
                    Pantau batas pengeluaran untuk setiap
                    kategori.
                </p>
            </div>

            <a
                href="{{ route(
                    'budgets.create'
                ) }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-laras-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-laras-800"
            >
                <i
                    data-lucide="plus"
                    class="size-4"
                ></i>

                Tambah anggaran
            </a>
        </header>

        @if (session('status'))
            <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <section class="mt-8 grid gap-4 sm:grid-cols-3">
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
                    Total batas aktif
                </p>

                <p class="mt-2 text-2xl font-semibold text-slate-950">
                    Rp{{ number_format(
                        (float) $summary[
                            'active_limit'
                        ],
                        0,
                        ',',
                        '.'
                    ) }}
                </p>
            </article>
        </section>

        <section class="mt-8">
            @forelse ($budgets as $budget)
                @php
                    $period =
                        $budget->periods->first();

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

                <article class="mb-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-laras">
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
                            </div>

                            <h2 class="mt-4 text-xl font-semibold text-slate-950">
                                {{ $budget->name }}
                            </h2>

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
                                            Penggunaan
                                        </span>

                                        <span class="font-semibold text-slate-800">
                                            {{ $period
                                                ->usage_percent }}%
                                        </span>
                                    </div>

                                    <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-slate-100">
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
                                                Rp{{ number_format(
                                                    (float) $period
                                                        ->used_amount,
                                                    0,
                                                    ',',
                                                    '.'
                                                ) }}
                                            </p>
                                        </div>

                                        <div>
                                            <p class="text-xs text-slate-400">
                                                Sisa
                                            </p>

                                            <p class="mt-1 font-semibold text-slate-800">
                                                Rp{{ number_format(
                                                    (float) $period
                                                        ->remaining_amount,
                                                    0,
                                                    ',',
                                                    '.'
                                                ) }}
                                            </p>
                                        </div>

                                        <div>
                                            <p class="text-xs text-slate-400">
                                                Batas
                                            </p>

                                            <p class="mt-1 font-semibold text-slate-800">
                                                Rp{{ number_format(
                                                    (float) $period
                                                        ->budget_amount,
                                                    0,
                                                    ',',
                                                    '.'
                                                ) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="flex shrink-0 gap-2">
                            <a
                                href="{{ route(
                                    'budgets.show',
                                    $budget
                                ) }}"
                                class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                            >
                                Detail
                            </a>

                            <a
                                href="{{ route(
                                    'budgets.edit',
                                    $budget
                                ) }}"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-laras-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-laras-800"
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
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                    <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-laras-50 text-laras-700">
                        <i
                            data-lucide="piggy-bank"
                            class="size-6"
                        ></i>
                    </span>

                    <h2 class="mt-5 text-lg font-semibold text-slate-900">
                        Belum ada anggaran
                    </h2>

                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">
                        Buat anggaran pertama untuk mulai
                        mengendalikan pengeluaran.
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
            @endforelse
        </section>

        @if ($budgets->hasPages())
            <div class="mt-7">
                {{ $budgets->links() }}
            </div>
        @endif
    </div>
@endsection
