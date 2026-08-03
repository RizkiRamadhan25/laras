@extends('layouts.app')

@section('title', 'Riwayat Rekomendasi — Laras')
@section('page-title', 'Riwayat rekomendasi')
@section(
    'page-description',
    'Riwayat pembukaan dan feedback rekomendasi personal.'
)

@section('content')
    @php
        $timezone = $user->preference
            ?->timezone
            ?? config(
                'laras.defaults.timezone',
                'Asia/Jakarta'
            );
    @endphp

    <div
        class="mx-auto max-w-5xl"
        data-deletion-manager
        data-dialog-id="recommendation-history-deletion-dialog"
        data-preview-url="{{ route(
            'recommendations.history.deletion-preview'
        ) }}"
        data-purge-url="{{ route(
            'recommendations.history.purge'
        ) }}"
        data-id-field="interaction_ids"
        data-resource-label="riwayat"
    >
        <header class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div>
                <a
                    href="{{ route(
                        'recommendations.index'
                    ) }}"
                    class="text-sm font-semibold text-laras-700 hover:text-laras-900 focus:outline-none focus:ring-2 focus:ring-laras-500 focus:ring-offset-2"
                >
                    ← Kembali ke rekomendasi
                </a>

                <h1 class="mt-4 text-3xl font-semibold tracking-tight">
                    Riwayat interaksi
                </h1>

                <p class="mt-3 max-w-2xl leading-7 text-slate-500">
                    Catatan rekomendasi yang pernah dibuka,
                    ditindaklanjuti, ditunda, atau dianggap
                    tidak relevan.
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-laras">
                <p class="text-sm text-slate-500">
                    Total interaksi
                </p>

                <p class="mt-1 text-2xl font-semibold text-slate-950">
                    {{ $interactions->total() }}
                </p>
            </div>
        </header>

        @if ($interactions->total() > 0)
            <section class="mt-7 rounded-2xl border border-slate-200 bg-white p-4 shadow-laras sm:p-5">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <label class="inline-flex cursor-pointer items-center gap-3 text-sm font-semibold text-slate-700">
                        <input
                            type="checkbox"
                            data-deletion-select-page
                            class="size-4 rounded border-slate-300 text-laras-700 focus:ring-laras-500"
                        >

                        Pilih semua di halaman ini
                    </label>

                    <p
                        data-deletion-selection-summary
                        class="text-sm text-slate-500"
                        aria-live="polite"
                    >
                        Belum ada data dipilih
                    </p>

                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            data-deletion-trigger
                            data-deletion-selected-button
                            data-scope="selected"
                            data-title="Hapus riwayat rekomendasi terpilih?"
                            data-description="Riwayat terpilih akan dihapus permanen dan tidak lagi digunakan untuk personalisasi."
                            disabled
                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-rose-200 bg-white px-3.5 py-2.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            <i
                                data-lucide="trash-2"
                                class="size-4"
                            ></i>

                            Hapus terpilih
                        </button>

                        <button
                            type="button"
                            data-deletion-trigger
                            data-scope="older"
                            data-older-than-days="180"
                            data-title="Hapus riwayat lebih lama dari 180 hari?"
                            data-description="Riwayat yang lebih baru tetap disimpan untuk personalisasi rekomendasi."
                            class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-laras-500 focus:ring-offset-2"
                        >
                            Hapus lebih dari 180 hari
                        </button>

                        <button
                            type="button"
                            data-deletion-trigger
                            data-scope="all"
                            data-title="Hapus seluruh riwayat rekomendasi?"
                            data-description="Seluruh feedback dan catatan interaksi rekomendasi akan dihapus permanen."
                            class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-3.5 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2"
                        >
                            Hapus semua
                        </button>
                    </div>
                </div>
            </section>
        @endif

        <section class="mt-7 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-laras">
            @if ($interactions->isEmpty())
                <div class="px-6 py-16 text-center">
                    <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                        <i
                            data-lucide="history"
                            class="size-6"
                        ></i>
                    </span>

                    <h2 class="mt-4 font-semibold text-slate-900">
                        Belum ada interaksi
                    </h2>

                    <p class="mt-2 text-sm text-slate-500">
                        Interaksi rekomendasi akan tampil di sini.
                    </p>
                </div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach (
                        $interactions
                        as $interaction
                    )
                        @php
                            $typeStyle = match (
                                $interaction
                                    ->interaction_type
                            ) {
                                \App\Enums\RecommendationInteractionType::Opened => [
                                    'icon' =>
                                        'eye',

                                    'class' =>
                                        'bg-blue-100 text-blue-700',
                                ],

                                \App\Enums\RecommendationInteractionType::FollowedUp => [
                                    'icon' =>
                                        'circle-check',

                                    'class' =>
                                        'bg-emerald-100 text-emerald-700',
                                ],

                                \App\Enums\RecommendationInteractionType::Dismissed => [
                                    'icon' =>
                                        'clock',

                                    'class' =>
                                        'bg-amber-100 text-amber-700',
                                ],

                                \App\Enums\RecommendationInteractionType::Irrelevant => [
                                    'icon' =>
                                        'ban',

                                    'class' =>
                                        'bg-rose-100 text-rose-700',
                                ],
                            };

                            $snapshot =
                                $interaction
                                    ->snapshot
                                ?? [];
                        @endphp

                        <article class="px-5 py-5 sm:px-6">
                            <div class="flex items-start gap-3 sm:gap-4">
                                <label class="mt-3 inline-flex shrink-0 cursor-pointer items-center">
                                    <span class="sr-only">
                                        Pilih riwayat
                                        {{ $interaction->title }}
                                    </span>

                                    <input
                                        type="checkbox"
                                        value="{{ $interaction->id }}"
                                        data-deletion-checkbox
                                        class="size-4 rounded border-slate-300 text-laras-700 focus:ring-laras-500"
                                    >
                                </label>

                                <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl {{ $typeStyle['class'] }}">
                                    <i
                                        data-lucide="{{ $typeStyle['icon'] }}"
                                        class="size-5"
                                    ></i>
                                </span>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="font-semibold text-slate-950">
                                            {{ $interaction->title }}
                                        </h2>

                                        <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $typeStyle['class'] }}">
                                            {{ $interaction
                                                ->interaction_type
                                                ->label() }}
                                        </span>
                                    </div>

                                    @if (
                                        filled(
                                            $snapshot[
                                                'message'
                                            ] ?? null
                                        )
                                    )
                                        <p class="mt-2 text-sm leading-6 text-slate-600">
                                            {{ $snapshot[
                                                'message'
                                            ] }}
                                        </p>
                                    @endif

                                    <div class="mt-3 flex flex-wrap gap-x-4 gap-y-2 text-xs text-slate-400">
                                        <span>
                                            {{ $interaction
                                                ->occurred_at
                                                ->setTimezone(
                                                    $timezone
                                                )
                                                ->locale('id')
                                                ->translatedFormat(
                                                    'd F Y, H:i'
                                                ) }}
                                        </span>

                                        <span>
                                            {{ $interaction
                                                ->occurred_at
                                                ->locale('id')
                                                ->diffForHumans() }}
                                        </span>

                                        <span>
                                            {{ $interaction
                                                ->interaction_type
                                                ->description() }}
                                        </span>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    data-deletion-trigger
                                    data-scope="selected"
                                    data-identifier="{{ $interaction->id }}"
                                    data-purge-url="{{ route(
                                        'recommendations.history.destroy',
                                        $interaction->id
                                    ) }}"
                                    data-title="Hapus riwayat rekomendasi ini?"
                                    data-description="Riwayat ini akan dihapus permanen dan tidak lagi digunakan untuk personalisasi."
                                    aria-label="Hapus riwayat {{ $interaction->title }}"
                                    class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl border border-rose-200 text-rose-700 transition hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2"
                                >
                                    <i
                                        data-lucide="trash-2"
                                        class="size-4"
                                    ></i>
                                </button>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="border-t border-slate-100 px-5 py-4 sm:px-6">
                    {{ $interactions->links() }}
                </div>
            @endif
        </section>
    </div>

    <x-data-deletion-dialog id="recommendation-history-deletion-dialog" />
@endsection
