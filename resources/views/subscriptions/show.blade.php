@extends('layouts.app')

@section('title', 'Detail Langganan — Laras')
@section('page-title', 'Detail langganan')
@section(
    'page-description',
    'Jadwal, biaya, dan riwayat tagihan langganan.'
)

@section('content')
    @php
        $timezone = $user->preference?->timezone
            ?? config(
                'laras.defaults.timezone',
                'Asia/Jakarta'
            );

        $statusClass = match (
            $subscription->status
        ) {
            \App\Enums\SubscriptionStatus::Active =>
                'bg-emerald-100 text-emerald-700',

            \App\Enums\SubscriptionStatus::Paused =>
                'bg-amber-100 text-amber-700',

            \App\Enums\SubscriptionStatus::Cancelled =>
                'bg-slate-100 text-slate-600',

            \App\Enums\SubscriptionStatus::Expired =>
                'bg-rose-100 text-rose-700',
        };
    @endphp

    <div class="mx-auto max-w-6xl">
        <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-start">
            <div>
                <a
                    href="{{ route(
                        'subscriptions.index'
                    ) }}"
                    class="text-sm font-semibold text-laras-700 hover:text-laras-900"
                >
                    ← Kembali ke langganan
                </a>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <h1 class="text-3xl font-semibold tracking-tight">
                        {{ $subscription->name }}
                    </h1>

                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">
                        {{ $subscription->status->label() }}
                    </span>
                </div>

                <p class="mt-2 text-slate-500">
                    {{ $subscription->provider
                        ?? $subscription
                            ->financeCategory?->name }}
                    ·
                    {{ $subscription->recurringLabel() }}
                </p>
            </div>

            @if (
                in_array(
                    $subscription->status,
                    [
                        \App\Enums\SubscriptionStatus::Active,
                        \App\Enums\SubscriptionStatus::Paused,
                    ],
                    true
                )
            )
                <a
                    href="{{ route(
                        'subscriptions.edit',
                        $subscription->id
                    ) }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                >
                    <i
                        data-lucide="pencil"
                        class="size-4"
                    ></i>

                    Edit langganan
                </a>
            @endif
        </div>

        <section class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                <p class="text-sm text-slate-500">
                    Nominal per tagihan
                </p>

                <p class="mt-2 text-xl font-semibold">
                    {{ $subscription->currency_code }}
                    {{ number_format(
                        (float) $subscription->amount,
                        0,
                        ',',
                        '.'
                    ) }}
                </p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                <p class="text-sm text-slate-500">
                    Tagihan berikutnya
                </p>

                <p class="mt-2 text-xl font-semibold">
                    {{ $subscription->next_billing_on
                        ?->translatedFormat(
                            'd M Y'
                        ) ?? 'Tidak ada' }}
                </p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                <p class="text-sm text-slate-500">
                    Total berhasil dibayar
                </p>

                <p class="mt-2 text-xl font-semibold text-emerald-700">
                    {{ $subscription->currency_code }}
                    {{ number_format(
                        (float) $billingSummary[
                            'total_paid'
                        ],
                        0,
                        ',',
                        '.'
                    ) }}
                </p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                <p class="text-sm text-slate-500">
                    Pencatatan
                </p>

                <p class="mt-2 text-xl font-semibold">
                    {{ $subscription->auto_post
                        ? 'Otomatis'
                        : 'Manual' }}
                </p>
            </article>
        </section>

        <section class="mt-6 grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-laras">
                <h2 class="font-semibold text-slate-950">
                    Informasi pembayaran
                </h2>

                <dl class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm text-slate-400">
                            Rekening
                        </dt>

                        <dd class="mt-1 font-semibold">
                            {{ $subscription->account?->name }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm text-slate-400">
                            Kategori
                        </dt>

                        <dd class="mt-1 font-semibold">
                            {{ $subscription
                                ->financeCategory?->name }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm text-slate-400">
                            Tanggal mulai
                        </dt>

                        <dd class="mt-1 font-semibold">
                            {{ $subscription
                                ->started_on
                                ->translatedFormat(
                                    'd F Y'
                                ) }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm text-slate-400">
                            Waktu pemrosesan
                        </dt>

                        <dd class="mt-1 font-semibold">
                            {{ substr(
                                (string) $subscription
                                    ->billing_time,
                                0,
                                5
                            ) }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm text-slate-400">
                            Tanggal berakhir
                        </dt>

                        <dd class="mt-1 font-semibold">
                            {{ $subscription->end_on
                                ?->translatedFormat(
                                    'd F Y'
                                ) ?? 'Tidak ditentukan' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm text-slate-400">
                            Pengingat
                        </dt>

                        <dd class="mt-1 font-semibold">
                            {{ collect(
                                $subscription
                                    ->reminder_days
                                ?? []
                            )
                                ->map(
                                    fn ($day) =>
                                        $day === 0
                                            ? 'Hari H'
                                            : $day.' hari'
                                )
                                ->join(', ') }}
                        </dd>
                    </div>
                </dl>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-slate-950 p-6 text-white shadow-laras">
                <h2 class="font-semibold">
                    Ringkasan billing
                </h2>

                <div class="mt-6 grid grid-cols-2 gap-4">
                    <div class="rounded-2xl bg-white/10 p-4">
                        <p class="text-xs text-slate-300">
                            Seluruh billing
                        </p>

                        <p class="mt-2 text-2xl font-semibold">
                            {{ $billingSummary['total'] }}
                        </p>
                    </div>

                    <div class="rounded-2xl bg-white/10 p-4">
                        <p class="text-xs text-slate-300">
                            Berhasil
                        </p>

                        <p class="mt-2 text-2xl font-semibold text-emerald-300">
                            {{ $billingSummary['posted'] }}
                        </p>
                    </div>

                    <div class="rounded-2xl bg-white/10 p-4">
                        <p class="text-xs text-slate-300">
                            Gagal
                        </p>

                        <p class="mt-2 text-2xl font-semibold text-rose-300">
                            {{ $billingSummary['failed'] }}
                        </p>
                    </div>

                    <div class="rounded-2xl bg-white/10 p-4">
                        <p class="text-xs text-slate-300">
                            Terjadwal
                        </p>

                        <p class="mt-2 text-2xl font-semibold text-amber-300">
                            {{ $billingSummary['scheduled'] }}
                        </p>
                    </div>
                </div>
            </article>
        </section>

        <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-laras">
            <header class="border-b border-slate-100 px-5 py-5 sm:px-6">
                <h2 class="font-semibold text-slate-950">
                    Riwayat billing
                </h2>

                <p class="mt-1 text-sm text-slate-400">
                    Jadwal dan hasil pemrosesan setiap siklus langganan.
                </p>
            </header>

            @if ($billings->isEmpty())
                <div class="px-6 py-14 text-center">
                    <i
                        data-lucide="receipt-text"
                        class="mx-auto size-8 text-slate-400"
                    ></i>

                    <p class="mt-4 font-semibold text-slate-800">
                        Belum ada billing
                    </p>
                </div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach ($billings as $billing)
                        @php
                            $billingStatusClass = match (
                                $billing->status
                            ) {
                                \App\Enums\SubscriptionBillingStatus::Scheduled =>
                                    'bg-blue-100 text-blue-700',

                                \App\Enums\SubscriptionBillingStatus::Processing =>
                                    'bg-amber-100 text-amber-700',

                                \App\Enums\SubscriptionBillingStatus::Posted =>
                                    'bg-emerald-100 text-emerald-700',

                                \App\Enums\SubscriptionBillingStatus::Failed =>
                                    'bg-rose-100 text-rose-700',

                                \App\Enums\SubscriptionBillingStatus::Skipped,
                                \App\Enums\SubscriptionBillingStatus::Cancelled =>
                                    'bg-slate-100 text-slate-600',
                            };
                        @endphp

                        <article class="px-5 py-5 sm:px-6">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a
                                            href="{{ route(
                                                'subscriptions.billings.show',
                                                [
                                                    'subscription' =>
                                                        $subscription->id,

                                                    'billing' =>
                                                        $billing->id,
                                                ]
                                            ) }}"
                                            class="font-semibold text-slate-900 hover:text-laras-700"
                                        >
                                            {{ $billing
                                                ->scheduled_for
                                                ->translatedFormat(
                                                    'd F Y'
                                                ) }}
                                        </a>

                                        <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $billingStatusClass }}">
                                            {{ $billing->status->label() }}
                                        </span>
                                    </div>

                                    @if ($billing->failure_reason)
                                        <p class="mt-2 line-clamp-2 text-sm text-rose-600">
                                            {{ $billing->failure_reason }}
                                        </p>
                                    @elseif ($billing->transaction)
                                        <p class="mt-2 text-sm text-slate-500">
                                            Transaksi
                                            #{{ $billing
                                                ->transaction
                                                ->id }}
                                        </p>
                                    @else
                                        <p class="mt-2 text-sm text-slate-400">
                                            Menunggu waktu pemrosesan.
                                        </p>
                                    @endif
                                </div>

                                <p class="shrink-0 font-semibold text-slate-900">
                                    {{ $billing->currency_code }}
                                    {{ number_format(
                                        (float) $billing->amount,
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </p>

                                <div class="flex shrink-0 flex-wrap gap-2">
                                    <a
                                        href="{{ route(
                                            'subscriptions.billings.show',
                                            [
                                                'subscription' =>
                                                    $subscription->id,

                                                'billing' =>
                                                    $billing->id,
                                            ]
                                        ) }}"
                                        class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                                    >
                                        Detail
                                    </a>

                                    @if (
                                        $billing->status
                                        === \App\Enums\SubscriptionBillingStatus::Failed
                                        && $subscription->status
                                        === \App\Enums\SubscriptionStatus::Active
                                        && $subscription->auto_post
                                    )
                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'subscriptions.billings.retry',
                                                [
                                                    'subscription' =>
                                                        $subscription->id,

                                                    'billing' =>
                                                        $billing->id,
                                                ]
                                            ) }}"
                                            data-confirm
                                            data-confirm-title="Proses ulang tagihan?"
                                            data-confirm-message="Sistem akan mencoba membuat transaksi kembali. Saldo rekening akan berkurang jika proses berhasil."
                                            data-confirm-label="Proses ulang"
                                            data-confirm-busy-label="Memproses..."
                                            data-confirm-tone="warning"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700"
                                            >
                                                <i
                                                    data-lucide="rotate-ccw"
                                                    class="size-4"
                                                ></i>

                                                Coba lagi
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="border-t border-slate-100 px-5 py-4 sm:px-6">
                    {{ $billings->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
