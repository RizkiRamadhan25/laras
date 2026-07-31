@extends('layouts.auth')

@section('title', 'Password Baru — Laras')

@section('content')
    <header class="mb-8">
        <h1 class="text-3xl font-semibold tracking-tight">
            Buat password baru
        </h1>

        <p class="mt-3 leading-7 text-slate-500">
            Gunakan password yang kuat dan tidak digunakan pada akun lain.
        </p>
    </header>

    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf

        <input
            type="hidden"
            name="token"
            value="{{ $request->route('token') }}"
        >

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
                value="{{ old('email', $request->email) }}"
                autocomplete="email"
                required
                readonly
                class="w-full rounded-xl border border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-600"
            >

            @error('email')
                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label
                for="password"
                class="mb-2 block text-sm font-medium text-slate-700"
            >
                Password baru
            </label>

            <input
                id="password"
                name="password"
                type="password"
                autocomplete="new-password"
                required
                autofocus
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100"
            >

            @error('password')
                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label
                for="password_confirmation"
                class="mb-2 block text-sm font-medium text-slate-700"
            >
                Konfirmasi password
            </label>

            <input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                autocomplete="new-password"
                required
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100"
            >
        </div>

        <button
            type="submit"
            class="w-full rounded-xl bg-blue-700 px-5 py-3.5 text-sm font-semibold text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-200"
        >
            Simpan password baru
        </button>
    </form>
@endsection
