@extends('layouts.app')

@section('title', 'Rekomendasi — Laras')
@section('page-title', 'Rekomendasi')
@section(
    'page-description',
    'Saran personal berdasarkan aktivitas, keuangan, dan langganan.'
)

@section('content')
    @php
        $items = $recommendations['items'];
        $summary = $recommendations['summary'];

        $timezone = $user->preference?->timezone
            ?? config(
                'laras.defaults.timezone',
                'Asia/Jakarta'
            );
    @endphp

    <div class="mx-auto max-w-6xl">
        <section>
            <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
                <div>
                    <p class="text-sm font-semibold text-laras-700">
                        Rekomendasi personal
                    </p>

                    <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">
                        Fokus pada hal yang paling penting.
                    </h1>

                    <p class="mt-3 max-w-2xl leading-7 text-slate-500">
                        Laras menggabungkan aktivitas, tagihan,
                        langganan, dan pola pengeluaran untuk
                        menyusun urutan tindakan yang disarankan.
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-laras">
                    <p class="text-xs font-semibold uppercase tracking-[0.15em] text-slate-400">
                        Diperbarui
                    </p>

                    <p class="mt-1 text-sm font-semibold text-slate-800">
                        {{ $recommendations[
                            'generated_at'
                        ]
                            ->setTimezone($timezone)
                            ->locale('id')
                            ->translatedFormat(
                                'd F Y, H:i'
                            ) }}
                    </p>
                </div>
            </div>

            <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                    <span class="flex size-11 items-center justify-center rounded-2xl bg-laras-100 text-laras-700">
                        <i
                            data-lucide="lightbulb"
                            class="size-5"
                        ></i>
                    </span>

                    <p class="mt-5 text-sm text-slate-500">
                        Seluruh rekomendasi
                    </p>

                    <p class="mt-2 text-3xl font-semibold">
                        {{ $summary['total'] }}
                    </p>
                </article>

                <article class="rounded-2xl border border-rose-200 bg-rose-50 p-5">
                    <span class="flex size-11 items-center justify-center rounded-2xl bg-rose-100 text-rose-700">
                        <i
                            data-lucide="circle-alert"
                            class="size-5"
                        ></i>
                    </span>

                    <p class="mt-5 text-sm text-rose-700">
                        Perlu segera ditangani
                    </p>

                    <p class="mt-2 text-3xl font-semibold text-rose-950">
                        {{ $summary['critical'] }}
                    </p>
                </article>

                <article class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                    <span class="flex size-11 items-center justify-center rounded-2xl bg-amber-100 text-amber-700">
                        <i
                            data-lucide="alarm-clock"
                            class="size-5"
                        ></i>
                    </span>

                    <p class="mt-5 text-sm text-amber-700">
                        Perlu perhatian
                    </p>

                    <p class="mt-2 text-3xl font-semibold text-amber-950">
                        {{ $summary['attention'] }}
                    </p>
                </article>

                <article class="rounded-2xl border border-blue-200 bg-blue-50 p-5">
                    <span class="flex size-11 items-center justify-center rounded-2xl bg-blue-100 text-blue-700">
                        <i
                            data-lucide="chart-no-axes-combined"
                            class="size-5"
                        ></i>
                    </span>

                    <p class="mt-5 text-sm text-blue-700">
                        Insight evaluasi
                    </p>

                    <p class="mt-2 text-3xl font-semibold text-blue-950">
                        {{ $summary['insight'] }}
                    </p>
                </article>
            </div>
        </section>

        <section class="mt-7">
            @if ($items->isEmpty())
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-6 py-16 text-center">
                    <span class="mx-auto flex size-16 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
                        <i
                            data-lucide="circle-check"
                            class="size-7"
                        ></i>
                    </span>

                    <h2 class="mt-5 text-xl font-semibold text-emerald-950">
                        Semuanya terkendali
                    </h2>

                    <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-emerald-700">
                        Tidak ada aktivitas mendesak, tagihan gagal,
                        langganan dekat jatuh tempo, atau perubahan
                        pengeluaran penting saat ini.
                    </p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($items as $item)
                        @php
                            $style = match (
                                $item['severity']
                            ) {
                                'danger' => [
                                    'border' =>
                                        'border-rose-200',

                                    'background' =>
                                        'bg-rose-50/40',

                                    'icon' =>
                                        'bg-rose-100 text-rose-700',

                                    'badge' =>
                                        'bg-rose-100 text-rose-700',

                                    'badge_label' =>
                                        'Mendesak',
                                ],

                                'warning' => [
                                    'border' =>
                                        'border-amber-200',

                                    'background' =>
                                        'bg-amber-50/30',

                                    'icon' =>
                                        'bg-amber-100 text-amber-700',

                                    'badge' =>
                                        'bg-amber-100 text-amber-700',

                                    'badge_label' =>
                                        'Perhatian',
                                ],

                                default => [
                                    'border' =>
                                        'border-blue-200',

                                    'background' =>
                                        'bg-blue-50/20',

                                    'icon' =>
                                        'bg-blue-100 text-blue-700',

                                    'badge' =>
                                        'bg-blue-100 text-blue-700',

                                    'badge_label' =>
                                        'Insight',
                                ],
                            };
                        @endphp

                        <article class="rounded-2xl border {{ $style['border'] }} {{ $style['background'] }} p-5 shadow-laras sm:p-6">
                            <div class="flex flex-col gap-5 lg:flex-row lg:items-center">
                                <div class="flex min-w-0 flex-1 items-start gap-4">
                                    <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl {{ $style['icon'] }}">
                                        <i
                                            data-lucide="{{ $item['icon'] }}"
                                            class="size-5"
                                        ></i>
                                    </span>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="flex size-7 items-center justify-center rounded-lg bg-white text-xs font-bold text-slate-500 shadow-sm">
                                                {{ $loop->iteration }}
                                            </span>

                                            <h2 class="font-semibold text-slate-950">
                                                {{ $item['title'] }}
                                            </h2>

                                            <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $style['badge'] }}">
                                                {{ $style[
                                                    'badge_label'
                                                ] }}
                                            </span>
                                        </div>

                                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                                            {{ $item['message'] }}
                                        </p>

                                        <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-slate-400">
                                            <span>
                                                {{ $item['meta'] }}
                                            </span>

                                            <span class="rounded-full bg-white px-2.5 py-1 font-semibold text-slate-500 shadow-sm">
                                                Skor
                                                {{ $item['score'] }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <a
                                    href="{{ $item[
                                        'action_url'
                                    ] }}"
                                    class="inline-flex shrink-0 items-center justify-center rounded-xl bg-laras-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-laras-800"
                                >
                                    {{ $item[
                                        'action_label'
                                    ] }}
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="mt-7 rounded-2xl border border-slate-200 bg-white p-5 shadow-laras sm:p-6">
            <div class="flex items-start gap-4">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-600">
                    <i
                        data-lucide="lightbulb"
                        class="size-5"
                    ></i>
                </span>

                <div>
                    <h2 class="font-semibold text-slate-950">
                        Cara Laras menyusun rekomendasi
                    </h2>

                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                        Tagihan gagal ditempatkan paling tinggi,
                        diikuti aktivitas terlambat atau mendesak,
                        langganan yang segera ditagihkan, kemudian
                        insight pola pengeluaran. Setiap rekomendasi
                        hanya menggunakan data milik akunmu.
                    </p>
                </div>
            </div>
        </section>
    </div>
@endsection
