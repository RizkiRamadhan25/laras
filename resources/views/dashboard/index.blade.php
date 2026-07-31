<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Dashboard — Laras</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-50 text-slate-950">
    <main class="mx-auto flex min-h-screen max-w-5xl items-center px-5 py-12">
        <section class="w-full rounded-2xl border border-slate-200 bg-white p-7 shadow-sm sm:p-10">
            @if (session('status'))
                <div
                    class="mb-7 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800"
                    role="status"
                >
                    {{ session('status') }}
                </div>
            @endif

            {{-- Content --}}
            <div class="flex flex-col justify-between gap-8 sm:flex-row sm:items-start">
                <div>
                    <p class="text-sm font-semibold text-blue-700">
                        Autentikasi berhasil
                    </p>

                    <h1 class="mt-2 text-3xl font-semibold tracking-tight">
                        Selamat datang, {{ auth()->user()->name }}.
                    </h1>

                    <p class="mt-4 max-w-2xl leading-7 text-slate-500">
                        Dashboard ini masih bersifat sementara. Tahap berikutnya
                        adalah membuat onboarding dan struktur aplikasi Laras.
                    </p>

                    <dl class="mt-8 grid gap-4 text-sm sm:grid-cols-2">
                        <div class="rounded-xl bg-slate-50 p-4">
                            <dt class="text-slate-500">Email</dt>
                            <dd class="mt-1 font-medium">
                                {{ auth()->user()->email }}
                            </dd>
                        </div>

                        <div class="rounded-xl bg-slate-50 p-4">
                            <dt class="text-slate-500">Login terakhir</dt>
                            <dd class="mt-1 font-medium">
                                {{ auth()->user()->last_login_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') ?? 'Belum tercatat' }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button
                        type="submit"
                        class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                    >
                        Logout
                    </button>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
