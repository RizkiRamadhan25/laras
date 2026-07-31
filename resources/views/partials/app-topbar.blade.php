@php
    $userInitial = mb_strtoupper(
        mb_substr(auth()->user()->name, 0, 1)
    );
@endphp

<header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/90 backdrop-blur-xl">
    <div class="flex h-20 items-center gap-4 px-4 sm:px-6 lg:px-8">
        <button
            type="button"
            x-on:click="sidebarOpen = true"
            class="flex size-11 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-slate-300 hover:text-slate-950 lg:hidden"
            aria-label="Buka navigasi"
        >
            <i data-lucide="menu" class="size-5"></i>
        </button>

        <div class="min-w-0 flex-1">
            <p class="truncate text-lg font-semibold tracking-tight text-slate-950">
                @yield('page-title', 'Dashboard')
            </p>

            <p class="mt-0.5 hidden truncate text-xs text-slate-400 sm:block">
                @yield(
                    'page-description',
                    'Selaraskan hari, tentukan langkah.'
                )
            </p>
        </div>

        <div class="hidden items-center gap-2 md:flex">
            <button
                type="button"
                disabled
                class="flex h-11 items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-400"
                title="Pencarian akan tersedia pada pengembangan berikutnya"
            >
                <i data-lucide="search" class="size-4"></i>
                <span>Cari</span>
                <kbd class="ml-4 rounded-md border border-slate-200 bg-white px-1.5 py-0.5 text-[10px]">
                    /
                </kbd>
            </button>

            <button
                type="button"
                disabled
                class="relative flex size-11 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-400"
                title="Notifikasi akan tersedia pada pengembangan berikutnya"
                aria-label="Notifikasi"
            >
                <i data-lucide="bell" class="size-5"></i>
            </button>
        </div>

        <div
            x-data="{ profileOpen: false }"
            x-on:keydown.escape.window="profileOpen = false"
            class="relative"
        >
            <button
                type="button"
                x-on:click="profileOpen = ! profileOpen"
                class="flex items-center gap-2 rounded-xl p-1.5 transition hover:bg-slate-100 focus:outline-none focus:ring-4 focus:ring-laras-100"
                x-bind:aria-expanded="profileOpen"
                aria-haspopup="menu"
            >
                <span class="flex size-9 items-center justify-center rounded-xl bg-laras-950 text-sm font-semibold text-white">
                    {{ $userInitial }}
                </span>

                <span class="hidden max-w-36 text-left lg:block">
                    <span class="block truncate text-sm font-semibold text-slate-800">
                        {{ auth()->user()->name }}
                    </span>

                    <span class="block truncate text-xs text-slate-400">
                        Akun personal
                    </span>
                </span>

                <i
                    data-lucide="chevron-down"
                    class="hidden size-4 text-slate-400 lg:block"
                ></i>
            </button>

            <div
                x-cloak
                x-show="profileOpen"
                x-transition:enter="transition duration-150 ease-out"
                x-transition:enter-start="translate-y-1 opacity-0"
                x-transition:enter-end="translate-y-0 opacity-100"
                x-transition:leave="transition duration-100 ease-in"
                x-transition:leave-start="translate-y-0 opacity-100"
                x-transition:leave-end="translate-y-1 opacity-0"
                x-on:click.outside="profileOpen = false"
                class="absolute right-0 mt-3 w-64 overflow-hidden rounded-2xl border border-slate-200 bg-white p-2 shadow-laras"
                role="menu"
            >
                <div class="border-b border-slate-100 px-3 py-3">
                    <p class="truncate text-sm font-semibold text-slate-900">
                        {{ auth()->user()->name }}
                    </p>

                    <p class="mt-1 truncate text-xs text-slate-400">
                        {{ auth()->user()->email }}
                    </p>
                </div>

                <div class="py-2">
                    <button
                        type="button"
                        disabled
                        class="flex w-full cursor-not-allowed items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium text-slate-400"
                        role="menuitem"
                    >
                        <i data-lucide="circle-user-round" class="size-4"></i>
                        Profil dan pengaturan
                    </button>
                </div>

                <div class="border-t border-slate-100 pt-2">
                    <form
                        method="POST"
                        action="{{ route('logout') }}"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-rose-600 transition hover:bg-rose-50"
                            role="menuitem"
                        >
                            <i data-lucide="log-out" class="size-4"></i>
                            Keluar dari Laras
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
