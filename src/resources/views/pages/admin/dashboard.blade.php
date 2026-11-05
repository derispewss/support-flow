@extends('layouts.admin')

@section('title', 'Dashboard — Admin '.config('app.name'))

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="font-display text-xl font-semibold tracking-tight text-ink sm:text-2xl">Dashboard agent</h1>
            <p class="mt-0.5 text-sm text-ink-faint">
                Ringkasan operasional antrean tiket, kepatuhan SLA, dan aktivitas tim {{ $organization->name }}.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.reports.index') }}" class="btn btn-outline btn-sm">
                <x-heroicon-o-chart-bar-square class="size-4 text-ink-faint" />
                <span>Laporan</span>
            </a>
            <a href="{{ route('admin.tickets.index') }}" class="btn btn-ink btn-sm">
                <span>Buka queue</span>
                <x-heroicon-o-arrow-right class="size-3.5" />
            </a>
        </div>
    </div>

    <dl class="mt-6 grid grid-cols-2 border-y border-rule bg-paper-raised sm:grid-cols-3 lg:grid-cols-6">
        @foreach ($metrics as $index => $metric)
            @php
                [$iconName, $badgeClass] = match ($index) {
                    0 => ['heroicon-o-inbox-stack', 'border-rule bg-paper text-ink-soft'],
                    1 => [
                        'heroicon-o-user-plus',
                        $metric['tone'] === 'pending' ? 'border-ochre/25 bg-ochre-soft text-ochre' : 'border-pine/25 bg-pine-soft text-pine',
                    ],
                    2 => [
                        'heroicon-o-exclamation-triangle',
                        $metric['tone'] === 'open' ? 'border-signal/25 bg-signal-soft text-signal-dark' : 'border-pine/25 bg-pine-soft text-pine',
                    ],
                    3 => ['heroicon-o-check-badge', 'border-pine/25 bg-pine-soft text-pine'],
                    4 => ['heroicon-o-bolt', 'border-steel/25 bg-steel-soft text-steel'],
                    default => ['heroicon-o-shield-check', 'border-rule bg-paper text-ink-soft'],
                };
            @endphp
            <div class="border-b border-rule px-4 py-4 last:border-b-0 sm:border-b-0 sm:border-r sm:last:border-r-0 lg:px-5">
                <div class="flex items-center justify-between gap-2">
                    <dt class="truncate text-xs font-medium text-ink-faint">{{ $metric['label'] }}</dt>
                    <span class="grid size-6 shrink-0 place-items-center rounded border {{ $badgeClass }}">
                        <x-dynamic-component :component="$iconName" class="size-3.5" />
                    </span>
                </div>
                <dd class="mt-2 font-display text-2xl font-semibold tracking-tight text-ink tabular-nums">
                    {{ $metric['value'] }}
                </dd>
                <p class="mt-1 truncate text-[11px] text-ink-faint">{{ $metric['note'] }}</p>
            </div>
        @endforeach
    </dl>

    <div class="mt-8 grid gap-8 xl:grid-cols-3">
        <section class="flex flex-col justify-between overflow-hidden border-y border-rule xl:col-span-2">
            <div class="flex flex-col gap-3 border-b border-rule px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-ink">Tren Volume Tiket</h2>
                    <p class="mt-0.5 text-xs text-ink-faint">Perbandingan tiket masuk dan selesai dalam 14 hari terakhir</p>
                </div>
                <div class="inline-flex items-center gap-3 self-start rounded-lg border border-rule bg-paper/60 px-3 py-1.5 text-xs font-medium text-ink-soft sm:self-auto">
                    <span class="flex items-center gap-1.5">
                        <span class="size-2 rounded-xs bg-ink" aria-hidden="true"></span>
                        <span>Dibuat</span>
                        <strong class="font-mono font-semibold text-ink">{{ collect($volume)->sum('created') }}</strong>
                    </span>
                    <span class="h-3 w-px bg-rule" aria-hidden="true"></span>
                    <span class="flex items-center gap-1.5">
                        <span class="size-2 rounded-xs bg-pine" aria-hidden="true"></span>
                        <span>Selesai</span>
                        <strong class="font-mono font-semibold text-ink">{{ collect($volume)->sum('resolved') }}</strong>
                    </span>
                </div>
            </div>

            <div class="px-5 pb-4 pt-5">
                <div class="relative h-52 border-b border-rule" role="img" aria-label="Grafik tren tiket 14 hari terakhir">
                    <div class="pointer-events-none absolute inset-0 flex flex-col justify-between" aria-hidden="true">
                        @for ($i = 0; $i < 4; $i++)
                            <span class="h-px w-full border-b border-dashed border-rule"></span>
                        @endfor
                    </div>

                    <div class="relative flex h-full items-end gap-1.5 sm:gap-2">
                        @foreach ($volume as $row)
                            <div
                                class="group relative flex h-full flex-1 items-end justify-center gap-0.5 rounded-t-md px-0.5 transition hover:bg-paper/80"
                                title="{{ $row['day']->translatedFormat('d M') }}: {{ $row['created'] }} dibuat, {{ $row['resolved'] }} selesai"
                            >
                                <div
                                    class="w-1/2 rounded-t-xs bg-ink transition-all group-hover:bg-ink-soft"
                                    style="height: {{ $row['created'] > 0 ? max($row['created_height'], 4) : 0 }}%"
                                ></div>
                                <div
                                    class="w-1/2 rounded-t-xs bg-pine transition-all group-hover:bg-pine/80"
                                    style="height: {{ $row['resolved'] > 0 ? max($row['resolved_height'], 4) : 0 }}%"
                                ></div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-2.5 flex justify-between text-[11px] font-medium text-ink-faint">
                    @foreach ([0, 3, 6, 9, 13] as $index)
                        @if (isset($volume[$index]))
                            <span>{{ $volume[$index]['day']->translatedFormat('d M') }}</span>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>

        <section class="flex flex-col overflow-hidden border-y border-rule">
            <div class="flex items-center justify-between border-b border-rule px-5 py-4">
                <div>
                    <h2 class="text-sm font-semibold text-ink">Pantauan Risiko SLA</h2>
                    <p class="mt-0.5 text-xs text-ink-faint">Tiket mendekati atau melewati tenggat</p>
                </div>
                @if ($risk->isNotEmpty())
                    <x-ui.stamp tone="open">{{ $risk->count() }} risiko</x-ui.stamp>
                @endif
            </div>

            <ul class="flex-1 divide-y divide-rule">
                @forelse ($risk as $ticket)
                    <li>
                        <a href="{{ route('admin.tickets.show', $ticket) }}" class="flex items-start gap-3 px-5 py-3 transition hover:bg-paper/60">
                            <span class="mt-0.5 grid size-7 shrink-0 place-items-center rounded-md border {{ $ticket->due_at?->isPast() ? 'border-signal/30 bg-signal-soft text-signal-dark' : 'border-ochre/30 bg-ochre-soft text-ochre' }}">
                                <x-heroicon-o-clock class="size-3.5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-mono text-[11px] font-medium text-ink-faint">{{ $ticket->ticket_number }}</span>
                                    <span class="text-[11px] font-semibold {{ $ticket->due_at?->isPast() ? 'text-signal-dark' : 'text-ochre' }}">
                                        {{ $ticket->due_at?->isPast() ? 'Lewat' : 'Sisa' }} {{ $ticket->due_at?->diffForHumans(short: true) }}
                                    </span>
                                </div>
                                <p class="mt-0.5 truncate text-xs font-semibold text-ink">{{ $ticket->subject }}</p>
                                <p class="mt-0.5 truncate text-[11px] text-ink-faint">{{ $ticket->requester?->name ?? 'Customer' }}</p>
                            </div>
                        </a>
                    </li>
                @empty
                    <li class="flex flex-col items-center justify-center px-5 py-10 text-center">
                        <div class="grid size-9 place-items-center rounded-lg border border-pine/20 bg-pine-soft text-pine">
                            <x-heroicon-o-check-circle class="size-5" />
                        </div>
                        <p class="mt-2.5 text-xs font-semibold text-ink">Semua SLA Terkendali</p>
                        <p class="mt-0.5 text-xs text-ink-faint">Tidak ada tiket terbuka yang mendekati batas SLA.</p>
                    </li>
                @endforelse
            </ul>

            <div class="flex items-center justify-between border-t border-rule bg-paper/40 px-5 py-2.5 text-xs">
                <span class="text-ink-faint">{{ $risk->count() }} tiket dalam pantauan</span>
                <a href="{{ route('admin.tickets.index', ['view' => 'overdue']) }}" class="font-medium text-ink hover:underline">
                    Tinjau semua &rarr;
                </a>
            </div>
        </section>
    </div>

    <section class="mt-8 overflow-hidden border-y border-rule">
        <div class="flex flex-col gap-3 border-b border-rule px-5 py-3.5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-semibold text-ink">Queue tiket</h2>
                <p class="mt-0.5 text-xs text-ink-faint">Daftar tiket dengan aktivitas terbaru di organisasi Anda</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="hidden items-center rounded-lg border border-rule bg-paper/60 p-0.5 text-xs sm:inline-flex">
                    <a href="{{ route('admin.tickets.index') }}" class="rounded-md bg-paper-raised px-2.5 py-1 font-medium text-ink shadow-2xs">Terbaru</a>
                    <a href="{{ route('admin.tickets.index', ['view' => 'unassigned']) }}" class="rounded-md px-2.5 py-1 font-medium text-ink-faint transition hover:text-ink">Belum ditugaskan</a>
                    <a href="{{ route('admin.tickets.index', ['view' => 'overdue']) }}" class="rounded-md px-2.5 py-1 font-medium text-ink-faint transition hover:text-ink">Lewat SLA</a>
                </div>
                <a href="{{ route('admin.tickets.index') }}" class="btn btn-outline btn-sm">
                    <span>Lihat semua</span>
                </a>
            </div>
        </div>

        <div class="table-scroll">
            <table class="min-w-full divide-y divide-rule text-left text-sm">
                <thead class="bg-paper/50 text-xs font-medium text-ink-faint">
                    <tr>
                        <th scope="col" class="px-5 py-2.5">Tiket</th>
                        <th scope="col" class="px-5 py-2.5">Customer</th>
                        <th scope="col" class="px-5 py-2.5">Status</th>
                        <th scope="col" class="px-5 py-2.5">Prioritas</th>
                        <th scope="col" class="px-5 py-2.5">Assignee</th>
                        <th scope="col" class="px-5 py-2.5 text-right">Aktivitas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-rule">
                    @forelse ($queue as $ticket)
                        <tr class="transition-colors hover:bg-paper/40">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="shrink-0 rounded border border-rule bg-paper/60 px-1.5 py-0.5 font-mono text-[11px] font-medium text-ink-soft">
                                        {{ $ticket->ticket_number }}
                                    </span>
                                    <a href="{{ route('admin.tickets.show', $ticket) }}" class="truncate font-medium text-ink hover:underline">
                                        {{ $ticket->subject }}
                                    </a>
                                </div>
                                @if ($ticket->category)
                                    <p class="mt-0.5 text-xs text-ink-faint">{{ $ticket->category->name }}</p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-xs font-medium text-ink-soft">
                                {{ $ticket->requester?->name ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-3">
                                <x-ui.stamp :tone="$ticket->status->tone()">{{ $ticket->status->label() }}</x-ui.stamp>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3">
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium {{ in_array($ticket->priority->value, ['high', 'urgent'], true) ? 'text-signal-dark' : 'text-ink-soft' }}">
                                    <span class="size-1.5 rounded-full {{ in_array($ticket->priority->value, ['high', 'urgent'], true) ? 'bg-signal' : 'bg-rule-strong' }}" aria-hidden="true"></span>
                                    {{ $ticket->priority->label() }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-xs text-ink-soft">
                                {{ $ticket->assignee?->name ?? 'Belum ditugaskan' }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-right text-xs text-ink-faint">
                                {{ $ticket->last_activity_at?->diffForHumans() ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-xs text-ink-faint">
                                Belum ada tiket di antrean saat ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-2 border-t border-rule bg-paper/40 px-5 py-2.5 text-xs sm:flex-row sm:items-center sm:justify-between">
            <p class="text-ink-faint">Menampilkan {{ $queue->count() }} tiket dengan aktivitas terbaru</p>
            <a href="{{ route('admin.tickets.index') }}" class="font-medium text-ink hover:underline">Buka antrean lengkap &rarr;</a>
        </div>
    </section>
@endsection
