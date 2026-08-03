<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Laras')</title>

    <meta
        name="description"
        content="Laras — Selaraskan hari, tentukan langkah."
    >

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-50 text-slate-950 antialiased">
    <main class="min-h-screen lg:grid lg:grid-cols-[1.05fr_0.95fr]">
        <section
            class="relative hidden overflow-hidden bg-[#0b2a5b] px-12 py-10 text-white lg:flex lg:flex-col lg:justify-between"
        >
            <div
                class="pointer-events-none absolute inset-0 opacity-[0.08]"
                style="background-image:
                    linear-gradient(rgba(255,255,255,.8) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(255,255,255,.8) 1px, transparent 1px);
                    background-size: 52px 52px;"
            ></div>

            <div class="relative z-10 flex items-center gap-3">
                <div
                    class="flex size-11 items-center justify-center rounded-xl bg-white text-lg font-bold text-[#0b2a5b]"
                >
                    L
                </div>

                <div>
                    <p class="text-xl font-semibold tracking-tight">Laras</p>
                    <p class="text-sm text-blue-200">
                        Personal life management
                    </p>
                </div>
            </div>

            <div class="relative z-10 max-w-xl">
                <div class="mb-7 h-1.5 w-16 rounded-full bg-amber-400"></div>

                <h1 class="text-5xl font-semibold leading-[1.12] tracking-tight">
                    Selaraskan hari,
                    <span class="text-blue-200">tentukan langkah.</span>
                </h1>

                <p class="mt-6 max-w-lg text-base leading-8 text-blue-100">
                    Kelola kegiatan, prioritas, rekening, dan keputusan
                    harianmu dalam satu ruang yang teratur.
                </p>
            </div>

            <div class="relative z-10 flex items-center gap-3 text-sm text-blue-200">
                <span class="size-2 rounded-full bg-amber-400"></span>
                <span>Dirancang untuk penggunaan personal</span>
            </div>
        </section>

        <section class="flex min-h-screen items-center justify-center px-5 py-10 sm:px-10">
            <div class="w-full max-w-md">
                <div class="mb-10 flex items-center gap-3 lg:hidden">
                    <div
                        class="flex size-11 items-center justify-center rounded-xl bg-[#0b2a5b] font-bold text-white"
                    >
                        L
                    </div>

                    <div>
                        <p class="text-lg font-semibold">Laras</p>
                        <p class="text-sm text-slate-500">
                            Selaraskan hari, tentukan langkah.
                        </p>
                    </div>
                </div>

                @yield('content')
            </div>
        </section>
    </main>

    <x-ui.toast-container />
</body>
</html>
