@extends('layouts.auth')

@section('title', 'Lupa Password — Laras')

@section('content')
    <header class="mb-8">
        <a
            href="{{ route('login') }}"
            class="mb-6 inline-flex text-sm font-medium text-blue-700 hover:text-blue-900"
        >
            ← Kembali ke login
        </a>

        <h1 class="text-3xl font-semibold tracking-tight">
            Atur ulang password
        </h1>

        <p class="mt-3 leading-7 text-slate-500">
            Masukkan email akun Laras. Tautan pengaturan ulang akan dikirim
            melalui email.
        </p>
    </header>


    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
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
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100"
            >

            @error('email')
                <p class="mt-2 text-sm text-rose-600" role="alert">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <button
            type="submit"
            class="w-full rounded-xl bg-blue-700 px-5 py-3.5 text-sm font-semibold text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-200"
        >
            Kirim tautan reset
        </button>
    </form>
@endsection
