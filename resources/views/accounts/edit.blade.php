@extends('layouts.app')

@section('title', 'Edit Rekening — Laras')
@section('page-title', 'Edit rekening')
@section(
    'page-description',
    'Perbarui identitas dan saldo awal rekening.'
)

@section('content')
    <div class="mx-auto max-w-4xl">
        <header class="mb-6">
            <p class="text-sm font-semibold text-laras-700">
                Keuangan
            </p>

            <h1 class="mt-2 text-3xl font-semibold tracking-tight">
                Edit {{ $account->name }}
            </h1>

            <p class="mt-3 text-slate-500">
                Perubahan saldo awal akan diterapkan pada saldo terkini
                menggunakan nilai selisihnya.
            </p>
        </header>

        <form
            method="POST"
            action="{{ route('accounts.update', $account->id) }}"
            class="rounded-2xl border border-slate-200 bg-white p-6 shadow-laras sm:p-8"
        >
            @csrf
            @method('PUT')

            @include('accounts._form')
        </form>
    </div>
@endsection
