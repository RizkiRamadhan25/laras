@extends('layouts.app')

@section('title', 'Detail Transaksi — Laras')
@section('page-title', 'Detail transaksi')
@section(
    'page-description',
    'Lihat informasi dan ledger transaksi.'
)

@section('content')
    @php
        $currencyCode = $user->preference?->currency_code
            ?? 'IDR';

        $timezone = $user->preference?->timezone
            ?? config('laras.defaults.timezone');

        $statusClass = match ($transaction->status) {
            \App\Enums\TransactionStatus::Posted =>
                'bg-emerald-50 text-emerald-700',
            \App\Enums\TransactionStatus::Cancelled =>
                'bg-slate-100 text-slate-600',
            \App\Enums\TransactionStatus::Draft =>
                'bg-amber-50 text-amber-700',
            \App\Enums\TransactionStatus::Pending =>
                'bg-blue-50 text-blue-700',
            default =>
                'bg-rose-50 text-rose-700',
        };
    @endphp

    <div class="mx-auto max-w-5xl">
        <div class="mb-6">
            <a
                href="{{ route('transactions.index') }}"
                class="text-sm font-semibold text-laras-700 hover:text-laras-900"
            >
                ← Kembali ke riwayat
            </a>
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-laras">
            <header class="border-b border-slate-100 p-6 sm:p-8">
                <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-start">
                    <div>
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="rounded-full bg-laras-50 px-3 py-1 text-xs font-semibold text-laras-700">
                                {{ $transaction->type->label() }}
                            </span>

                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">
                                {{ $transaction->status->label() }}
                            </span>
                        </div>

                        <h1 class="mt-4 text-2xl font-semibold tracking-tight sm:text-3xl">
                            {{ $transaction->description
                                ?? $transaction->type->label() }}
                        </h1>

                        <p class="mt-2 text-sm text-slate-400">
                            Transaksi #{{ $transaction->id }}
                        </p>
                    </div>

                    <div class="sm:text-right">
                        <p class="text-sm text-slate-400">
                            Nominal utama
                        </p>

                        <p class="mt-1 text-2xl font-semibold text-slate-950">
                            {{ $currencyCode }}
                            {{ number_format(
                                (float) $transaction->displayAmount(),
                                0,
                                ',',
                                '.'
                            ) }}
                        </p>
                    </div>
                </div>
            </header>

            <div class="grid gap-8 p-6 sm:p-8 lg:grid-cols-[0.8fr_1.2fr]">
                <section>
                    <h2 class="font-semibold text-slate-900">
                        Informasi transaksi
                    </h2>

                    <dl class="mt-5 space-y-4">
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                Tanggal dan waktu
                            </dt>

                            <dd class="mt-1 text-sm font-medium text-slate-800">
                                {{ $transaction->occurred_at
                                    ->timezone($timezone)
                                    ->translatedFormat(
                                        'l, d F Y H:i'
                                    ) }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                Pihak terkait
                            </dt>

                            <dd class="mt-1 text-sm font-medium text-slate-800">
                                {{ $transaction->counterparty ?? '—' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                Nomor referensi
                            </dt>

                            <dd class="mt-1 break-all text-sm font-medium text-slate-800">
                                {{ $transaction->reference_number ?? '—' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                Sumber pencatatan
                            </dt>

                            <dd class="mt-1 text-sm font-medium text-slate-800">
                                {{ $transaction->source->label() }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                Catatan
                            </dt>

                            <dd class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">
                                {{ $transaction->notes ?? '—' }}
                            </dd>
                        </div>
                    </dl>
                </section>

                <section>
                    <h2 class="font-semibold text-slate-900">
                        Ledger transaksi
                    </h2>

                    <p class="mt-1 text-sm text-slate-400">
                        Nilai positif menambah saldo, nilai negatif mengurangi saldo.
                    </p>

                    <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200">
                        <div class="divide-y divide-slate-100">
                            @foreach ($transaction->entries as $entry)
                                @php
                                    $positive = bccomp(
                                        $entry->amount,
                                        '0.00',
                                        2
                                    ) >= 0;
                                @endphp

                                <div class="flex items-center gap-4 p-4">
                                    <span
                                        class="size-3 shrink-0 rounded-full"
                                        style="
                                            background-color:
                                            {{ $entry->account?->color
                                                ?? '#64748B' }}
                                        "
                                    ></span>

                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-slate-900">
                                            {{ $entry->account?->name
                                                ?? 'Rekening tidak tersedia' }}
                                        </p>

                                        <p class="mt-1 truncate text-xs text-slate-400">
                                            {{ $entry->role->label() }}

                                            @if ($entry->financeCategory)
                                                •
                                                {{ $entry->financeCategory->name }}
                                            @endif

                                            @if ($entry->memo)
                                                • {{ $entry->memo }}
                                            @endif
                                        </p>
                                    </div>

                                    <p
                                        @class([
                                            'shrink-0 text-sm font-semibold',
                                            'text-emerald-700' => $positive,
                                            'text-rose-700' => ! $positive,
                                        ])
                                    >
                                        {{ $positive ? '+' : '' }}
                                        {{ $currencyCode }}
                                        {{ number_format(
                                            (float) $entry->amount,
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            </div>

            @if (
                $transaction->status
                === \App\Enums\TransactionStatus::Cancelled
            )
                <div class="border-t border-slate-100 bg-slate-50 px-6 py-5 sm:px-8">
                    <p class="text-sm font-semibold text-slate-700">
                        Transaksi dibatalkan
                    </p>

                    <p class="mt-1 text-sm text-slate-500">
                        {{ $transaction->metadata['cancellation_reason']
                            ?? 'Tidak ada alasan pembatalan.' }}
                    </p>
                </div>
            @elseif (
                $transaction->status
                === \App\Enums\TransactionStatus::Posted
            )
                <div
                    x-data="{ cancelOpen: false }"
                    class="border-t border-slate-100 px-6 py-5 sm:px-8"
                >
                    <button
                        type="button"
                        x-on:click="cancelOpen = ! cancelOpen"
                        class="inline-flex items-center gap-2 rounded-xl border border-rose-200 px-4 py-2.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-50"
                    >
                        <i data-lucide="ban" class="size-4"></i>
                        Batalkan transaksi
                    </button>

                    <form
                        x-cloak
                        x-show="cancelOpen"
                        x-transition
                        method="POST"
                        action="{{ route(
                            'transactions.cancel',
                            $transaction->id
                        ) }}"
                        class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 p-5"
                        data-confirm
                        data-confirm-title="Batalkan transaksi?"
                        data-confirm-message="Perubahan saldo akan dikembalikan dan transaksi tetap tersimpan sebagai riwayat pembatalan."
                        data-confirm-label="Batalkan transaksi"
                        data-confirm-busy-label="Membatalkan..."
                        data-confirm-tone="danger"
                    >
                        @csrf
                        @method('PATCH')

                        <label
                            for="reason"
                            class="mb-2 block text-sm font-medium text-rose-900"
                        >
                            Alasan pembatalan
                            <span class="font-normal text-rose-500">
                                (opsional)
                            </span>
                        </label>

                        <textarea
                            id="reason"
                            name="reason"
                            rows="3"
                            maxlength="500"
                            class="w-full rounded-xl border border-rose-200 bg-white px-4 py-3 text-sm outline-none focus:border-rose-500 focus:ring-4 focus:ring-rose-100"
                            placeholder="Contoh: Salah memilih rekening"
                        >{{ old('reason') }}</textarea>

                        <div class="mt-4 flex flex-wrap gap-3">
                            <button
                                type="submit"
                                class="rounded-xl bg-rose-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-800"
                            >
                                Konfirmasi pembatalan
                            </button>

                            <button
                                type="button"
                                x-on:click="cancelOpen = false"
                                class="rounded-xl border border-rose-200 px-5 py-2.5 text-sm font-semibold text-rose-700"
                            >
                                Kembali
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </section>
    </div>
@endsection
