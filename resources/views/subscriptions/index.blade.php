@extends('layouts.app')

@section('title', 'Langganan — Laras')
@section('page-title', 'Langganan')
@section(
    'page-description',
    'Kelola biaya dan jadwal pembayaran berulang.'
)

@section('content')
    <section>
        <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
            <div>
                <p class="text-sm font-semibold text-laras-700">
                    Pembayaran berulang
                </p>

                <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">
                    Kelola seluruh langganan.
                </h1>

                <p class="mt-3 max-w-2xl leading-7 text-slate-500">
                    Pantau biaya rutin dan ketahui tagihan yang akan datang.
                </p>
            </div>

            <a
                href="{{ route(
                    'subscriptions.create'
                ) }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-laras-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-laras-800"
            >
                <i
                    data-lucide="plus"
                    class="size-4"
                ></i>

                Tambah langganan
            </a>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                <p class="text-sm text-slate-500">
                    Langganan aktif
                </p>

                <p class="mt-2 text-3xl font-semibold">
                    {{ $summary['active'] }}
                </p>
            </article>

            <article class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <p class="text-sm text-amber-700">
                    Tagihan tujuh hari
                </p>

                <p class="mt-2 text-3xl font-semibold text-amber-900">
                    {{ $summary['due_soon'] }}
                </p>
            </article>

            <article class="rounded-2xl border border-blue-200 bg-blue-50 p-5">
                <p class="text-sm text-blue-700">
                    Estimasi per bulan
                </p>

                <p class="mt-2 text-2xl font-semibold text-blue-950">
                    IDR
                    {{ number_format(
                        (float) $summary['monthly'],
                        0,
                        ',',
                        '.'
                    ) }}
                </p>
            </article>

            <article class="rounded-2xl border border-violet-200 bg-violet-50 p-5">
                <p class="text-sm text-violet-700">
                    Estimasi per tahun
                </p>

                <p class="mt-2 text-2xl font-semibold text-violet-950">
                    IDR
                    {{ number_format(
                        (float) $summary['yearly'],
                        0,
                        ',',
                        '.'
                    ) }}
                </p>
            </article>
        </div>
    </section>

    <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
        <form
            method="GET"
            action="{{ route(
                'subscriptions.index'
            ) }}"
            class="grid gap-4 md:grid-cols-2 xl:grid-cols-5"
        >
            <div class="xl:col-span-2">
                <label
                    for="search"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Pencarian
                </label>

                <input
                    id="search"
                    name="search"
                    type="search"
                    maxlength="100"
                    value="{{ $filters['search'] ?? '' }}"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none"
                    placeholder="Netflix, Spotify, Google..."
                >
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Status
                </label>

                <select
                    name="status"
                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm"
                >
                    <option value="">
                        Semua
                    </option>

                    @foreach ($statuses as $status)
                        <option
                            value="{{ $status->value }}"
                            @selected(
                                ($filters['status'] ?? '')
                                    === $status->value
                            )
                        >
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Rekening
                </label>

                <select
                    name="account_id"
                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm"
                >
                    <option value="">
                        Semua
                    </option>

                    @foreach ($accounts as $account)
                        <option
                            value="{{ $account->id }}"
                            @selected(
                                (string) (
                                    $filters[
                                        'account_id'
                                    ] ?? ''
                                ) === (string) $account->id
                            )
                        >
                            {{ $account->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Kategori
                </label>

                <select
                    name="finance_category_id"
                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm"
                >
                    <option value="">
                        Semua
                    </option>

                    @foreach ($categories as $category)
                        <option
                            value="{{ $category->id }}"
                            @selected(
                                (string) (
                                    $filters[
                                        'finance_category_id'
                                    ] ?? ''
                                ) === (string) $category->id
                            )
                        >
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2 md:col-span-2 xl:col-span-5">
                <button
                    type="submit"
                    class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white"
                >
                    Terapkan
                </button>

                <a
                    href="{{ route(
                        'subscriptions.index'
                    ) }}"
                    class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700"
                >
                    Reset
                </a>
            </div>
        </form>
    </section>

    <section class="mt-6">
        @if ($subscriptions->isEmpty())
            <div class="rounded-2xl border border-slate-200 bg-white px-6 py-16 text-center shadow-laras">
                <i
                    data-lucide="repeat-2"
                    class="mx-auto size-8 text-slate-400"
                ></i>

                <h2 class="mt-4 font-semibold">
                    Tidak ada langganan
                </h2>

                <p class="mt-2 text-sm text-slate-500">
                    Belum ada langganan yang sesuai dengan filter.
                </p>
            </div>
        @else
            <div class="grid gap-4 xl:grid-cols-2">
                @foreach ($subscriptions as $subscription)
                    @php
                        $statusClass = match (
                            $subscription->status
                        ) {
                            \App\Enums\SubscriptionStatus::Active =>
                                'bg-emerald-100 text-emerald-700',

                            \App\Enums\SubscriptionStatus::Paused =>
                                'bg-amber-100 text-amber-700',

                            \App\Enums\SubscriptionStatus::Cancelled =>
                                'bg-slate-100 text-slate-600',

                            \App\Enums\SubscriptionStatus::Expired =>
                                'bg-rose-100 text-rose-700',
                        };

                        $dueSoon =
                            $subscription->status
                                === \App\Enums\SubscriptionStatus::Active
                            && $subscription->next_billing_on
                                !== null
                            && $subscription->next_billing_on
                                ->between(
                                    $today,
                                    $today->addDays(7)
                                );
                    @endphp

                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras sm:p-6">
                        <div class="flex items-start gap-4">
                            <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-laras-100 text-laras-700">
                                <i
                                    data-lucide="repeat-2"
                                    class="size-5"
                                ></i>
                            </span>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a
                                        href="{{ route(
                                            'subscriptions.show',
                                            $subscription->id
                                        ) }}"
                                        class="truncate font-semibold text-slate-950 hover:text-laras-700"
                                    >
                                        {{ $subscription->name }}
                                    </a>

                                    <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $statusClass }}">
                                        {{ $subscription->status->label() }}
                                    </span>

                                    @if ($dueSoon)
                                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold text-amber-700">
                                            Segera ditagihkan
                                        </span>
                                    @endif
                                </div>

                                <p class="mt-1 text-sm text-slate-400">
                                    {{ $subscription->provider
                                        ?? $subscription->financeCategory?->name }}
                                </p>
                            </div>

                            <p class="shrink-0 text-right">
                                <span class="block font-semibold text-slate-950">
                                    {{ $subscription->currency_code }}
                                    {{ number_format(
                                        (float) $subscription->amount,
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </span>

                                <span class="mt-1 block text-xs text-slate-400">
                                    {{ $subscription->recurringLabel() }}
                                </span>
                            </p>
                        </div>

                        <div class="mt-5 grid gap-3 rounded-2xl bg-slate-50 p-4 sm:grid-cols-3">
                            <div>
                                <p class="text-xs text-slate-400">
                                    Tagihan berikutnya
                                </p>

                                <p class="mt-1 text-sm font-semibold text-slate-800">
                                    {{ $subscription->next_billing_on
                                        ?->translatedFormat(
                                            'd M Y'
                                        ) ?? 'Tidak ada' }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-400">
                                    Rekening
                                </p>

                                <p class="mt-1 truncate text-sm font-semibold text-slate-800">
                                    {{ $subscription->account?->name }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-400">
                                    Kategori
                                </p>

                                <p class="mt-1 truncate text-sm font-semibold text-slate-800">
                                    {{ $subscription
                                        ->financeCategory?->name }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 flex flex-wrap gap-2">
                            <a
                                href="{{ route(
                                    'subscriptions.show',
                                    $subscription->id
                                ) }}"
                                class="rounded-xl bg-laras-700 px-4 py-2.5 text-sm font-semibold text-white"
                            >
                                Detail
                            </a>

                            @if (
                                $subscription->status
                                === \App\Enums\SubscriptionStatus::Active
                            )
                                <form
                                    method="POST"
                                    action="{{ route(
                                        'subscriptions.pause',
                                        $subscription->id
                                    ) }}"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <button
                                        class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-700"
                                    >
                                        Jeda
                                    </button>
                                </form>
                            @endif

                            @if (
                                $subscription->status
                                === \App\Enums\SubscriptionStatus::Paused
                            )
                                <form
                                    method="POST"
                                    action="{{ route(
                                        'subscriptions.resume',
                                        $subscription->id
                                    ) }}"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <button
                                        class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700"
                                    >
                                        Aktifkan
                                    </button>
                                </form>
                            @endif

                            @if (
                                in_array(
                                    $subscription->status,
                                    [
                                        \App\Enums\SubscriptionStatus::Active,
                                        \App\Enums\SubscriptionStatus::Paused,
                                    ],
                                    true
                                )
                            )
                                <a
                                    href="{{ route(
                                        'subscriptions.edit',
                                        $subscription->id
                                    ) }}"
                                    class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700"
                                >
                                    Edit
                                </a>

                                <form
                                    method="POST"
                                    action="{{ route(
                                        'subscriptions.cancel',
                                        $subscription->id
                                    ) }}"
                                    onsubmit="return confirm(
                                        'Hentikan langganan ini?'
                                    )"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <button
                                        class="rounded-xl border border-rose-200 px-4 py-2.5 text-sm font-semibold text-rose-600"
                                    >
                                        Hentikan
                                    </button>
                                </form>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $subscriptions->links() }}
            </div>
        @endif
    </section>
@endsection
