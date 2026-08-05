@extends('layouts.auth')

@section('title', 'Lupa Kata Sandi — Laras')

@section('content')
    <header class="mb-8">
        <a
            href="{{ route('login') }}"
            class="mb-6 inline-flex text-sm font-medium text-blue-700 transition hover:text-blue-900"
        >
            ← Kembali ke login
        </a>

        <h1 class="text-3xl font-semibold tracking-tight text-slate-950">
            Atur ulang kata sandi
        </h1>

        <p class="mt-3 leading-7 text-slate-500">
            Masukkan email akun Laras. Tautan pengaturan ulang
            akan dikirim melalui email.
        </p>
    </header>

    <form
        method="POST"
        action="{{ route('password.email') }}"
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
            hint="Gunakan alamat email yang terhubung dengan akun Laras."
        />

        <button
            type="submit"
            class="w-full rounded-xl bg-blue-700 px-5 py-3.5 text-sm font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-200"
        >
            Kirim tautan pengaturan ulang
        </button>
    </form>
@endsection
