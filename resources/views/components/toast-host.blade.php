<div class="pointer-events-none fixed inset-x-0 top-20 z-50 flex flex-col items-center gap-2 px-4"
     x-data="{ toast: null, timer: null }"
     x-on:toast.window="toast = { message: $event.detail.message, type: $event.detail.type || 'info' }; clearTimeout(timer); timer = setTimeout(() => toast = null, 4000)"
     x-on:toast-dismiss.window="toast = null">
    <template x-if="toast">
        <div class="pointer-events-auto flex w-full max-w-md items-start gap-3 rounded-xl border px-4 py-3 shadow-lg"
             :class="{
                'border-emerald-200 bg-emerald-50 text-emerald-800': toast.type === 'success',
                'border-red-200 bg-red-50 text-red-800': toast.type === 'error',
                'border-ink-200 bg-white text-ink-700': toast.type !== 'success' && toast.type !== 'error',
             }"
             role="status">
            <span class="flex-1 text-sm" x-text="toast.message"></span>
            <button type="button" x-on:click="toast = null" class="opacity-60 hover:opacity-100" aria-label="Fermer">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </template>
</div>