@php
    $editing = isset($activity);

    $timezone = $user->preference?->timezone
        ?? config('laras.defaults.timezone');

    $selectedTypeValue = old(
        'type',
        $editing
            ? $activity->type->value
            : $selectedType->value
    );

    $selectedPriority = old(
        'priority',
        $editing
            ? $activity->priority->value
            : \App\Enums\ActivityPriority::Medium->value
    );

    $formatDateTime = static function (
        $value
    ) use ($timezone): string {
        return $value === null
            ? ''
            : $value
                ->timezone($timezone)
                ->format('Y-m-d\TH:i');
    };
@endphp

<div
    data-modern-activity-form
    x-data="{
        type: @js($selectedTypeValue),
        allDay: @js(
            (bool) old(
                'all_day',
                $activity->all_day ?? false
            )
        ),
        isFlexible: @js(
            (bool) old(
                'is_flexible',
                $activity->is_flexible ?? true
            )
        ),
    }"
>
    @if ($errors->any())
        <div
            class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4"
            role="alert"
        >
            <p class="text-sm font-semibold text-rose-800">
                Periksa kembali data aktivitas.
            </p>

            <p class="mt-1 text-sm leading-6 text-rose-700">
                Setiap bagian yang perlu diperbaiki sudah ditandai pada form.
            </p>
        </div>
    @endif

    <section>
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-base font-semibold text-slate-900">
                    Jenis aktivitas
                </h2>

                <p class="mt-1 text-sm leading-6 text-slate-500">
                    Pilih bentuk aktivitas agar Laras menyesuaikan kebutuhan waktunya.
                </p>
            </div>
        </div>

        <div class="mt-4 grid gap-3 sm:grid-cols-3">
            @foreach ($activityTypes as $type)
                @php
                    $typeDescription = match ($type) {
                        \App\Enums\ActivityType::Task =>
                            'Tugas yang perlu dikerjakan',

                        \App\Enums\ActivityType::Event =>
                            'Kegiatan pada waktu tertentu',

                        \App\Enums\ActivityType::Deadline =>
                            'Pekerjaan dengan tenggat',
                    };
                @endphp

                <label
                    class="laras-choice-card"
                    x-bind:data-selected="
                        type === '{{ $type->value }}'
                            ? 'true'
                            : 'false'
                    "
                >
                    <input
                        type="radio"
                        name="type"
                        value="{{ $type->value }}"
                        x-model="type"
                        class="sr-only"
                    >

                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                        <i
                            data-lucide="{{ $type->icon() }}"
                            class="size-5"
                        ></i>
                    </span>

                    <span class="min-w-0">
                        <span class="block text-sm font-semibold text-slate-900">
                            {{ $type->label() }}
                        </span>

                        <span class="mt-1 block text-xs leading-5 text-slate-500">
                            {{ $typeDescription }}
                        </span>
                    </span>
                </label>
            @endforeach
        </div>

        @error('type')
            <p class="mt-2 text-sm text-rose-600">
                {{ $message }}
            </p>
        @enderror
    </section>

    <hr class="my-7 border-slate-200">

    <section class="grid gap-6 lg:grid-cols-2">
        <div class="lg:col-span-2">
            <x-ui.floating-input
                name="title"
                label="Judul aktivitas"
                :value="$activity->title ?? ''"
                :required="true"
                maxlength="160"
                autofocus
                hint="Gunakan judul singkat dan jelas agar mudah ditemukan."
            />
        </div>

        <x-ui.floating-select
            name="priority"
            label="Prioritas"
            :required="true"
        >
            @foreach ($priorities as $priority)
                <option
                    value="{{ $priority->value }}"
                    @selected(
                        $selectedPriority
                            === $priority->value
                    )
                >
                    {{ $priority->label() }}
                </option>
            @endforeach
        </x-ui.floating-select>

        <x-ui.floating-input
            name="estimated_minutes"
            type="number"
            label="Estimasi durasi"
            :value="$activity->estimated_minutes ?? ''"
            suffix="menit"
            min="5"
            max="1440"
            step="5"
            inputmode="numeric"
            hint="Opsional. Gunakan kelipatan 5 menit."
        />

        <x-ui.floating-input
            name="starts_at"
            type="datetime-local"
            label="Waktu mulai"
            :value="$editing
                ? $formatDateTime($activity->starts_at)
                : ''"
            x-bind:required="type === 'event'"
            hint="Wajib untuk acara dan opsional untuk jenis lainnya."
        />

        <div
            x-cloak
            x-show="type !== 'deadline'"
        >
            <x-ui.floating-input
                name="ends_at"
                type="datetime-local"
                label="Waktu selesai"
                :value="$editing
                    ? $formatDateTime($activity->ends_at)
                    : ''"
                x-bind:disabled="type === 'deadline'"
                hint="Opsional. Pastikan waktunya setelah waktu mulai."
            />
        </div>

        <x-ui.floating-input
            name="due_at"
            type="datetime-local"
            label="Tenggat"
            :value="$editing
                ? $formatDateTime($activity->due_at)
                : ''"
            x-bind:required="type === 'deadline'"
            hint="Wajib untuk deadline dan opsional untuk jenis lainnya."
        />

        <x-ui.floating-input
            name="location"
            label="Lokasi"
            :value="$activity->location ?? ''"
            maxlength="160"
            hint="Opsional, misalnya Kampus atau Google Meet."
        />

        <div>
            <label
                for="color"
                class="mb-2 block text-sm font-medium text-slate-700"
            >
                Warna penanda
            </label>

            <div class="flex min-h-[58px] items-center gap-4 rounded-2xl border border-slate-300 bg-white px-4 transition focus-within:border-laras-600 focus-within:ring-4 focus-within:ring-laras-100">
                <input
                    id="color"
                    name="color"
                    type="color"
                    value="{{ old(
                        'color',
                        $activity->color ?? '#3B82F6'
                    ) }}"
                    required
                    class="size-10 cursor-pointer rounded-xl border-0 bg-transparent p-0"
                >

                <div>
                    <p class="text-sm font-semibold text-slate-700">
                        Pilih warna aktivitas
                    </p>

                    <p class="mt-0.5 text-xs text-slate-400">
                        Digunakan pada agenda dan prioritas.
                    </p>
                </div>
            </div>

            @error('color')
                <p class="mt-2 text-sm text-rose-600">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="lg:col-span-2">
            <x-ui.floating-textarea
                name="description"
                label="Deskripsi"
                :value="$activity->description ?? ''"
                rows="5"
                maxlength="5000"
                hint="Opsional. Tambahkan rincian, catatan, atau tujuan aktivitas."
            />
        </div>
    </section>

    <section class="mt-7 grid gap-4 sm:grid-cols-2">
        <label
            class="laras-choice-card"
            x-bind:data-selected="allDay ? 'true' : 'false'"
        >
            <input
                type="hidden"
                name="all_day"
                value="0"
            >

            <input
                type="checkbox"
                name="all_day"
                value="1"
                x-model="allDay"
                @checked(
                    old(
                        'all_day',
                        $activity->all_day ?? false
                    )
                )
                class="sr-only"
            >

            <span class="laras-choice-card__indicator"></span>

            <span>
                <span class="block text-sm font-semibold text-slate-800">
                    Aktivitas sepanjang hari
                </span>

                <span class="mt-1 block text-xs leading-5 text-slate-500">
                    Waktu akan ditampilkan sebagai agenda sepanjang hari.
                </span>
            </span>
        </label>

        <label
            class="laras-choice-card"
            x-bind:data-selected="isFlexible ? 'true' : 'false'"
        >
            <input
                type="hidden"
                name="is_flexible"
                value="0"
            >

            <input
                type="checkbox"
                name="is_flexible"
                value="1"
                x-model="isFlexible"
                @checked(
                    old(
                        'is_flexible',
                        $activity->is_flexible ?? true
                    )
                )
                class="sr-only"
            >

            <span class="laras-choice-card__indicator"></span>

            <span>
                <span class="block text-sm font-semibold text-slate-800">
                    Jadwal fleksibel
                </span>

                <span class="mt-1 block text-xs leading-5 text-slate-500">
                    Aktivitas dapat dipindahkan jika ada prioritas yang lebih mendesak.
                </span>
            </span>
        </label>
    </section>

    @if ($editing)
        <div class="mt-7 rounded-2xl bg-slate-50 p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.15em] text-slate-400">
                Status saat ini
            </p>

            <div class="mt-3 flex flex-wrap items-center gap-3">
                <span class="rounded-full bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 shadow-sm">
                    {{ $activity->status->label() }}
                </span>

                @if ($activity->completed_at)
                    <span class="text-sm text-slate-500">
                        Diselesaikan
                        {{ $activity->completed_at
                            ->timezone($timezone)
                            ->translatedFormat(
                                'd F Y H:i'
                            ) }}
                    </span>
                @endif
            </div>
        </div>
    @endif

    <div class="mt-8 flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:justify-end">
        <a
            href="{{ route('activities.index') }}"
            class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
        >
            Kembali
        </a>

        <button
            type="submit"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-laras-700 px-6 py-3 text-sm font-semibold text-white transition hover:bg-laras-800 focus:outline-none focus:ring-4 focus:ring-laras-200"
        >
            <i
                data-lucide="check"
                class="size-4"
            ></i>

            {{ $editing
                ? 'Simpan perubahan'
                : 'Tambah aktivitas' }}
        </button>
    </div>
</div>
