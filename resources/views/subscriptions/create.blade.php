@extends('layouts.app')

@section('title', 'Tambah Langganan — Laras')
@section('page-title', 'Tambah langganan')
@section(
    'page-description',
    'Atur jadwal dan sumber pembayaran langganan.'
)

@section('content')
    <div class="mx-auto max-w-5xl">
        <header class="mb-7">
            <p class="text-sm font-semibold text-laras-700">
                Langganan
            </p>

            <h1 class="mt-2 text-3xl font-semibold tracking-tight">
                Tambah langganan baru
            </h1>

            <p class="mt-3 max-w-2xl leading-7 text-slate-500">
                Tambahkan layanan berulang agar Laras dapat
                mengingatkan dan mencatat tagihannya.
            </p>
        </header>

        <form
            method="POST"
            action="{{ route(
                'subscriptions.store'
            ) }}"
            class="rounded-2xl border border-slate-200 bg-white p-6 shadow-laras sm:p-8"
        >
            @csrf

            @include('subscriptions._form')
        </form>
    </div>
@endsection
