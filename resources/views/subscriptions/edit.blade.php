@extends('layouts.app')

@section('title', 'Edit Langganan — Laras')
@section('page-title', 'Edit langganan')
@section(
    'page-description',
    'Perbarui jadwal dan pengaturan pembayaran.'
)

@section('content')
    <div class="mx-auto max-w-5xl">
        <header class="mb-7">
            <p class="text-sm font-semibold text-laras-700">
                Langganan
            </p>

            <h1 class="mt-2 text-3xl font-semibold tracking-tight">
                Edit {{ $subscription->name }}
            </h1>
        </header>

        <form
            method="POST"
            action="{{ route(
                'subscriptions.update',
                $subscription->id
            ) }}"
            class="rounded-2xl border border-slate-200 bg-white p-6 shadow-laras sm:p-8"
        >
            @csrf
            @method('PUT')

            @include('subscriptions._form')
        </form>
    </div>
@endsection
