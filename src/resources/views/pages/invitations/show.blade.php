@extends('layouts.auth')

@section('title', 'Undangan bergabung — '.config('app.name'))

@section('content')
    <div>
        <h2 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">
            Undangan bergabung
        </h2>
        <p class="mt-1.5 text-sm leading-relaxed text-ink-soft">
            <span class="font-semibold text-ink">{{ $invitation->inviter?->name ?? 'Admin' }}</span>
            mengundang Anda bergabung dengan
            <span class="font-semibold text-ink">{{ $invitation->organization->name }}</span>
            sebagai <span class="font-semibold text-ink">{{ $invitation->role->label() }}</span>.
        </p>
    </div>

    <dl class="mt-5 divide-y divide-rule overflow-hidden rounded-xl border border-rule bg-paper">
        <div class="flex items-center justify-between gap-4 px-4 py-3">
            <dt class="text-xs text-ink-soft">Organisasi</dt>
            <dd class="text-sm font-semibold text-ink">{{ $invitation->organization->name }}</dd>
        </div>
        <div class="flex items-center justify-between gap-4 px-4 py-3">
            <dt class="text-xs text-ink-soft">Role akses</dt>
            <dd><x-ui.stamp tone="ink">{{ $invitation->role->label() }}</x-ui.stamp></dd>
        </div>
        <div class="flex items-center justify-between gap-4 px-4 py-3">
            <dt class="text-xs text-ink-soft">Email tujuan</dt>
            <dd class="font-mono text-xs text-ink">{{ $invitation->email }}</dd>
        </div>
        <div class="flex items-center justify-between gap-4 px-4 py-3">
            <dt class="text-xs text-ink-soft">Berlaku sampai</dt>
            <dd class="text-xs font-medium text-ink-soft">{{ $invitation->expires_at->translatedFormat('d M Y H:i') }}</dd>
        </div>
    </dl>

    @if ($status === 'accepted')
        <div class="mt-5 rounded-xl border border-pine/30 bg-pine-tint p-4">
            <p class="text-xs font-medium text-pine">Undangan ini sudah diterima.</p>
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-signal mt-4 w-full">Buka portal</a>
    @elseif ($status === 'expired')
        <div class="mt-5 rounded-xl border border-signal/30 bg-signal-tint p-4">
            <p class="text-xs font-medium text-signal">
                Undangan sudah kedaluwarsa. Minta admin organisasi untuk mengirim ulang undangan baru.
            </p>
        </div>
        <a href="{{ route('home') }}" class="btn btn-outline mt-4 w-full">Kembali ke beranda</a>
    @elseif (auth()->check())
        @if ($emailMatches)
            <form method="POST" action="{{ route('invitations.accept', ['token' => $token]) }}" class="mt-5">
                @csrf
                <button type="submit" class="btn btn-signal w-full">
                    Terima undangan
                    <x-heroicon-o-arrow-right class="size-4" />
                </button>
            </form>
        @else
            <div class="mt-5 rounded-xl border border-ochre/30 bg-ochre-tint p-4">
                <p class="text-xs font-medium leading-relaxed text-ochre">
                    Anda masuk sebagai <span class="font-mono">{{ auth()->user()->email }}</span>, sedangkan undangan ini ditujukan untuk <span class="font-mono">{{ $invitation->email }}</span>.
                </p>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="mt-4">
                @csrf
                <button type="submit" class="btn btn-outline w-full">Keluar dan masuk dengan email yang sesuai</button>
            </form>
        @endif
    @else
        <div class="mt-5 space-y-2.5">
            <a href="{{ route('login', ['email' => $invitation->email, 'redirect' => route('invitations.show', ['token' => $token], false)]) }}" class="btn btn-signal w-full">
                Masuk untuk menerima
                <x-heroicon-o-arrow-right class="size-4" />
            </a>
            <a href="{{ route('register', ['email' => $invitation->email, 'redirect' => route('invitations.show', ['token' => $token], false)]) }}" class="btn btn-outline w-full">
                Daftar dengan email ini
            </a>
        </div>
        <p class="mt-4 text-center text-xs text-ink-faint">
            Gunakan alamat email <span class="font-medium text-ink-soft">{{ $invitation->email }}</span> agar undangan dapat divalidasi.
        </p>
    @endif
@endsection
