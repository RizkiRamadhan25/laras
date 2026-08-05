<dialog
    id="laras-confirm-dialog"
    data-laras-confirm-dialog
    data-tone="danger"
    class="m-auto w-[calc(100%-2rem)] max-w-md overflow-hidden rounded-3xl border border-slate-200 bg-white p-0 text-slate-950 shadow-2xl backdrop:bg-slate-950/50"
>
    <div class="p-6 sm:p-7">
        <div class="flex items-start gap-4">
            <span
                data-laras-confirm-icon-shell
                class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-700"
            >
                <i
                    data-lucide="triangle-alert"
                    class="size-5"
                ></i>
            </span>

            <div class="min-w-0 flex-1">
                <h2
                    id="laras-confirm-dialog-title"
                    data-laras-confirm-title
                    class="text-xl font-semibold tracking-tight text-slate-950"
                >
                    Konfirmasi tindakan
                </h2>

                <p
                    id="laras-confirm-dialog-description"
                    data-laras-confirm-message
                    class="mt-2 text-sm leading-6 text-slate-500"
                >
                    Pastikan kamu ingin melanjutkan tindakan ini.
                </p>
            </div>

            <button
                type="button"
                data-laras-confirm-close
                class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-laras-500 focus:ring-offset-2"
                aria-label="Tutup dialog"
            >
                <i
                    data-lucide="x"
                    class="size-5"
                ></i>
            </button>
        </div>

        <div class="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <button
                type="button"
                data-laras-confirm-cancel
                class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2"
            >
                Batal
            </button>

            <button
                type="button"
                data-laras-confirm-accept
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-rose-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
            >
                <i
                    data-lucide="check"
                    class="size-4"
                ></i>

                <span data-laras-confirm-label>
                    Lanjutkan
                </span>
            </button>
        </div>
    </div>
</dialog>
