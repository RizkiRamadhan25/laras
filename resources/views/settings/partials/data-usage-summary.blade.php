@php
    $fileBytes =
        $dataUsageSummary['files']['bytes'];

    $fileSizeLabel = $fileBytes >= 1048576
        ? number_format(
            $fileBytes / 1048576,
            1,
            ',',
            '.'
        ).' MB'
        : number_format(
            $fileBytes / 1024,
            1,
            ',',
            '.'
        ).' KB';
@endphp

<section
    data-data-usage-summary
    aria-labelledby="data-usage-summary-title"
    class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-laras sm:p-6"
>
    <div>
        <h3
            id="data-usage-summary-title"
            class="font-semibold text-slate-950"
        >
            Ringkasan data tersimpan
        </h3>

        <p class="mt-1 text-sm leading-6 text-slate-500">
            Jumlah record milik akunmu yang tersimpan
            di Laras.
        </p>
    </div>

    <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article
            data-data-usage-item="accounts"
            class="rounded-2xl bg-slate-50 p-4"
        >
            <p class="text-sm font-semibold text-slate-900">
                Rekening
            </p>

            <p class="mt-2 text-2xl font-semibold text-slate-950">
                {{ $dataUsageSummary['accounts']['active'] }}
            </p>

            <p class="mt-1 text-xs text-slate-500">
                Aktif ·
                {{ $dataUsageSummary['accounts']['archived'] }}
                diarsipkan
            </p>
        </article>

        <article
            data-data-usage-item="transactions"
            class="rounded-2xl bg-slate-50 p-4"
        >
            <p class="text-sm font-semibold text-slate-900">
                Transaksi
            </p>

            <p class="mt-2 text-2xl font-semibold text-slate-950">
                {{ $dataUsageSummary['transactions']['recorded'] }}
            </p>

            <p class="mt-1 text-xs text-slate-500">
                Tercatat ·
                {{ $dataUsageSummary['transactions']['archived'] }}
                diarsipkan
            </p>
        </article>

        <article
            data-data-usage-item="activities"
            class="rounded-2xl bg-slate-50 p-4"
        >
            <p class="text-sm font-semibold text-slate-900">
                Aktivitas
            </p>

            <p class="mt-2 text-2xl font-semibold text-slate-950">
                {{ $dataUsageSummary['activities']['current'] }}
            </p>

            <p class="mt-1 text-xs text-slate-500">
                Tersimpan ·
                {{ $dataUsageSummary['activities']['archived'] }}
                diarsipkan
            </p>
        </article>

        <article
            data-data-usage-item="subscriptions"
            class="rounded-2xl bg-slate-50 p-4"
        >
            <p class="text-sm font-semibold text-slate-900">
                Langganan
            </p>

            <p class="mt-2 text-2xl font-semibold text-slate-950">
                {{ $dataUsageSummary['subscriptions']['active'] }}
            </p>

            <p class="mt-1 text-xs text-slate-500">
                Aktif ·
                {{ $dataUsageSummary['subscriptions']['paused'] }}
                dijeda ·
                {{ $dataUsageSummary['subscriptions']['archived'] }}
                diarsipkan
            </p>
        </article>

        <article
            data-data-usage-item="budgets"
            class="rounded-2xl bg-slate-50 p-4"
        >
            <p class="text-sm font-semibold text-slate-900">
                Anggaran
            </p>

            <p class="mt-2 text-2xl font-semibold text-slate-950">
                {{ $dataUsageSummary['budgets']['active'] }}
            </p>

            <p class="mt-1 text-xs text-slate-500">
                Aktif ·
                {{ $dataUsageSummary['budgets']['archived'] }}
                diarsipkan
            </p>
        </article>

        <article
            data-data-usage-item="notifications"
            class="rounded-2xl bg-slate-50 p-4"
        >
            <p class="text-sm font-semibold text-slate-900">
                Notifikasi
            </p>

            <p class="mt-2 text-2xl font-semibold text-slate-950">
                {{ $dataUsageSummary['notifications']['total'] }}
            </p>

            <p class="mt-1 text-xs text-slate-500">
                {{ $dataUsageSummary['notifications']['unread'] }}
                belum dibaca
            </p>
        </article>

        <article
            data-data-usage-item="recommendations"
            class="rounded-2xl bg-slate-50 p-4"
        >
            <p class="text-sm font-semibold text-slate-900">
                Histori rekomendasi
            </p>

            <p class="mt-2 text-2xl font-semibold text-slate-950">
                {{ $dataUsageSummary['recommendations']['interactions'] }}
            </p>

            <p class="mt-1 text-xs text-slate-500">
                Interaksi tersimpan
            </p>
        </article>

        <article
            data-data-usage-item="files"
            class="rounded-2xl bg-slate-50 p-4"
        >
            <p class="text-sm font-semibold text-slate-900">
                File pengguna
            </p>

            <p class="mt-2 text-2xl font-semibold text-slate-950">
                {{ $dataUsageSummary['files']['count'] }}
            </p>

            <p class="mt-1 text-xs text-slate-500">
                {{ $fileSizeLabel }}
                tersimpan
            </p>
        </article>
    </div>

    <div class="mt-5 flex flex-wrap gap-2 border-t border-slate-100 pt-5">
        <a
            href="{{ route(
                'activities.index',
                ['view' => 'archived']
            ) }}"
            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100"
        >
            <i
                data-lucide="archive"
                class="size-4"
            ></i>

            Arsip aktivitas
        </a>

        <a
            href="{{ route('accounts.index') }}"
            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100"
        >
            <i
                data-lucide="wallet-cards"
                class="size-4"
            ></i>

            Kelola rekening
        </a>

        <a
            href="{{ route('notifications.index') }}"
            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100"
        >
            <i
                data-lucide="bell"
                class="size-4"
            ></i>

            Kelola notifikasi
        </a>

        <a
            href="{{ route(
                'recommendations.history'
            ) }}"
            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100"
        >
            <i
                data-lucide="history"
                class="size-4"
            ></i>

            Histori rekomendasi
        </a>
    </div>
</section>
