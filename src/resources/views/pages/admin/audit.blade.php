@extends('layouts.admin')

@section('title', 'Audit log — '.config('app.name'))

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Audit log</h1>
            <p class="mt-1 text-sm text-ink-soft">Jejak aktivitas penting pada tiket, master data, dan anggota <span class="font-medium text-ink">{{ $organization->name }}</span>.</p>
        </div>
        <div class="self-start sm:self-auto">
            <x-ui.stamp tone="ink">{{ $logs->total() }} entri</x-ui.stamp>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.audit.index') }}" class="sheet mt-6 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-[14rem_14rem_1fr_auto] lg:items-end sm:p-5">
        <div>
            <label for="audit-event" class="field-label">Peristiwa</label>
            <select id="audit-event" name="event" class="field mt-1.5">
                <option value="">Semua peristiwa</option>
                @foreach ($events as $option)
                    <option value="{{ $option }}" @selected($event === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="audit-actor" class="field-label">Aktor</label>
            <select id="audit-actor" name="actor" class="field mt-1.5">
                <option value="">Semua aktor</option>
                @foreach ($actors as $membership)
                    <option value="{{ $membership->user_id }}" @selected($actor === (int) $membership->user_id)>{{ $membership->user?->name ?? 'Anggota' }}</option>
                @endforeach
            </select>
        </div>

        <div class="sm:col-span-2 lg:col-span-1">
            <label for="audit-search" class="field-label">Cari deskripsi</label>
            <input id="audit-search" name="q" type="search" value="{{ $search }}" class="field mt-1.5" placeholder="Nomor tiket, nama, atau kata kunci…">
        </div>

        <div class="flex items-center gap-2 sm:col-span-2 lg:col-span-1">
            <button type="submit" class="btn btn-ink flex-1 lg:flex-initial">Terapkan</button>
            @if ($event !== '' || $actor > 0 || $search !== '')
                <a href="{{ route('admin.audit.index') }}" class="btn btn-outline">Reset</a>
            @endif
        </div>
    </form>

    <section class="sheet mt-4 overflow-hidden">
        <div class="table-scroll">
            <table class="w-full min-w-[740px] text-left text-sm">
                <thead>
                    <tr class="border-b border-rule bg-paper/70 text-xs font-semibold text-ink-soft">
                        <th scope="col" class="px-5 py-3">Waktu</th>
                        <th scope="col" class="px-5 py-3">Peristiwa</th>
                        <th scope="col" class="px-5 py-3">Deskripsi</th>
                        <th scope="col" class="px-5 py-3">Aktor</th>
                        <th scope="col" class="px-5 py-3 text-right">Alamat IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-rule">
                    @forelse ($logs as $log)
                        <tr class="transition-colors hover:bg-paper/60">
                            <td class="whitespace-nowrap px-5 py-3.5 font-mono text-xs text-ink-faint">
                                {{ $log->created_at?->translatedFormat('d M Y, H:i') }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5">
                                <x-ui.stamp tone="ink">{{ $log->eventLabel() }}</x-ui.stamp>
                                <p class="mt-1 font-mono text-[11px] text-ink-faint">{{ $log->event }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-sm leading-relaxed text-ink">
                                {{ $log->description }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5">
                                <p class="text-sm font-medium text-ink">{{ $log->actor?->name ?? 'Sistem' }}</p>
                                <p class="text-xs text-ink-faint">{{ $log->actor?->email ?? '—' }}</p>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right font-mono text-xs text-ink-faint">
                                {{ $log->ip_address ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-14 text-center">
                                <x-heroicon-o-clipboard-document-list class="mx-auto size-9 text-ink-faint" />
                                <p class="mt-3 font-display text-base font-semibold text-ink">Belum ada aktivitas tercatat.</p>
                                <p class="mt-1 text-sm text-ink-soft">Audit log terisi otomatis saat tiket, anggota, dan master data dikelola.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-rule bg-paper/50 px-5 py-3.5 text-xs">
                <p class="text-ink-soft">Halaman <span class="font-semibold text-ink">{{ $logs->currentPage() }}</span> dari <span class="font-semibold text-ink">{{ $logs->lastPage() }}</span></p>
                <div class="flex items-center gap-2">
                    @if ($logs->onFirstPage())
                        <span class="rounded-lg border border-rule px-3 py-1.5 text-ink-faint">Sebelumnya</span>
                    @else
                        <a href="{{ $logs->previousPageUrl() }}" class="rounded-lg border border-rule bg-paper-raised px-3 py-1.5 font-medium text-ink-soft transition-colors hover:border-rule-strong hover:text-ink">Sebelumnya</a>
                    @endif

                    @if ($logs->hasMorePages())
                        <a href="{{ $logs->nextPageUrl() }}" class="rounded-lg border border-rule bg-paper-raised px-3 py-1.5 font-medium text-ink-soft transition-colors hover:border-rule-strong hover:text-ink">Berikutnya</a>
                    @else
                        <span class="rounded-lg border border-rule px-3 py-1.5 text-ink-faint">Berikutnya</span>
                    @endif
                </div>
            </div>
        @endif
    </section>
@endsection
