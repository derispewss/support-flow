<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name'))</title>
    <meta name="description" content="@yield('description', 'SupportFlow — tiket, SLA, portal customer, dan knowledge base dalam satu meja kerja.')">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <tallstackui:script />
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-paper text-ink">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:border focus:border-ink focus:bg-paper-raised focus:px-4 focus:py-2 focus:text-xs focus:font-semibold focus:shadow-md">
        Lewati ke konten
    </a>

    <header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-rule bg-paper-raised/90 backdrop-blur-md">
        <nav class="mx-auto flex h-16 max-w-[1240px] items-center justify-between gap-6 px-4 sm:px-6 lg:px-8" aria-label="Navigasi utama">
            <a href="{{ url('/') }}" class="shrink-0">
                <x-ui.logo />
            </a>

            <div class="hidden items-center gap-1 md:flex">
                @foreach ([
                    ['Fitur', '/#fitur', false],
                    ['Alur Kerja', '/#alur', false],
                    ['Harga', '/#harga', false],
                    ['FAQ', '/#faq', false],
                    ['Pusat Bantuan', '/help', request()->is('help*')],
                ] as [$label, $href, $active])
                    <a
                        href="{{ url($href) }}"
                        class="rounded-lg px-3.5 py-2 text-sm font-medium transition-colors {{ $active ? 'bg-paper text-ink font-semibold' : 'text-ink-soft hover:bg-paper hover:text-ink' }}"
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <div class="flex items-center gap-2.5">
                @auth
                    @php
                        $publicStaff = tenant_context()->isStaff();
                    @endphp
                    <a href="{{ $publicStaff ? route('admin.dashboard') : route('dashboard') }}" class="btn btn-outline hidden sm:inline-flex">
                        {{ $publicStaff ? 'Konsol Admin' : 'Buka Portal' }}
                    </a>
                    <a href="{{ route('dashboard') }}" class="grid size-9 place-items-center rounded-lg bg-ink text-xs font-semibold text-white transition hover:bg-ink-soft" aria-label="Buka portal">
                        {{ auth()->user()->initials() }}
                    </a>
                @else
                    <a href="{{ route('login') }}" class="hidden rounded-lg px-3.5 py-2 text-sm font-medium text-ink-soft transition hover:text-ink sm:inline-flex">Masuk</a>
                    <a href="{{ route('register') }}" class="btn btn-signal hidden sm:inline-flex">Mulai gratis</a>
                @endauth

                <button
                    type="button"
                    @click="open = ! open"
                    class="grid size-9 place-items-center rounded-lg border border-rule-strong text-ink md:hidden"
                    :aria-expanded="open"
                    aria-controls="mobile-nav"
                    aria-label="Buka menu"
                >
                    <x-heroicon-o-bars-3 x-show="! open" class="size-5" />
                    <x-heroicon-o-x-mark x-show="open" x-cloak class="size-5" />
                </button>
            </div>
        </nav>

        <div id="mobile-nav" x-show="open" x-cloak x-transition class="border-t border-rule bg-paper-raised md:hidden">
            <div class="mx-auto flex max-w-[1240px] flex-col gap-1 px-4 py-3 sm:px-6">
                @foreach ([['Fitur', '/#fitur'], ['Alur Kerja', '/#alur'], ['Harga', '/#harga'], ['FAQ', '/#faq'], ['Pusat Bantuan', '/help']] as [$label, $href])
                    <a href="{{ url($href) }}" @click="open = false" class="rounded-lg px-3 py-2.5 text-sm font-medium text-ink-soft hover:bg-paper hover:text-ink">{{ $label }}</a>
                @endforeach
                <div class="mt-2 flex flex-col gap-2 border-t border-rule pt-3">
                    @auth
                        <a href="{{ tenant_context()->isStaff() ? route('admin.dashboard') : route('dashboard') }}" class="btn btn-ink w-full">Buka Workspace</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-outline w-full">Masuk</a>
                        <a href="{{ route('register') }}" class="btn btn-signal w-full">Mulai gratis</a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <main id="main" class="flex-1">
        @yield('content')
    </main>

    <footer class="border-t border-rule bg-paper-raised">
        <div class="mx-auto max-w-[1240px] px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid gap-10 lg:grid-cols-[1.2fr_1.8fr]">
                <div>
                    <x-ui.logo />
                    <p class="mt-4 max-w-sm text-sm leading-6 text-ink-soft">
                        Ruang kerja terpadu untuk mengelola antrean tiket, memantau SLA, melayani pelanggan lewat portal mandiri, dan menyusun dokumentasi bantuan.
                    </p>
                    <p class="mt-5 inline-flex items-center gap-2 rounded-full border border-pine/20 bg-pine-soft px-3 py-1 text-xs font-medium text-pine">
                        <span class="size-1.5 rounded-full bg-pine" aria-hidden="true"></span>
                        Semua layanan berjalan normal
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-8 sm:grid-cols-3">
                    <div>
                        <p class="text-xs font-semibold text-ink">Produk</p>
                        <ul class="mt-3.5 space-y-2.5 text-sm text-ink-soft">
                            <li><a href="{{ url('/#fitur') }}" class="hover:text-ink">Fitur Utama</a></li>
                            <li><a href="{{ url('/#alur') }}" class="hover:text-ink">Alur Kerja</a></li>
                            <li><a href="{{ url('/#harga') }}" class="hover:text-ink">Paket Harga</a></li>
                        </ul>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-ink">Sumber Daya</p>
                        <ul class="mt-3.5 space-y-2.5 text-sm text-ink-soft">
                            <li><a href="{{ url('/help') }}" class="hover:text-ink">Pusat Bantuan</a></li>
                            <li><a href="{{ url('/tickets/create') }}" class="hover:text-ink">Buat Tiket Baru</a></li>
                            <li><a href="{{ url('/docs/api') }}" class="hover:text-ink">Dokumentasi REST API</a></li>
                        </ul>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-ink">Akses Akun</p>
                        <ul class="mt-3.5 space-y-2.5 text-sm text-ink-soft">
                            @auth
                                <li><a href="{{ route('dashboard') }}" class="hover:text-ink">Portal Customer</a></li>
                                <li><a href="{{ route('notifications.index') }}" class="hover:text-ink">Notifikasi</a></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="hover:text-signal">Keluar</button>
                                    </form>
                                </li>
                            @else
                                <li><a href="{{ route('login') }}" class="hover:text-ink">Masuk</a></li>
                                <li><a href="{{ route('register') }}" class="hover:text-ink">Daftar Akun</a></li>
                                <li><a href="{{ route('password.request') }}" class="hover:text-ink">Atur Ulang Password</a></li>
                            @endauth
                        </ul>
                    </div>
                </div>
            </div>

            <div class="mt-10 flex flex-col gap-2 border-t border-rule pt-6 text-xs text-ink-faint sm:flex-row sm:items-center sm:justify-between">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Hak cipta dilindungi.</p>
                <p>Jakarta · Waktu Indonesia Barat (WIB)</p>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
