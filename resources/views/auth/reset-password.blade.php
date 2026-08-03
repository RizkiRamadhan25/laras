@extends('layouts.auth')

@section('title', 'Kata Sandi Baru — Laras')

@section('content')
    <header class="mb-8">
        <h1 class="text-3xl font-semibold tracking-tight text-slate-950">
            Buat kata sandi baru
        </h1>

        <p class="mt-3 leading-7 text-slate-500">
            Gunakan kata sandi yang kuat dan tidak digunakan
            pada akun lain.
        </p>
    </header>

    <form
        method="POST"
        action="{{ route('password.update') }}"
        class="space-y-5"
    >
        @csrf

        <input
            type="hidden"
            name="token"
            value="{{ $request->route('token') }}"
        >

        <x-ui.floating-input
            name="email"
            label="Email"
            type="email"
            :value="$request->email"
            autocomplete="email"
            required
            readonly
        />

        <x-ui.password-input
            name="password"
            label="Kata sandi baru"
            autocomplete="new-password"
            minlength="8"
            required
            autofocus
        />

        <x-ui.password-requirements for="password" />

        <x-ui.password-input
            name="password_confirmation"
            label="Konfirmasi kata sandi"
            autocomplete="new-password"
            minlength="8"
            required
        />

        <button
            type="submit"
            class="w-full rounded-xl bg-blue-700 px-5 py-3.5 text-sm font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-200"
        >
            Simpan kata sandi baru
        </button>
    </form>
@endsection
