@extends('layouts.app')

@section(
    'title',
    $priorityPage
        ? 'Prioritas — Laras'
        : 'Aktivitas — Laras'
)

@section(
    'page-title',
    $priorityPage
        ? 'Prioritas'
        : 'Aktivitas'
)

@section(
    'page-description',
    $priorityPage
        ? 'Fokus pada aktivitas yang paling perlu dikerjakan.'
        : 'Kelola tugas, acara, deadline, dan agenda harian.'
)

@section('content')
    @php
        $filterRoute = $priorityPage
            ? route('priorities.index')
            : route('activities.index');

        $now = now(
            config('app.timezone')
        );

        $viewTabs = [
            'open' => 'Aktif',
            'today' => 'Hari ini',
            'priority' => 'Prioritas',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            'archived' => 'Arsip',
        ];

        /*
        * Panel agenda dan rekomendasi hanya ditampilkan pada
        * halaman aktivitas aktif tanpa filter.
        *
        * Dengan demikian, halaman hasil filter tidak menampilkan
        * aktivitas lain di luar hasil pencarian.
        */
        $hasActiveFilters =
            filled($filters['search'] ?? null)
            || filled($filters['type'] ?? null)
            || filled($filters['priority'] ?? null)
            || filled($filters['status'] ?? null)
            || filled($filters['date_from'] ?? null)
            || filled($filters['date_to'] ?? null);

        $showActivityOverview =
            ! $priorityPage
            && $selectedView === 'open'
            && ! $hasActiveFilters;
    @endphp

    <section>
        <div class="flex flex-col justify-between gap-5 xl:flex-row xl:items-end">
            <div>
                <p class="text-sm font-semibold text-laras-700">
                    {{ $currentDate }}
                </p>

                <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">
                    {{ $priorityPage
                        ? 'Tentukan fokus berikutnya.'
                        : 'Selaraskan aktivitasmu.' }}
                </h1>

                <p class="mt-3 max-w-2xl leading-7 text-slate-500">
                    {{ $priorityPage
                        ? 'Aktivitas diurutkan berdasarkan prioritas, tenggat, fleksibilitas, dan estimasi durasi.'
                        : 'Kelola aktivitas yang sedang dikerjakan, agenda hari ini, serta deadline yang akan datang.' }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route(
                        'activities.create',
                        ['type' => 'task']
                    ) }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                >
                    <i
                        data-lucide="list-todo"
                        class="size-4"
                    ></i>
                    Tugas
                </a>

                <a
                    href="{{ route(
                        'activities.create',
                        ['type' => 'event']
                    ) }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                >
                    <i
                        data-lucide="calendar-days"
                        class="size-4"
                    ></i>
                    Acara
                </a>

                <a
                    href="{{ route(
                        'activities.create',
                        ['type' => 'deadline']
                    ) }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-laras-700 px-4 py-3 text-sm font-semibold text-white transition hover:bg-laras-800"
                >
                    <i
                        data-lucide="plus"
                        class="size-4"
                    ></i>
                    Tambah aktivitas
                </a>
            </div>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                <span class="flex size-11 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                    <i
                        data-lucide="list-todo"
                        class="size-5"
                    ></i>
                </span>

                <p class="mt-5 text-sm text-slate-500">
                    Aktivitas aktif
                </p>

                <p class="mt-2 text-3xl font-semibold">
                    {{ $summary['open'] }}
                </p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                <span class="flex size-11 items-center justify-center rounded-2xl bg-violet-50 text-violet-700">
                    <i
                        data-lucide="calendar-days"
                        class="size-5"
                    ></i>
                </span>

                <p class="mt-5 text-sm text-slate-500">
                    Agenda hari ini
                </p>

                <p class="mt-2 text-3xl font-semibold">
                    {{ $summary['today'] }}
                </p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                <span class="flex size-11 items-center justify-center rounded-2xl bg-rose-50 text-rose-700">
                    <i
                        data-lucide="alarm-clock"
                        class="size-5"
                    ></i>
                </span>

                <p class="mt-5 text-sm text-slate-500">
                    Melewati tenggat
                </p>

                <p class="mt-2 text-3xl font-semibold text-rose-700">
                    {{ $summary['overdue'] }}
                </p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-laras">
                <span class="flex size-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                    <i
                        data-lucide="check"
                        class="size-5"
                    ></i>
                </span>

                <p class="mt-5 text-sm text-slate-500">
                    Selesai bulan ini
                </p>

                <p class="mt-2 text-3xl font-semibold text-emerald-700">
                    {{ $summary['completed_month'] }}
                </p>
            </article>
        </div>
    </section>

    <div
        data-activity-browser
        class="relative"
        aria-live="polite"
        aria-busy="false"
    >
    @if ($showActivityOverview)
        <section class="mt-6 grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-laras">
                <header class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-5 sm:px-6">
                    <div>
                        <h2 class="font-semibold text-slate-950">
                            Agenda hari ini
                        </h2>

                        <p class="mt-1 text-sm text-slate-400">
                            Aktivitas terjadwal atau jatuh tempo hari ini.
                        </p>
                    </div>

                    <a
                        href="{{ route(
                            'activities.index',
                            ['view' => 'today']
                        ) }}"
                        class="text-sm font-semibold text-laras-700"
                    >
                        Lihat semua
                    </a>
                </header>

                @if ($todayActivities->isEmpty())
                    <div class="px-6 py-12 text-center">
                        <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                            <i
                                data-lucide="calendar-days"
                                class="size-6"
                            ></i>
                        </span>

                        <p class="mt-4 font-semibold text-slate-900">
                            Agenda hari ini kosong
                        </p>

                        <p class="mt-2 text-sm text-slate-500">
                            Tidak ada aktivitas terjadwal atau jatuh tempo hari ini.
                        </p>
                    </div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach ($todayActivities as $activity)
                            @php
                                $relevantAt =
                                    $activity->starts_at
                                    ?? $activity->due_at;
                            @endphp

                            <div class="flex items-center gap-4 px-5 py-4 sm:px-6">
                                <span
                                    class="size-3 shrink-0 rounded-full"
                                    style="
                                        background-color:
                                        {{ $activity->color }}
                                    "
                                ></span>

                                <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-600">
                                    <i
                                        data-lucide="{{ $activity->type->icon() }}"
                                        class="size-5"
                                    ></i>
                                </span>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-slate-900">
                                        {{ $activity->title }}
                                    </p>

                                    <p class="mt-1 truncate text-xs text-slate-400">
                                        {{ $activity->priority->label() }}

                                        @if ($activity->location)
                                            • {{ $activity->location }}
                                        @endif
                                    </p>
                                </div>

                                <p class="shrink-0 text-sm font-semibold text-slate-700">
                                    @if ($activity->all_day)
                                        Sepanjang hari
                                    @elseif ($relevantAt)
                                        {{ $relevantAt
                                            ->timezone($timezone)
                                            ->format('H:i') }}
                                    @else
                                        —
                                    @endif
                                </p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>

            <article class="rounded-2xl border border-slate-200 bg-laras-950 p-6 text-white shadow-laras">
                <div class="flex items-center justify-between">
                    <span class="flex size-11 items-center justify-center rounded-2xl bg-white/10 text-laras-100">
                        <i
                            data-lucide="sparkles"
                            class="size-5"
                        ></i>
                    </span>

                    <a
                        href="{{ route('priorities.index') }}"
                        class="text-sm font-semibold text-laras-100"
                    >
                        Buka prioritas
                    </a>
                </div>

                <h2 class="mt-6 text-xl font-semibold">
                    Rekomendasi Laras
                </h2>

                @if ($recommendations->isEmpty())
                    <p class="mt-4 leading-7 text-laras-100">
                        Belum ada aktivitas aktif yang perlu direkomendasikan.
                    </p>
                @else
                    <div class="mt-5 space-y-4">
                        @foreach (
                            $recommendations->take(3)
                            as $recommendation
                        )
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                <div class="flex items-start gap-3">
                                    <span
                                        class="mt-1 size-2.5 shrink-0 rounded-full"
                                        style="
                                            background-color:
                                            {{ $recommendation['activity']->priority->color() }}
                                        "
                                    ></span>

                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-white">
                                            {{ $recommendation['activity']->title }}
                                        </p>

                                        <p class="mt-2 text-xs leading-5 text-laras-100">
                                            {{ $recommendation['reason'] }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>
        </section>
    @endif

    <section class="mt-7">
        <div class="overflow-x-auto">
            <nav class="flex min-w-max gap-2 rounded-2xl border border-slate-200 bg-white p-2 shadow-laras">
                @foreach ($viewTabs as $value => $label)
                    @php
                        $tabRoute = $priorityPage
                            ? (
                                $value === 'priority'
                                    ? route('priorities.index')
                                    : route(
                                        'activities.index',
                                        ['view' => $value]
                                    )
                            )
                            : route(
                                'activities.index',
                                ['view' => $value]
                            );

                        $tabActive =
                            $selectedView === $value;
                    @endphp

                    <a
                        href="{{ $tabRoute }}"
                        @if (! $priorityPage)
                            data-activity-tab
                        @endif
                        @class([
                            'rounded-xl px-4 py-2.5 text-sm font-semibold transition',
                            'bg-laras-700 text-white shadow-sm' =>
                                $tabActive,

                            'text-slate-500 hover:bg-slate-100 hover:text-slate-900' =>
                                ! $tabActive,
                        ])
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
        </div>
    </section>

    <section class="laras-filter-panel mt-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-laras sm:p-6">
        <div class="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <h2 class="font-semibold text-slate-950">
                    Saring aktivitas
                </h2>

                <p class="mt-1 text-sm text-slate-400">
                    Persempit daftar berdasarkan kata kunci, jenis, prioritas, atau tanggal.
                </p>
            </div>

            @if (collect($filters)->filter()->isNotEmpty())
                <span class="inline-flex w-fit items-center gap-2 rounded-full bg-laras-50 px-3 py-1.5 text-xs font-semibold text-laras-700">
                    <i
                        data-lucide="sliders-horizontal"
                        class="size-3.5"
                    ></i>
                    Filter aktif
                </span>
            @endif
        </div>

        <form
            method="GET"
            action="{{ $filterRoute }}"
            data-activity-filter-form
            class="grid gap-4 md:grid-cols-2 xl:grid-cols-6"
        >
            @if (! $priorityPage)
                <input
                    type="hidden"
                    name="view"
                    value="{{ $selectedView }}"
                >
            @endif

            <div class="xl:col-span-2">
                <x-ui.floating-input
                    name="search"
                    type="search"
                    label="Pencarian"
                    :value="$filters['search'] ?? ''"
                    density="compact"
                    maxlength="100"
                    autocomplete="off"
                    data-activity-search
                    hint="Cari judul, deskripsi, atau lokasi."
                />
            </div>

            <x-ui.floating-select
                name="type"
                label="Jenis"
                density="compact"
            >
                <option value="">
                    Semua jenis
                </option>

                @foreach ($activityTypes as $type)
                    <option
                        value="{{ $type->value }}"
                        @selected(
                            ($filters['type'] ?? '')
                                === $type->value
                        )
                    >
                        {{ $type->label() }}
                    </option>
                @endforeach
            </x-ui.floating-select>

            <x-ui.floating-select
                name="priority"
                label="Prioritas"
                density="compact"
            >
                <option value="">
                    Semua prioritas
                </option>

                @foreach ($priorities as $priority)
                    <option
                        value="{{ $priority->value }}"
                        @selected(
                            ($filters['priority'] ?? '')
                                === $priority->value
                        )
                    >
                        {{ $priority->label() }}
                    </option>
                @endforeach
            </x-ui.floating-select>

            <x-ui.floating-input
                name="date_from"
                type="date"
                label="Mulai"
                :value="$filters['date_from'] ?? ''"
                density="compact"
            />

            <x-ui.floating-input
                name="date_to"
                type="date"
                label="Sampai"
                :value="$filters['date_to'] ?? ''"
                density="compact"
            />

            <div class="flex flex-wrap items-center gap-2 md:col-span-2 xl:col-span-6">
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
                >
                    <i
                        data-lucide="sliders-horizontal"
                        class="size-4"
                    ></i>
                    Terapkan filter
                </button>

                <a
                    data-activity-reset
                    href="{{ $priorityPage
                        ? route('priorities.index')
                        : route(
                            'activities.index',
                            ['view' => $selectedView]
                        ) }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                >
                    <i
                        data-lucide="rotate-ccw"
                        class="size-4"
                    ></i>
                    Reset
                </a>
            </div>
        </form>
    </section>

    @if ($priorityPage && $recommendations->isNotEmpty())
        <section class="mt-6 rounded-2xl border border-laras-200 bg-laras-50 p-5 sm:p-6">
            <div class="flex items-start gap-4">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-laras-700 text-white">
                    <i
                        data-lucide="sparkles"
                        class="size-5"
                    ></i>
                </span>

                <div>
                    <h2 class="font-semibold text-laras-950">
                        Urutan fokus yang disarankan
                    </h2>

                    <p class="mt-1 text-sm leading-6 text-laras-700">
                        Skor mempertimbangkan prioritas, tenggat,
                        status pengerjaan, fleksibilitas, dan durasi.
                    </p>
                </div>
            </div>

            <div class="mt-5 grid gap-3 lg:grid-cols-2">
                @foreach ($recommendations as $recommendation)
                    <article class="rounded-2xl border border-laras-200 bg-white p-4">
                        <div class="flex items-start gap-3">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-laras-100 text-sm font-bold text-laras-700">
                                {{ $loop->iteration }}
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-900">
                                    {{ $recommendation['activity']->title }}
                                </p>

                                <p class="mt-2 text-xs leading-5 text-slate-500">
                                    {{ $recommendation['reason'] }}
                                </p>
                            </div>

                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                {{ $recommendation['score'] }}
                            </span>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <section class="mt-6">
        @if ($activities->isEmpty())
            <div class="rounded-2xl border border-slate-200 bg-white px-6 py-16 text-center shadow-laras">
                <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                    <i
                        data-lucide="list-todo"
                        class="size-6"
                    ></i>
                </span>

                <h2 class="mt-4 font-semibold text-slate-900">
                    Tidak ada aktivitas
                </h2>

                <p class="mt-2 text-sm text-slate-500">
                    Belum ada aktivitas yang sesuai dengan tampilan atau filter ini.
                </p>

                <a
                    href="{{ route('activities.create') }}"
                    class="mt-5 inline-flex items-center gap-2 rounded-xl bg-laras-700 px-5 py-3 text-sm font-semibold text-white"
                >
                    <i
                        data-lucide="plus"
                        class="size-4"
                    ></i>
                    Tambah aktivitas
                </a>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($activities as $activity)
                    @php
                        $relevantAt =
                            $activity->due_at
                            ?? $activity->starts_at;

                        $overdue =
                            $activity->isOpen()
                            && $activity->due_at !== null
                            && $activity->due_at
                                ->lessThan($now);

                        $statusClass = match (
                            $activity->status
                        ) {
                            \App\Enums\ActivityStatus::Planned =>
                                'bg-blue-50 text-blue-700',

                            \App\Enums\ActivityStatus::InProgress =>
                                'bg-amber-50 text-amber-700',

                            \App\Enums\ActivityStatus::Completed =>
                                'bg-emerald-50 text-emerald-700',

                            \App\Enums\ActivityStatus::Cancelled =>
                                'bg-slate-100 text-slate-500',
                        };
                    @endphp

                    <article
                        @class([
                            'rounded-2xl border bg-white p-5 shadow-laras sm:p-6',
                            'border-rose-200' => $overdue,
                            'border-slate-200' => ! $overdue,
                            'opacity-75' => $activity->trashed(),
                        ])
                    >
                        <div class="flex flex-col gap-5 xl:flex-row xl:items-center">
                            <div class="flex min-w-0 flex-1 items-start gap-4">
                                <span
                                    class="mt-1 size-3 shrink-0 rounded-full"
                                    style="
                                        background-color:
                                        {{ $activity->color }}
                                    "
                                ></span>

                                <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-600">
                                    <i
                                        data-lucide="{{ $activity->type->icon() }}"
                                        class="size-5"
                                    ></i>
                                </span>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="truncate font-semibold text-slate-900">
                                            {{ $activity->title }}
                                        </h2>

                                        <span
                                            class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $statusClass }}"
                                        >
                                            {{ $activity->status->label() }}
                                        </span>

                                        <span
                                            class="rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                            style="
                                                color:
                                                {{ $activity->priority->color() }};
                                                background-color:
                                                {{ $activity->priority->color() }}14;
                                            "
                                        >
                                            {{ $activity->priority->label() }}
                                        </span>

                                        @if ($overdue)
                                            <span class="rounded-full bg-rose-100 px-2.5 py-1 text-[11px] font-semibold text-rose-700">
                                                Terlambat
                                            </span>
                                        @endif
                                    </div>

                                    @if ($activity->description)
                                        <p class="mt-2 line-clamp-2 text-sm leading-6 text-slate-500">
                                            {{ $activity->description }}
                                        </p>
                                    @endif

                                    <div class="mt-3 flex flex-wrap gap-x-4 gap-y-2 text-xs text-slate-400">
                                        <span class="inline-flex items-center gap-1.5">
                                            <i
                                                data-lucide="clock"
                                                class="size-3.5"
                                            ></i>

                                            @if ($activity->all_day)
                                                Sepanjang hari
                                            @elseif ($relevantAt)
                                                {{ $relevantAt
                                                    ->timezone($timezone)
                                                    ->translatedFormat(
                                                        'd M Y, H:i'
                                                    ) }}
                                            @else
                                                Belum dijadwalkan
                                            @endif
                                        </span>

                                        @if ($activity->estimated_minutes)
                                            <span>
                                                {{ $activity->estimated_minutes }}
                                                menit
                                            </span>
                                        @endif

                                        @if ($activity->location)
                                            <span class="inline-flex items-center gap-1.5">
                                                <i
                                                    data-lucide="map-pin"
                                                    class="size-3.5"
                                                ></i>

                                                {{ $activity->location }}
                                            </span>
                                        @endif

                                        <span>
                                            {{ $activity->type->label() }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                @if ($activity->trashed())
                                    <form
                                        method="POST"
                                        action="{{ route(
                                            'activities.restore',
                                            $activity->id
                                        ) }}"
                                    >
                                        @csrf
                                        @method('PATCH')

                                        <button
                                            type="submit"
                                            class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                                        >
                                            <i
                                                data-lucide="archive-restore"
                                                class="size-4"
                                            ></i>
                                            Pulihkan
                                        </button>
                                    </form>
                                @else
                                    @if (
                                        $activity->status
                                        === \App\Enums\ActivityStatus::Planned
                                    )
                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'activities.start',
                                                $activity->id
                                            ) }}"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-2.5 text-sm font-semibold text-amber-700 transition hover:bg-amber-100"
                                            >
                                                <i
                                                    data-lucide="play"
                                                    class="size-4"
                                                ></i>
                                                Mulai
                                            </button>
                                        </form>
                                    @endif

                                    @if ($activity->status->isOpen())
                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'activities.complete',
                                                $activity->id
                                            ) }}"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-3.5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700"
                                            >
                                                <i
                                                    data-lucide="check"
                                                    class="size-4"
                                                ></i>
                                                Selesai
                                            </button>
                                        </form>

                                        <a
                                            href="{{ route(
                                                'activities.edit',
                                                $activity->id
                                            ) }}"
                                            class="flex size-10 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:bg-slate-100"
                                            aria-label="Edit aktivitas"
                                        >
                                            <i
                                                data-lucide="pencil"
                                                class="size-4"
                                            ></i>
                                        </a>

                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'activities.cancel',
                                                $activity->id
                                            ) }}"
                                            data-confirm
                                            data-confirm-title="Batalkan aktivitas?"
                                            data-confirm-message="Aktivitas akan ditandai sebagai dibatalkan dan tetap tersimpan dalam riwayat."
                                            data-confirm-label="Batalkan aktivitas"
                                            data-confirm-busy-label="Membatalkan..."
                                            data-confirm-tone="warning"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <button
                                                type="submit"
                                                class="flex size-10 items-center justify-center rounded-xl border border-slate-200 text-slate-500 transition hover:bg-slate-100"
                                                aria-label="Batalkan aktivitas"
                                            >
                                                <i
                                                    data-lucide="ban"
                                                    class="size-4"
                                                ></i>
                                            </button>
                                        </form>
                                    @else
                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'activities.reopen',
                                                $activity->id
                                            ) }}"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-3.5 py-2.5 text-sm font-semibold text-blue-700 transition hover:bg-blue-100"
                                            >
                                                <i
                                                    data-lucide="rotate-ccw"
                                                    class="size-4"
                                                ></i>
                                                Buka kembali
                                            </button>
                                        </form>
                                    @endif

                                    <form
                                        method="POST"
                                        action="{{ route(
                                            'activities.destroy',
                                            $activity->id
                                        ) }}"
                                        data-confirm
                                        data-confirm-title="Arsipkan aktivitas?"
                                        data-confirm-message="Aktivitas akan dipindahkan dari daftar utama dan dapat dipulihkan kembali."
                                        data-confirm-label="Arsipkan"
                                        data-confirm-busy-label="Mengarsipkan..."
                                        data-confirm-tone="warning"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="flex size-10 items-center justify-center rounded-xl border border-rose-200 text-rose-600 transition hover:bg-rose-50"
                                            aria-label="Arsipkan aktivitas"
                                        >
                                            <i
                                                data-lucide="archive"
                                                class="size-4"
                                            ></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
            <div
            data-activity-pagination
            @class(['mt-6' => $activities->hasPages(),])>
            @if ($activities->hasPages())
                {{ $activities->withQueryString()->links() }}
            @endif
        </div>
    </section>
    </div>
@endsection
