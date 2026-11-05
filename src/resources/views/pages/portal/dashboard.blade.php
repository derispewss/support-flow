@extends('layouts.portal')

@section('title', 'Dashboard — '.config('app.name'))

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="font-display text-xl font-semibold tracking-tight text-ink sm:text-2xl">
                Halo, {{ str(auth()->user()?->name ?? 'Customer')->before(' ') }}.
            </h1>
            <p class="mt-0.5 text-sm text-ink-faint">
                Ringkasan status tiket dan aktivitas bantuan terakhir Anda di {{ tenant()?->name ?? config('app.name') }}.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('help.index') }}" class="btn btn-outline btn-sm">
                <x-heroicon-o-book-open class="size-4 text-ink-faint" />
                <span>Pusat Bantuan</span>
            </a>
        </div>
    </div>

    @if (! tenant())
        <section class="sheet mt-6 overflow-hidden">
            <div class="grid gap-6 p-6 sm:p-8 lg:grid-cols-[1fr_auto] lg:items-center">
                <div>
                    <span class="label-mono">Belum ada workspace</span>
                    <h2 class="mt-3 max-w-xl font-display text-2xl font-bold tracking-tight text-ink">
                        Anda belum terhubung ke organisasi.
                    </h2>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-ink-soft">
                        Minta owner mengundang email Anda. Undangan yang cocok akan muncul di sini dan bisa langsung Anda terima.
                    </p>
                </div>
                <div class="grid size-20 place-items-center rounded-2xl bg-steel-soft text-steel">
                    <x-heroicon-o-building-office-2 class="size-10" />
                </div>
            </div>
            @if ($invitations->isNotEmpty())
                <div class="border-t border-rule px-6 py-5 sm:px-8">
                    <h3 class="text-sm font-semibold text-ink">Undangan yang tersedia</h3>
                    <div class="mt-3 space-y-3">
                        @foreach ($invitations as $invitation)
                            <div class="flex flex-col gap-3 rounded-lg border border-rule p-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-ink">{{ $invitation->organization->name }}</p>
                                    <p class="mt-1 text-xs text-ink-faint">
                                        Diundang sebagai {{ $invitation->role->label() }} · berlaku sampai {{ $invitation->expires_at->translatedFormat('d M Y') }}
                                    </p>
                                </div>
                                <form method="POST" action="{{ route('invitations.accept-from-customer', $invitation) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-signal btn-sm">Gabung workspace</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            <div class="flex flex-col gap-3 border-t border-rule bg-paper/40 px-6 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                <p class="text-xs text-ink-faint">Undangan dikirim ke <strong class="font-semibold text-ink-soft">{{ auth()->user()->email }}</strong>.</p>
                <a href="{{ route('help.index') }}" class="btn btn-outline btn-sm">Buka pusat bantuan</a>
            </div>
        </section>
    @else
    {{-- shadcn SectionCards --}}
    <dl class="mt-5 grid grid-cols-1 gap-3.5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['Tiket aktif', $counts['active'], 'Baru, terbuka, menunggu', 'heroicon-o-inbox-stack', 'border-rule bg-paper text-ink-soft'],
            ['Menunggu balasan', $counts['pending'], 'Perlu informasi tambahan', 'heroicon-o-clock', $counts['pending'] > 0 ? 'border-ochre/25 bg-ochre-soft text-ochre' : 'border-rule bg-paper text-ink-soft'],
            ['Selesai', $counts['resolved'], 'Sudah ditandai selesai', 'heroicon-o-check-badge', 'border-pine/25 bg-pine-soft text-pine'],
            ['Total tiket', $counts['total'], 'Riwayat sepanjang waktu', 'heroicon-o-archive-box', 'border-steel/25 bg-steel-soft text-steel'],
        ] as [$label, $value, $note, $iconName, $badgeClass])
            <div class="flex flex-col justify-between rounded-xl border border-rule bg-gradient-to-t from-paper/50 to-paper-raised p-4 shadow-2xs">
                <div class="flex items-center justify-between gap-2">
                    <dt class="truncate text-xs font-medium text-ink-faint">{{ $label }}</dt>
                    <span class="grid size-7 shrink-0 place-items-center rounded-md border {{ $badgeClass }}">
                        <x-dynamic-component :component="$iconName" class="size-3.5" />
                    </span>
                </div>
                <dd class="mt-2 font-display text-2xl font-semibold tracking-tight text-ink tabular-nums sm:text-[26px]">
                    {{ $value }}
                </dd>
                <p class="mt-2 truncate text-xs text-ink-faint">{{ $note }}</p>
            </div>
        @endforeach
    </dl>

    <div class="mt-5 grid gap-4 xl:grid-cols-3">
        {{-- shadcn DataTable Section --}}
        <section class="sheet flex flex-col overflow-hidden self-start xl:col-span-2">
            <div class="flex flex-col gap-3 border-b border-rule px-5 py-3.5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-sm font-semibold text-ink">Tiket terbaru</h2>
                        <span class="rounded-md border border-rule bg-paper px-1.5 py-0.5 font-mono text-[11px] font-medium text-ink-faint">
                            {{ $tickets->count() }} / {{ $counts['total'] }}
                        </span>
                    </div>
                    <p class="mt-0.5 text-xs text-ink-faint">Permintaan bantuan terakhir yang Anda kirimkan</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="hidden items-center rounded-lg border border-rule bg-paper/60 p-0.5 text-xs sm:inline-flex">
                        <a href="{{ route('tickets.index') }}" class="rounded-md bg-paper-raised px-2.5 py-1 font-medium text-ink shadow-2xs">Semua</a>
                        <a href="{{ route('tickets.index', ['status' => 'open']) }}" class="rounded-md px-2.5 py-1 font-medium text-ink-faint transition hover:text-ink">Terbuka</a>
                        <a href="{{ route('tickets.index', ['status' => 'pending']) }}" class="rounded-md px-2.5 py-1 font-medium text-ink-faint transition hover:text-ink">Menunggu</a>
                    </div>
                    <a href="{{ route('tickets.index') }}" class="btn btn-outline btn-sm">
                        <span>Lihat semua</span>
                    </a>
                </div>
            </div>

            @if ($tickets->isNotEmpty())
                <div class="table-scroll">
                    <table class="min-w-full divide-y divide-rule text-left text-sm">
                        <thead class="bg-paper/50 text-xs font-medium text-ink-faint">
                            <tr>
                                <th scope="col" class="px-5 py-2.5">Tiket</th>
                                <th scope="col" class="px-5 py-2.5">Kategori</th>
                                <th scope="col" class="px-5 py-2.5">Prioritas</th>
                                <th scope="col" class="px-5 py-2.5">Status</th>
                                <th scope="col" class="px-5 py-2.5 text-right">Aktivitas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-rule">
                            @foreach ($tickets as $ticket)
                                <tr class="transition-colors hover:bg-paper/40">
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-2.5">
                                            <span class="shrink-0 rounded border border-rule bg-paper/60 px-1.5 py-0.5 font-mono text-[11px] font-medium text-ink-soft">
                                                {{ $ticket->ticket_number }}
                                            </span>
                                            <a href="{{ route('tickets.show', $ticket) }}" class="truncate font-medium text-ink hover:underline">
                                                {{ $ticket->subject }}
                                            </a>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-xs text-ink-soft">
                                        {{ $ticket->category?->name ?? 'Tanpa kategori' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3">
                                        <span class="inline-flex items-center gap-1.5 text-xs font-medium {{ in_array($ticket->priority->value, ['high', 'urgent'], true) ? 'text-signal-dark' : 'text-ink-soft' }}">
                                            <span class="size-1.5 rounded-full {{ in_array($ticket->priority->value, ['high', 'urgent'], true) ? 'bg-signal' : 'bg-rule-strong' }}" aria-hidden="true"></span>
                                            {{ $ticket->priority->label() }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3">
                                        <x-ui.stamp :tone="$ticket->status->tone()">{{ $ticket->status->label() }}</x-ui.stamp>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-xs text-ink-faint">
                                        {{ $ticket->last_activity_at?->diffForHumans() ?? 'baru saja' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-5 py-12 text-center">
                    <div class="mx-auto grid size-10 place-items-center rounded-lg border border-rule bg-paper text-ink-faint">
                        <x-heroicon-o-inbox class="size-5" />
                    </div>
                    <p class="mt-3 text-sm font-semibold text-ink">Belum ada tiket</p>
                    <p class="mt-1 text-xs text-ink-faint">Buat tiket pertama Anda dan tim support kami siap membantu.</p>
                </div>
            @endif

            <div class="flex items-center justify-between border-t border-rule bg-paper/40 px-5 py-2.5 text-xs text-ink-faint">
                <span>Portal pelanggan · {{ tenant()?->name }}</span>
                <span class="inline-flex items-center gap-1.5 font-medium text-pine">
                    <span class="size-1.5 rounded-full bg-pine" aria-hidden="true"></span>
                    Kanal terenkripsi
                </span>
            </div>
        </section>

        {{-- Right Side Cards --}}
        <aside class="space-y-4">
            <section class="sheet overflow-hidden">
                <div class="border-b border-rule px-5 py-3.5">
                    <h2 class="text-sm font-semibold text-ink">Panduan populer</h2>
                    <p class="mt-0.5 text-xs text-ink-faint">Solusi cepat sebelum membuat tiket baru</p>
                </div>
                <ul class="divide-y divide-rule">
                    @foreach ([
                        'Cara reset password dengan aman',
                        'Mengatur notifikasi email',
                        'Panduan upload lampiran tiket',
                    ] as $article)
                        <li>
                            <a href="{{ route('help.index') }}" class="group flex items-center justify-between gap-3 px-5 py-3 text-xs font-medium text-ink-soft transition-colors hover:bg-paper/60 hover:text-ink">
                                <span class="leading-snug">{{ $article }}</span>
                                <x-heroicon-o-arrow-up-right class="size-3.5 shrink-0 text-ink-faint transition-colors group-hover:text-ink" />
                            </a>
                        </li>
                    @endforeach
                </ul>
                <div class="border-t border-rule bg-paper/40 px-5 py-2.5">
                    <a href="{{ route('help.index') }}" class="text-xs font-medium text-ink hover:underline">Buka pusat bantuan &rarr;</a>
                </div>
            </section>

            <section class="sheet overflow-hidden p-5">
                <div class="flex items-center gap-2">
                    <span class="grid size-7 place-items-center rounded-md border border-pine/25 bg-pine-soft text-pine">
                        <x-heroicon-o-bolt class="size-3.5" />
                    </span>
                    <span class="text-xs font-semibold text-ink">Respon cepat SLA</span>
                </div>
                <h2 class="mt-3 text-sm font-semibold leading-snug text-ink">
                    Tim support merespons dalam 1&times;24 jam kerja.
                </h2>
                <p class="mt-1 text-xs leading-relaxed text-ink-faint">
                    Sertakan tangkapan layar atau langkah yang sudah dicoba agar penanganan kendala lebih cepat.
                </p>
            </section>
        </aside>
    </div>
    @endif
@endsection
