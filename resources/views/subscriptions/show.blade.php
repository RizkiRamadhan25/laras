@extends('layouts.app')

@section('title', 'Detail Langganan — Laras')
@section('page-title', 'Detail langganan')
@section(
    'page-description',
    'Jadwal, biaya, dan pengaturan langganan.'
)

@section('content')
    @php
        $timezone = $user->preference?->timezone
            ?? config(
                'laras.defaults.timezone',
                'Asia/Jakarta'
            );
    @endphp

    <div class="mx-auto max-w-5xl">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-start">
            <div>
                <p class="text-sm font-semibold text-laras-700">
                    {{ $subscription->provider
                        ?? 'Langganan' }}
                </p>

                <h1 class="mt-2 text-3xl font-semibold tracking-tight">
                    {{ $subscription->name }}
                </h1>

                <p class="mt-2 text-slate-500">
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
                    class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700"
                >
                    Edit langganan
                </a>
            @endif
        </div>

        <section class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                <p class="text-sm text-slate-500">
                    Nominal
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
                    Status
                </p>

                <p class="mt-2 text-xl font-semibold">
                    {{ $subscription->status->label() }}
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
                    Pencatatan
                </p>

                <p class="mt-2 text-xl font-semibold">
                    {{ $subscription->auto_post
                        ? 'Otomatis'
                        : 'Manual' }}
                </p>
            </article>
        </section>

        <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-laras">
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
                            $subscription->billing_time,
                            0,
                            5
                        ) }}
                    </dd>
                </div>
            </dl>
        </section>

        <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-laras">
            <h2 class="font-semibold text-slate-950">
                Riwayat billing
            </h2>

            @if ($subscription->billings->isEmpty())
                <p class="mt-4 text-sm text-slate-500">
                    Belum ada billing langganan.
                </p>
            @else
                <div class="mt-5 divide-y divide-slate-100">
                    @foreach (
                        $subscription->billings->take(5)
                        as $billing
                    )
                        <div class="flex items-center justify-between gap-4 py-4">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">
                                    {{ $billing
                                        ->scheduled_for
                                        ->translatedFormat(
                                            'd F Y'
                                        ) }}
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    {{ $billing->status->label() }}
                                </p>
                            </div>

                            <p class="text-sm font-semibold">
                                {{ $billing->currency_code }}
                                {{ number_format(
                                    (float) $billing->amount,
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </p>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection
