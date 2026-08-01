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
    x-data="{
        type: @js($selectedTypeValue),
        allDay: @js(
            (bool) old(
                'all_day',
                $activity->all_day ?? false
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

            <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-rose-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section>
        <h2 class="text-base font-semibold text-slate-900">
            Jenis aktivitas
        </h2>

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
                    class="cursor-pointer rounded-2xl border p-4 transition"
                    x-bind:class="
                        type === '{{ $type->value }}'
                            ? 'border-laras-600 bg-laras-50 ring-4 ring-laras-100'
                            : 'border-slate-200 hover:border-slate-300'
                    "
                >
                    <input
                        type="radio"
                        name="type"
                        value="{{ $type->value }}"
                        x-model="type"
                        class="sr-only"
                    >

                    <span class="flex items-center gap-3">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                            <i
                                data-lucide="{{ $type->icon() }}"
                                class="size-5"
                            ></i>
                        </span>

                        <span>
                            <span class="block text-sm font-semibold text-slate-900">
                                {{ $type->label() }}
                            </span>

                            <span class="mt-1 block text-xs leading-5 text-slate-500">
                                {{ $typeDescription }}
                            </span>
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
            <label
                for="title"
                class="mb-2 block text-sm font-medium text-slate-700"
            >
                Judul aktivitas
            </label>

            <input
                id="title"
                name="title"
                type="text"
                value="{{ old(
                    'title',
                    $activity->title ?? ''
                ) }}"
                maxlength="160"
                required
                autofocus
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
                placeholder="Contoh: Menyelesaikan laporan proyek"
            >

            @error('title')
                <p class="mt-2 text-sm text-rose-600">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div>
            <label
                for="priority"
                class="mb-2 block text-sm font-medium text-slate-700"
            >
                Prioritas
            </label>

            <select
                id="priority"
                name="priority"
                required
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
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
            </select>

            @error('priority')
                <p class="mt-2 text-sm text-rose-600">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div>
            <label
                for="estimated_minutes"
                class="mb-2 block text-sm font-medium text-slate-700"
            >
                Estimasi durasi
                <span class="font-normal text-slate-400">
                    (opsional)
                </span>
            </label>

            <div class="flex rounded-xl border border-slate-300 bg-white focus-within:border-laras-600 focus-within:ring-4 focus-within:ring-laras-100">
                <input
                    id="estimated_minutes"
                    name="estimated_minutes"
                    type="number"
                    value="{{ old(
                        'estimated_minutes',
                        $activity->estimated_minutes ?? ''
                    ) }}"
                    min="5"
                    max="1440"
                    step="5"
                    class="min-w-0 flex-1 rounded-l-xl px-4 py-3 text-sm outline-none"
                    placeholder="60"
                >

                <span class="flex items-center border-l border-slate-200 px-4 text-sm text-slate-500">
                    menit
                </span>
            </div>

            @error('estimated_minutes')
                <p class="mt-2 text-sm text-rose-600">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div>
            <label
                for="starts_at"
                class="mb-2 block text-sm font-medium text-slate-700"
            >
                <span
                    x-text="
                        type === 'event'
                            ? 'Waktu mulai'
                            : 'Mulai dikerjakan'
                    "
                ></span>

                <span
                    x-show="type !== 'event'"
                    class="font-normal text-slate-400"
                >
                    (opsional)
                </span>
            </label>

            <input
                id="starts_at"
                name="starts_at"
                type="datetime-local"
                value="{{ old(
                    'starts_at',
                    $editing
                        ? $formatDateTime(
                            $activity->starts_at
                        )
                        : ''
                ) }}"
                x-bind:required="type === 'event'"
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
            >

            @error('starts_at')
                <p class="mt-2 text-sm text-rose-600">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div
            x-cloak
            x-show="type !== 'deadline'"
        >
            <label
                for="ends_at"
                class="mb-2 block text-sm font-medium text-slate-700"
            >
                Waktu selesai
                <span class="font-normal text-slate-400">
                    (opsional)
                </span>
            </label>

            <input
                id="ends_at"
                name="ends_at"
                type="datetime-local"
                value="{{ old(
                    'ends_at',
                    $editing
                        ? $formatDateTime(
                            $activity->ends_at
                        )
                        : ''
                ) }}"
                x-bind:disabled="type === 'deadline'"
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
            >

            @error('ends_at')
                <p class="mt-2 text-sm text-rose-600">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div>
            <label
                for="due_at"
                class="mb-2 block text-sm font-medium text-slate-700"
            >
                Tenggat

                <span
                    x-show="type !== 'deadline'"
                    class="font-normal text-slate-400"
                >
                    (opsional)
                </span>
            </label>

            <input
                id="due_at"
                name="due_at"
                type="datetime-local"
                value="{{ old(
                    'due_at',
                    $editing
                        ? $formatDateTime(
                            $activity->due_at
                        )
                        : ''
                ) }}"
                x-bind:required="type === 'deadline'"
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
            >

            @error('due_at')
                <p class="mt-2 text-sm text-rose-600">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div>
            <label
                for="location"
                class="mb-2 block text-sm font-medium text-slate-700"
            >
                Lokasi
                <span class="font-normal text-slate-400">
                    (opsional)
                </span>
            </label>

            <input
                id="location"
                name="location"
                type="text"
                value="{{ old(
                    'location',
                    $activity->location ?? ''
                ) }}"
                maxlength="160"
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
                placeholder="Contoh: Kampus atau Google Meet"
            >

            @error('location')
                <p class="mt-2 text-sm text-rose-600">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div>
            <label
                for="color"
                class="mb-2 block text-sm font-medium text-slate-700"
            >
                Warna penanda
            </label>

            <div class="flex h-[50px] items-center gap-4 rounded-xl border border-slate-300 px-4">
                <input
                    id="color"
                    name="color"
                    type="color"
                    value="{{ old(
                        'color',
                        $activity->color
                            ?? '#3B82F6'
                    ) }}"
                    required
                    class="size-9 cursor-pointer border-0 bg-transparent p-0"
                >

                <span class="text-sm text-slate-500">
                    Digunakan pada agenda dan prioritas
                </span>
            </div>

            @error('color')
                <p class="mt-2 text-sm text-rose-600">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="lg:col-span-2">
            <label
                for="description"
                class="mb-2 block text-sm font-medium text-slate-700"
            >
                Deskripsi
                <span class="font-normal text-slate-400">
                    (opsional)
                </span>
            </label>

            <textarea
                id="description"
                name="description"
                rows="5"
                maxlength="5000"
                class="w-full resize-y rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
                placeholder="Tambahkan rincian, catatan, atau tujuan aktivitas"
            >{{ old(
                'description',
                $activity->description ?? ''
            ) }}</textarea>

            @error('description')
                <p class="mt-2 text-sm text-rose-600">
                    {{ $message }}
                </p>
            @enderror
        </div>
    </section>

    <section class="mt-7 grid gap-4 sm:grid-cols-2">
        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 transition hover:border-slate-300">
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
                class="mt-1 size-4 rounded border-slate-300 text-laras-700 focus:ring-laras-500"
            >

            <span>
                <span class="block text-sm font-semibold text-slate-800">
                    Aktivitas sepanjang hari
                </span>

                <span class="mt-1 block text-xs leading-5 text-slate-500">
                    Waktu akan ditampilkan sebagai agenda sepanjang hari.
                </span>
            </span>
        </label>

        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 transition hover:border-slate-300">
            <input
                type="hidden"
                name="is_flexible"
                value="0"
            >

            <input
                type="checkbox"
                name="is_flexible"
                value="1"
                @checked(
                    old(
                        'is_flexible',
                        $activity->is_flexible ?? true
                    )
                )
                class="mt-1 size-4 rounded border-slate-300 text-laras-700 focus:ring-laras-500"
            >

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
            class="inline-flex items-center justify-center rounded-xl bg-laras-700 px-6 py-3 text-sm font-semibold text-white transition hover:bg-laras-800 focus:outline-none focus:ring-4 focus:ring-laras-200"
        >
            {{ $editing
                ? 'Simpan perubahan'
                : 'Tambah aktivitas' }}
        </button>
    </div>
</div>
