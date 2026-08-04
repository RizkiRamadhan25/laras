@extends('layouts.app')

@section('title', 'Pengaturan — Laras')
@section('page-title', 'Pengaturan')
@section(
    'page-description',
    'Kelola profil dan preferensi personal Laras.'
)

@section('content')
    @php
        $selectedTimezone = old(
            'timezone',
            $preference?->timezone
                ?? 'Asia/Jakarta'
        );

        $selectedDateFormat = old(
            'date_format',
            $preference?->date_format
                ?? 'd/m/Y'
        );

        $selectedTimeFormat = old(
            'time_format',
            $preference?->time_format
                ?? 'H:i'
        );

        $selectedCurrency = old(
            'currency_code',
            $preference?->currency_code
                ?? 'IDR'
        );

        $selectedWeekStart = (int) old(
            'week_starts_on',
            $preference?->week_starts_on
                ?? 1
        );
    @endphp

    <div class="mx-auto max-w-6xl">
        <section>
            <p class="text-sm font-semibold text-laras-700">
                Akun personal
            </p>

            <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">
                Sesuaikan Laras dengan kebutuhanmu.
            </h1>

            <p class="mt-3 max-w-2xl leading-7 text-slate-500">
                Perbarui identitas tampilan serta cara Laras
                menampilkan tanggal, waktu, mata uang, dan minggu.
            </p>
        </section>

        @include(
            'settings.partials.navigation'
        )


        <section class="mt-8 grid gap-6 lg:grid-cols-[0.72fr_1.28fr]">
            <aside class="space-y-6">
                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-laras">
                    <div class="flex items-center gap-4">
                        <x-ui.user-avatar
                            :user="$user"
                            size="xl"
                            rounded="2xl"
                            class="shadow-sm"
                        />

                        <div class="min-w-0">
                            <h2 class="truncate text-lg font-semibold text-slate-950">
                                {{ $user->name }}
                            </h2>

                            <p class="mt-1 truncate text-sm text-slate-400">
                                {{ $user->email }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-2">
                        <form
                            method="POST"
                            action="{{ route(
                                'settings.photo.update'
                            ) }}"
                            enctype="multipart/form-data"
                        >
                            @csrf
                            @method('PATCH')

                            <input
                                id="profile-camera-photo"
                                name="photo"
                                type="file"
                                accept="image/*"
                                capture="user"
                                class="sr-only"
                                onchange="
                                    if (this.files.length > 0) {
                                        this.form.submit();
                                    }
                                "
                            >

                            <label
                                for="profile-camera-photo"
                                class="inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-laras-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-laras-800"
                            >
                                <i
                                    data-lucide="camera"
                                    class="size-4"
                                ></i>

                                Ambil foto
                            </label>
                        </form>

                        <form
                            method="POST"
                            action="{{ route(
                                'settings.photo.update'
                            ) }}"
                            enctype="multipart/form-data"
                        >
                            @csrf
                            @method('PATCH')

                            <input
                                id="profile-gallery-photo"
                                name="photo"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                class="sr-only"
                                onchange="
                                    if (this.files.length > 0) {
                                        this.form.submit();
                                    }
                                "
                            >

                            <label
                                for="profile-gallery-photo"
                                class="inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                            >
                                <i
                                    data-lucide="image"
                                    class="size-4"
                                ></i>

                                Pilih dari galeri atau file
                            </label>
                        </form>

                        @if ($user->profilePhotoUrl() !== null)
                            <form
                                method="POST"
                                action="{{ route(
                                    'settings.photo.destroy'
                                ) }}"
                                data-confirm
                                data-confirm-title="Hapus foto profil?"
                                data-confirm-message="Foto profil saat ini akan dihapus dan avatar kembali menggunakan inisial nama."
                                data-confirm-label="Hapus foto"
                                data-confirm-busy-label="Menghapus..."
                                data-confirm-tone="danger"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-rose-600 transition hover:bg-rose-50"
                                >
                                    <i
                                        data-lucide="trash-2"
                                        class="size-4"
                                    ></i>

                                    Hapus foto
                                </button>
                            </form>
                        @endif

                        <p class="text-xs leading-5 text-slate-400">
                            JPG, PNG, atau WebP. Maksimal 5 MB.
                        </p>

                        @error('photo')
                            <p class="text-sm text-rose-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <dl class="mt-6 space-y-4 border-t border-slate-100 pt-5">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Zona waktu
                            </dt>

                            <dd class="mt-1 text-sm font-semibold text-slate-800">
                                {{ $timezones[
                                    $selectedTimezone
                                ]['short_label'] ?? 'WIB' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Mata uang utama
                            </dt>

                            <dd class="mt-1 text-sm font-semibold text-slate-800">
                                {{ $selectedCurrency }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Awal minggu
                            </dt>

                            <dd class="mt-1 text-sm font-semibold text-slate-800">
                                {{ $weekStarts[
                                    $selectedWeekStart
                                ] ?? 'Senin' }}
                            </dd>
                        </div>
                    </dl>
                </article>

                <article class="rounded-2xl border border-blue-200 bg-blue-50 p-5">
                    <div class="flex items-start gap-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700">
                            <i
                                data-lucide="wallet"
                                class="size-5"
                            ></i>
                        </span>

                        <div>
                            <h2 class="font-semibold text-blue-950">
                                Tentang mata uang
                            </h2>

                            <p class="mt-2 text-sm leading-6 text-blue-700">
                                Mengubah mata uang utama tidak
                                mengonversi saldo, rekening,
                                transaksi, atau langganan lama.
                            </p>
                        </div>
                    </div>
                </article>
            </aside>

            <div class="space-y-6">
                <form
                    id="profile"
                    method="POST"
                    action="{{ route(
                        'settings.profile.update'
                    ) }}"
                    data-settings-section
                    data-track-unsaved
                    data-modern-profile-form
                    class="scroll-mt-44 rounded-2xl border border-slate-200 bg-white p-6 shadow-laras sm:p-8"
                >
                    @csrf
                    @method('PATCH')

                    <header class="border-b border-slate-100 pb-5">
                        <div class="flex items-start gap-3">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-laras-100 text-laras-700">
                                <i
                                    data-lucide="circle-user-round"
                                    class="size-5"
                                ></i>
                            </span>

                            <div>
                                <h2 class="font-semibold text-slate-950">
                                    Informasi profil
                                </h2>

                                <p class="mt-1 text-sm text-slate-400">
                                    Nama akan tampil pada sidebar,
                                    topbar, dan halaman akun.
                                </p>
                            </div>
                        </div>
                    </header>

                    <div class="mt-6 space-y-5">
                        <x-ui.floating-input
                            name="name"
                            label="Nama lengkap"
                            :value="$user->name"
                            :required="true"
                            autocomplete="name"
                            minlength="2"
                            maxlength="120"
                        />

                        <x-ui.floating-input
                            name="profile_email_display"
                            id="profile_email"
                            type="email"
                            label="Alamat email"
                            :value="$user->email"
                            hint="Perubahan email akan tersedia pada pengaturan keamanan."
                            autocomplete="email"
                            disabled
                        />
                    </div>

                    <div class="mt-7 flex flex-col justify-between gap-4 border-t border-slate-100 pt-6 sm:flex-row sm:items-center">
                        <p
                            data-unsaved-indicator
                            class="hidden text-sm font-medium text-amber-700"
                            aria-hidden="true"
                        >
                            Perubahan profil belum disimpan.
                        </p>

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-laras-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-laras-800"
                        >
                            <i
                                data-lucide="check"
                                class="size-4"
                            ></i>

                            Simpan profil
                        </button>
                    </div>
                </form>

                <form
                    id="preferences"
                    method="POST"
                    action="{{ route(
                        'settings.preferences.update'
                    ) }}"
                    data-settings-section
                    data-track-unsaved
                    data-modern-preferences-form
                    class="scroll-mt-44 rounded-2xl border border-slate-200 bg-white p-6 shadow-laras sm:p-8"
                >
                    @csrf
                    @method('PATCH')

                    <header class="border-b border-slate-100 pb-5">
                        <div class="flex items-start gap-3">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-violet-100 text-violet-700">
                                <i
                                    data-lucide="settings"
                                    class="size-5"
                                ></i>
                            </span>

                            <div>
                                <h2 class="font-semibold text-slate-950">
                                    Preferensi tampilan
                                </h2>

                                <p class="mt-1 text-sm text-slate-400">
                                    Atur cara Laras membaca waktu dan data finansial.
                                </p>
                            </div>
                        </div>
                    </header>

                    @if (
                        $errors->hasAny([
                            'timezone',
                            'date_format',
                            'time_format',
                            'currency_code',
                            'week_starts_on',
                        ])
                    )
                        <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4">
                            <p class="text-sm font-semibold text-rose-800">
                                Periksa kembali preferensi yang dipilih.
                            </p>
                        </div>
                    @endif

                    <div class="mt-6 space-y-5">
                        <x-ui.floating-select
                            name="timezone"
                            label="Zona waktu"
                            :required="true"
                            :hint="$timezones[$selectedTimezone]['description'] ?? ''"
                        >
                            @foreach (
                                $timezones
                                as $value => $timezone
                            )
                                <option
                                    value="{{ $value }}"
                                    @selected(
                                        $selectedTimezone
                                            === $value
                                    )
                                >
                                    {{ $timezone['short_label'] }}
                                    — {{ $timezone['label'] }}
                                </option>
                            @endforeach
                        </x-ui.floating-select>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-ui.floating-select
                                name="date_format"
                                label="Format tanggal"
                                :required="true"
                            >
                                @foreach (
                                    $dateFormats
                                    as $value => $preview
                                )
                                    <option
                                        value="{{ $value }}"
                                        @selected(
                                            $selectedDateFormat
                                                === $value
                                        )
                                    >
                                        {{ $preview }}
                                    </option>
                                @endforeach
                            </x-ui.floating-select>

                            <x-ui.floating-select
                                name="time_format"
                                label="Format waktu"
                                :required="true"
                            >
                                @foreach (
                                    $timeFormats
                                    as $value => $preview
                                )
                                    <option
                                        value="{{ $value }}"
                                        @selected(
                                            $selectedTimeFormat
                                                === $value
                                        )
                                    >
                                        {{ $preview }}
                                    </option>
                                @endforeach
                            </x-ui.floating-select>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-ui.floating-select
                                name="currency_code"
                                label="Mata uang utama"
                                :required="true"
                                hint="Mata uang hanya mengatur tampilan data baru dan tidak mengonversi histori."
                            >
                                @foreach (
                                    $currencies
                                    as $code => $currency
                                )
                                    <option
                                        value="{{ $code }}"
                                        @selected(
                                            $selectedCurrency
                                                === $code
                                        )
                                    >
                                        {{ $code }}
                                        — {{ $currency['name'] }}
                                        ({{ $currency['symbol'] }})
                                    </option>
                                @endforeach
                            </x-ui.floating-select>

                            <x-ui.floating-select
                                name="week_starts_on"
                                label="Awal minggu"
                                :required="true"
                            >
                                @foreach (
                                    $weekStarts
                                    as $value => $label
                                )
                                    <option
                                        value="{{ $value }}"
                                        @selected(
                                            $selectedWeekStart
                                                === (int) $value
                                        )
                                    >
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </x-ui.floating-select>
                        </div>
                    </div>

                    <div class="mt-7 flex flex-col justify-between gap-4 border-t border-slate-100 pt-6 sm:flex-row sm:items-center">
                        <p
                            data-unsaved-indicator
                            class="hidden text-sm font-medium text-amber-700"
                            aria-hidden="true"
                        >
                            Perubahan preferensi belum disimpan.
                        </p>

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-laras-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-laras-800"
                        >
                            <i
                                data-lucide="check"
                                class="size-4"
                            ></i>

                            Simpan preferensi
                        </button>
                    </div>
                </form>
            </div>
        </section>
        @include(
            'settings.partials.security'
        )

        @include(
            'settings.partials.data-privacy'
        )
    </div>
@endsection
