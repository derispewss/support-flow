@extends('layouts.auth')

@section('title', 'Bergabung ke organisasi — '.config('app.name'))
@section('auth_container_class', 'max-w-3xl')
@section('auth_sheet_class', 'p-6 sm:p-8 lg:p-10')

@section('content')
    <div class="flex items-start justify-between gap-4">
        <div>
            <span class="label-mono">Langkah berikutnya</span>
            <h1 class="mt-3 font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">
                Tunggu akses workspace
            </h1>
            <p class="mt-2 max-w-xl text-sm leading-6 text-ink-soft">
                Anda terdaftar sebagai {{ $role }}. Gunakan undangan dari organisasi untuk mulai bekerja.
            </p>
        </div>
        <x-ui.logo compact class="hidden sm:inline-flex" />
    </div>

    <div class="mt-8 rounded-xl border border-steel/20 bg-steel-soft p-4">
        <div class="flex gap-3">
            <x-heroicon-o-information-circle class="mt-0.5 size-5 shrink-0 text-steel" />
            <div>
                <p class="text-sm font-semibold text-ink">Belum ada organisasi yang terhubung</p>
                <p class="mt-1 text-xs leading-5 text-ink-soft">
                    Minta admin organisasi mengundang <strong>{{ auth()->user()->email }}</strong>. Undangan akan muncul di sini dan juga dikirim lewat email.
                </p>
            </div>
        </div>
    </div>

    @if ($invitations->isNotEmpty())
        <div class="mt-8">
            <h2 class="text-sm font-semibold text-ink">Undangan yang tersedia</h2>
            <div class="mt-3 space-y-3">
                @foreach ($invitations as $invitation)
                    <div class="flex flex-col gap-3 rounded-xl border border-rule p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-semibold text-ink">{{ $invitation->organization->name }}</p>
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

    <div class="mt-8 flex flex-wrap gap-2">
        <a href="{{ route('help.index') }}" class="btn btn-outline btn-sm">Buka pusat bantuan</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-ghost btn-sm">Keluar</button>
        </form>
    </div>
@endsection
