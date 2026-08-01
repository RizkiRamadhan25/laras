@extends('layouts.app')

@section('title', 'Tambah Rekening — Laras')
@section('page-title', 'Tambah rekening')
@section(
    'page-description',
    'Tambahkan rekening bank, dompet digital, investasi, atau uang tunai.'
)

@section('content')
    <div class="mx-auto max-w-4xl">
        <header class="mb-6">
            <p class="text-sm font-semibold text-laras-700">
                Keuangan
            </p>

            <h1 class="mt-2 text-3xl font-semibold tracking-tight">
                Tambah rekening baru
            </h1>

            <p class="mt-3 text-slate-500">
                Rekening akan menggunakan mata uang utama dari preferensi
                Laras.
            </p>
        </header>

        <form
            method="POST"
            action="{{ route('accounts.store') }}"
            class="rounded-2xl border border-slate-200 bg-white p-6 shadow-laras sm:p-8"
        >
            @csrf

            @include('accounts._form')
        </form>
    </div>
@endsection
