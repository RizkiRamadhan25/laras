@php
    $navigation = [
        [
            'label' => 'Dashboard',
            'description' => 'Ringkasan utama',
            'icon' => 'layout-dashboard',
            'href' => route('dashboard'),
            'active' => request()->routeIs('dashboard'),
            'enabled' => true,
        ],
        [
            'label' => 'Aktivitas',
            'description' => 'Agenda dan jadwal',
            'icon' => 'calendar-days',
            'href' => '#',
            'active' => false,
            'enabled' => false,
        ],
        [
            'label' => 'Prioritas',
            'description' => 'Tugas dan fokus',
            'icon' => 'list-todo',
            'href' => '#',
            'active' => false,
            'enabled' => false,
        ],
        [
            'label' => 'Keuangan',
            'description' => 'Rekening dan saldo',
            'icon' => 'wallet-cards',
            'href' => route('accounts.index'),
            'active' => request()->routeIs('accounts.*'),
            'enabled' => true,
        ],
        [
            'label' => 'Analisis',
            'description' => 'Insight dan laporan',
            'icon' => 'chart-no-axes-combined',
            'href' => '#',
            'active' => false,
            'enabled' => false,
        ],
        [
            'label' => 'Rekomendasi',
            'description' => 'Saran personal',
            'icon' => 'lightbulb',
            'href' => '#',
            'active' => false,
            'enabled' => false,
        ],
    ];

    $userInitial = mb_strtoupper(
        mb_substr(auth()->user()->name, 0, 1)
    );
@endphp

<aside
    x-bind:class="
        sidebarOpen
            ? 'translate-x-0'
            : '-translate-x-full'
    "
    class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-slate-200 bg-white transition-transform duration-200 ease-out lg:translate-x-0"
    aria-label="Navigasi utama"
>
    <div class="flex h-20 shrink-0 items-center justify-between border-b border-slate-100 px-5">
        <a
            href="{{ route('dashboard') }}"
            class="flex items-center gap-3 rounded-xl focus:outline-none focus:ring-4 focus:ring-laras-100"
        >
            <span class="flex size-11 items-center justify-center rounded-2xl bg-laras-950 text-lg font-bold text-white shadow-sm">
                L
            </span>

            <span>
                <span class="block text-lg font-semibold tracking-tight text-slate-950">
                    Laras
                </span>

                <span class="block text-xs text-slate-400">
                    Personal management
                </span>
            </span>
        </a>

        <button
            type="button"
            x-on:click="sidebarOpen = false"
            class="flex size-10 items-center justify-center rounded-xl text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 lg:hidden"
            aria-label="Tutup navigasi"
        >
            <i data-lucide="x" class="size-5"></i>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto px-4 py-6">
        <p class="px-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">
            Ruang kerja
        </p>

        <nav class="mt-3 space-y-1.5">
            @foreach ($navigation as $item)
                @if ($item['enabled'])
                    <a
                        href="{{ $item['href'] }}"
                        x-on:click="sidebarOpen = false"
                        @class([
                            'group flex items-center gap-3 rounded-2xl px-3 py-3 transition',
                            'bg-laras-50 text-laras-800' => $item['active'],
                            'text-slate-600 hover:bg-slate-100 hover:text-slate-950' =>
                                ! $item['active'],
                        ])
                        @if ($item['active'])
                            aria-current="page"
                        @endif
                    >
                        <span
                            @class([
                                'flex size-10 shrink-0 items-center justify-center rounded-xl transition',
                                'bg-laras-700 text-white shadow-sm' => $item['active'],
                                'bg-slate-100 text-slate-500 group-hover:bg-white group-hover:text-slate-800' =>
                                    ! $item['active'],
                            ])
                        >
                            <i
                                data-lucide="{{ $item['icon'] }}"
                                class="size-[19px]"
                            ></i>
                        </span>

                        <span class="min-w-0">
                            <span class="block text-sm font-semibold">
                                {{ $item['label'] }}
                            </span>

                            <span
                                @class([
                                    'mt-0.5 block truncate text-xs',
                                    'text-laras-600' => $item['active'],
                                    'text-slate-400' => ! $item['active'],
                                ])
                            >
                                {{ $item['description'] }}
                            </span>
                        </span>
                    </a>
                @else
                    <div
                        class="group flex cursor-not-allowed items-center gap-3 rounded-2xl px-3 py-3 text-slate-400"
                        aria-disabled="true"
                    >
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                            <i
                                data-lucide="{{ $item['icon'] }}"
                                class="size-[19px]"
                            ></i>
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold">
                                {{ $item['label'] }}
                            </span>

                            <span class="mt-0.5 block truncate text-xs text-slate-400">
                                {{ $item['description'] }}
                            </span>
                        </span>

                        <span class="rounded-full bg-slate-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                            Segera
                        </span>
                    </div>
                @endif
            @endforeach
        </nav>

        <div class="mt-8 border-t border-slate-100 pt-6">
            <p class="px-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">
                Sistem
            </p>

            <div class="mt-3">
                <div
                    class="flex cursor-not-allowed items-center gap-3 rounded-2xl px-3 py-3 text-slate-400"
                    aria-disabled="true"
                >
                    <span class="flex size-10 items-center justify-center rounded-xl bg-slate-100">
                        <i data-lucide="settings" class="size-[19px]"></i>
                    </span>

                    <span class="text-sm font-semibold">
                        Pengaturan
                    </span>

                    <span class="ml-auto rounded-full bg-slate-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide">
                        Segera
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="shrink-0 border-t border-slate-100 p-4">
        <div class="flex items-center gap-3 rounded-2xl bg-slate-50 p-3">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-laras-950 text-sm font-semibold text-white">
                {{ $userInitial }}
            </span>

            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-slate-900">
                    {{ auth()->user()->name }}
                </p>

                <p class="truncate text-xs text-slate-400">
                    {{ auth()->user()->email }}
                </p>
            </div>
        </div>
    </div>
</aside>
