@extends('layouts.admin')

@section('title', 'Laporan — Admin '.config('app.name'))

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Laporan tiket</h1>
            <p class="mt-1 text-sm text-ink-soft">Ringkasan performa layanan <span class="font-medium text-ink">{{ $organization->name }}</span> dalam {{ $days }} hari terakhir.</p>
        </div>
        <div class="self-start sm:self-auto">
            <a href="{{ route('admin.reports.export') }}" class="btn btn-signal">
                <x-heroicon-o-arrow-down-tray class="size-4" />
                Unduh CSV
            </a>
        </div>
    </div>

    <dl class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['Tiket dibuat', $summary['created'], 'Total tiket masuk pada periode ini'],
            ['Tiket selesai', $summary['resolved'], 'Tiket mencapai status resolved/closed'],
            ['Rata-rata resolusi', $summary['resolution'] === null ? '—' : $summary['resolution'].' mnt', 'Durasi rata-rata penyelesaian tiket'],
            ['First response', $summary['first_response'] === null ? '—' : $summary['first_response'].' mnt', 'Waktu rata-rata balasan pertama agent'],
        ] as [$label, $value, $caption])
            <div class="sheet p-5">
                <dt class="text-xs font-medium text-ink-soft">{{ $label }}</dt>
                <dd class="mt-2 font-display text-3xl font-bold tracking-tight text-ink">{{ $value }}</dd>
                <p class="mt-1.5 text-xs text-ink-faint">{{ $caption }}</p>
            </div>
        @endforeach
    </dl>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1.45fr_0.85fr]">
        <section class="sheet overflow-hidden self-start">
            <div class="flex items-center justify-between gap-3 border-b border-rule px-5 py-4">
                <div>
                    <h2 class="font-display text-base font-semibold text-ink">Tren tiket harian</h2>
                    <p class="mt-0.5 text-xs text-ink-faint">Volume tiket dibuat, diselesaikan, dan kepatuhan SLA per hari.</p>
                </div>
            </div>

            <div class="table-scroll">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-rule bg-paper/70 text-xs font-semibold text-ink-soft">
                            <th class="px-5 py-3">Tanggal</th>
                            <th class="px-5 py-3">Dibuat</th>
                            <th class="px-5 py-3">Selesai</th>
                            <th class="px-5 py-3 text-right">SLA on-time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rule">
                        @forelse ($stats as $stat)
                            @php
                                $slaRate = $stat->sla_evaluated_count
                                    ? round($stat->sla_on_time_count / $stat->sla_evaluated_count * 100)
                                    : null;
                            @endphp
                            <tr class="transition-colors hover:bg-paper/60">
                                <td class="whitespace-nowrap px-5 py-3.5 font-medium text-ink">{{ $stat->stat_date->format('d M Y') }}</td>
                                <td class="whitespace-nowrap px-5 py-3.5 font-mono text-xs text-ink">{{ $stat->created_count }}</td>
                                <td class="whitespace-nowrap px-5 py-3.5 font-mono text-xs text-ink">{{ $stat->resolved_count }}</td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                    @if ($slaRate !== null)
                                        <x-ui.stamp :tone="$slaRate >= 90 ? 'resolved' : ($slaRate >= 70 ? 'pending' : 'breached')">{{ $slaRate }}%</x-ui.stamp>
                                    @else
                                        <span class="font-mono text-xs text-ink-faint">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-12 text-center text-sm text-ink-soft">
                                    Belum ada agregasi harian. Jalankan <code class="rounded bg-paper px-1.5 py-0.5 font-mono text-xs text-ink">support:daily-stats</code>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="sheet overflow-hidden self-start">
            <div class="border-b border-rule px-5 py-4">
                <h2 class="font-display text-base font-semibold text-ink">Beban tiket per agent</h2>
                <p class="mt-0.5 text-xs text-ink-faint">Distribusi tiket berdasarkan penugasan saat ini.</p>
            </div>

            <ul class="divide-y divide-rule">
                @forelse ($workload as $row)
                    <li class="flex items-center justify-between gap-3 px-5 py-3.5">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="grid size-8 shrink-0 place-items-center rounded-full border border-rule bg-paper font-mono text-xs font-semibold text-ink-soft">
                                {{ $row->assignee?->initials() ?? '—' }}
                            </span>
                            <span class="truncate text-sm font-medium text-ink">{{ $row->assignee?->name ?? 'Belum ditugaskan' }}</span>
                        </div>
                        <span class="rounded-lg bg-paper px-2.5 py-1 font-mono text-xs font-semibold text-ink">{{ $row->total }} tiket</span>
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-sm text-ink-soft">Belum ada data beban kerja.</li>
                @endforelse
            </ul>
        </section>
    </div>
@endsection
