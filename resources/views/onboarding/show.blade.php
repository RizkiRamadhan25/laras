<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Preferensi Personal — Laras</title>

    <meta
        name="description"
        content="Atur preferensi personal untuk aplikasi Laras."
    >

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-50 text-slate-950 antialiased">
    <main class="mx-auto flex min-h-screen max-w-7xl items-center px-4 py-8 sm:px-6 lg:px-8">
        <section class="grid w-full overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm lg:grid-cols-[0.78fr_1.22fr]">
            <aside class="relative overflow-hidden bg-[#0b2a5b] p-8 text-white sm:p-10 lg:p-12">
                <div
                    class="pointer-events-none absolute inset-0 opacity-[0.07]"
                    style="
                        background-image:
                            linear-gradient(rgba(255,255,255,.8) 1px, transparent 1px),
                            linear-gradient(90deg, rgba(255,255,255,.8) 1px, transparent 1px);
                        background-size: 52px 52px;
                    "
                ></div>

                <div class="relative z-10 flex h-full flex-col">
                    <div class="flex items-center gap-3">
                        <div class="flex size-12 items-center justify-center rounded-2xl bg-white text-lg font-bold text-[#0b2a5b]">
                            L
                        </div>

                        <div>
                            <p class="text-lg font-semibold">Laras</p>
                            <p class="text-sm text-blue-200">
                                Personal life management
                            </p>
                        </div>
                    </div>

                    <div class="my-12 lg:my-auto">
                        <div class="mb-6 h-1.5 w-14 rounded-full bg-amber-400"></div>

                        <p class="text-sm font-semibold text-blue-200">
                            Pengaturan awal
                        </p>

                        <h1 class="mt-3 text-4xl font-semibold leading-tight tracking-tight">
                            Jadikan Laras lebih sesuai dengan keseharianmu.
                        </h1>

                        <p class="mt-5 max-w-md leading-7 text-blue-100">
                            Preferensi ini digunakan untuk menampilkan tanggal,
                            waktu, dan informasi keuangan secara konsisten.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/10 p-5 backdrop-blur-sm">
                        <p class="text-xs font-medium uppercase tracking-wider text-blue-200">
                            Akun aktif
                        </p>

                        <p class="mt-2 font-semibold">
                            {{ $user->name }}
                        </p>

                        <p class="mt-1 break-all text-sm text-blue-100">
                            {{ $user->email }}
                        </p>
                    </div>
                </div>
            </aside>

            <div class="p-6 sm:p-10 lg:p-12">
                <div class="flex items-center gap-3">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-blue-700 text-sm font-semibold text-white">
                        1
                    </span>

                    <div class="h-1 flex-1 overflow-hidden rounded-full bg-slate-200">
                        <div class="h-full w-1/2 rounded-full bg-blue-700"></div>
                    </div>

                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-slate-200 text-sm font-semibold text-slate-500">
                        2
                    </span>
                </div>

                <header class="mt-8">
                    <p class="text-sm font-semibold text-blue-700">
                        Langkah 1 dari 2
                    </p>

                    <h2 class="mt-2 text-3xl font-semibold tracking-tight">
                        Preferensi personal
                    </h2>

                    <p class="mt-3 max-w-2xl leading-7 text-slate-500">
                        Atur identitas dan format utama yang akan digunakan
                        dalam aplikasi Laras.
                    </p>
                </header>

                @if (session('warning'))
                    <div
                        class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900"
                        role="alert"
                    >
                        {{ session('warning') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div
                        class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4"
                        role="alert"
                    >
                        <p class="text-sm font-semibold text-rose-800">
                            Periksa kembali data yang kamu masukkan.
                        </p>

                        <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-rose-700">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form
                    method="POST"
                    action="{{ route('onboarding.preferences.store') }}"
                    class="mt-8 space-y-7"
                >
                    @csrf

                    <section>
                        <h3 class="text-base font-semibold text-slate-900">
                            Informasi profil
                        </h3>

                        <div class="mt-4">
                            <label
                                for="name"
                                class="mb-2 block text-sm font-medium text-slate-700"
                            >
                                Nama lengkap
                            </label>

                            <input
                                id="name"
                                name="name"
                                type="text"
                                value="{{ old('name', $user->name) }}"
                                maxlength="100"
                                autocomplete="name"
                                required
                                autofocus
                                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-100"
                            >

                            @error('name')
                                <p class="mt-2 text-sm text-rose-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="mt-4">
                            <label class="mb-2 block text-sm font-medium text-slate-700">
                                Bahasa aplikasi
                            </label>

                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <p class="text-sm font-medium text-slate-800">
                                    Bahasa Indonesia
                                </p>

                                <p class="mt-1 text-xs leading-5 text-slate-500">
                                    Dukungan bahasa lain dapat ditambahkan pada
                                    pengembangan berikutnya.
                                </p>
                            </div>

                            <input
                                type="hidden"
                                name="locale"
                                value="id"
                            >
                        </div>
                    </section>

                    <hr class="border-slate-200">

                    <section>
                        <h3 class="text-base font-semibold text-slate-900">
                            Regional dan keuangan
                        </h3>

                        <div class="mt-4 grid gap-5 sm:grid-cols-2">
                            <div>
                                <label
                                    for="currency_code"
                                    class="mb-2 block text-sm font-medium text-slate-700"
                                >
                                    Mata uang utama
                                </label>

                                <select
                                    id="currency_code"
                                    name="currency_code"
                                    required
                                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100"
                                >
                                    @php
                                        $selectedCurrency = old(
                                            'currency_code',
                                            $preference?->currency_code
                                                ?? $defaults['currency_code']
                                        );
                                    @endphp

                                    <option
                                        value="IDR"
                                        @selected($selectedCurrency === 'IDR')
                                    >
                                        IDR — Rupiah Indonesia
                                    </option>

                                    <option
                                        value="USD"
                                        @selected($selectedCurrency === 'USD')
                                    >
                                        USD — Dolar Amerika
                                    </option>

                                    <option
                                        value="SGD"
                                        @selected($selectedCurrency === 'SGD')
                                    >
                                        SGD — Dolar Singapura
                                    </option>
                                </select>

                                @error('currency_code')
                                    <p class="mt-2 text-sm text-rose-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div>
                                <label
                                    for="timezone"
                                    class="mb-2 block text-sm font-medium text-slate-700"
                                >
                                    Zona waktu
                                </label>

                                @php
                                    $selectedTimezone = old(
                                        'timezone',
                                        $preference?->timezone
                                            ?? $defaults['timezone']
                                    );
                                @endphp

                                <select
                                    id="timezone"
                                    name="timezone"
                                    required
                                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100"
                                >
                                    <option
                                        value="Asia/Jakarta"
                                        @selected($selectedTimezone === 'Asia/Jakarta')
                                    >
                                        WIB — Asia/Jakarta
                                    </option>

                                    <option
                                        value="Asia/Makassar"
                                        @selected($selectedTimezone === 'Asia/Makassar')
                                    >
                                        WITA — Asia/Makassar
                                    </option>

                                    <option
                                        value="Asia/Jayapura"
                                        @selected($selectedTimezone === 'Asia/Jayapura')
                                    >
                                        WIT — Asia/Jayapura
                                    </option>
                                </select>

                                @error('timezone')
                                    <p class="mt-2 text-sm text-rose-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        </div>
                    </section>

                    <hr class="border-slate-200">

                    <section>
                        <h3 class="text-base font-semibold text-slate-900">
                            Tampilan tanggal dan waktu
                        </h3>

                        <div class="mt-4 grid gap-5 sm:grid-cols-2">
                            <div>
                                <label
                                    for="date_format"
                                    class="mb-2 block text-sm font-medium text-slate-700"
                                >
                                    Format tanggal
                                </label>

                                @php
                                    $selectedDateFormat = old(
                                        'date_format',
                                        $preference?->date_format
                                            ?? $defaults['date_format']
                                    );
                                @endphp

                                <select
                                    id="date_format"
                                    name="date_format"
                                    required
                                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100"
                                >
                                    <option
                                        value="d/m/Y"
                                        @selected($selectedDateFormat === 'd/m/Y')
                                    >
                                        31/07/2026
                                    </option>

                                    <option
                                        value="Y-m-d"
                                        @selected($selectedDateFormat === 'Y-m-d')
                                    >
                                        2026-07-31
                                    </option>

                                    <option
                                        value="d M Y"
                                        @selected($selectedDateFormat === 'd M Y')
                                    >
                                        31 Jul 2026
                                    </option>
                                </select>

                                @error('date_format')
                                    <p class="mt-2 text-sm text-rose-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div>
                                <label
                                    for="time_format"
                                    class="mb-2 block text-sm font-medium text-slate-700"
                                >
                                    Format waktu
                                </label>

                                @php
                                    $selectedTimeFormat = old(
                                        'time_format',
                                        $preference?->time_format
                                            ?? $defaults['time_format']
                                    );
                                @endphp

                                <select
                                    id="time_format"
                                    name="time_format"
                                    required
                                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100"
                                >
                                    <option
                                        value="H:i"
                                        @selected($selectedTimeFormat === 'H:i')
                                    >
                                        21:55 — Format 24 jam
                                    </option>

                                    <option
                                        value="h:i A"
                                        @selected($selectedTimeFormat === 'h:i A')
                                    >
                                        09:55 PM — Format 12 jam
                                    </option>
                                </select>

                                @error('time_format')
                                    <p class="mt-2 text-sm text-rose-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-5">
                            <label
                                for="week_starts_on"
                                class="mb-2 block text-sm font-medium text-slate-700"
                            >
                                Hari pertama dalam minggu
                            </label>

                            @php
                                $selectedWeekStart = (int) old(
                                    'week_starts_on',
                                    $preference?->week_starts_on
                                        ?? $defaults['week_starts_on']
                                );
                            @endphp

                            <select
                                id="week_starts_on"
                                name="week_starts_on"
                                required
                                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100"
                            >
                                <option
                                    value="1"
                                    @selected($selectedWeekStart === 1)
                                >
                                    Senin
                                </option>

                                <option
                                    value="7"
                                    @selected($selectedWeekStart === 7)
                                >
                                    Minggu
                                </option>
                            </select>

                            @error('week_starts_on')
                                <p class="mt-2 text-sm text-rose-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </section>

                    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs leading-5 text-slate-400">
                            Preferensi dapat diubah kembali melalui pengaturan.
                        </p>

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-xl bg-blue-700 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-200 active:translate-y-px"
                        >
                            Simpan dan lanjutkan
                            <span class="ml-2" aria-hidden="true">→</span>
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
