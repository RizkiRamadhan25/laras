@extends('layouts.app')

@section('title', 'Dashboard — Laras')

@section('page-title', 'Dashboard')

@section(
    'page-description',
    'Ringkasan aktivitas, prioritas, dan kondisi keuanganmu.'
)

@section('content')
    @php
        $formattedTotalBalance = number_format(
            (float) $totalBalance,
            0,
            ',',
            '.'
        );
    @endphp

    <section>
        <div class="flex flex-col justify-between gap-5 xl:flex-row xl:items-end">
            <div>
                <p class="text-sm font-semibold text-laras-700">
                    {{ $currentDate }}
                </p>

                <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">
                    {{ $greeting }},
                    {{ $user->name }}.
                </h1>

                <p class="mt-3 max-w-2xl leading-7 text-slate-500">
                    Berikut gambaran awal harimu. Modul aktivitas, prioritas,
                    transaksi, dan rekomendasi akan segera dihubungkan ke
                    dashboard ini.
                </p>
            </div>

            <button
                type="button"
                disabled
                class="inline-flex cursor-not-allowed items-center justify-center gap-2 rounded-xl bg-laras-700 px-5 py-3 text-sm font-semibold text-white opacity-60"
                title="Fitur akan tersedia pada tahap berikutnya"
            >
                <i data-lucide="plus" class="size-4"></i>
                Tambah cepat
            </button>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                <div class="flex items-start justify-between gap-4">
                    <span class="flex size-11 items-center justify-center rounded-2xl bg-laras-50 text-laras-700">
                        <i data-lucide="wallet-cards" class="size-5"></i>
                    </span>

                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                        Aktif
                    </span>
                </div>

                <p class="mt-5 text-sm font-medium text-slate-500">
                    Total saldo
                </p>

                <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">
                    <span class="text-sm font-medium text-slate-400">
                        {{ $user->preference?->currency_code ?? 'IDR' }}
                    </span>
                    {{ $formattedTotalBalance }}
                </p>

                <p class="mt-2 text-xs text-slate-400">
                    Dari seluruh rekening aktif
                </p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                <div class="flex items-start justify-between gap-4">
                    <span class="flex size-11 items-center justify-center rounded-2xl bg-sky-50 text-sky-700">
                        <i data-lucide="landmark" class="size-5"></i>
                    </span>
                </div>

                <p class="mt-5 text-sm font-medium text-slate-500">
                    Rekening aktif
                </p>

                <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">
                    {{ $accounts->count() }}
                </p>

                <p class="mt-2 text-xs text-slate-400">
                    Bank, dompet digital, dan tunai
                </p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                <div class="flex items-start justify-between gap-4">
                    <span class="flex size-11 items-center justify-center rounded-2xl bg-violet-50 text-violet-700">
                        <i data-lucide="calendar-days" class="size-5"></i>
                    </span>

                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">
                        Segera
                    </span>
                </div>

                <p class="mt-5 text-sm font-medium text-slate-500">
                    Aktivitas hari ini
                </p>

                <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">
                    0
                </p>

                <p class="mt-2 text-xs text-slate-400">
                    Belum ada agenda yang dicatat
                </p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                <div class="flex items-start justify-between gap-4">
                    <span class="flex size-11 items-center justify-center rounded-2xl bg-amber-50 text-amber-700">
                        <i data-lucide="sparkles" class="size-5"></i>
                    </span>

                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">
                        Segera
                    </span>
                </div>

                <p class="mt-5 text-sm font-medium text-slate-500">
                    Rekomendasi Laras
                </p>

                <p class="mt-2 text-lg font-semibold tracking-tight text-slate-950">
                    Belum tersedia
                </p>

                <p class="mt-2 text-xs text-slate-400">
                    Akan muncul setelah pola penggunaan terbentuk
                </p>
            </article>
        </div>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1.45fr_0.75fr]">
        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-laras">
            <header class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-5 sm:px-6">
                <div>
                    <h2 class="font-semibold text-slate-950">
                        Ringkasan rekening
                    </h2>

                    <p class="mt-1 text-sm text-slate-400">
                        Saldo terkini dari akun yang aktif.
                    </p>
                </div>

                <button
                    type="button"
                    disabled
                    class="text-sm font-semibold text-slate-400"
                >
                    Lihat semua
                </button>
            </header>

            @if ($accounts->isEmpty())
                <div class="px-6 py-12 text-center">
                    <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                        <i data-lucide="wallet" class="size-6"></i>
                    </span>

                    <h3 class="mt-4 font-semibold text-slate-900">
                        Belum ada rekening
                    </h3>

                    <p class="mt-2 text-sm text-slate-500">
                        Tambahkan rekening agar saldo dapat ditampilkan.
                    </p>
                </div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach ($accounts->take(6) as $account)
                        @php
                            $formattedAccountBalance = number_format(
                                (float) $account->cached_balance,
                                0,
                                ',',
                                '.'
                            );
                        @endphp

                        <div class="flex items-center gap-4 px-5 py-4 sm:px-6">
                            <span
                                class="size-3 shrink-0 rounded-full"
                                style="background-color: {{ $account->color ?? '#2563EB' }}"
                            ></span>

                            <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-600">
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
                                    {{ $account->institution ?? $account->type->label() }}

                                    @if ($account->account_number_last_four)
                                        •••• {{ $account->account_number_last_four }}
                                    @endif
                                </p>
                            </div>

                            <div class="shrink-0 text-right">
                                <p class="text-sm font-semibold text-slate-900">
                                    {{ $account->currency_code }}
                                    {{ $formattedAccountBalance }}
                                </p>

                                <p class="mt-1 text-xs text-emerald-600">
                                    Aktif
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </article>

        <article class="rounded-2xl border border-slate-200 bg-laras-950 p-6 text-white shadow-laras">
            <div class="flex items-center justify-between gap-4">
                <span class="flex size-11 items-center justify-center rounded-2xl bg-white/10 text-laras-200">
                    <i data-lucide="lightbulb" class="size-5"></i>
                </span>

                <span class="rounded-full bg-white/10 px-2.5 py-1 text-xs font-semibold text-laras-100">
                    Insight
                </span>
            </div>

            <h2 class="mt-8 text-2xl font-semibold tracking-tight">
                Bangun ritme yang selaras.
            </h2>

            <p class="mt-4 leading-7 text-laras-100">
                Laras akan menghubungkan jadwal, prioritas, dan kondisi
                keuangan untuk membantumu menentukan langkah berikutnya.
            </p>

            <div class="mt-8 rounded-2xl border border-white/10 bg-white/5 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-laras-200">
                    Tahap berikutnya
                </p>

                <p class="mt-2 text-sm leading-6 text-white">
                    Mengaktifkan halaman rekening dan pengelolaan akun
                    keuangan.
                </p>
            </div>
        </article>
    </section>
@endsection
