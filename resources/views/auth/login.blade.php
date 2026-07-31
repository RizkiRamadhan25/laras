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

    @if (session('status'))
        <div
            class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
            role="status"
        >
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
        @csrf

        <div>
            <label
                for="email"
                class="mb-2 block text-sm font-medium text-slate-700"
            >
                Email
            </label>

            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                autocomplete="email"
                required
                autofocus
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-100"
                placeholder="nama@email.com"
            >

            @error('email')
                <p class="mt-2 text-sm text-rose-600" role="alert">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div>
            <div class="mb-2 flex items-center justify-between gap-4">
                <label
                    for="password"
                    class="block text-sm font-medium text-slate-700"
                >
                    Password
                </label>

                <a
                    href="{{ route('password.request') }}"
                    class="text-sm font-medium text-blue-700 hover:text-blue-900"
                >
                    Lupa password?
                </a>
            </div>

            <input
                id="password"
                name="password"
                type="password"
                autocomplete="current-password"
                required
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-100"
                placeholder="Masukkan password"
            >

            @error('password')
                <p class="mt-2 text-sm text-rose-600" role="alert">
                    {{ $message }}
                </p>
            @enderror
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
