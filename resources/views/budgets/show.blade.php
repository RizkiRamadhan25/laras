@extends('layouts.app')

@section('title', $budget->name)

@section('content')
    <div class="mx-auto max-w-6xl">
        <header class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div>
                <a
                    href="{{ route(
                        'budgets.index'
                    ) }}"
                    class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-slate-900"
                >
                    <i
                        data-lucide="arrow-left"
                        class="size-4"
                    ></i>

                    Kembali ke anggaran
                </a>

                <div class="mt-5 flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-laras-50 px-3 py-1 text-xs font-semibold text-laras-700">
                        {{ $budget
                            ->financeCategory
                            ->name }}
                    </span>

                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $budget->is_active
                        ? 'bg-emerald-50 text-emerald-700'
                        : 'bg-slate-100 text-slate-500' }}"
                    >
                        {{ $budget->is_active
                            ? 'Aktif'
                            : 'Tidak aktif' }}
                    </span>
                </div>

                <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">
                    {{ $budget->name }}
                </h1>

                <p class="mt-2 text-sm text-slate-500">
                    {{ $budget
                        ->period_type
                        ->label() }}

                    · Ambang peringatan
                    {{ $budget
                        ->warning_threshold_percent }}%
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route(
                        'budgets.edit',
                        $budget
                    ) }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                >
                    <i
                        data-lucide="pencil"
                        class="size-4"
                    ></i>

                    Edit
                </a>

                @if ($budget->is_active)
                    <form
                        method="POST"
                        action="{{ route(
                            'budgets.deactivate',
                            $budget
                        ) }}"
                        onsubmit="return confirm(
                            'Nonaktifkan anggaran ini?'
                        )"
                    >
                        @csrf
                        @method('PATCH')

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-100 px-4 py-2.5 text-sm font-semibold text-amber-700 transition hover:bg-amber-200"
                        >
                            <i
                                data-lucide="pause-circle"
                                class="size-4"
                            ></i>

                            Nonaktifkan
                        </button>
                    </form>
                @else
                    <form
                        method="POST"
                        action="{{ route(
                            'budgets.activate',
                            $budget
                        ) }}"
                    >
                        @csrf
                        @method('PATCH')

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-100 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-200"
                        >
                            <i
                                data-lucide="play-circle"
                                class="size-4"
                            ></i>

                            Aktifkan
                        </button>
                    </form>
                @endif
            </div>
        </header>

        @if (session('status'))
            <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4">
                @foreach ($errors->all() as $error)
                    <p class="text-sm font-medium text-rose-700">
                        {{ $error }}
                    </p>
                @endforeach
            </div>
        @endif

        <section class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                <p class="text-sm text-slate-500">
                    Batas anggaran
                </p>

                <p class="mt-2 text-xl font-semibold text-slate-950">
                    Rp{{ number_format(
                        (float) $budget->amount,
                        0,
                        ',',
                        '.'
                    ) }}
                </p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                <p class="text-sm text-slate-500">
                    Mulai
                </p>

                <p class="mt-2 text-xl font-semibold text-slate-950">
                    {{ $budget
                        ->start_date
                        ->format('d/m/Y') }}
                </p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                <p class="text-sm text-slate-500">
                    Berakhir
                </p>

                <p class="mt-2 text-xl font-semibold text-slate-950">
                    {{ $budget->end_date
                        ? $budget
                            ->end_date
                            ->format('d/m/Y')
                        : 'Berulang' }}
                </p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                <p class="text-sm text-slate-500">
                    Periode tercatat
                </p>

                <p class="mt-2 text-xl font-semibold text-slate-950">
                    {{ $budget->periods->count() }}
                </p>
            </article>
        </section>

        <section class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-laras">
            <header class="border-b border-slate-100 px-6 py-5">
                <h2 class="font-semibold text-slate-950">
                    Riwayat periode
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Penggunaan dan sisa anggaran pada
                    setiap periode.
                </p>
            </header>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                            <th class="px-6 py-4">
                                Periode
                            </th>

                            <th class="px-6 py-4">
                                Terpakai
                            </th>

                            <th class="px-6 py-4">
                                Sisa
                            </th>

                            <th class="px-6 py-4">
                                Penggunaan
                            </th>

                            <th class="px-6 py-4">
                                Kondisi
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse (
                            $budget->periods
                            as $period
                        )
                            @php
                                $alert =
                                    $periodAlerts[
                                        $period->id
                                    ];
                            @endphp

                            <tr class="text-sm">
                                <td class="whitespace-nowrap px-6 py-4 font-semibold text-slate-800">
                                    {{ $period
                                        ->period_start
                                        ->format('d/m/Y') }}

                                    –

                                    {{ $period
                                        ->period_end
                                        ->format('d/m/Y') }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-slate-600">
                                    Rp{{ number_format(
                                        (float) $period
                                            ->used_amount,
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-slate-600">
                                    Rp{{ number_format(
                                        (float) $period
                                            ->remaining_amount,
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 font-semibold text-slate-800">
                                    {{ $period
                                        ->usage_percent }}%
                                </td>

                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold {{ $alert->colorClass() }}">
                                        {{ $alert->label() }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="5"
                                    class="px-6 py-12 text-center text-sm text-slate-500"
                                >
                                    Belum ada periode anggaran.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
