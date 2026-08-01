@extends('layouts.app')

@section('title', 'Edit Aktivitas — Laras')
@section('page-title', 'Edit aktivitas')
@section(
    'page-description',
    'Perbarui jadwal, prioritas, dan rincian aktivitas.'
)

@section('content')
    <div class="mx-auto max-w-5xl">
        <header class="mb-7">
            <p class="text-sm font-semibold text-laras-700">
                Aktivitas
            </p>

            <h1 class="mt-2 text-3xl font-semibold tracking-tight">
                Edit {{ $activity->title }}
            </h1>

            <p class="mt-3 max-w-2xl leading-7 text-slate-500">
                Aktivitas yang telah selesai atau dibatalkan perlu
                dibuka kembali sebelum dapat diedit.
            </p>
        </header>

        <form
            method="POST"
            action="{{ route(
                'activities.update',
                $activity->id
            ) }}"
            class="rounded-2xl border border-slate-200 bg-white p-6 shadow-laras sm:p-8"
        >
            @csrf
            @method('PUT')

            @include('activities._form')
        </form>
    </div>
@endsection
