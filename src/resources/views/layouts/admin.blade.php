<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Admin — '.config('app.name'))</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <tallstackui:script />
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper text-ink" x-data="{ sidebar: false, collapsed: false }" @keydown.escape.window="sidebar = false">
    @php
        $adminUser = auth()->user();
        $adminOrganization = tenant();
        $adminMembership = tenant_context()->membership();
        $adminMemberships = $adminUser?->memberships()->with('organization')->where('status', 'active')->orderBy('id')->get() ?? collect();
        $adminSection = match (true) {
            request()->routeIs('admin.tickets.*') => 'Queue tiket',
            request()->routeIs('admin.members.*') => 'Anggota & Peran',
            request()->routeIs('admin.settings.categories.*') => 'Kategori Tiket',
            request()->routeIs('admin.settings.tags.*') => 'Label & Tag',
            request()->routeIs('admin.settings.sla.*') => 'Kebijakan SLA',
            request()->routeIs('admin.settings.teams.*') => 'Tim Support',
            request()->routeIs('admin.reports.*') => 'Laporan & Analitik',
            request()->routeIs('admin.knowledge-base.*') => 'Knowledge Base',
            request()->routeIs('admin.webhooks.*') => 'Webhooks',
            request()->routeIs('admin.audit.*') => 'Audit Log',
            default => 'Dashboard',
        };
        $canManage = (bool) $adminMembership?->role->canManageOrganization();
        $unreadNotifications = $adminUser?->unreadNotifications()->count() ?? 0;
        $openTicketsCount = $adminOrganization instanceof \App\Modules\Tenancy\Models\Organization
            ? \App\Modules\Support\Models\Ticket::query()
                ->forOrganization($adminOrganization)
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
            aria-label="Navigasi admin"
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
                            <span class="truncate text-sm font-semibold text-ink">{{ $adminOrganization?->name ?? config('app.name') }}</span>
                            <span class="truncate text-xs text-ink-faint">SupportFlow · {{ $adminMembership?->role->label() ?? 'Staff' }}</span>
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
                        @foreach ($adminMemberships as $membership)
                            @if ($membership->organization)
                                <form method="POST" action="{{ route('organizations.switch', $membership->organization) }}">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="flex w-full items-center justify-between gap-2 rounded-lg px-2 py-1.5 text-left text-xs transition {{ $membership->organization_id === $adminOrganization?->id ? 'bg-paper font-semibold text-ink' : 'text-ink-soft hover:bg-paper hover:text-ink' }}"
                                    >
                                        <span class="flex min-w-0 items-center gap-2">
                                            <span class="grid size-5 shrink-0 place-items-center rounded border border-rule bg-paper-raised text-[10px] font-semibold text-ink">
                                                {{ strtoupper(substr($membership->organization->name, 0, 1)) }}
                                            </span>
                                            <span class="truncate">{{ $membership->organization->name }}</span>
                                        </span>
                                        @if ($membership->organization_id === $adminOrganization?->id)
                                            <x-heroicon-o-check class="size-3.5 shrink-0 text-ink" />
                                        @endif
                                    </button>
                                </form>
                            @endif
                        @endforeach
                    </div>

                    <div class="my-1 border-t border-rule"></div>

                </div>
            </div>

            {{-- SidebarContent (shadcn SidebarGroup + SidebarMenu) --}}
            <nav class="admin-nav space-y-4 px-2 py-2">
                <div>
                    <p class="px-2 pb-1 text-[11px] font-medium text-ink-faint">Platform</p>
                    <div class="space-y-0.5">
                        @foreach ([
                            ['admin.dashboard', 'admin.dashboard', 'Dashboard', 'heroicon-o-squares-2x2', null],
                            ['admin.tickets.index', 'admin.tickets.*', 'Queue Tiket', 'heroicon-o-inbox-stack', $openTicketsCount > 0 ? $openTicketsCount : null],
                            ...($canManage ? [
                                ['admin.knowledge-base.index', 'admin.knowledge-base.*', 'Knowledge Base', 'heroicon-o-book-open', null],
                                ['admin.reports.index', 'admin.reports.*', 'Laporan & Analitik', 'heroicon-o-chart-bar-square', null],
                            ] : []),
                        ] as [$route, $pattern, $label, $icon, $badge])
                            @php $active = request()->routeIs($pattern); @endphp
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

                        @if ($canManage)
                            @php $membersActive = request()->routeIs('admin.members.*'); @endphp
                            <a
                                href="{{ route('admin.members.index') }}"
                                @click="sidebar = false"
                                aria-current="{{ $membersActive ? 'page' : 'false' }}"
                                class="group flex h-8 items-center gap-2 rounded-md px-2 text-[13px] transition-colors {{ $membersActive ? 'bg-paper-raised font-semibold text-ink shadow-2xs ring-1 ring-rule/80' : 'font-medium text-ink-soft hover:bg-ink/[0.05] hover:text-ink' }}"
                            >
                                <x-heroicon-o-users class="size-4 shrink-0 {{ $membersActive ? 'text-ink' : 'text-ink-faint group-hover:text-ink' }}" />
                                <span class="truncate">Anggota &amp; Peran</span>
                            </a>
                        @endif
                    </div>
                </div>

                <div>
                    <p class="px-2 pb-1 text-[11px] font-medium text-ink-faint">Konfigurasi</p>
                    @if ($canManage)
                        <div class="space-y-0.5">
                            @foreach ([
                                ['admin.settings.categories.index', 'admin.settings.categories.*', 'Kategori Tiket', 'heroicon-o-tag'],
                                ['admin.settings.tags.index', 'admin.settings.tags.*', 'Label & Tag', 'heroicon-o-hashtag'],
                                ['admin.settings.sla.index', 'admin.settings.sla.*', 'Kebijakan SLA', 'heroicon-o-clock'],
                                ['admin.settings.teams.index', 'admin.settings.teams.*', 'Tim Support', 'heroicon-o-user-group'],
                                ['admin.webhooks.index', 'admin.webhooks.*', 'Webhooks', 'heroicon-o-bolt'],
                            ] as [$route, $pattern, $label, $icon])
                                @php $active = request()->routeIs($pattern); @endphp
                                <a
                                    href="{{ route($route) }}"
                                    @click="sidebar = false"
                                    aria-current="{{ $active ? 'page' : 'false' }}"
                                    class="group flex h-8 items-center gap-2 rounded-md px-2 text-[13px] transition-colors {{ $active ? 'bg-paper-raised font-semibold text-ink shadow-2xs ring-1 ring-rule/80' : 'font-medium text-ink-soft hover:bg-ink/[0.05] hover:text-ink' }}"
                                >
                                    <x-dynamic-component :component="$icon" class="size-4 shrink-0 {{ $active ? 'text-ink' : 'text-ink-faint group-hover:text-ink' }}" />
                                    <span class="truncate">{{ $label }}</span>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="px-2 text-xs text-ink-faint">Dikelola oleh Owner &amp; Admin.</p>
                    @endif
                </div>

                <div>
                    <p class="px-2 pb-1 text-[11px] font-medium text-ink-faint">Keamanan</p>
                    @if ($canManage)
                        <div class="space-y-0.5">
                            @php $auditActive = request()->routeIs('admin.audit.*'); @endphp
                            <a
                                href="{{ route('admin.audit.index') }}"
                                @click="sidebar = false"
                                aria-current="{{ $auditActive ? 'page' : 'false' }}"
                                class="group flex h-8 items-center gap-2 rounded-md px-2 text-[13px] transition-colors {{ $auditActive ? 'bg-paper-raised font-semibold text-ink shadow-2xs ring-1 ring-rule/80' : 'font-medium text-ink-soft hover:bg-ink/[0.05] hover:text-ink' }}"
                            >
                                <x-heroicon-o-clipboard-document-list class="size-4 shrink-0 {{ $auditActive ? 'text-ink' : 'text-ink-faint group-hover:text-ink' }}" />
                                <span class="truncate">Audit Log</span>
                            </a>
                        </div>
                    @else
                        <p class="px-2 text-xs text-ink-faint">Dikelola oleh Owner &amp; Admin.</p>
                    @endif
                </div>
            </nav>

            {{-- SidebarFooter (shadcn NavUser) --}}
            <div class="admin-sidebar__footer relative shrink-0 border-t border-rule/80 p-2" x-data="{ userMenu: false }" @click.outside="userMenu = false">
                <button
                    type="button"
                    @click="userMenu = ! userMenu"
                    class="flex w-full items-center gap-2.5 rounded-lg p-2 text-left transition hover:bg-ink/[0.05]"
                >
                    <span class="grid size-8 shrink-0 place-items-center rounded-lg border border-rule bg-paper-raised text-xs font-semibold text-ink shadow-2xs">
                        {{ $adminUser?->initials() ?? 'SF' }}
                    </span>
                    <span class="grid min-w-0 flex-1 text-left leading-tight">
                        <span class="truncate text-xs font-semibold text-ink">{{ $adminUser?->name ?? 'Pengguna' }}</span>
                        <span class="truncate text-[11px] text-ink-faint">{{ $adminUser?->email }}</span>
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
                        <p class="truncate text-xs font-semibold text-ink">{{ $adminUser?->name ?? 'Pengguna' }}</p>
                        <p class="truncate text-[11px] text-ink-faint">{{ $adminUser?->email }}</p>
                    </div>

                    <div class="py-1">
                        <a href="{{ route('notifications.index') }}" class="flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium text-ink-soft transition hover:bg-paper hover:text-ink">
                            <span class="flex items-center gap-2">
                                <x-heroicon-o-bell class="size-3.5 text-ink-faint" />
                                <span>Notifikasi</span>
                            </span>
                            @if ($unreadNotifications > 0)
                                <span class="rounded-full bg-signal px-1.5 py-0.5 font-mono text-[10px] font-semibold leading-none text-white">{{ $unreadNotifications }}</span>
                            @endif
                        </a>
                        <a href="{{ route('help.index') }}" class="flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs font-medium text-ink-soft transition hover:bg-paper hover:text-ink">
                            <x-heroicon-o-question-mark-circle class="size-3.5 text-ink-faint" />
                            <span>Pusat Bantuan</span>
                        </a>
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
                                <a href="{{ route('admin.dashboard') }}" class="transition hover:text-ink">{{ $adminOrganization?->name ?? 'Workspace' }}</a>
                            </li>
                            <li class="hidden shrink-0 text-ink-faint sm:block" aria-hidden="true">
                                <x-heroicon-o-chevron-right class="size-3.5" />
                            </li>
                            <li class="min-w-0 truncate font-medium text-ink" aria-current="page">{{ $adminSection }}</li>
                        </ol>
                    </nav>
                </div>

                <div class="flex items-center gap-1.5 sm:gap-2">
                    <form method="GET" action="{{ route('admin.tickets.index') }}" class="hidden w-52 md:block lg:w-64">
                        <label for="admin-search" class="sr-only">Cari tiket</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-2.5 flex items-center text-ink-faint">
                                <x-heroicon-o-magnifying-glass class="size-3.5" />
                            </span>
                            <input
                                id="admin-search"
                                type="search"
                                name="q"
                                value="{{ request('q') }}"
                                placeholder="Cari tiket..."
                                class="h-8 w-full rounded-md border border-rule bg-paper/60 pl-8 pr-7 text-xs text-ink placeholder:text-ink-faint transition focus:border-ink focus:bg-paper-raised focus:outline-none"
                            >
                            <kbd class="pointer-events-none absolute right-2 top-1.5 hidden rounded border border-rule bg-paper-raised px-1 font-mono text-[10px] text-ink-faint sm:inline-block">/</kbd>
                        </div>
                    </form>

                    <a href="{{ route('notifications.index') }}" class="relative grid size-8 place-items-center rounded-md border border-rule bg-paper-raised text-ink-soft shadow-2xs transition hover:bg-paper hover:text-ink" title="Notifikasi" aria-label="Notifikasi">
                        <x-heroicon-o-bell class="size-4" />
                        @if ($unreadNotifications > 0)
                            <span class="absolute right-1.5 top-1.5 size-1.5 rounded-full bg-signal ring-2 ring-paper-raised" aria-hidden="true"></span>
                        @endif
                    </a>

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
