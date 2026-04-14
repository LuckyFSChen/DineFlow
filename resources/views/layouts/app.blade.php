<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $isAdminArea = request()->routeIs('admin.*') || request()->routeIs('super-admin.*') || request()->routeIs('merchant.*');
@endphp
<body class="font-sans antialiased bg-light {{ $isAdminArea ? 'is-admin-area' : '' }}">
    <div class="min-vh-100 app-shell">
        @include('layouts.navigation')

        <main class="app-main {{ $isAdminArea ? 'admin-stage' : '' }}">
            {{-- For extends/section templates --}}
            @hasSection('content')
                @yield('content')
            @else
                {{-- For x-app-layout slot content --}}
                {{ $slot ?? '' }}
            @endif
        </main>
    </div>

    <script>
    (() => {
        const phoneSelectors = [
            'input[name="phone"]',
            'input[name="customer_phone"]',
            'input[id="customer_phone"]',
        ];

        const formatTaiwanMobile = (raw) => {
            const digits = String(raw || '').replace(/\D/g, '').slice(0, 10);

            if (digits.length <= 4) {
                return digits;
            }

            if (digits.length <= 7) {
                return `${digits.slice(0, 4)}-${digits.slice(4)}`;
            }

            return `${digits.slice(0, 4)}-${digits.slice(4, 7)}-${digits.slice(7)}`;
        };

        const bindInput = (input) => {
            if (!input || input.dataset.phoneAutoHyphenBound === '1') {
                return;
            }

            input.dataset.phoneAutoHyphenBound = '1';
            input.setAttribute('inputmode', input.getAttribute('inputmode') || 'numeric');
            input.setAttribute('maxlength', '12');

            const apply = () => {
                input.value = formatTaiwanMobile(input.value);
            };

            input.addEventListener('input', apply);
            input.addEventListener('blur', apply);
            apply();
        };

        const applyAll = () => {
            const inputs = document.querySelectorAll(phoneSelectors.join(','));
            inputs.forEach(bindInput);
        };

        applyAll();
    })();
    </script>
</body>
</html>