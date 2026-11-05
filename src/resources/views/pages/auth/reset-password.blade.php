@extends('layouts.auth')

@section('title', 'Reset password — '.config('app.name'))

@section('content')
    <div>
        <h2 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Atur password baru</h2>
        <p class="mt-1.5 text-sm leading-relaxed text-ink-soft">
            Gunakan minimal 8 karakter dengan kombinasi huruf besar, huruf kecil, dan angka.
        </p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="mt-5 space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" class="field-label">Alamat email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" class="field mt-1.5" required>
            @error('email')
                <p class="mt-1.5 text-xs font-medium text-signal">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="field-label">Password baru</label>
            <input id="password" name="password" type="password" autocomplete="new-password" class="field mt-1.5" required autofocus>
            @error('password')
                <p class="mt-1.5 text-xs font-medium text-signal">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="field-label">Ulangi password baru</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="field mt-1.5" required>
        </div>

        <button type="submit" class="btn btn-signal w-full">
            Simpan password
            <x-heroicon-o-arrow-right class="size-4" />
        </button>
    </form>
@endsection
