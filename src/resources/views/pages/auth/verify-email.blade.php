@extends('layouts.auth')

@section('title', 'Verifikasi email — '.config('app.name'))

@section('content')
    <div>
        <h2 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Cek email Anda</h2>
        <p class="mt-1.5 text-sm leading-relaxed text-ink-soft">
            Kami telah mengirim tautan verifikasi ke
            <span class="font-semibold text-ink">{{ $email ?? 'email Anda' }}</span>.
            Buka tautan tersebut untuk mengaktifkan akses penuh.
        </p>
    </div>

    <div class="mt-5 rounded-xl border border-rule bg-paper p-4">
        <p class="text-xs leading-relaxed text-ink-soft">
            Tidak menerima email? Periksa folder spam atau promosi, atau klik tombol di bawah untuk mengirim ulang tautan verifikasi.
        </p>
    </div>

    <form method="POST" action="{{ route('verification.send') }}" class="mt-4">
        @csrf
        <button type="submit" class="btn btn-outline w-full">
            Kirim ulang tautan verifikasi
            <x-heroicon-o-paper-airplane class="size-4" />
        </button>
    </form>

    <div class="mt-6 flex items-center justify-between border-t border-rule pt-4 text-xs">
        <a href="{{ route('dashboard') }}" class="font-medium text-ink-soft transition-colors hover:text-ink">Lanjut ke portal</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="font-medium text-signal hover:underline">Keluar dari akun</button>
        </form>
    </div>
@endsection
