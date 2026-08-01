@extends('layouts.app')

@section('title', 'Tambah Aktivitas — Laras')
@section('page-title', 'Tambah aktivitas')
@section(
    'page-description',
    'Catat tugas, acara, atau deadline baru.'
)

@section('content')
    <div class="mx-auto max-w-5xl">
        <header class="mb-7">
            <p class="text-sm font-semibold text-laras-700">
                Aktivitas
            </p>

            <h1 class="mt-2 text-3xl font-semibold tracking-tight">
                Tambah aktivitas baru
            </h1>

            <p class="mt-3 max-w-2xl leading-7 text-slate-500">
                Tentukan waktu, tingkat prioritas, dan estimasi durasi
                agar Laras dapat membantu mengurutkan fokusmu.
            </p>
        </header>

        <form
            method="POST"
            action="{{ route('activities.store') }}"
            class="rounded-2xl border border-slate-200 bg-white p-6 shadow-laras sm:p-8"
        >
            @csrf

            @include('activities._form')
        </form>
    </div>
@endsection
