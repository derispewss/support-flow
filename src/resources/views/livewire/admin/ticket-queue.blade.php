<div>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="table-scroll -mx-1 px-1">
            <div class="inline-flex items-center gap-1 rounded-xl border border-rule bg-paper-raised p-1 shadow-2xs">
                @foreach ([
                    ['all', 'Semua'],
                    ['unassigned', 'Belum ditugaskan'],
                    ['mine', 'Milik saya'],
                    ['overdue', 'Lewat SLA'],
                ] as [$key, $label])
                    <button
                        type="button"
                        wire:click="setView('{{ $key }}')"
                        @class([
                            'inline-flex items-center gap-2 whitespace-nowrap rounded-lg px-3 py-1.5 text-xs font-semibold transition-all',
                            'bg-ink text-white shadow-2xs' => $view === $key,
                            'text-ink-soft hover:bg-paper hover:text-ink' => $view !== $key,
                        ])
                    >
                        <span>{{ $label }}</span>
                        <span @class([
                            'rounded-full px-1.5 py-0.2 text-[11px] font-semibold',
                            'bg-white/20 text-white' => $view === $key,
                            'bg-paper text-ink-faint' => $view !== $key,
                        ])>{{ $counts[$key] }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        <p class="text-xs font-medium text-ink-faint">
            <strong class="font-semibold text-ink">{{ $tickets->total() }}</strong> tiket cocok dengan filter
        </p>
    </div>

    <div class="mt-5 overflow-hidden border-y border-rule" wire:loading.class="opacity-60" wire:target="search,status,priority,assignee,category,tag,sort,view">
        <div class="border-b border-rule bg-paper-raised p-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                <div class="relative flex-1">
                    <label for="queue-search" class="sr-only">Cari tiket</label>
                    <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-ink-faint">
                        <x-heroicon-o-magnifying-glass class="size-4" />
                    </span>
                    <input
                        id="queue-search"
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari nomor tiket, subjek, atau nama customer…"
                        class="field w-full pl-10"
                    >
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" wire:click="resetFilters" class="btn btn-outline btn-sm">
                        <x-heroicon-o-arrow-path class="size-3.5" />
                        Reset filter
                    </button>
                </div>
            </div>

            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6">
                <div>
                    <label for="queue-status" class="label-mono">Status</label>
                    <select id="queue-status" wire:model.live="status" class="field mt-1">
                        <option value="">Semua status</option>
                        @foreach ($statuses as $option)
                            <option value="{{ $option->value }}">{{ $option->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="queue-priority" class="label-mono">Prioritas</label>
                    <select id="queue-priority" wire:model.live="priority" class="field mt-1">
                        <option value="">Semua prioritas</option>
                        @foreach ($priorities as $option)
                            <option value="{{ $option->value }}">{{ $option->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="queue-assignee" class="label-mono">Assignee</label>
                    <select id="queue-assignee" wire:model.live="assignee" class="field mt-1">
                        <option value="all">Semua agen</option>
                        <option value="unassigned">Belum ditugaskan</option>
                        <option value="me">Milik saya</option>
                        @foreach ($agents as $membership)
                            <option value="{{ $membership->user_id }}">{{ $membership->user?->name ?? 'Anggota' }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="queue-category" class="label-mono">Kategori</label>
                    <select id="queue-category" wire:model.live="category" class="field mt-1">
                        <option value="">Semua kategori</option>
                        @foreach ($categories as $option)
                            <option value="{{ $option->getKey() }}">{{ $option->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="queue-tag" class="label-mono">Tag</label>
                    <select id="queue-tag" wire:model.live="tag" class="field mt-1">
                        <option value="">Semua tag</option>
                        @foreach ($tags as $option)
                            <option value="{{ $option->getKey() }}">{{ $option->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="queue-sort" class="label-mono">Urutkan</label>
                    <select id="queue-sort" wire:model.live="sort" class="field mt-1">
                        <option value="recent">Aktivitas terbaru</option>
                        <option value="oldest">Paling lama</option>
                        <option value="priority">Prioritas tertinggi</option>
                        <option value="due">Batas SLA terdekat</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="hidden grid-cols-[135px_1fr_170px_100px_140px_110px] gap-4 border-b border-rule bg-paper/40 px-5 py-2.5 text-xs font-semibold text-ink-faint lg:grid">
            <span>No. Tiket</span>
            <span>Subjek &amp; Konteks</span>
            <span>Customer</span>
            <span>Prioritas</span>
            <span>Assignee</span>
            <span class="text-right">Status</span>
        </div>

        <div class="divide-y divide-rule">
            @forelse ($tickets as $ticket)
                <a
                    href="{{ route('admin.tickets.show', $ticket) }}"
                    wire:key="ticket-{{ $ticket->getKey() }}"
                    class="group block px-4 py-4 transition-colors hover:bg-paper/60 sm:px-5 lg:grid lg:grid-cols-[135px_1fr_170px_100px_140px_110px] lg:items-center lg:gap-4"
                >
                    <div class="flex items-center justify-between gap-2 lg:block">
                        <span class="font-mono text-xs font-medium text-ink-faint group-hover:text-ink">{{ $ticket->ticket_number }}</span>
                        <span class="lg:hidden">
                            <x-ui.stamp :tone="$ticket->status->tone()">{{ $ticket->status->label() }}</x-ui.stamp>
                        </span>
                    </div>

                    <div class="mt-1.5 min-w-0 lg:mt-0">
                        <p class="truncate text-sm font-semibold text-ink group-hover:text-signal">{{ $ticket->subject }}</p>
                        <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-ink-faint">
                            <span>{{ $ticket->category?->name ?? 'Tanpa kategori' }}</span>
                            <span aria-hidden="true">·</span>
                            <span>{{ $ticket->last_activity_at?->diffForHumans() ?? 'baru saja' }}</span>
                            @if ($ticket->due_at !== null && $ticket->isOpen())
                                <span aria-hidden="true">·</span>
                                <span class="font-medium {{ $ticket->due_at->isPast() ? 'text-signal-dark' : 'text-ink-soft' }}">
                                    SLA {{ $ticket->due_at->diffForHumans(short: true) }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="mt-2.5 flex flex-wrap items-center justify-between gap-2 border-t border-rule/60 pt-2.5 text-xs lg:mt-0 lg:block lg:border-t-0 lg:pt-0">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-ink-soft">{{ $ticket->requester?->name ?? '—' }}</p>
                            <p class="hidden truncate text-xs text-ink-faint lg:block">{{ $ticket->requester?->email }}</p>
                        </div>

                        <div class="flex items-center gap-3 lg:hidden">
                            <span class="font-semibold {{ in_array($ticket->priority->value, ['high', 'urgent'], true) ? 'text-signal-dark' : 'text-ink-soft' }}">
                                {{ $ticket->priority->label() }}
                            </span>
                            <span class="text-ink-faint">·</span>
                            <span class="text-ink-soft">{{ $ticket->assignee?->name ?? 'Belum ditugaskan' }}</span>
                        </div>
                    </div>

                    <div class="hidden lg:block">
                        <span class="text-xs font-semibold {{ in_array($ticket->priority->value, ['high', 'urgent'], true) ? 'text-signal-dark' : 'text-ink-soft' }}">
                            {{ $ticket->priority->label() }}
                        </span>
                    </div>

                    <div class="hidden truncate text-xs font-medium text-ink-soft lg:block">
                        {{ $ticket->assignee?->name ?? '—' }}
                    </div>

                    <div class="hidden lg:block lg:text-right">
                        <x-ui.stamp :tone="$ticket->status->tone()">{{ $ticket->status->label() }}</x-ui.stamp>
                    </div>
                </a>
            @empty
                <div class="px-5 py-14 text-center">
                    <div class="mx-auto grid size-12 place-items-center rounded-full bg-paper text-ink-faint">
                        <x-heroicon-o-inbox class="size-6" />
                    </div>
                    <p class="mt-3 font-display text-base font-semibold text-ink">Tidak ada tiket yang cocok</p>
                    <p class="mt-1 text-sm text-ink-soft">Coba longgarkan kata kunci pencarian atau pilih filter antrean lain.</p>
                    <button type="button" wire:click="resetFilters" class="btn btn-outline btn-sm mt-4">Reset filter</button>
                </div>
            @endforelse
        </div>

        <div class="flex flex-col gap-3 border-t border-rule bg-paper/60 px-5 py-3.5 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs text-ink-faint">
                Menampilkan <strong class="font-semibold text-ink">{{ $tickets->firstItem() ?? 0 }}–{{ $tickets->lastItem() ?? 0 }}</strong> dari <strong class="font-semibold text-ink">{{ $tickets->total() }}</strong> tiket
            </p>

            @if ($tickets->hasPages())
                @php
                    $start = max(1, $tickets->currentPage() - 2);
                    $end = min($tickets->lastPage(), $start + 4);
                    $start = max(1, $end - 4);
                @endphp

                <nav class="flex flex-wrap items-center gap-1 text-xs font-medium" aria-label="Navigasi halaman antrean">
                    <button
                        type="button"
                        wire:click="previousPage"
                        @disabled($tickets->onFirstPage())
                        class="rounded-lg border border-rule bg-paper-raised px-3 py-1.5 text-ink-soft transition hover:border-ink hover:text-ink disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Sebelumnya
                    </button>

                    @foreach (range($start, $end) as $page)
                        <button
                            type="button"
                            wire:click="gotoPage({{ $page }})"
                            @class([
                                'rounded-lg px-3 py-1.5 transition',
                                'bg-ink text-white font-semibold' => $tickets->currentPage() === $page,
                                'border border-rule bg-paper-raised text-ink-soft hover:border-ink hover:text-ink' => $tickets->currentPage() !== $page,
                            ])
                            aria-label="Halaman {{ $page }}"
                        >
                            {{ $page }}
                        </button>
                    @endforeach

                    <button
                        type="button"
                        wire:click="nextPage"
                        @disabled(! $tickets->hasMorePages())
                        class="rounded-lg border border-rule bg-paper-raised px-3 py-1.5 text-ink-soft transition hover:border-ink hover:text-ink disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Berikutnya
                    </button>
                </nav>
            @endif
        </div>
    </div>
</div>
