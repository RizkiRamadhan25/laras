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
    data-modern-budget-form
    class="rounded-2xl border border-slate-200 bg-white p-6 shadow-laras sm:p-8"
>
    @csrf

    @if ($isEditing)
        @method('PUT')
    @endif

    @if (! $isEditing)
        <x-ui.floating-select
            name="finance_category_id"
            label="Kategori pengeluaran"
            :required="true"
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
        </x-ui.floating-select>
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
        <x-ui.floating-input
            name="name"
            label="Nama anggaran"
            :value="$budget->name ?? ''"
            :required="true"
            hint="Contoh: Anggaran makan bulanan."
            minlength="2"
            maxlength="120"
        />
    </div>

    <div class="mt-6 grid gap-5 sm:grid-cols-2">
        <x-ui.floating-input
            name="amount"
            label="Batas anggaran"
            type="number"
            :value="$budget->amount ?? ''"
            :required="true"
            prefix="Rp"
            min="0.01"
            step="0.01"
            inputmode="decimal"
        />

        <x-ui.floating-input
            name="warning_threshold_percent"
            label="Ambang peringatan"
            type="number"
            :value="$budget->warning_threshold_percent ?? '80'"
            :required="true"
            suffix="%"
            hint="Laras memberi peringatan saat penggunaan mencapai persentase ini."
            min="1"
            max="100"
            step="0.01"
        />
    </div>

    @if (! $isEditing)
        <div class="mt-6">
            <x-ui.floating-select
                name="period_type"
                label="Jenis periode"
                :required="true"
                x-model="periodType"
            >
                @foreach (
                    \App\Enums\BudgetPeriodType::cases()
                    as $periodType
                )
                    <option
                        value="{{ $periodType->value }}"
                        @selected(
                            $selectedPeriodType
                                === $periodType->value
                        )
                    >
                        {{ $periodType->label() }}
                    </option>
                @endforeach
            </x-ui.floating-select>
        </div>

        <div class="mt-6 grid gap-5 sm:grid-cols-2">
            <x-ui.floating-input
                name="start_date"
                label="Tanggal mulai"
                type="date"
                :value="now()->startOfMonth()->toDateString()"
                :required="true"
            />

            <div
                x-show="periodType === 'custom'"
                x-cloak
            >
                <x-ui.floating-input
                    name="end_date"
                    label="Tanggal selesai"
                    type="date"
                    x-bind:required="periodType === 'custom'"
                />
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
