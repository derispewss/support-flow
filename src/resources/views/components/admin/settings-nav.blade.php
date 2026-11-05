@props(['active' => ''])

<nav class="table-scroll -mx-1 px-1" aria-label="Navigasi master data">
    <div class="inline-flex min-w-full sm:min-w-0 items-center gap-1 rounded-xl border border-rule bg-paper p-1">
        @foreach ([
            ['admin.settings.categories.index', 'categories', 'Kategori', 'heroicon-o-tag'],
            ['admin.settings.tags.index', 'tags', 'Tag', 'heroicon-o-hashtag'],
            ['admin.settings.sla.index', 'sla', 'Kebijakan SLA', 'heroicon-o-clock'],
            ['admin.settings.teams.index', 'teams', 'Tim Support', 'heroicon-o-user-group'],
        ] as [$route, $key, $label, $icon])
            <a
                href="{{ route($route) }}"
                @class([
                    'inline-flex items-center gap-2 whitespace-nowrap rounded-lg px-3.5 py-2 text-xs font-semibold transition-all',
                    'bg-paper-raised text-ink shadow-2xs ring-1 ring-rule' => $active === $key,
                    'text-ink-soft hover:text-ink' => $active !== $key,
                ])
                @if ($active === $key) aria-current="page" @endif
            >
                <x-dynamic-component :component="$icon" class="size-4 {{ $active === $key ? 'text-signal' : 'text-ink-faint' }}" />
                {{ $label }}
            </a>
        @endforeach
    </div>
</nav>
