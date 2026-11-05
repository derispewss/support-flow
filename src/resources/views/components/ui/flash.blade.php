@if (session('status') || session('error'))
    @php
        $isError = (bool) session('error');
        $message = session('error') ?: session('status');
    @endphp

    <div
        x-data="{ show: true }"
        x-show="show"
        role="status"
        aria-live="polite"
        class="mb-6 flex items-center justify-between gap-3 rounded-xl border px-4 py-3 text-sm font-medium shadow-2xs {{ $isError ? 'border-signal/30 bg-signal-soft text-signal-dark' : 'border-pine/25 bg-pine-soft text-pine' }}"
    >
        <div class="flex items-center gap-3 min-w-0">
            @if ($isError)
                <x-heroicon-o-exclamation-triangle class="size-5 shrink-0" />
            @else
                <x-heroicon-o-check-circle class="size-5 shrink-0" />
            @endif
            <p class="truncate">{{ $message }}</p>
        </div>
        <button type="button" @click="show = false" class="rounded-md p-1 opacity-70 hover:opacity-100" aria-label="Tutup pesan">
            <x-heroicon-o-x-mark class="size-4" />
        </button>
    </div>
@endif
