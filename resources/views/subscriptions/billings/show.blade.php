@extends('layouts.app')

@section('title', 'Detail Billing — Laras')
@section('page-title', 'Detail billing')
@section(
    'page-description',
    'Informasi pemrosesan tagihan langganan.'
)

@section('content')
    @php
        $timezone = $user->preference?->timezone
            ?? config(
                'laras.defaults.timezone',
                'Asia/Jakarta'
            );

        $statusClass = match (
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

        $attemptCount = (int) (
            $billing->metadata[
                'attempt_count'
            ] ?? 0
        );

        $remindersSent =
            $billing->metadata[
                'reminders_sent'
            ] ?? [];

        $canRetry =
            $billing->status
                === \App\Enums\SubscriptionBillingStatus::Failed
            && $subscription->status
                === \App\Enums\SubscriptionStatus::Active
            && $subscription->auto_post;
    @endphp

    <div class="mx-auto max-w-5xl">
        <a
            href="{{ route(
                'subscriptions.show',
                $subscription->id
            ) }}"
            class="text-sm font-semibold text-laras-700 hover:text-laras-900"
        >
            ← Kembali ke {{ $subscription->name }}
        </a>

        <section class="mt-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-laras sm:p-8">
            <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-start">
                <div>
                    <p class="text-sm font-semibold text-laras-700">
                        {{ $subscription->name }}
                    </p>

                    <div class="mt-2 flex flex-wrap items-center gap-3">
                        <h1 class="text-3xl font-semibold tracking-tight">
                            Billing
                            {{ $billing
                                ->scheduled_for
                                ->translatedFormat(
                                    'd F Y'
                                ) }}
                        </h1>

                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">
                            {{ $billing->status->label() }}
                        </span>
                    </div>
                </div>

                @if ($canRetry)
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
                        onsubmit="return confirm(
                            'Coba proses kembali tagihan ini? Saldo akan berkurang jika berhasil.'
                        )"
                    >
                        @csrf
                        @method('PATCH')

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-rose-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-rose-700"
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

            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <article class="rounded-2xl bg-slate-50 p-5">
                    <p class="text-sm text-slate-400">
                        Nominal
                    </p>

                    <p class="mt-2 text-xl font-semibold">
                        {{ $billing->currency_code }}
                        {{ number_format(
                            (float) $billing->amount,
                            0,
                            ',',
                            '.'
                        ) }}
                    </p>
                </article>

                <article class="rounded-2xl bg-slate-50 p-5">
                    <p class="text-sm text-slate-400">
                        Tanggal tagihan
                    </p>

                    <p class="mt-2 text-xl font-semibold">
                        {{ $billing
                            ->scheduled_for
                            ->translatedFormat(
                                'd M Y'
                            ) }}
                    </p>
                </article>

                <article class="rounded-2xl bg-slate-50 p-5">
                    <p class="text-sm text-slate-400">
                        Jumlah percobaan
                    </p>

                    <p class="mt-2 text-xl font-semibold">
                        {{ $attemptCount }}
                    </p>
                </article>

                <article class="rounded-2xl bg-slate-50 p-5">
                    <p class="text-sm text-slate-400">
                        Rekening
                    </p>

                    <p class="mt-2 truncate text-xl font-semibold">
                        {{ $subscription->account?->name }}
                    </p>
                </article>
            </div>

            @if ($billing->failure_reason)
                <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 p-5">
                    <div class="flex items-start gap-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-700">
                            <i
                                data-lucide="circle-alert"
                                class="size-5"
                            ></i>
                        </span>

                        <div>
                            <h2 class="font-semibold text-rose-900">
                                Penyebab kegagalan
                            </h2>

                            <p class="mt-2 text-sm leading-6 text-rose-700">
                                {{ $billing->failure_reason }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </section>

        <section class="mt-6 grid gap-6 lg:grid-cols-2">
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-laras">
                <h2 class="font-semibold text-slate-950">
                    Informasi pemrosesan
                </h2>

                <dl class="mt-5 space-y-5">
                    <div>
                        <dt class="text-sm text-slate-400">
                            Percobaan terakhir
                        </dt>

                        <dd class="mt-1 font-semibold">
                            {{ $billing->attempted_at
                                ?->timezone($timezone)
                                ->translatedFormat(
                                    'd F Y, H:i'
                                ) ?? 'Belum pernah dicoba' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm text-slate-400">
                            Berhasil diproses
                        </dt>

                        <dd class="mt-1 font-semibold">
                            {{ $billing->processed_at
                                ?->timezone($timezone)
                                ->translatedFormat(
                                    'd F Y, H:i'
                                ) ?? 'Belum berhasil' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm text-slate-400">
                            Pengingat terkirim
                        </dt>

                        <dd class="mt-1 font-semibold">
                            {{ collect($remindersSent)
                                ->map(
                                    fn ($day) =>
                                        ((int) $day) === 0
                                            ? 'Hari H'
                                            : $day.' hari sebelumnya'
                                )
                                ->join(', ')
                                ?: 'Belum ada' }}
                        </dd>
                    </div>
                </dl>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-laras">
                <h2 class="font-semibold text-slate-950">
                    Transaksi terkait
                </h2>

                @if ($billing->transaction)
                    <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                        <p class="text-sm text-emerald-700">
                            Billing telah dicatat sebagai transaksi.
                        </p>

                        <p class="mt-2 font-semibold text-emerald-950">
                            Transaksi
                            #{{ $billing->transaction->id }}
                        </p>

                        <p class="mt-1 text-sm text-emerald-700">
                            {{ $billing->transaction
                                ->description }}
                        </p>

                        <a
                            href="{{ route(
                                'transactions.show',
                                $billing
                                    ->transaction
                                    ->id
                            ) }}"
                            class="mt-5 inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white"
                        >
                            <i
                                data-lucide="receipt-text"
                                class="size-4"
                            ></i>

                            Buka transaksi
                        </a>
                    </div>
                @else
                    <div class="mt-5 rounded-2xl bg-slate-50 p-5">
                        <p class="text-sm leading-6 text-slate-500">
                            Belum ada transaksi yang terhubung dengan billing ini.
                        </p>
                    </div>
                @endif
            </article>
        </section>
    </div>
@endsection
