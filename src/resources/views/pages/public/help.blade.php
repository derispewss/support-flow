@extends('layouts.public')

@section('title', 'Pusat Bantuan — '.config('app.name'))
@section('description', 'Cari panduan dan jawaban atas pertanyaan umum tentang SupportFlow.')

@section('content')
    <section class="border-b border-rule bg-paper-raised">
        <div class="mx-auto max-w-[1280px] px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
            <div class="grid gap-8 lg:grid-cols-[1.2fr_0.8fr] lg:items-center lg:gap-12">
                <div>
                    <span class="inline-flex items-center gap-2 rounded-full border border-rule bg-paper px-3 py-1 text-xs font-medium text-ink-soft">
                        <x-heroicon-o-lifebuoy class="size-3.5 text-signal" />
                        Pusat Bantuan
                    </span>

                    <h1 class="mt-4 font-display text-3xl font-bold tracking-tight text-ink sm:text-5xl">
                        Ada yang bisa <span class="text-signal">kami bantu?</span>
                    </h1>

                    <p class="mt-3 max-w-lg text-base leading-7 text-ink-soft">
                        Telusuri panduan penggunaan berdasarkan topik, atau hubungi tim support bila pertanyaan Anda belum terjawab.
                    </p>
                </div>

                <div class="rounded-xl border border-rule bg-paper p-5">
                    <label for="help-search" class="field-label">Cari artikel panduan</label>
                    <div class="relative mt-2">
                        <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-ink-faint">
                            <x-heroicon-o-magnifying-glass class="size-4" />
                        </span>
                        <input
                            id="help-search"
                            type="search"
                            placeholder="Misalnya: reset password, SLA, webhook…"
                            class="field pl-10"
                        >
                    </div>

                    <dl class="mt-4 grid grid-cols-3 gap-2 border-t border-rule pt-4 text-center">
                        @foreach ([['42', 'Artikel'], ['6', 'Kategori'], ['24/7', 'Portal']] as [$value, $label])
                            <div class="rounded-lg bg-paper-raised px-3 py-2">
                                <dd class="font-display text-base font-bold text-ink">{{ $value }}</dd>
                                <dt class="text-xs text-ink-faint">{{ $label }}</dt>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </div>
        </div>
    </section>

    <section class="border-b border-rule">
        <div class="mx-auto max-w-[1280px] px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Telusuri berdasarkan topik</h2>
                    <p class="mt-1 text-sm text-ink-soft">Semua panduan ditulis dan diperbarui berkala oleh tim produk & support.</p>
                </div>
            </div>

            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    ['icon' => 'heroicon-o-user-circle', 'title' => 'Akun & Login', 'body' => 'Reset password, verifikasi email, dan login dengan Google.', 'count' => 8],
                    ['icon' => 'heroicon-o-ticket', 'title' => 'Tiket', 'body' => 'Membuat tiket, membalas pesan, dan mengunggah lampiran.', 'count' => 12],
                    ['icon' => 'heroicon-o-bell', 'title' => 'Notifikasi', 'body' => 'Mengatur email dan notifikasi di dalam aplikasi.', 'count' => 5],
                    ['icon' => 'heroicon-o-building-office-2', 'title' => 'Organisasi', 'body' => 'Mengelola anggota, role, dan ruang kerja multi-tenant.', 'count' => 7],
                    ['icon' => 'heroicon-o-book-open', 'title' => 'Knowledge Base', 'body' => 'Menulis dan menerbitkan dokumentasi untuk pelanggan.', 'count' => 6],
                    ['icon' => 'heroicon-o-code-bracket', 'title' => 'API & Integrasi', 'body' => 'Token Sanctum, pengiriman webhook, dan spesifikasi OpenAPI.', 'count' => 4],
                ] as $category)
                    <a href="{{ url('/login') }}" class="sheet group flex flex-col justify-between p-5 transition-colors hover:border-rule-strong">
                        <div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="inline-flex size-10 items-center justify-center rounded-lg border border-rule bg-paper text-signal transition-colors group-hover:bg-signal group-hover:text-white">
                                    <x-dynamic-component :component="$category['icon']" class="size-5" />
                                </span>
                                <span class="rounded-full bg-paper px-2.5 py-0.5 font-mono text-xs text-ink-faint">{{ $category['count'] }} artikel</span>
                            </div>
                            <h3 class="mt-4 font-display text-lg font-semibold text-ink group-hover:text-signal">{{ $category['title'] }}</h3>
                            <p class="mt-1.5 text-sm leading-6 text-ink-soft">{{ $category['body'] }}</p>
                        </div>

                        <div class="mt-4 inline-flex items-center gap-1.5 text-xs font-semibold text-ink-soft group-hover:text-signal">
                            <span>Buka topik</span>
                            <x-heroicon-o-arrow-right class="size-3.5" />
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-paper-raised">
        <div class="mx-auto max-w-[1280px] px-4 py-12 sm:px-6 lg:px-8">
            <div class="sheet flex flex-col justify-between gap-6 p-6 sm:flex-row sm:items-center sm:p-8">
                <div>
                    <h2 class="font-display text-xl font-bold text-ink sm:text-2xl">Belum menemukan jawaban yang Anda cari?</h2>
                    <p class="mt-1.5 max-w-xl text-sm leading-6 text-ink-soft">
                        Buat tiket baru dan tim support kami akan membalas secepatnya. Sertakan langkah atau tangkapan layar yang sudah Anda coba.
                    </p>
                </div>

                <div class="flex shrink-0 flex-col gap-2 sm:items-end">
                    <a href="{{ url('/tickets/create') }}" class="btn btn-signal w-full sm:w-auto">
                        Buat tiket dukungan
                        <x-heroicon-o-arrow-right class="size-4" />
                    </a>
                    <p class="text-xs text-ink-faint">
                        Sudah punya tiket? <a href="{{ url('/login') }}" class="font-medium text-signal hover:underline">Masuk ke portal</a>
                    </p>
                </div>
            </div>
        </div>
    </section>
@endsection
