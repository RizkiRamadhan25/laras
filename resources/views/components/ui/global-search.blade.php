<div
    data-laras-global-search
    data-search-endpoint="{{ route('search.global') }}"
>
    <button
        type="button"
        data-global-search-open
        class="hidden h-11 items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-500 transition hover:border-slate-300 hover:bg-white hover:text-slate-800 focus:outline-none focus:ring-4 focus:ring-laras-100 md:flex"
        aria-label="Buka pencarian global"
        aria-haspopup="dialog"
    >
        <i
            data-lucide="search"
            class="size-4"
        ></i>

        <span>Cari</span>

        <kbd class="ml-4 rounded-md border border-slate-200 bg-white px-1.5 py-0.5 text-[10px] font-semibold text-slate-400 shadow-sm">
            /
        </kbd>
    </button>

    <button
        type="button"
        data-global-search-open
        class="flex size-11 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-950 focus:outline-none focus:ring-4 focus:ring-laras-100 md:hidden"
        aria-label="Buka pencarian global"
        aria-haspopup="dialog"
    >
        <i
            data-lucide="search"
            class="size-5"
        ></i>
    </button>

    <dialog
        data-global-search-dialog
        class="m-auto w-[calc(100%-1.5rem)] max-w-2xl overflow-hidden rounded-3xl border border-slate-200 bg-white p-0 text-slate-950 shadow-2xl backdrop:bg-slate-950/55 backdrop:backdrop-blur-sm"
        aria-labelledby="laras-global-search-title"
    >
        <div class="flex max-h-[min(760px,calc(100svh-2rem))] flex-col">
            <header class="border-b border-slate-100 px-4 py-4 sm:px-5">
                <div class="flex items-center gap-3">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-laras-50 text-laras-700">
                        <i
                            data-lucide="search"
                            class="size-5"
                        ></i>
                    </span>

                    <div class="min-w-0 flex-1">
                        <h2
                            id="laras-global-search-title"
                            class="sr-only"
                        >
                            Pencarian global Laras
                        </h2>

                        <label
                            for="laras-global-search-input"
                            class="sr-only"
                        >
                            Cari data atau halaman
                        </label>

                        <input
                            id="laras-global-search-input"
                            type="search"
                            inputmode="search"
                            autocomplete="off"
                            spellcheck="false"
                            data-global-search-input
                            class="w-full border-0 bg-transparent p-0 text-base font-medium text-slate-950 outline-none placeholder:text-slate-400 focus:ring-0 sm:text-lg"
                            placeholder="Cari aktivitas, transaksi, rekening..."
                        >

                        <p class="mt-1 text-xs text-slate-400">
                            Ketik minimal 2 karakter untuk mencari datamu.
                        </p>
                    </div>

                    <button
                        type="button"
                        data-global-search-close
                        class="flex size-10 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-laras-500 focus:ring-offset-2"
                        aria-label="Tutup pencarian"
                    >
                        <i
                            data-lucide="x"
                            class="size-5"
                        ></i>
                    </button>
                </div>
            </header>

            <div class="min-h-0 flex-1 overflow-y-auto px-3 py-3 sm:px-4">
                <section
                    data-global-search-initial
                    aria-label="Akses cepat"
                >
                    <p class="px-2 pb-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
                        Akses cepat
                    </p>

                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach ([
                            [
                                'label' => 'Dashboard',
                                'description' => 'Lihat ringkasan Laras',
                                'icon' => 'layout-dashboard',
                                'route' => 'dashboard',
                            ],
                            [
                                'label' => 'Aktivitas',
                                'description' => 'Kelola tugas dan jadwal',
                                'icon' => 'list-todo',
                                'route' => 'activities.index',
                            ],
                            [
                                'label' => 'Transaksi',
                                'description' => 'Buka riwayat keuangan',
                                'icon' => 'receipt-text',
                                'route' => 'transactions.index',
                            ],
                            [
                                'label' => 'Rekening',
                                'description' => 'Kelola saldo dan sumber dana',
                                'icon' => 'wallet-cards',
                                'route' => 'accounts.index',
                            ],
                            [
                                'label' => 'Anggaran',
                                'description' => 'Pantau batas pengeluaran',
                                'icon' => 'piggy-bank',
                                'route' => 'budgets.index',
                            ],
                            [
                                'label' => 'Pengaturan',
                                'description' => 'Profil, keamanan, dan privasi',
                                'icon' => 'settings',
                                'route' => 'settings.index',
                            ],
                        ] as $quickLink)
                            <a
                                href="{{ route($quickLink['route']) }}"
                                class="group flex items-center gap-3 rounded-2xl border border-slate-200 p-3.5 transition hover:border-laras-200 hover:bg-laras-50/60 focus:outline-none focus:ring-4 focus:ring-laras-100"
                            >
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600 transition group-hover:bg-white group-hover:text-laras-700">
                                    <i
                                        data-lucide="{{ $quickLink['icon'] }}"
                                        class="size-4"
                                    ></i>
                                </span>

                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-slate-900">
                                        {{ $quickLink['label'] }}
                                    </span>

                                    <span class="mt-0.5 block truncate text-xs text-slate-400">
                                        {{ $quickLink['description'] }}
                                    </span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </section>

                <section
                    data-global-search-loading
                    class="hidden space-y-3"
                    aria-label="Memuat hasil pencarian"
                >
                    @for ($index = 0; $index < 4; $index++)
                        <div class="flex animate-pulse items-center gap-3 rounded-2xl border border-slate-100 p-3.5">
                            <span class="size-10 shrink-0 rounded-xl bg-slate-100"></span>

                            <span class="min-w-0 flex-1 space-y-2">
                                <span class="block h-3.5 w-2/5 rounded bg-slate-100"></span>
                                <span class="block h-3 w-3/5 rounded bg-slate-100"></span>
                            </span>
                        </div>
                    @endfor
                </section>

                <section
                    data-global-search-results
                    class="hidden"
                    aria-label="Hasil pencarian"
                >
                    <div
                        data-global-search-groups
                        class="space-y-4"
                    ></div>
                </section>

                <section
                    data-global-search-empty
                    class="hidden px-5 py-12 text-center"
                    aria-live="polite"
                >
                    <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                        <i
                            data-lucide="search"
                            class="size-6"
                        ></i>
                    </span>

                    <p class="mt-4 font-semibold text-slate-900">
                        Data tidak ditemukan
                    </p>

                    <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-slate-500">
                        Coba gunakan nama, deskripsi, bank, pihak transaksi, atau halaman yang berbeda.
                    </p>
                </section>

                <section
                    data-global-search-error
                    class="hidden px-5 py-12 text-center"
                    aria-live="assertive"
                >
                    <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-rose-100 text-rose-700">
                        <i
                            data-lucide="triangle-alert"
                            class="size-6"
                        ></i>
                    </span>

                    <p class="mt-4 font-semibold text-slate-900">
                        Pencarian tidak dapat dimuat
                    </p>

                    <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-slate-500">
                        Periksa koneksi lalu coba ketik ulang kata pencarian.
                    </p>
                </section>
            </div>

            <footer class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/80 px-4 py-3 text-[11px] text-slate-400 sm:px-5">
                <span class="flex items-center gap-2">
                    <kbd class="rounded-md border border-slate-200 bg-white px-1.5 py-0.5 font-semibold shadow-sm">
                        ↑↓
                    </kbd>
                    Pilih

                    <kbd class="ml-2 rounded-md border border-slate-200 bg-white px-1.5 py-0.5 font-semibold shadow-sm">
                        Enter
                    </kbd>
                    Buka
                </span>

                <span class="flex items-center gap-2">
                    <kbd class="rounded-md border border-slate-200 bg-white px-1.5 py-0.5 font-semibold shadow-sm">
                        Esc
                    </kbd>
                    Tutup
                </span>
            </footer>
        </div>
    </dialog>
</div>
