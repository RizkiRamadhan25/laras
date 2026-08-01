@extends('layouts.app')

@section('title', 'Notifikasi — Laras')
@section('page-title', 'Notifikasi')
@section(
    'page-description',
    'Pengingat, hasil pemrosesan, dan pembaruan penting.'
)

@section('content')
    @php
        $filterTabs = [
            'all' => [
                'label' => 'Semua',
                'count' => $summary['all'],
            ],

            'unread' => [
                'label' => 'Belum dibaca',
                'count' => $summary['unread'],
            ],

            'read' => [
                'label' => 'Sudah dibaca',
                'count' => $summary['read'],
            ],
        ];
    @endphp

    <div class="mx-auto max-w-5xl">
        <section>
            <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
                <div>
                    <p class="text-sm font-semibold text-laras-700">
                        Pusat informasi
                    </p>

                    <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">
                        Notifikasi
                    </h1>

                    <p class="mt-3 max-w-2xl leading-7 text-slate-500">
                        Pantau pengingat langganan, tagihan berhasil,
                        dan transaksi yang memerlukan perhatian.
                    </p>
                </div>

                @if ($summary['unread'] > 0)
                    <form
                        method="POST"
                        action="{{ route(
                            'notifications.read-all'
                        ) }}"
                    >
                        @csrf
                        @method('PATCH')

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                        >
                            <i
                                data-lucide="check-check"
                                class="size-4"
                            ></i>

                            Tandai semua dibaca
                        </button>
                    </form>
                @endif
            </div>

            <div class="mt-8 grid gap-4 sm:grid-cols-3">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                    <p class="text-sm text-slate-500">
                        Seluruh notifikasi
                    </p>

                    <p class="mt-2 text-3xl font-semibold text-slate-950">
                        {{ $summary['all'] }}
                    </p>
                </article>

                <article class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                    <p class="text-sm text-amber-700">
                        Belum dibaca
                    </p>

                    <p class="mt-2 text-3xl font-semibold text-amber-900">
                        {{ $summary['unread'] }}
                    </p>
                </article>

                <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                    <p class="text-sm text-emerald-700">
                        Sudah dibaca
                    </p>

                    <p class="mt-2 text-3xl font-semibold text-emerald-900">
                        {{ $summary['read'] }}
                    </p>
                </article>
            </div>
        </section>

        <section class="mt-6">
            <div class="overflow-x-auto">
                <nav class="flex min-w-max gap-2 rounded-2xl border border-slate-200 bg-white p-2 shadow-laras">
                    @foreach (
                        $filterTabs
                        as $value => $tab
                    )
                        <a
                            href="{{ route(
                                'notifications.index',
                                ['filter' => $value]
                            ) }}"
                            @class([
                                'inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition',

                                'bg-laras-700 text-white shadow-sm' =>
                                    $selectedFilter
                                        === $value,

                                'text-slate-500 hover:bg-slate-100 hover:text-slate-900' =>
                                    $selectedFilter
                                        !== $value,
                            ])
                        >
                            {{ $tab['label'] }}

                            <span
                                @class([
                                    'rounded-full px-2 py-0.5 text-[11px]',

                                    'bg-white/20 text-white' =>
                                        $selectedFilter
                                            === $value,

                                    'bg-slate-100 text-slate-500' =>
                                        $selectedFilter
                                            !== $value,
                                ])
                            >
                                {{ $tab['count'] }}
                            </span>
                        </a>
                    @endforeach
                </nav>
            </div>
        </section>

        <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-laras">
            @if ($notifications->isEmpty())
                <div class="px-6 py-16 text-center">
                    <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                        <i
                            data-lucide="bell"
                            class="size-6"
                        ></i>
                    </span>

                    <h2 class="mt-4 font-semibold text-slate-900">
                        Tidak ada notifikasi
                    </h2>

                    <p class="mt-2 text-sm text-slate-500">
                        Tidak ada notifikasi yang sesuai dengan filter ini.
                    </p>
                </div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach (
                        $notifications
                        as $notification
                    )
                        @php
                            $notificationData =
                                $notification->data;

                            $severity =
                                $notificationData[
                                    'severity'
                                ] ?? 'info';

                            $notificationStyle =
                                match ($severity) {
                                    'success' => [
                                        'icon' =>
                                            'bg-emerald-100 text-emerald-700',

                                        'border' =>
                                            'border-l-emerald-500',

                                        'dot' =>
                                            'bg-emerald-500',
                                    ],

                                    'danger' => [
                                        'icon' =>
                                            'bg-rose-100 text-rose-700',

                                        'border' =>
                                            'border-l-rose-500',

                                        'dot' =>
                                            'bg-rose-500',
                                    ],

                                    'warning' => [
                                        'icon' =>
                                            'bg-amber-100 text-amber-700',

                                        'border' =>
                                            'border-l-amber-500',

                                        'dot' =>
                                            'bg-amber-500',
                                    ],

                                    default => [
                                        'icon' =>
                                            'bg-blue-100 text-blue-700',

                                        'border' =>
                                            'border-l-blue-500',

                                        'dot' =>
                                            'bg-blue-500',
                                    ],
                                };
                        @endphp

                        <article
                            @class([
                                'border-l-4 px-5 py-5 sm:px-6',
                                $notificationStyle['border'],

                                'bg-laras-50/30' =>
                                    $notification->read_at
                                        === null,
                            ])
                        >
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                                <span
                                    class="flex size-11 shrink-0 items-center justify-center rounded-2xl {{ $notificationStyle['icon'] }}"
                                >
                                    <i
                                        data-lucide="{{ $notificationData['icon'] ?? 'bell' }}"
                                        class="size-5"
                                    ></i>
                                </span>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="font-semibold text-slate-900">
                                            {{ $notificationData[
                                                'title'
                                            ] ?? 'Notifikasi' }}
                                        </h2>

                                        @if (
                                            $notification->read_at
                                                === null
                                        )
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-laras-100 px-2.5 py-1 text-[11px] font-semibold text-laras-700">
                                                <span
                                                    class="size-1.5 rounded-full {{ $notificationStyle['dot'] }}"
                                                ></span>

                                                Baru
                                            </span>
                                        @endif
                                    </div>

                                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                                        {{ $notificationData[
                                            'message'
                                        ] ?? '' }}
                                    </p>

                                    <div class="mt-3 flex flex-wrap gap-x-4 gap-y-2 text-xs text-slate-400">
                                        <span>
                                            {{ $notification
                                                ->created_at
                                                ->diffForHumans() }}
                                        </span>

                                        <span>
                                            {{ $notification
                                                ->created_at
                                                ->timezone(
                                                    $user
                                                        ->preference
                                                        ?->timezone
                                                    ?? config(
                                                        'laras.defaults.timezone'
                                                    )
                                                )
                                                ->translatedFormat(
                                                    'd F Y, H:i'
                                                ) }}
                                        </span>

                                        @if (
                                            isset(
                                                $notificationData[
                                                    'scheduled_for'
                                                ]
                                            )
                                        )
                                            <span>
                                                Tagihan:
                                                {{ \Carbon\CarbonImmutable::parse(
                                                    $notificationData[
                                                        'scheduled_for'
                                                    ]
                                                )->translatedFormat(
                                                    'd F Y'
                                                ) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex shrink-0 flex-wrap gap-2">
                                    <a
                                        href="{{ route(
                                            'notifications.open',
                                            $notification->id
                                        ) }}"
                                        class="inline-flex items-center justify-center rounded-xl bg-laras-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-laras-800"
                                    >
                                        Buka
                                    </a>

                                    @if (
                                        $notification->read_at
                                            === null
                                    )
                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'notifications.read',
                                                $notification->id
                                            ) }}"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <button
                                                type="submit"
                                                class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                                            >
                                                <i
                                                    data-lucide="check"
                                                    class="size-4"
                                                ></i>

                                                Sudah dibaca
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="border-t border-slate-100 px-5 py-4 sm:px-6">
                    {{ $notifications->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
