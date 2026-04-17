<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @php
        $appName = config('app.name', 'DineFlow');
        $seoTitle = trim((string) $__env->yieldContent('title', $appName));
        $seoDescription = trim((string) $__env->yieldContent('meta_description', 'DineFlow QR 掃碼點餐與外帶點餐平台，協助餐廳快速上線並提升點餐效率。'));
        $seoCanonical = trim((string) $__env->yieldContent('canonical', url()->current()));
        $seoRobots = trim((string) $__env->yieldContent('meta_robots', 'index,follow,max-image-preview:large'));
        $seoImage = trim((string) $__env->yieldContent('meta_image', asset('images/logo.svg')));
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="{{ $seoRobots }}">
    <link rel="canonical" href="{{ $seoCanonical }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $appName }}">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $seoCanonical }}">
    <meta property="og:image" content="{{ $seoImage }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $seoImage }}">

    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    @stack('structured-data')

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
            'input[type="tel"]',
            'input[name*="phone"]',
            'input[id*="phone"]',
        ];

        const detectDigitsLimit = (input) => {
            const explicit = Number(input.dataset.phoneDigits || 0);
            if (explicit > 0) {
                return explicit;
            }

            const rawMaxLength = Number(input.getAttribute('maxlength') || 0);
            if (rawMaxLength > 0) {
                return rawMaxLength > 11 ? rawMaxLength - 2 : rawMaxLength;
            }

            return 11;
        };

        const formatPhone = (raw, digitsLimit) => {
            const digits = String(raw || '').replace(/\D/g, '').slice(0, digitsLimit);

            if (digits.length <= 4) {
                return digits;
            }

            if (digits.length === 11) {
                return `${digits.slice(0, 3)}-${digits.slice(3, 7)}-${digits.slice(7)}`;
            }

            if (digits.length === 10) {
                if (digits.startsWith('09')) {
                    return `${digits.slice(0, 4)}-${digits.slice(4, 7)}-${digits.slice(7)}`;
                }

                return `${digits.slice(0, 3)}-${digits.slice(3, 6)}-${digits.slice(6)}`;
            }

            if (digits.length <= 7) {
                return `${digits.slice(0, 4)}-${digits.slice(4)}`;
            }

            return `${digits.slice(0, 3)}-${digits.slice(3, 6)}-${digits.slice(6)}`;
        };

        const bindInput = (input) => {
            if (!input || input.dataset.phoneAutoHyphenBound === '1') {
                return;
            }

            input.dataset.phoneAutoHyphenBound = '1';
            const digitsLimit = detectDigitsLimit(input);
            input.setAttribute('inputmode', input.getAttribute('inputmode') || 'numeric');
            input.setAttribute('pattern', '[0-9-]*');
            input.setAttribute('maxlength', String(digitsLimit + 2));

            const apply = () => {
                input.value = formatPhone(input.value, digitsLimit);
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
