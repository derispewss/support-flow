@props(['tone' => 'ink'])

@php
    $tones = [
        'ink' => 'border-rule-strong bg-paper text-ink-soft',
        'faint' => 'border-rule bg-paper/70 text-ink-faint',
        'open' => 'border-signal/25 bg-signal-soft text-signal-dark',
        'pending' => 'border-ochre/25 bg-ochre-soft text-ochre',
        'resolved' => 'border-pine/25 bg-pine-soft text-pine',
        'info' => 'border-steel/25 bg-steel-soft text-steel',
        'solid' => 'border-signal bg-signal text-paper-raised',
    ];

    $classes = $tones[$tone] ?? $tones['ink'];
@endphp

<span {{ $attributes->merge(['class' => 'stamp '.$classes]) }}>{{ $slot }}</span>
