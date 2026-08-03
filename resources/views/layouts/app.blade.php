<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="h-full"
>
<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <meta
        name="description"
        content="@yield(
            'meta-description',
            'Laras — Selaraskan hari, tentukan langkah.'
        )"
    >

    <title>
        @yield('title', 'Laras')
    </title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('head')
</head>

<body
    x-data="{
        sidebarOpen: false,
    }"
    x-on:keydown.escape.window="sidebarOpen = false"
    class="min-h-full bg-slate-50 text-slate-950 antialiased"
>
    <div class="min-h-screen">
        <div
            x-cloak
            x-show="sidebarOpen"
            x-transition:enter="transition-opacity duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-on:click="sidebarOpen = false"
            class="fixed inset-0 z-40 bg-slate-950/45 backdrop-blur-[2px] lg:hidden"
            aria-hidden="true"
        ></div>

        @include('partials.app-sidebar')

        <div class="min-h-screen lg:pl-72">
            @include('partials.app-topbar')

            <main class="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <div class="mx-auto w-full max-w-[1600px]">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <x-ui.toast-container />
    <x-ui.confirm-dialog />

    @stack('scripts')
</body>
</html>
