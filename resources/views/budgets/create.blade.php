@extends('layouts.app')

@section(
    'title',
    'Tambah Anggaran'
)

@section('content')
    <div class="mx-auto max-w-3xl">
        <header class="mb-7">
            <a
                href="{{ route(
                    'budgets.index'
                ) }}"
                class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-slate-900"
            >
                <i
                    data-lucide="arrow-left"
                    class="size-4"
                ></i>

                Kembali ke anggaran
            </a>

            <h1 class="mt-5 text-3xl font-semibold tracking-tight text-slate-950">
                Tambah anggaran
            </h1>

            <p class="mt-2 text-sm leading-6 text-slate-500">
                Tentukan batas pengeluaran untuk
                kategori tertentu.
            </p>
        </header>

        @include(
            'budgets.partials.form',
            [
                'categories' =>
                    $categories,
            ]
        )
    </div>
@endsection
