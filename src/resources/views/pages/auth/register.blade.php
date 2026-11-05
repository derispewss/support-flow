@extends('layouts.auth')

@section('title', 'Daftar — '.config('app.name'))

@section('content')
    <div>
        <h2 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Buat akun gratis</h2>
        <p class="mt-1.5 text-sm text-ink-soft">
            Sudah punya akun?
            <a href="{{ route('login', array_filter(['redirect' => request('redirect')])) }}" class="font-semibold text-signal hover:underline">Masuk</a>
        </p>
    </div>

    <a href="{{ route('auth.google.redirect') }}" class="btn btn-outline mt-5 w-full">
        <svg class="size-4" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.27-4.74 3.27-8.1Z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.65l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84A11 11 0 0 0 12 23Z"/><path fill="#FBBC05" d="M5.84 14.11a6.6 6.6 0 0 1 0-4.22V7.05H2.18a11 11 0 0 0 0 9.9l3.66-2.84Z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15A11 11 0 0 0 2.18 7.05l3.66 2.84c.87-2.6 3.3-4.51 6.16-4.51Z"/></svg>
        Daftar dengan Google
    </a>

    <div class="my-5 flex items-center gap-3">
        <span class="h-px flex-1 bg-rule"></span>
        <span class="text-xs text-ink-faint">atau daftar dengan email</span>
        <span class="h-px flex-1 bg-rule"></span>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        @if (request('redirect'))
            <input type="hidden" name="redirect" value="{{ request('redirect') }}" />
        @endif

        <div>
            <label for="name" class="field-label">Nama lengkap</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" placeholder="Nama Anda" class="field mt-1.5" required autofocus>
            @error('name')
                <p class="mt-1.5 text-xs font-medium text-signal">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="field-label">Email kerja</label>
            <input id="email" name="email" type="email" value="{{ old('email', request('email')) }}" autocomplete="email" placeholder="nama@perusahaan.com" class="field mt-1.5" required />
            @error('email')
                <p class="mt-1.5 text-xs font-medium text-signal">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-3.5 sm:grid-cols-2">
            <div>
                <label for="password" class="field-label">Password</label>
                <input id="password" name="password" type="password" autocomplete="new-password" placeholder="Min. 8 karakter" class="field mt-1.5" required />
                @error('password')
                    <p class="mt-1.5 text-xs font-medium text-signal">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="password_confirmation" class="field-label">Ulangi password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" placeholder="Ulangi password" class="field mt-1.5" required />
            </div>
        </div>

        <div>
            <label for="organization_name" class="field-label">Nama organisasi <span class="font-normal text-ink-faint">(opsional)</span></label>
            <input id="organization_name" name="organization_name" type="text" value="{{ old('organization_name') }}" autocomplete="organization" placeholder="Contoh: Acme Support" class="field mt-1.5">
            @error('organization_name')
                <p class="mt-1.5 text-xs font-medium text-signal">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-start gap-2.5 text-xs leading-relaxed text-ink-soft">
            <input type="checkbox" name="terms" value="1" @checked(old('terms')) class="mt-0.5 size-4 shrink-0 rounded border-rule-strong text-signal focus:ring-2 focus:ring-signal/20" required>
            <span>Saya menyetujui Ketentuan Layanan dan Kebijakan Privasi {{ config('app.name') }}.</span>
        </label>
        @error('terms')
            <p class="-mt-2 text-xs font-medium text-signal">{{ $message }}</p>
        @enderror

        <button type="submit" class="btn btn-signal w-full">
            Buat akun
            <x-heroicon-o-arrow-right class="size-4" />
        </button>
    </form>
@endsection