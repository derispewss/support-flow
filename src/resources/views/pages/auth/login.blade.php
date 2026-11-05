@extends('layouts.auth')

@section('title', 'Masuk — '.config('app.name'))

@section('content')
    <div>
        <h2 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Masuk ke akun Anda</h2>
        <p class="mt-1.5 text-sm text-ink-soft">
                Belum punya akun?
                <a href="{{ route('register', array_filter(['redirect' => $redirect ?? null])) }}" class="font-semibold text-signal hover:underline">Daftar gratis</a>
        </p>
        <a href="{{ route('auth.google.redirect') }}" class="btn btn-outline mt-5 w-full">
                <svg class="size-4" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.27-4.74 3.27-8.1Z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.65l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84A11 11 0 0 0 12 23Z"/><path fill="#FBBC05" d="M5.84 14.11a6.6 6.6 0 0 1 0-4.22V7.05H2.18a11 11 0 0 0 0 9.9l3.66-2.84Z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15A11 11 0 0 0 2.18 7.05l3.66 2.84c.87-2.6 3.3-4.51 6.16-4.51Z"/></svg>
                Lanjutkan dengan Google
        </a>
        <div class="my-5 flex items-center gap-3">
            <span class="h-px flex-1 bg-rule"></span>
            <span class="text-xs text-ink-faint">atau masuk dengan email</span>
            <span class="h-px flex-1 bg-rule"></span>
        </div>
        <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                @if (! empty($redirect))
                    <input type="hidden" name="redirect" value="{{ $redirect }}" />
                @endif
                <div>
                    <label for="email" class="field-label">Alamat email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $email ?? '') }}" autocomplete="email" placeholder="nama@perusahaan.com"
                        class="field mt-1.5" required autofocus />
                    @error('email')
                        <p class="mt-1.5 text-xs font-medium text-signal">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <div class="flex items-center justify-between gap-3">
                        <label for="password" class="field-label">Password</label>
                        <a href="{{ route('password.request') }}" class="text-xs font-medium text-signal hover:underline">Lupa password?</a>
                    </div>
                    <input id="password" name="password" type="password" autocomplete="current-password" placeholder="••••••••"
                        class="field mt-1.5" required />
                    @error('password')
                        <p class="mt-1.5 text-xs font-medium text-signal">{{ $message }}</p>
                    @enderror
                </div>
                <label class="flex cursor-pointer items-center gap-2.5 text-sm text-ink-soft">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember')) class="size-4 rounded border-rule-strong text-signal focus:ring-2 focus:ring-signal/20" />
                    <span>Ingat saya di perangkat ini</span>
                </label>
                <button type="submit" class="btn btn-signal w-full">
                    Masuk
                    <x-heroicon-o-arrow-right class="size-4" />
                </button>
        </form>
    </div>
@endsection