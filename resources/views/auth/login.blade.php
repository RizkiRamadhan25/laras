@extends('layouts.auth')

@section('title', 'Masuk — Laras')

@section('content')
    <header class="mb-8">
        <p class="mb-2 text-sm font-semibold text-blue-700">
            Selamat datang kembali
        </p>

        <h1 class="text-3xl font-semibold tracking-tight text-slate-950">
            Masuk ke Laras
        </h1>

        <p class="mt-3 leading-7 text-slate-500">
            Gunakan akun pribadimu untuk melanjutkan.
        </p>
    </header>

    <form
        method="POST"
        action="{{ route('login.store') }}"
        class="space-y-5"
    >
        @csrf

        <x-ui.floating-input
            name="email"
            label="Email"
            type="email"
            autocomplete="email"
            required
            autofocus
            inputmode="email"
        />

        <div>
            <x-ui.password-input
                name="password"
                label="Kata sandi"
                autocomplete="current-password"
                required
            />

            <div class="mt-2 flex justify-end">
                <a
                    href="{{ route('password.request') }}"
                    class="text-sm font-medium text-blue-700 transition hover:text-blue-900"
                >
                    Lupa kata sandi?
                </a>
            </div>
        </div>

        <label class="flex cursor-pointer items-center gap-3">
            <input
                name="remember"
                type="checkbox"
                value="1"
                @checked(old('remember'))
                class="size-4 rounded border-slate-300 text-blue-700 focus:ring-blue-600"
            >

            <span class="text-sm text-slate-600">
                Tetap masuk di perangkat ini
            </span>
        </label>

        <button
            type="submit"
            class="flex w-full items-center justify-center rounded-xl bg-blue-700 px-5 py-3.5 text-sm font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-200 active:translate-y-px"
        >
            Masuk
        </button>
    </form>

    <p class="mt-8 text-center text-xs leading-6 text-slate-400">
        Laras adalah aplikasi personal. Registrasi publik tidak tersedia.
    </p>
@endsection
