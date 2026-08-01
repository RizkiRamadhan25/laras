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

    <div class="mx-auto max-w-5xl">
        <header class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div>
                <a
                    href="{{ route(
                        'recommendations.index'
                    ) }}"
                    class="text-sm font-semibold text-laras-700 hover:text-laras-900"
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
                            <div class="flex items-start gap-4">
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
@endsection
