@extends('layouts.portal')

@section('title', 'Notifikasi — '.config('app.name'))

@section('content')
    <div>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="font-display text-xl font-semibold tracking-tight text-ink sm:text-2xl">Notifikasi</h1>
                <p class="mt-0.5 text-sm text-ink-faint">
                    {{ $hasOrganization ? 'Pembaruan status tiket dan balasan terbaru dari tim support.' : 'Pemberitahuan akun dan aktivitas workspace Anda.' }}
                </p>
            </div>
            @if ($notifications->isNotEmpty())
                <form method="POST" action="{{ route('notifications.read-all') }}" class="self-start sm:self-auto">
                    @csrf
                    <button type="submit" class="btn btn-outline btn-sm">
                        <x-heroicon-o-check class="size-3.5" />
                        <span>Tandai semua dibaca</span>
                    </button>
                </form>
            @endif
        </div>

        @if (! $hasOrganization)
            <section class="sheet mt-5 overflow-hidden">
                <div class="flex flex-col items-center px-5 py-14 text-center">
                    <div class="grid size-12 place-items-center rounded-xl border border-steel/20 bg-steel-soft text-steel">
                        <x-heroicon-o-bell class="size-6" />
                    </div>
                    <p class="mt-4 text-sm font-semibold text-ink">Belum terhubung ke organisasi</p>
                    <p class="mt-1 max-w-md text-xs leading-5 text-ink-faint">
                        Notifikasi umum tetap muncul di sini. Notifikasi tiket dan aktivitas organisasi akan tersedia setelah Anda bergabung ke workspace.
                    </p>
                    <a href="{{ route('dashboard') }}" class="btn btn-outline btn-sm mt-5">Kembali ke dashboard</a>
                </div>
            </section>
        @else
        <div class="sheet mt-5 overflow-hidden divide-y divide-rule">
            @forelse ($notifications as $notification)
                <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="flex flex-col gap-3 p-4 transition-colors sm:flex-row sm:items-center sm:justify-between sm:px-5 {{ $notification->read_at ? 'bg-paper-raised' : 'bg-signal-soft/40' }}">
                    @csrf
                    <div class="flex min-w-0 items-start gap-3">
                        <span class="mt-1.5 size-2 shrink-0 rounded-full {{ $notification->read_at ? 'bg-rule-strong' : 'bg-signal' }}" aria-hidden="true"></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-ink">
                                {{ $notification->data['subject'] ?? $notification->data['event'] ?? 'Pembaruan aktivitas' }}
                            </p>
                            <p class="mt-0.5 text-xs text-ink-soft">
                                {{ $notification->data['ticket_number'] ?? 'Ada aktivitas baru pada akun Anda.' }}
                            </p>
                            <p class="mt-1 text-xs text-ink-faint">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>
                    </div>

                    @if (! $notification->read_at)
                        <button type="submit" class="btn btn-outline btn-sm self-start sm:self-center">
                            Tandai dibaca
                        </button>
                    @else
                        <span class="text-xs text-ink-faint">Sudah dibaca</span>
                    @endif
                </form>
            @empty
                <div class="px-5 py-14 text-center">
                    <div class="mx-auto grid size-10 place-items-center rounded-lg border border-rule bg-paper text-ink-faint">
                        <x-heroicon-o-bell class="size-5" />
                    </div>
                    <p class="mt-3 text-sm font-semibold text-ink">Belum ada notifikasi.</p>
                    <p class="mt-1 text-xs text-ink-faint">Semua pemberitahuan balasan dan perubahan status tiket akan muncul di sini.</p>
                </div>
            @endforelse
        </div>
        @endif

        @if ($hasOrganization && $notifications->hasPages())
            <div class="mt-5">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
@endsection
