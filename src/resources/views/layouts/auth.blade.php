<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Masuk — '.config('app.name'))</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <tallstackui:script />
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper text-ink flex items-center justify-center @yield('auth_body_class')">
    <x-ui.toast />
    <div class="w-full @yield('auth_container_class', 'max-w-md') p-6">
        <div class="sheet @yield('auth_sheet_class', 'p-6 sm:p-8')">
            @yield('content')
        </div>
        <p class="mt-6 text-center text-xs text-ink-faint @yield('auth_footer_class')">
            Dengan melanjutkan, Anda menyetujui Ketentuan Layanan dan Kebijakan Privasi {{ config('app.name') }}.
        </p>
    </div>
    @livewireScripts
</body>
</html>
