{{-- One instance per page. Open it from anywhere on the page with:
     $dispatch('confirm-delete', { action, title, message, confirmLabel })
     "action" is the URL the DELETE request is sent to. --}}
<div
    x-data="{
        open: false,
        busy: false,
        action: '',
        title: '',
        message: '',
        confirmLabel: 'Delete',
        show(detail) {
            Object.assign(this, { busy: false, confirmLabel: 'Delete' }, detail);
            this.open = true;
            this.$nextTick(() => this.$refs.cancel.focus());
        },
        close() { this.open = false; },
    }"
    x-on:confirm-delete.window="show($event.detail)"
    x-on:keydown.escape.window="close()"
    x-effect="document.body.classList.toggle('overflow-y-hidden', open)"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[60] flex items-center justify-center px-4 py-6"
    role="dialog"
    aria-modal="true"
    :aria-label="title"
>
    <div x-show="open"
         x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="close()"></div>

    <div x-show="open"
         x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
         class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <div class="flex items-start gap-4">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-100">
                <svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                </svg>
            </div>
            <div class="min-w-0">
                <h3 class="text-base font-semibold text-gray-900" x-text="title"></h3>
                <p class="mt-2 break-words text-sm text-gray-500" x-text="message"></p>
            </div>
        </div>

        <form method="POST" :action="action" @submit="busy = true"
              class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            @csrf
            @method('DELETE')
            <button type="button" x-ref="cancel" @click="close()"
                    class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Cancel
            </button>
            <button type="submit" :disabled="busy"
                    class="inline-flex justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-60"
                    x-text="confirmLabel"></button>
        </form>
    </div>
</div>
