@php
    $isEditing = isset($budget);

    $selectedPeriodType = old(
        'period_type',
        $isEditing
            ? $budget->period_type->value
            : \App\Enums\BudgetPeriodType::Monthly->value
    );
@endphp

<form
    method="POST"
    action="{{ $isEditing
        ? route('budgets.update', $budget)
        : route('budgets.store') }}"
    x-data="{
        periodType: @js(
            $selectedPeriodType
        ),
    }"
    class="rounded-2xl border border-slate-200 bg-white p-6 shadow-laras sm:p-8"
>
    @csrf

    @if ($isEditing)
        @method('PUT')
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4">
            <p class="text-sm font-semibold text-rose-800">
                Periksa kembali data anggaran.
            </p>

            <ul class="mt-2 space-y-1 text-sm text-rose-700">
                @foreach ($errors->all() as $error)
                    <li>
                        • {{ $error }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (! $isEditing)
        <div>
            <label
                for="finance_category_id"
                class="mb-2 block text-sm font-medium text-slate-700"
            >
                Kategori pengeluaran
            </label>

            <select
                id="finance_category_id"
                name="finance_category_id"
                required
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
            >
                <option value="">
                    Pilih kategori
                </option>

                @foreach ($categories as $category)
                    <option
                        value="{{ $category->id }}"
                        @selected(
                            (string) old(
                                'finance_category_id'
                            )
                            === (string) $category->id
                        )
                    >
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>
    @else
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                Kategori dan periode
            </p>

            <p class="mt-2 font-semibold text-slate-900">
                {{ $budget->financeCategory->name }}
            </p>

            <p class="mt-1 text-sm text-slate-500">
                {{ $budget->period_type->label() }}
                ·
                {{ $budget->start_date->format('d/m/Y') }}

                @if ($budget->end_date)
                    sampai
                    {{ $budget->end_date->format('d/m/Y') }}
                @endif
            </p>

            <p class="mt-3 text-xs leading-5 text-slate-400">
                Kategori dan jenis periode dikunci agar
                riwayat periode tetap konsisten.
            </p>
        </div>
    @endif

    <div class="mt-6">
        <label
            for="name"
            class="mb-2 block text-sm font-medium text-slate-700"
        >
            Nama anggaran
        </label>

        <input
            id="name"
            name="name"
            type="text"
            required
            minlength="2"
            maxlength="120"
            value="{{ old(
                'name',
                $budget->name ?? ''
            ) }}"
            placeholder="Contoh: Anggaran makan bulanan"
            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
        >
    </div>

    <div class="mt-6 grid gap-5 sm:grid-cols-2">
        <div>
            <label
                for="amount"
                class="mb-2 block text-sm font-medium text-slate-700"
            >
                Batas anggaran
            </label>

            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-semibold text-slate-400">
                    Rp
                </span>

                <input
                    id="amount"
                    name="amount"
                    type="number"
                    required
                    min="0.01"
                    step="0.01"
                    value="{{ old(
                        'amount',
                        $budget->amount ?? ''
                    ) }}"
                    class="w-full rounded-xl border border-slate-300 bg-white py-3 pl-12 pr-4 text-sm outline-none transition focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
                >
            </div>
        </div>

        <div>
            <label
                for="warning_threshold_percent"
                class="mb-2 block text-sm font-medium text-slate-700"
            >
                Ambang peringatan
            </label>

            <div class="relative">
                <input
                    id="warning_threshold_percent"
                    name="warning_threshold_percent"
                    type="number"
                    required
                    min="1"
                    max="100"
                    step="0.01"
                    value="{{ old(
                        'warning_threshold_percent',
                        $budget
                            ->warning_threshold_percent
                        ?? '80'
                    ) }}"
                    class="w-full rounded-xl border border-slate-300 bg-white py-3 pl-4 pr-12 text-sm outline-none transition focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
                >

                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-sm font-semibold text-slate-400">
                    %
                </span>
            </div>
        </div>
    </div>

    @if (! $isEditing)
        <div class="mt-6">
            <label
                for="period_type"
                class="mb-2 block text-sm font-medium text-slate-700"
            >
                Jenis periode
            </label>

            <select
                id="period_type"
                name="period_type"
                required
                x-model="periodType"
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
            >
                @foreach (
                    \App\Enums\BudgetPeriodType::cases()
                    as $periodType
                )
                    <option
                        value="{{ $periodType->value }}"
                    >
                        {{ $periodType->label() }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mt-6 grid gap-5 sm:grid-cols-2">
            <div>
                <label
                    for="start_date"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Tanggal mulai
                </label>

                <input
                    id="start_date"
                    name="start_date"
                    type="date"
                    required
                    value="{{ old(
                        'start_date',
                        now()->startOfMonth()->toDateString()
                    ) }}"
                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
                >
            </div>

            <div
                x-show="periodType === 'custom'"
                x-cloak
            >
                <label
                    for="end_date"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Tanggal selesai
                </label>

                <input
                    id="end_date"
                    name="end_date"
                    type="date"
                    value="{{ old(
                        'end_date'
                    ) }}"
                    x-bind:required="
                        periodType === 'custom'
                    "
                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-laras-600 focus:ring-4 focus:ring-laras-100"
                >
            </div>
        </div>
    @endif

    <div class="mt-8 flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end">
        <a
            href="{{ $isEditing
                ? route(
                    'budgets.show',
                    $budget
                )
                : route('budgets.index') }}"
            class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
        >
            Batal
        </a>

        <button
            type="submit"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-laras-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-laras-800"
        >
            <i
                data-lucide="{{ $isEditing
                    ? 'save'
                    : 'plus' }}"
                class="size-4"
            ></i>

            {{ $isEditing
                ? 'Simpan perubahan'
                : 'Buat anggaran' }}
        </button>
    </div>
</form>
