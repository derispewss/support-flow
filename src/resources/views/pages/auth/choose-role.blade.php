@extends('layouts.auth')

@section('title', 'Pilih Peran — '.config('app.name'))

@section('content')
<div class="flex min-h-svh items-center justify-center bg-muted p-6 md:p-10">
    <div class="w-full max-w-sm bg-white rounded-xl shadow-lg p-6 md:p-8">
        <h2 class="font-display text-2xl font-bold tracking-tight text-ink mb-6 text-center">Pilih Peran Anda</h2>
        <form method="POST" action="{{ route('register.role.store') }}" class="space-y-4">
            @csrf
            <button type="submit" name="role" value="customer"
                class="btn btn-primary w-full flex items-center justify-center gap-2">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Saya Customer (akses portal)
            </button>
            <button type="submit" name="role" value="owner"
                class="btn btn-ink w-full flex items-center justify-center gap-2">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Saya Owner (buat organisasi)
            </button>
        </form>
    </div>
</div>
@endsection