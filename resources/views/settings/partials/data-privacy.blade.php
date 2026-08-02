<section
    id="data-privacy"
    data-settings-section
    class="mt-8 scroll-mt-44"
>
    <header>
        <p class="text-sm font-semibold text-laras-700">
            Data dan privasi
        </p>

        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">
            Kendalikan data Laras milikmu.
        </h2>

        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
            Unduh salinan data pribadi atau hapus akun
            secara permanen ketika Laras tidak lagi digunakan.
        </p>
    </header>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- Ekspor data --}}
        <article class="flex flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-laras sm:p-8">
            <header class="flex items-start gap-3 border-b border-slate-100 pb-5">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-blue-100 text-blue-700">
                    <i
                        data-lucide="file-json"
                        class="size-5"
                    ></i>
                </span>

                <div>
                    <h3 class="font-semibold text-slate-950">
                        Unduh data pribadi
                    </h3>

                    <p class="mt-1 text-sm leading-6 text-slate-400">
                        Buat arsip ZIP berisi data JSON dan
                        foto profil yang tersimpan.
                    </p>
                </div>
            </header>

            <div class="mt-6 flex-1">
                <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5">
                    <h4 class="text-sm font-semibold text-blue-950">
                        Isi arsip
                    </h4>

                    <ul class="mt-3 space-y-2 text-sm leading-6 text-blue-700">
                        <li>
                            • Profil dan preferensi
                        </li>

                        <li>
                            • Rekening dan transaksi
                        </li>

                        <li>
                            • Aktivitas dan prioritas
                        </li>

                        <li>
                            • Langganan dan billing
                        </li>

                        <li>
                            • Rekomendasi dan keamanan
                        </li>

                        <li>
                            • Riwayat peringatan anggaran
                        </li>

                        <li>
                            • Foto profil, bila tersedia
                        </li>
                    </ul>
                </div>

                <p class="mt-4 text-xs leading-5 text-slate-400">
                    Hash kata sandi, remember token, token sesi,
                    dan token reset kata sandi tidak dimasukkan.
                </p>
            </div>

            <form
                method="POST"
                action="{{ route(
                    'settings.data.export'
                ) }}"
                class="mt-7 border-t border-slate-100 pt-6"
            >
                @csrf

                <div class="mb-5">
                    <label
                        for="export_current_password"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Kata sandi saat ini
                    </label>

                    <input
                        id="export_current_password"
                        name="export_current_password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
                    >

                    @error('export_current_password')
                        <p class="mt-2 text-sm text-rose-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-laras-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-laras-800"
                >
                    <i
                        data-lucide="download"
                        class="size-4"
                    ></i>

                    Unduh arsip data
                </button>
            </form>
        </article>

        {{-- Hapus akun --}}
        <form
            method="POST"
            action="{{ route(
                'settings.account.destroy'
            ) }}"
            class="rounded-2xl border border-rose-200 bg-white p-6 shadow-laras sm:p-8"
            onsubmit="return confirm(
                'Akun dan seluruh data akan dihapus permanen. Lanjutkan?'
            )"
        >
            @csrf
            @method('DELETE')

            <header class="flex items-start gap-3 border-b border-rose-100 pb-5">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-700">
                    <i
                        data-lucide="triangle-alert"
                        class="size-5"
                    ></i>
                </span>

                <div>
                    <h3 class="font-semibold text-rose-950">
                        Hapus akun
                    </h3>

                    <p class="mt-1 text-sm leading-6 text-rose-600">
                        Tindakan ini permanen dan tidak
                        dapat dibatalkan.
                    </p>
                </div>
            </header>

            <div class="mt-6 rounded-2xl bg-rose-50 p-5">
                <p class="text-sm font-semibold text-rose-900">
                    Data berikut akan dihapus:
                </p>

                <p class="mt-2 text-sm leading-6 text-rose-700">
                    Profil, foto, rekening, saldo, transaksi,
                    aktivitas, langganan, notifikasi,
                    rekomendasi, dan riwayat keamanan.
                </p>
            </div>

            <div class="mt-6">
                <label
                    for="delete_current_password"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Kata sandi saat ini
                </label>

                <input
                    id="delete_current_password"
                    name="delete_current_password"
                    type="password"
                    required
                    autocomplete="current-password"
                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-rose-500 focus:ring-4 focus:ring-rose-100"
                >

                @error('delete_current_password')
                    <p class="mt-2 text-sm text-rose-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div class="mt-5">
                <label
                    for="confirmation"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Ketik
                    <strong>HAPUS AKUN</strong>
                </label>

                <input
                    id="confirmation"
                    name="confirmation"
                    type="text"
                    required
                    autocomplete="off"
                    placeholder="HAPUS AKUN"
                    value="{{ old(
                        'confirmation'
                    ) }}"
                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-rose-500 focus:ring-4 focus:ring-rose-100"
                >

                @error('confirmation')
                    <p class="mt-2 text-sm text-rose-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div class="mt-7 border-t border-rose-100 pt-6">
                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-rose-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-rose-700"
                >
                    <i
                        data-lucide="trash-2"
                        class="size-4"
                    ></i>

                    Hapus akun secara permanen
                </button>
            </div>
        </form>
    </div>
</section>
