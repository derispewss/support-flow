@extends('layouts.auth')

@section('title', 'Lupa password — '.config('app.name'))

@section('content')
    <div>
        <h2 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Lupa password?</h2>
        <p class="mt-1.5 text-sm leading-relaxed text-ink-soft">
            Masukkan alamat email akun Anda. Kami akan mengirimkan tautan untuk mengatur ulang password.
        </p>
    </div>

    <form method="POST" action="{{ route('password.email') }}" class="mt-5 space-y-4">
        @csrf

        <div>
            <label for="email" class="field-label">Alamat email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" placeholder="nama@perusahaan.com" class="field mt-1.5" required autofocus>
            @error('email')
                <p class="mt-1.5 text-xs font-medium text-signal">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn btn-signal w-full">
            Kirim tautan reset
            <x-heroicon-o-paper-airplane class="size-4" />
        </button>
    </form>

    <div class="mt-6 border-t border-rule pt-4 text-center">
        <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-ink-soft transition-colors hover:text-ink">
            <x-heroicon-o-arrow-left class="size-3.5" />
            Kembali ke halaman masuk
        </a>
    </div>
@endsection
