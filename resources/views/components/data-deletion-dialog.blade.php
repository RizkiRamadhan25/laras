@props([
    'id',
])

<dialog
    id="{{ $id }}"
    data-data-deletion-dialog
    class="m-auto w-[calc(100%-2rem)] max-w-lg overflow-hidden rounded-3xl border border-slate-200 bg-white p-0 text-slate-950 shadow-2xl backdrop:bg-slate-950/50"
>
    <form
        method="POST"
        data-deletion-submit-form
        class="contents"
    >
        @csrf
        @method('DELETE')

        <div class="p-6 sm:p-7">
            <div class="flex items-start gap-4">
                <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-700">
                    <i
                        data-lucide="triangle-alert"
                        class="size-5"
                    ></i>
                </span>

                <div class="min-w-0 flex-1">
                    <h2
                        data-deletion-dialog-title
                        class="text-xl font-semibold tracking-tight text-slate-950"
                    >
                        Hapus data secara permanen?
                    </h2>

                    <p
                        data-deletion-dialog-description
                        class="mt-2 text-sm leading-6 text-slate-500"
                    >
                        Data yang sudah dihapus tidak dapat dipulihkan kembali.
                    </p>
                </div>

                <button
                    type="button"
                    data-deletion-dialog-close
                    class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-laras-500 focus:ring-offset-2"
                    aria-label="Tutup dialog"
                >
                    <i
                        data-lucide="x"
                        class="size-5"
                    ></i>
                </button>
            </div>

            <div
                data-deletion-dialog-loading
                class="mt-6 flex items-center gap-3 rounded-2xl border border-laras-200 bg-laras-50 px-4 py-4 text-sm text-laras-800"
            >
                <span class="size-5 animate-spin rounded-full border-2 border-laras-200 border-t-laras-700"></span>

                Menghitung data yang akan dihapus...
            </div>

            <div
                data-deletion-dialog-preview
                class="mt-6 hidden rounded-2xl border border-rose-200 bg-rose-50 p-4"
            >
                <p
                    data-deletion-dialog-message
                    class="text-sm font-semibold text-rose-900"
                ></p>

                <dl
                    data-deletion-dialog-details
                    class="mt-3 grid gap-2 text-sm text-rose-800"
                ></dl>
            </div>

            <div
                data-deletion-dialog-error
                class="mt-6 hidden rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm leading-6 text-amber-900"
                role="alert"
            ></div>

            <div class="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    data-deletion-dialog-cancel
                    class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2"
                >
                    Batal
                </button>

                <button
                    type="submit"
                    data-deletion-dialog-confirm
                    disabled
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-rose-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <i
                        data-lucide="trash-2"
                        class="size-4"
                    ></i>

                    <span data-deletion-dialog-confirm-label>
                        Hapus permanen
                    </span>
                </button>
            </div>
        </div>

        <input
            type="hidden"
            name="scope"
            data-deletion-form-scope
        >

        <input
            type="hidden"
            name="older_than_days"
            data-deletion-form-older-than-days
            disabled
        >

        <div data-deletion-form-identifiers></div>
    </form>
</dialog>
