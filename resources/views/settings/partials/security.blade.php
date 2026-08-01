@php
    $securityTimezone =
        $user->preference?->timezone
        ?? 'Asia/Jakarta';
@endphp

<section
    id="security"
    class="mt-8"
>
    <header>
        <p class="text-sm font-semibold text-laras-700">
            Keamanan
        </p>

        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">
            Lindungi akun Laras.
        </h2>

        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
            Perbarui kata sandi dan keluarkan sesi yang
            masih aktif pada perangkat lain.
        </p>
    </header>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- Perubahan kata sandi --}}
        <form
            method="POST"
            action="{{ route(
                'settings.security.password.update'
            ) }}"
            class="rounded-2xl border border-slate-200 bg-white p-6 shadow-laras sm:p-8"
        >
            @csrf
            @method('PATCH')

            <header class="flex items-start gap-3 border-b border-slate-100 pb-5">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
                    <i
                        data-lucide="key-round"
                        class="size-5"
                    ></i>
                </span>

                <div>
                    <h3 class="font-semibold text-slate-950">
                        Ubah kata sandi
                    </h3>

                    <p class="mt-1 text-sm leading-6 text-slate-400">
                        Gunakan kombinasi yang kuat dan tidak
                        digunakan pada akun lain.
                    </p>
                </div>
            </header>

            <div class="mt-6">
                <label
                    for="current_password"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Kata sandi saat ini
                </label>

                <input
                    id="current_password"
                    name="current_password"
                    type="password"
                    required
                    autocomplete="current-password"
                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
                >

                @error('current_password')
                    <p class="mt-2 text-sm text-rose-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div class="mt-5">
                <label
                    for="password"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Kata sandi baru
                </label>

                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    minlength="8"
                    autocomplete="new-password"
                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
                >

                @error('password')
                    <p class="mt-2 text-sm text-rose-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div class="mt-5">
                <label
                    for="password_confirmation"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Konfirmasi kata sandi baru
                </label>

                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    required
                    minlength="8"
                    autocomplete="new-password"
                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
                >
            </div>

            <div class="mt-5 rounded-2xl bg-slate-50 px-4 py-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Ketentuan
                </p>

                <p class="mt-2 text-xs leading-5 text-slate-500">
                    Minimal 8 karakter, mengandung huruf
                    besar, huruf kecil, angka, dan simbol.
                </p>
            </div>

            <div class="mt-7 flex justify-end border-t border-slate-100 pt-6">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-laras-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-laras-800"
                >
                    <i
                        data-lucide="shield-check"
                        class="size-4"
                    ></i>

                    Perbarui kata sandi
                </button>
            </div>
        </form>

        {{-- Logout perangkat lain --}}
        <form
            method="POST"
            action="{{ route(
                'settings.security.sessions.logout-others'
            ) }}"
            class="rounded-2xl border border-slate-200 bg-white p-6 shadow-laras sm:p-8"
            onsubmit="return confirm(
                'Keluarkan akun dari seluruh perangkat lain?'
            )"
        >
            @csrf

            <header class="flex items-start gap-3 border-b border-slate-100 pb-5">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-blue-100 text-blue-700">
                    <i
                        data-lucide="monitor-smartphone"
                        class="size-5"
                    ></i>
                </span>

                <div>
                    <h3 class="font-semibold text-slate-950">
                        Perangkat dan sesi
                    </h3>

                    <p class="mt-1 text-sm leading-6 text-slate-400">
                        Keluarkan akun dari browser atau
                        perangkat lain yang pernah digunakan.
                    </p>
                </div>
            </header>

            <div class="mt-6 rounded-2xl border border-blue-200 bg-blue-50 p-5">
                <div class="flex items-start gap-3">
                    <i
                        data-lucide="shield-check"
                        class="mt-0.5 size-5 shrink-0 text-blue-700"
                    ></i>

                    <div>
                        <h4 class="text-sm font-semibold text-blue-950">
                            Perangkat ini tetap aktif
                        </h4>

                        <p class="mt-1 text-sm leading-6 text-blue-700">
                            Hanya sesi pada perangkat lain
                            yang akan dikeluarkan.
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <label
                    for="logout_current_password"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Konfirmasi kata sandi
                </label>

                <input
                    id="logout_current_password"
                    name="logout_current_password"
                    type="password"
                    required
                    autocomplete="current-password"
                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
                >

                @error('logout_current_password')
                    <p class="mt-2 text-sm text-rose-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div class="mt-7 flex justify-end border-t border-slate-100 pt-6">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-5 py-3 text-sm font-semibold text-rose-700 transition hover:bg-rose-100"
                >
                    <i
                        data-lucide="log-out"
                        class="size-4"
                    ></i>

                    Keluarkan perangkat lain
                </button>
            </div>
        </form>
    </div>

    {{-- Riwayat keamanan --}}
    <article class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-laras">
        <header class="flex flex-col justify-between gap-3 border-b border-slate-100 px-5 py-5 sm:flex-row sm:items-center sm:px-6">
            <div class="flex items-start gap-3">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-violet-100 text-violet-700">
                    <i
                        data-lucide="history"
                        class="size-5"
                    ></i>
                </span>

                <div>
                    <h3 class="font-semibold text-slate-950">
                        Riwayat keamanan
                    </h3>

                    <p class="mt-1 text-sm text-slate-400">
                        Aktivitas keamanan terbaru pada akun.
                    </p>
                </div>
            </div>

            @if ($lastPasswordChangedAt !== null)
                <p class="text-xs text-slate-400">
                    Kata sandi terakhir diubah

                    {{ $lastPasswordChangedAt
                        ->setTimezone(
                            $securityTimezone
                        )
                        ->locale('id')
                        ->diffForHumans() }}
                </p>
            @endif
        </header>

        @if ($securityEvents->isEmpty())
            <div class="px-6 py-12 text-center">
                <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                    <i
                        data-lucide="shield-check"
                        class="size-6"
                    ></i>
                </span>

                <h4 class="mt-4 font-semibold text-slate-900">
                    Belum ada aktivitas keamanan
                </h4>

                <p class="mt-2 text-sm text-slate-500">
                    Perubahan kata sandi dan logout perangkat
                    lain akan dicatat di sini.
                </p>
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach (
                    $securityEvents
                    as $securityEvent
                )
                    <div class="flex items-start gap-4 px-5 py-5 sm:px-6">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl {{ $securityEvent->type->colorClass() }}">
                            <i
                                data-lucide="{{ $securityEvent->type->icon() }}"
                                class="size-5"
                            ></i>
                        </span>

                        <div class="min-w-0 flex-1">
                            <h4 class="font-semibold text-slate-950">
                                {{ $securityEvent
                                    ->type
                                    ->label() }}
                            </h4>

                            <p class="mt-1 text-sm leading-6 text-slate-500">
                                {{ $securityEvent
                                    ->type
                                    ->description() }}
                            </p>

                            <div class="mt-3 flex flex-wrap gap-x-4 gap-y-2 text-xs text-slate-400">
                                <span>
                                    {{ $securityEvent
                                        ->occurred_at
                                        ->setTimezone(
                                            $securityTimezone
                                        )
                                        ->locale('id')
                                        ->translatedFormat(
                                            'd F Y, H:i'
                                        ) }}
                                </span>

                                @if (
                                    filled(
                                        $securityEvent
                                            ->ip_address
                                    )
                                )
                                    <span>
                                        IP:
                                        {{ $securityEvent
                                            ->ip_address }}
                                    </span>
                                @endif
                            </div>

                            @if (
                                filled(
                                    $securityEvent
                                        ->user_agent
                                )
                            )
                                <p class="mt-2 truncate text-xs text-slate-400">
                                    {{ \Illuminate\Support\Str::limit(
                                        $securityEvent
                                            ->user_agent,
                                        110
                                    ) }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </article>
</section>
