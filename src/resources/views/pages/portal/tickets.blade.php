@extends('layouts.portal')

@section('title', 'Tiket Saya — '.config('app.name'))

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="font-display text-xl font-semibold tracking-tight text-ink sm:text-2xl">Tiket Saya</h1>
            <p class="mt-0.5 text-sm text-ink-faint">Daftar seluruh permintaan bantuan Anda beserta progres penanganannya.</p>
        </div>
    </div>

    <div class="sheet mt-5 overflow-hidden">
        <form method="GET" action="{{ route('tickets.index') }}" class="flex flex-col gap-3 border-b border-rule bg-paper/40 p-4 sm:flex-row sm:items-center sm:px-5">
            <div class="flex items-center gap-2.5">
                <label for="ticket-filter" class="text-xs font-medium text-ink-faint">Status</label>
                <select id="ticket-filter" name="status" class="field sm:w-44">
                    <option value="">Semua status</option>
                    @foreach ($statuses as $option)
                        <option value="{{ $option->value }}" @selected($status === $option)>{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="relative flex-1 sm:ml-auto sm:max-w-xs">
                <label for="ticket-search" class="sr-only">Cari tiket</label>
                <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-ink-faint">
                    <x-heroicon-o-magnifying-glass class="size-4" />
                </span>
                <input id="ticket-search" type="search" name="search" value="{{ $search }}" placeholder="Cari nomor atau subjek tiket…" class="field pl-9">
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="btn btn-ink btn-sm flex-1 sm:flex-initial">Terapkan</button>

                @if ($status !== null || $search !== '')
                    <a href="{{ route('tickets.index') }}" class="btn btn-outline btn-sm">Reset</a>
                @endif
            </div>
        </form>

        @if ($tickets->isNotEmpty())
            <div class="table-scroll">
                <table class="min-w-full divide-y divide-rule text-left text-sm">
                    <thead class="bg-paper/50 text-xs font-medium text-ink-faint">
                        <tr>
                            <th scope="col" class="px-5 py-2.5">No. tiket</th>
                            <th scope="col" class="px-5 py-2.5">Subjek</th>
                            <th scope="col" class="px-5 py-2.5">Kategori</th>
                            <th scope="col" class="px-5 py-2.5">Prioritas</th>
                            <th scope="col" class="px-5 py-2.5 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rule">
                        @foreach ($tickets as $ticket)
                            <tr class="transition-colors hover:bg-paper/40">
                                <td class="whitespace-nowrap px-5 py-3.5">
                                    <a href="{{ route('tickets.show', $ticket) }}" class="rounded border border-rule bg-paper/60 px-1.5 py-0.5 font-mono text-[11px] font-medium text-ink-soft hover:border-rule-strong hover:text-ink">
                                        {{ $ticket->ticket_number }}
                                    </a>
                                </td>
                                <td class="px-5 py-3.5">
                                    <a href="{{ route('tickets.show', $ticket) }}" class="font-medium text-ink hover:underline">
                                        {{ $ticket->subject }}
                                    </a>
                                    <p class="mt-0.5 text-xs text-ink-faint">
                                        Diperbarui {{ $ticket->last_activity_at?->diffForHumans() ?? 'baru saja' }}
                                    </p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-xs text-ink-soft">
                                    {{ $ticket->category?->name ?? 'Tanpa kategori' }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium {{ in_array($ticket->priority->value, ['high', 'urgent'], true) ? 'text-signal-dark' : 'text-ink-soft' }}">
                                        <span class="size-1.5 rounded-full {{ in_array($ticket->priority->value, ['high', 'urgent'], true) ? 'bg-signal' : 'bg-rule-strong' }}" aria-hidden="true"></span>
                                        {{ $ticket->priority->label() }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                    <x-ui.stamp :tone="$ticket->status->tone()">{{ $ticket->status->label() }}</x-ui.stamp>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-5 py-14 text-center">
                <div class="mx-auto grid size-10 place-items-center rounded-lg border border-rule bg-paper text-ink-faint">
                    <x-heroicon-o-inbox class="size-5" />
                </div>
                <p class="mt-3 text-sm font-semibold text-ink">Tidak ada tiket yang cocok.</p>
                <p class="mt-1 text-xs text-ink-faint">Ubah filter pencarian atau buat tiket baru untuk memulai.</p>
                <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
                    <a href="{{ route('tickets.index') }}" class="btn btn-outline btn-sm">Reset filter</a>
                </div>
            </div>
        @endif

        <div class="flex flex-col gap-3 border-t border-rule bg-paper/40 px-5 py-3 text-xs sm:flex-row sm:items-center sm:justify-between">
            <p class="text-ink-faint">
                Menampilkan <span class="font-semibold text-ink">{{ $tickets->firstItem() ?? 0 }}–{{ $tickets->lastItem() ?? 0 }}</span> dari <span class="font-semibold text-ink">{{ $tickets->total() }}</span> tiket
            </p>

            @if ($tickets->lastPage() > 1)
                <nav class="flex flex-wrap items-center gap-1.5" aria-label="Navigasi halaman tiket">
                    @if ($tickets->onFirstPage())
                        <span class="rounded-md border border-rule px-2.5 py-1 text-ink-faint">Sebelumnya</span>
                    @else
                        <a href="{{ $tickets->previousPageUrl() }}" class="rounded-md border border-rule bg-paper-raised px-2.5 py-1 font-medium text-ink-soft transition-colors hover:border-rule-strong hover:text-ink">Sebelumnya</a>
                    @endif

                    @foreach ($tickets->getUrlRange(max(1, $tickets->currentPage() - 2), min($tickets->lastPage(), $tickets->currentPage() + 2)) as $page => $url)
                        @if ($page === $tickets->currentPage())
                            <span class="rounded-md bg-ink px-2.5 py-1 font-semibold text-white">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="rounded-md border border-rule bg-paper-raised px-2.5 py-1 font-medium text-ink-soft transition-colors hover:border-rule-strong hover:text-ink">{{ $page }}</a>
                        @endif
                    @endforeach

                    @if ($tickets->hasMorePages())
                        <a href="{{ $tickets->nextPageUrl() }}" class="rounded-md border border-rule bg-paper-raised px-2.5 py-1 font-medium text-ink-soft transition-colors hover:border-rule-strong hover:text-ink">Berikutnya</a>
                    @else
                        <span class="rounded-md border border-rule px-2.5 py-1 text-ink-faint">Berikutnya</span>
                    @endif
                </nav>
            @endif
        </div>
    </div>
@endsection
