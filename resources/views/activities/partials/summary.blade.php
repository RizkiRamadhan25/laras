<div
    data-activity-summary
    class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
    aria-live="polite"
    aria-busy="false"
>
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

        <p
            data-activity-summary-value="open"
            data-activity-summary-count="{{ $summary['open'] }}"
            class="mt-2 text-3xl font-semibold text-slate-950"
        >
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

        <p
            data-activity-summary-value="today"
            data-activity-summary-count="{{ $summary['today'] }}"
            class="mt-2 text-3xl font-semibold text-slate-950"
        >
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

        <p
            data-activity-summary-value="overdue"
            data-activity-summary-count="{{ $summary['overdue'] }}"
            class="mt-2 text-3xl font-semibold text-rose-700"
        >
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

        <p
            data-activity-summary-value="completed_month"
            data-activity-summary-count="{{ $summary['completed_month'] }}"
            class="mt-2 text-3xl font-semibold text-emerald-700"
        >
            {{ $summary['completed_month'] }}
        </p>
    </article>
</div>
