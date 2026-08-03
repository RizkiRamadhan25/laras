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

        <div class="flex items-center gap-2">
            <button
                type="button"
                disabled
                class="hidden h-11 items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-400 md:flex"
                title="Pencarian akan tersedia pada pengembangan berikutnya"
            >
                <i
                    data-lucide="search"
                    class="size-4"
                ></i>

                <span>Cari</span>

                <kbd class="ml-4 rounded-md border border-slate-200 bg-white px-1.5 py-0.5 text-[10px]">
                    /
                </kbd>
            </button>

            <div
                x-data="{ notificationOpen: false }"
                x-on:keydown.escape.window="
                    notificationOpen = false
                "
                class="relative"
            >
                <button
                    type="button"
                    x-on:click="
                        notificationOpen =
                            ! notificationOpen
                    "
                    x-bind:aria-expanded="
                        notificationOpen
                    "
                    class="relative flex size-11 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-950 focus:outline-none focus:ring-4 focus:ring-laras-100"
                    aria-label="Notifikasi"
                    aria-haspopup="menu"
                >
                    <i
                        data-lucide="bell"
                        class="size-5"
                    ></i>

                    @if (
                        $headerUnreadNotificationCount > 0
                    )
                        <span class="absolute -right-1 -top-1 flex min-w-5 items-center justify-center rounded-full border-2 border-white bg-rose-600 px-1 text-[10px] font-bold leading-4 text-white">
                            {{ $headerUnreadNotificationCount > 99
                                ? '99+'
                                : $headerUnreadNotificationCount }}
                        </span>
                    @endif
                </button>

                <div
                    x-cloak
                    x-show="notificationOpen"
                    x-transition:enter="transition duration-150 ease-out"
                    x-transition:enter-start="translate-y-1 opacity-0"
                    x-transition:enter-end="translate-y-0 opacity-100"
                    x-transition:leave="transition duration-100 ease-in"
                    x-transition:leave-start="translate-y-0 opacity-100"
                    x-transition:leave-end="translate-y-1 opacity-0"
                    x-on:click.outside="
                        notificationOpen = false
                    "
                    class="fixed inset-x-4 top-[76px] z-50 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-laras sm:absolute sm:inset-x-auto sm:right-0 sm:top-auto sm:mt-3 sm:w-[390px]"
                    role="menu"
                >
                    <header class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                        <div>
                            <p class="font-semibold text-slate-900">
                                Notifikasi
                            </p>

                            <p class="mt-0.5 text-xs text-slate-400">
                                {{ $headerUnreadNotificationCount }}
                                belum dibaca
                            </p>
                        </div>

                        <a
                            href="{{ route(
                                'notifications.index'
                            ) }}"
                            class="text-sm font-semibold text-laras-700 hover:text-laras-900"
                        >
                            Lihat semua
                        </a>
                    </header>

                    @if (
                        $headerNotifications->isEmpty()
                    )
                        <div class="px-6 py-10 text-center">
                            <span class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                <i
                                    data-lucide="bell"
                                    class="size-5"
                                ></i>
                            </span>

                            <p class="mt-4 text-sm font-semibold text-slate-800">
                                Belum ada notifikasi
                            </p>

                            <p class="mt-1 text-xs text-slate-400">
                                Pengingat dan hasil tagihan akan muncul di sini.
                            </p>
                        </div>
                    @else
                        <div class="max-h-[430px] overflow-y-auto divide-y divide-slate-100">
                            @foreach (
                                $headerNotifications
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

                                                'dot' =>
                                                    'bg-emerald-500',
                                            ],

                                            'danger' => [
                                                'icon' =>
                                                    'bg-rose-100 text-rose-700',

                                                'dot' =>
                                                    'bg-rose-500',
                                            ],

                                            'warning' => [
                                                'icon' =>
                                                    'bg-amber-100 text-amber-700',

                                                'dot' =>
                                                    'bg-amber-500',
                                            ],

                                            default => [
                                                'icon' =>
                                                    'bg-blue-100 text-blue-700',

                                                'dot' =>
                                                    'bg-blue-500',
                                            ],
                                        };
                                @endphp

                                <a
                                    href="{{ route(
                                        'notifications.open',
                                        $notification->id
                                    ) }}"
                                    @class([
                                        'flex items-start gap-3 px-5 py-4 transition hover:bg-slate-50',
                                        'bg-laras-50/40' =>
                                            $notification->read_at
                                                === null,
                                    ])
                                    role="menuitem"
                                >
                                    <span
                                        class="flex size-10 shrink-0 items-center justify-center rounded-xl {{ $notificationStyle['icon'] }}"
                                    >
                                        <i
                                            data-lucide="{{ $notificationData['icon'] ?? 'bell' }}"
                                            class="size-4"
                                        ></i>
                                    </span>

                                    <span class="min-w-0 flex-1">
                                        <span class="flex items-start gap-2">
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-sm font-semibold text-slate-900">
                                                    {{ $notificationData[
                                                        'title'
                                                    ] ?? 'Notifikasi' }}
                                                </span>

                                                <span class="mt-1 line-clamp-2 block text-xs leading-5 text-slate-500">
                                                    {{ $notificationData[
                                                        'message'
                                                    ] ?? '' }}
                                                </span>
                                            </span>

                                            @if (
                                                $notification->read_at
                                                    === null
                                            )
                                                <span
                                                    class="mt-1.5 size-2 shrink-0 rounded-full {{ $notificationStyle['dot'] }}"
                                                ></span>
                                            @endif
                                        </span>

                                        <span class="mt-2 block text-[11px] text-slate-400">
                                            {{ $notification
                                                ->created_at
                                                ->diffForHumans() }}
                                        </span>
                                    </span>
                                </a>
                            @endforeach
                        </div>

                        @if (
                            $headerUnreadNotificationCount > 0
                        )
                            <form
                                method="POST"
                                action="{{ route(
                                    'notifications.read-all'
                                ) }}"
                                class="border-t border-slate-100 p-3"
                            >
                                @csrf
                                @method('PATCH')

                                <button
                                    type="submit"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                                >
                                    <i
                                        data-lucide="check-check"
                                        class="size-4"
                                    ></i>

                                    Tandai semua dibaca
                                </button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>
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
                <x-ui.user-avatar
                    :user="auth()->user()"
                    size="sm"
                    rounded="xl"
                />

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
                    <a
                        href="{{ route('settings.index') }}"
                        x-on:click="profileOpen = false"
                        class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                        role="menuitem"
                    >
                        <i
                            data-lucide="circle-user-round"
                            class="size-4"
                        ></i>

                        Profil dan pengaturan
                    </a>
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
