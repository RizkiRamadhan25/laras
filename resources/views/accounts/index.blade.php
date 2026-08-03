@extends('layouts.app')

@section('title', 'Rekening — Laras')
@section('page-title', 'Keuangan')
@section(
    'page-description',
    'Kelola rekening, saldo awal, dan urutan akun keuangan.'
)

@section('content')
    @php
        $currencyCode = $user->preference?->currency_code ?? 'IDR';
    @endphp

    <section>
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-semibold text-laras-700">
                    Akun keuangan
                </p>

                <h1 class="mt-2 text-3xl font-semibold tracking-tight">
                    Rekening dan saldo
                </h1>

                <p class="mt-3 max-w-2xl leading-7 text-slate-500">
                    Kelola seluruh rekening yang digunakan untuk mencatat
                    pemasukan, pengeluaran, dan transfer.
                </p>
            </div>

            <a
                href="{{ route('accounts.create') }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-laras-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-laras-800 focus:outline-none focus:ring-4 focus:ring-laras-200"
            >
                <i data-lucide="plus" class="size-4"></i>
                Tambah rekening
            </a>
        </div>

        <div class="mt-7 grid gap-4 sm:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                <p class="text-sm text-slate-500">
                    Total saldo aktif
                </p>

                <p class="mt-2 text-2xl font-semibold">
                    <span class="text-sm text-slate-400">
                        {{ $currencyCode }}
                    </span>
                    {{ number_format(
                        (float) $totalBalance,
                        0,
                        ',',
                        '.'
                    ) }}
                </p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                <p class="text-sm text-slate-500">
                    Rekening aktif
                </p>

                <p class="mt-2 text-3xl font-semibold">
                    {{ $accounts->count() }}
                </p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                <p class="text-sm text-slate-500">
                    Rekening diarsipkan
                </p>

                <p class="mt-2 text-3xl font-semibold">
                    {{ $archivedAccounts->count() }}
                </p>
            </article>
        </div>
    </section>

    <section class="mt-7">
        <div class="mb-4">
            <h2 class="text-lg font-semibold">
                Rekening aktif
            </h2>

            <p class="mt-1 text-sm text-slate-400">
                Gunakan tombol panah untuk mengubah urutan.
            </p>
        </div>

        <div class="space-y-4">
            @foreach ($accounts as $account)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras sm:p-6">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-center">
                        <div class="flex min-w-0 flex-1 items-center gap-4">
                            <span
                                class="size-3 shrink-0 rounded-full"
                                style="background-color: {{ $account->color }}"
                            ></span>

                            <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-600">
                                <i
                                    data-lucide="{{ $account->icon }}"
                                    class="size-5"
                                ></i>
                            </span>

                            <div class="min-w-0">
                                <p class="truncate font-semibold text-slate-900">
                                    {{ $account->name }}
                                </p>

                                <p class="mt-1 truncate text-sm text-slate-400">
                                    {{ $account->institution ?? $account->type->label() }}

                                    @if ($account->account_number_last_four)
                                        •••• {{ $account->account_number_last_four }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="lg:min-w-48 lg:text-right">
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                Saldo terkini
                            </p>

                            <p class="mt-1 font-semibold text-slate-900">
                                {{ $account->currency_code }}
                                {{ number_format(
                                    (float) $account->cached_balance,
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <form
                                method="POST"
                                action="{{ route(
                                    'accounts.move',
                                    $account->id
                                ) }}"
                            >
                                @csrf
                                @method('PATCH')

                                <input
                                    type="hidden"
                                    name="direction"
                                    value="up"
                                >

                                <button
                                    type="submit"
                                    class="flex size-10 items-center justify-center rounded-xl border border-slate-200 text-slate-500 transition hover:bg-slate-100 disabled:opacity-30"
                                    @disabled($loop->first)
                                    aria-label="Pindahkan ke atas"
                                >
                                    <i data-lucide="arrow-up" class="size-4"></i>
                                </button>
                            </form>

                            <form
                                method="POST"
                                action="{{ route(
                                    'accounts.move',
                                    $account->id
                                ) }}"
                            >
                                @csrf
                                @method('PATCH')

                                <input
                                    type="hidden"
                                    name="direction"
                                    value="down"
                                >

                                <button
                                    type="submit"
                                    class="flex size-10 items-center justify-center rounded-xl border border-slate-200 text-slate-500 transition hover:bg-slate-100 disabled:opacity-30"
                                    @disabled($loop->last)
                                    aria-label="Pindahkan ke bawah"
                                >
                                    <i data-lucide="arrow-down" class="size-4"></i>
                                </button>
                            </form>

                            <a
                                href="{{ route(
                                    'accounts.edit',
                                    $account->id
                                ) }}"
                                class="flex size-10 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:bg-slate-100"
                                aria-label="Edit rekening"
                            >
                                <i data-lucide="pencil" class="size-4"></i>
                            </a>

                            <form
                                method="POST"
                                action="{{ route(
                                    'accounts.destroy',
                                    $account->id
                                ) }}"
                                data-confirm
                                data-confirm-title="Arsipkan rekening?"
                                data-confirm-message="Rekening {{ $account->name }} akan dipindahkan dari daftar aktif. Riwayat keuangan tetap disimpan."
                                data-confirm-label="Arsipkan"
                                data-confirm-busy-label="Mengarsipkan..."
                                data-confirm-tone="warning"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="flex size-10 items-center justify-center rounded-xl border border-rose-200 text-rose-600 transition hover:bg-rose-50"
                                    aria-label="Arsipkan rekening"
                                >
                                    <i data-lucide="archive" class="size-4"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    @if ($archivedAccounts->isNotEmpty())
        <section class="mt-9 border-t border-slate-200 pt-8">
            <div class="mb-4">
                <h2 class="text-lg font-semibold">
                    Arsip
                </h2>

                <p class="mt-1 text-sm text-slate-400">
                    Rekening arsip tidak dihitung dalam total saldo aktif.
                </p>
            </div>

            <div class="space-y-3">
                @foreach ($archivedAccounts as $account)
                    <article class="flex flex-col gap-4 rounded-2xl border border-dashed border-slate-300 bg-white/60 p-5 sm:flex-row sm:items-center">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-slate-700">
                                {{ $account->name }}
                            </p>

                            <p class="mt-1 text-sm text-slate-400">
                                Diarsipkan
                                {{ $account->deleted_at?->diffForHumans() }}
                            </p>
                        </div>

                        <form
                            method="POST"
                            action="{{ route(
                                'accounts.restore',
                                $account->id
                            ) }}"
                        >
                            @csrf
                            @method('PATCH')

                            <button
                                type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                            >
                                <i
                                    data-lucide="archive-restore"
                                    class="size-4"
                                ></i>
                                Aktifkan kembali
                            </button>
                        </form>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
@endsection
