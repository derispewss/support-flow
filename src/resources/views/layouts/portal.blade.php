<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Portal Customer — '.config('app.name'))</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <tallstackui:script />
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper text-ink" x-data="{ sidebar: false, collapsed: false }" @keydown.escape.window="sidebar = false">
    @php
        $portalUser = auth()->user();
        $portalOrganization = tenant();
        $portalHasOrganization = $portalOrganization instanceof \App\Modules\Tenancy\Models\Organization;
        $portalMembership = tenant_context()->membership();
        $portalMemberships = $portalUser?->memberships()->with('organization')->where('status', 'active')->orderBy('id')->get() ?? collect();
        $portalStaff = tenant_context()->isStaff();
        $unreadCount = $portalUser?->unreadNotifications()->count() ?? 0;
        $portalSection = match (true) {
            request()->routeIs('tickets.create') => 'Buat Tiket Baru',
            request()->routeIs('tickets.show') => 'Detail Tiket',
            request()->routeIs('tickets.*') => 'Tiket Saya',
            request()->routeIs('notifications.*') => 'Notifikasi',
            default => 'Dashboard',
        };
        $myActiveTicketsCount = ($portalOrganization instanceof \App\Modules\Tenancy\Models\Organization && $portalUser !== null)
            ? \App\Modules\Support\Models\Ticket::query()
                ->forOrganization($portalOrganization)
                ->where('requester_id', $portalUser->getKey())
                ->whereIn('status', [
                    \App\Shared\Enums\TicketStatus::New,
                    \App\Shared\Enums\TicketStatus::Open,
                    \App\Shared\Enums\TicketStatus::Pending,
                ])
                ->count()
            : 0;
    @endphp

    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:border focus:border-ink focus:bg-paper-raised focus:px-4 focus:py-2 focus:text-xs focus:font-semibold focus:shadow-md">
        Lewati ke konten
    </a>

    <div class="admin-shell" :class="{ 'sidebar-collapsed': collapsed }">
        <div
            x-show="sidebar"
            x-cloak
            x-transition.opacity
            @click="sidebar = false"
            class="admin-backdrop fixed inset-0 z-30 bg-ink/40 backdrop-blur-xs"
            aria-hidden="true"
        ></div>

        <aside
            :class="{ 'sidebar-open': sidebar }"
            class="admin-sidebar"
            aria-label="Navigasi portal customer"
        >
            {{-- SidebarHeader (shadcn TeamSwitcher) --}}
            <div class="admin-sidebar__header relative shrink-0 p-2" x-data="{ orgSwitcher: false }" @click.outside="orgSwitcher = false">
                <div class="flex items-center gap-1">
                    <button
                        type="button"
                        @click="orgSwitcher = ! orgSwitcher"
                        class="flex min-w-0 flex-1 items-center gap-2.5 rounded-lg p-2 text-left transition hover:bg-ink/[0.05] data-[state=open]:bg-ink/[0.05]"
                        :data-state="orgSwitcher ? 'open' : 'closed'"
                    >
                        <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-ink text-white shadow-2xs">
                            <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.75 6.25a1 1 0 0 1 1-1h12.5a1 1 0 0 1 1 1v2.1a1.65 1.65 0 0 0 0 3.3v2.1a1 1 0 0 1-1 1H3.75a1 1 0 0 1-1-1v-2.1a1.65 1.65 0 0 0 0-3.3v-2.1Z" />
                                <path stroke-linecap="round" d="M7.75 6.25v7.5" stroke-dasharray="1.5 2" />
                            </svg>
                        </span>
                        <span class="grid min-w-0 flex-1 text-left leading-tight">
                            <span class="truncate text-sm font-semibold text-ink">{{ $portalOrganization?->name ?? config('app.name') }}</span>
                            <span class="truncate text-xs text-ink-faint">Portal Customer · {{ $portalMembership?->role->label() ?? 'Customer' }}</span>
                        </span>
                        <x-heroicon-o-chevron-up-down class="ml-auto size-4 shrink-0 text-ink-faint" />
                    </button>

                    <button
                        type="button"
                        @click="sidebar = false"
                        class="admin-mobile-only size-8 shrink-0 place-items-center rounded-lg text-ink-faint transition hover:bg-ink/[0.06] hover:text-ink"
                        aria-label="Tutup menu"
                    >
                        <x-heroicon-o-x-mark class="size-4" />
                    </button>
                </div>

                <div
                    x-show="orgSwitcher"
                    x-cloak
                    x-transition.origin.top.left
                    class="absolute left-2 right-2 top-full z-50 mt-1 rounded-xl border border-rule bg-paper-raised p-1.5 shadow-lg"
                >
                    <p class="px-2 py-1 text-[11px] font-medium text-ink-faint">Workspace Organisasi</p>
                    <div class="mt-0.5 space-y-0.5">
                        @foreach ($portalMemberships as $membership)
                            @if ($membership->organization)
                                <form method="POST" action="{{ route('organizations.switch', $membership->organization) }}">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="flex w-full items-center justify-between gap-2 rounded-lg px-2 py-1.5 text-left text-xs transition {{ $membership->organization_id === $portalOrganization?->id ? 'bg-paper font-semibold text-ink' : 'text-ink-soft hover:bg-paper hover:text-ink' }}"
                                    >
                                        <span class="flex min-w-0 items-center gap-2">
                                            <span class="grid size-5 shrink-0 place-items-center rounded border border-rule bg-paper-raised text-[10px] font-semibold text-ink">
                                                {{ strtoupper(substr($membership->organization->name, 0, 1)) }}
                                            </span>
                                            <span class="truncate">{{ $membership->organization->name }}</span>
                                        </span>
                                        @if ($membership->organization_id === $portalOrganization?->id)
                                            <x-heroicon-o-check class="size-3.5 shrink-0 text-ink" />
                                        @endif
                                    </button>
                                </form>
                            @endif
                        @endforeach
                    </div>

                    <div class="my-1 border-t border-rule"></div>

                    @if ($portalStaff)
                        <a
                            href="{{ route('admin.dashboard') }}"
                            class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-xs font-medium text-ink-soft transition hover:bg-paper hover:text-ink"
                        >
                            <x-heroicon-o-command-line class="size-3.5 text-ink-faint" />
                            <span>Buka Konsol Admin</span>
                        </a>
                    @endif
                </div>
            </div>

            {{-- SidebarContent (shadcn SidebarGroup + SidebarMenu) --}}
            <nav class="admin-nav space-y-4 px-2 py-2">
                <div>
                    <p class="px-2 pb-1 text-[11px] font-medium text-ink-faint">Layanan Support</p>
                    <div class="space-y-0.5">
                        @php
                            $portalNavItems = [
                                ['dashboard', request()->routeIs('dashboard'), 'Dashboard', 'heroicon-o-squares-2x2', null],
                                ['notifications.index', request()->routeIs('notifications.*'), 'Notifikasi', 'heroicon-o-bell', $unreadCount > 0 ? $unreadCount : null],
                            ];
                            if ($portalHasOrganization) {
                                array_splice($portalNavItems, 1, 0, [
                                    ['tickets.index', request()->routeIs('tickets.index') || request()->routeIs('tickets.show'), 'Tiket Saya', 'heroicon-o-inbox-stack', $myActiveTicketsCount > 0 ? $myActiveTicketsCount : null],
                                    ['tickets.create', request()->routeIs('tickets.create'), 'Buat Tiket Baru', 'heroicon-o-plus-circle', null],
                                ]);
                            }
                        @endphp
                        @foreach ($portalNavItems as [$route, $active, $label, $icon, $badge])
                            <a
                                href="{{ route($route) }}"
                                @click="sidebar = false"
                                aria-current="{{ $active ? 'page' : 'false' }}"
                                class="group flex h-8 items-center justify-between gap-2 rounded-md px-2 text-[13px] transition-colors {{ $active ? 'bg-paper-raised font-semibold text-ink shadow-2xs ring-1 ring-rule/80' : 'font-medium text-ink-soft hover:bg-ink/[0.05] hover:text-ink' }}"
                            >
                                <span class="flex min-w-0 items-center gap-2">
                                    <x-dynamic-component :component="$icon" class="size-4 shrink-0 {{ $active ? 'text-ink' : 'text-ink-faint group-hover:text-ink' }}" />
                                    <span class="truncate">{{ $label }}</span>
                                </span>
                                @if ($badge !== null)
                                    <span class="shrink-0 rounded-md px-1.5 py-0.5 font-mono text-[10px] font-semibold leading-none {{ $active ? 'bg-ink text-white' : 'bg-ink/[0.07] text-ink-soft' }}">
                                        {{ $badge }}
                                    </span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>

                <div>
                    <p class="px-2 pb-1 text-[11px] font-medium text-ink-faint">Bantuan &amp; Sumber Daya</p>
                    <div class="space-y-0.5">
                        <a
                            href="{{ route('help.index') }}"
                            @click="sidebar = false"
                            class="group flex h-8 items-center justify-between gap-2 rounded-md px-2 text-[13px] font-medium text-ink-soft transition-colors hover:bg-ink/[0.05] hover:text-ink"
                        >
                            <span class="flex min-w-0 items-center gap-2">
                                <x-heroicon-o-book-open class="size-4 shrink-0 text-ink-faint group-hover:text-ink" />
                                <span class="truncate">Pusat Bantuan</span>
                            </span>
                            <x-heroicon-o-arrow-up-right class="size-3.5 shrink-0 text-ink-faint" />
                        </a>

                        @if ($portalStaff)
                            <a
                                href="{{ route('admin.dashboard') }}"
                                @click="sidebar = false"
                                class="group flex h-8 items-center justify-between gap-2 rounded-md px-2 text-[13px] font-medium text-ink-soft transition-colors hover:bg-ink/[0.05] hover:text-ink"
                            >
                                <span class="flex min-w-0 items-center gap-2">
                                    <x-heroicon-o-command-line class="size-4 shrink-0 text-ink-faint group-hover:text-ink" />
                                    <span class="truncate">Konsol Admin</span>
                                </span>
                                <x-heroicon-o-arrow-top-right-on-square class="size-3.5 shrink-0 text-ink-faint" />
                            </a>
                        @endif
                    </div>
                </div>
            </nav>

            {{-- SidebarFooter (shadcn NavUser) --}}
            <div class="admin-sidebar__footer relative shrink-0 border-t border-rule/80 p-2" x-data="{ userMenu: false }" @click.outside="userMenu = false">
                <button
                    type="button"
                    @click="userMenu = ! userMenu"
                    class="flex w-full items-center gap-2.5 rounded-lg p-2 text-left transition hover:bg-ink/[0.05]"
                    aria-label="Menu akun"
                    :aria-expanded="userMenu"
                >
                    <span class="grid size-8 shrink-0 place-items-center rounded-lg border border-rule bg-paper-raised text-xs font-semibold text-ink shadow-2xs">
                        {{ $portalUser?->initials() ?? 'SF' }}
                    </span>
                    <span class="grid min-w-0 flex-1 text-left leading-tight">
                        <span class="truncate text-xs font-semibold text-ink">{{ $portalUser?->name ?? 'Pengguna' }}</span>
                        <span class="truncate text-[11px] text-ink-faint">{{ $portalUser?->email }}</span>
                    </span>
                    <x-heroicon-o-chevron-up-down class="ml-auto size-4 shrink-0 text-ink-faint" />
                </button>

                <div
                    x-show="userMenu"
                    x-cloak
                    x-transition.origin.bottom.left
                    class="absolute bottom-full left-2 right-2 z-50 mb-1 rounded-xl border border-rule bg-paper-raised p-1.5 shadow-lg"
                >
                    <div class="border-b border-rule px-2.5 py-2">
                        <p class="truncate text-xs font-semibold text-ink">{{ $portalUser?->name ?? 'Pengguna' }}</p>
                        <p class="truncate text-[11px] text-ink-faint">{{ $portalUser?->email }}</p>
                    </div>

                    <div class="py-1">
                        @if ($portalHasOrganization)
                            <a href="{{ route('tickets.index') }}" class="flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs font-medium text-ink-soft transition hover:bg-paper hover:text-ink">
                                <x-heroicon-o-inbox-stack class="size-3.5 text-ink-faint" />
                                <span>Tiket Saya</span>
                            </a>
                        @endif
                        <a href="{{ route('notifications.index') }}" class="flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium text-ink-soft transition hover:bg-paper hover:text-ink">
                            <span class="flex items-center gap-2">
                                <x-heroicon-o-bell class="size-3.5 text-ink-faint" />
                                <span>Notifikasi</span>
                            </span>
                            @if ($unreadCount > 0)
                                <span class="rounded-full bg-signal px-1.5 py-0.5 font-mono text-[10px] font-semibold leading-none text-white">{{ $unreadCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('help.index') }}" class="flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs font-medium text-ink-soft transition hover:bg-paper hover:text-ink">
                            <x-heroicon-o-question-mark-circle class="size-3.5 text-ink-faint" />
                            <span>Pusat Bantuan</span>
                        </a>
                        @if ($portalStaff)
                            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs font-medium text-ink-soft transition hover:bg-paper hover:text-ink">
                                <x-heroicon-o-command-line class="size-3.5 text-ink-faint" />
                                <span>Konsol Admin</span>
                            </a>
                        @endif
                    </div>

                    <div class="border-t border-rule pt-1">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button
                                type="submit"
                                class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left text-xs font-medium text-signal-dark transition hover:bg-signal-soft"
                            >
                                <x-heroicon-o-arrow-right-on-rectangle class="size-3.5" />
                                <span>Keluar</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </aside>

        {{-- SidebarInset --}}
        <div class="admin-main">
            <header class="admin-topbar sticky top-0 z-20 flex h-12 shrink-0 min-w-0 items-center justify-between gap-3 border-b border-rule bg-paper-raised/95 px-4 backdrop-blur-md lg:px-6">
                <div class="flex min-w-0 items-center gap-2">
                    {{-- Mobile SidebarTrigger --}}
                    <button
                        type="button"
                        @click="sidebar = true"
                        class="admin-mobile-only size-7 shrink-0 place-items-center rounded-md text-ink-soft transition hover:bg-paper hover:text-ink"
                        aria-label="Buka menu navigasi"
                    >
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect width="18" height="18" x="3" y="3" rx="2" />
                            <path d="M9 3v18" />
                        </svg>
                    </button>

                    {{-- Desktop SidebarTrigger --}}
                    <button
                        type="button"
                        @click="collapsed = ! collapsed"
                        class="admin-desktop-only size-7 shrink-0 place-items-center rounded-md text-ink-soft transition hover:bg-paper hover:text-ink"
                        aria-label="Toggle sidebar"
                        title="Toggle sidebar"
                    >
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect width="18" height="18" x="3" y="3" rx="2" />
                            <path d="M9 3v18" />
                        </svg>
                    </button>

                    <span class="mx-1 h-4 w-px shrink-0 bg-rule" aria-hidden="true"></span>

                    <nav class="min-w-0" aria-label="Breadcrumb">
                        <ol class="flex min-w-0 items-center gap-1.5 text-xs sm:text-sm">
                            <li class="hidden shrink-0 text-ink-faint sm:block">
                                <a href="{{ route('dashboard') }}" class="transition hover:text-ink">{{ $portalOrganization?->name ?? 'Portal Customer' }}</a>
                            </li>
                            <li class="hidden shrink-0 text-ink-faint sm:block" aria-hidden="true">
                                <x-heroicon-o-chevron-right class="size-3.5" />
                            </li>
                            <li class="min-w-0 truncate font-medium text-ink" aria-current="page">{{ $portalSection }}</li>
                        </ol>
                    </nav>
                </div>

                <div class="flex items-center gap-1.5 sm:gap-2">
                    @if ($portalHasOrganization)
                    <form method="GET" action="{{ route('tickets.index') }}" class="hidden w-52 md:block lg:w-64">
                        <label for="portal-topbar-search" class="sr-only">Cari tiket</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-2.5 flex items-center text-ink-faint">
                                <x-heroicon-o-magnifying-glass class="size-3.5" />
                            </span>
                            <input
                                id="portal-topbar-search"
                                type="search"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="Cari tiket saya..."
                                class="h-8 w-full rounded-md border border-rule bg-paper/60 pl-8 pr-7 text-xs text-ink placeholder:text-ink-faint transition focus:border-ink focus:bg-paper-raised focus:outline-none"
                            >
                            <kbd class="pointer-events-none absolute right-2 top-1.5 hidden rounded border border-rule bg-paper-raised px-1 font-mono text-[10px] text-ink-faint sm:inline-block">/</kbd>
                        </div>
                    </form>
                    @endif

                    <a href="{{ route('notifications.index') }}" class="relative grid size-8 place-items-center rounded-md border border-rule bg-paper-raised text-ink-soft shadow-2xs transition hover:bg-paper hover:text-ink" title="Notifikasi" aria-label="Notifikasi">
                        <x-heroicon-o-bell class="size-4" />
                        @if ($unreadCount > 0)
                            <span class="absolute right-1.5 top-1.5 size-1.5 rounded-full bg-signal ring-2 ring-paper-raised" aria-hidden="true"></span>
                        @endif
                    </a>

                    @if ($portalStaff)
                        <a href="{{ route('admin.dashboard') }}" class="hidden h-8 items-center gap-1.5 rounded-md border border-rule bg-paper-raised px-2.5 text-xs font-medium text-ink shadow-2xs transition hover:bg-paper sm:inline-flex">
                            <span>Konsol Admin</span>
                            <x-heroicon-o-arrow-top-right-on-square class="size-3.5 text-ink-faint" />
                        </a>
                    @endif

                    @if ($portalHasOrganization)
                        <a href="{{ route('tickets.create') }}" class="inline-flex h-8 items-center gap-1.5 rounded-md bg-ink px-2.5 text-xs font-medium text-white shadow-2xs transition hover:bg-ink-soft">
                            <x-heroicon-o-plus class="size-3.5" />
                            <span>Buat tiket</span>
                        </a>
                    @endif
                </div>
            </header>

            <main id="main" class="mx-auto w-full max-w-[1440px] flex-1 p-4 sm:p-6">
                <x-ui.toast />
                @yield('content')
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
