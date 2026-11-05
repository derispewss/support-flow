<div
    x-data="{
        toasts: [],
        init() {
            @if (session('status'))
                this.add(@js(session('status')), 'success');
            @endif
            @if (session('success'))
                this.add(@js(session('success')), 'success');
            @endif
            @if (session('error'))
                this.add(@js(session('error')), 'error');
            @endif
            window.addEventListener('toast', (e) => {
                this.add(e.detail.message, e.detail.type || 'info');
            });
        },
        add(message, type = 'info') {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, message, type });
            setTimeout(() => this.remove(id), 5000);
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
    }"
    class="fixed top-4 right-4 z-50 flex flex-col gap-2 w-full max-w-sm pointer-events-none"
    role="region"
    aria-label="Notifikasi"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="pointer-events-auto flex items-start gap-3 w-full rounded-lg border border-zinc-200/80 bg-white p-4 shadow-lg text-zinc-900 transition-all dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-50"
            :class="{
                'border-destructive/30 text-destructive dark:border-destructive/50': toast.type === 'error',
                'border-emerald-500/30': toast.type === 'success'
            }"
        >
            <template x-if="toast.type === 'success'">
                <svg class="size-5 shrink-0 text-emerald-600 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                </svg>
            </template>

            <template x-if="toast.type === 'error'">
                <svg class="size-5 shrink-0 text-red-600 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                </svg>
            </template>

            <template x-if="toast.type !== 'success' && toast.type !== 'error'">
                <svg class="size-5 shrink-0 text-zinc-500 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                </svg>
            </template>

            <div class="flex-1 text-sm font-medium leading-relaxed" x-text="toast.message"></div>

            <button
                type="button"
                @click="remove(toast.id)"
                class="rounded-md p-1 text-zinc-400 hover:text-zinc-700 hover:bg-zinc-100 transition-colors dark:text-zinc-500 dark:hover:text-zinc-300 dark:hover:bg-zinc-800"
                aria-label="Tutup notifikasi"
            >
                <svg class="size-4" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                </svg>
            </button>
        </div>
    </template>
</div>
