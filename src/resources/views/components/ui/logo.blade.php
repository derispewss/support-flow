@props(['variant' => 'dark', 'compact' => false])

@php
    $ink = $variant === 'light' ? 'text-white' : 'text-ink';
    $muted = $variant === 'light' ? 'text-white/55' : 'text-ink-faint';
    $mark = $variant === 'light' ? 'bg-signal text-white shadow-xs' : 'bg-ink text-white shadow-xs';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex min-w-0 items-center gap-2.5']) }}>
    <span class="grid size-8 shrink-0 place-items-center rounded-lg {{ $mark }}">
        <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.75 6.25a1 1 0 0 1 1-1h12.5a1 1 0 0 1 1 1v2.1a1.65 1.65 0 0 0 0 3.3v2.1a1 1 0 0 1-1 1H3.75a1 1 0 0 1-1-1v-2.1a1.65 1.65 0 0 0 0-3.3v-2.1Z" />
            <path stroke-linecap="round" d="M7.75 6.25v7.5" stroke-dasharray="1.5 2" />
        </svg>
    </span>

    @unless ($compact)
        <span class="min-w-0 leading-tight">
            <span class="block truncate font-display text-base font-bold tracking-tight {{ $ink }}">
                SupportFlow
            </span>
            <span class="block truncate text-[11px] font-medium {{ $muted }}">Customer Operations</span>
        </span>
    @endunless
</span>
