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
            const isStrictNumeric = (input.getAttribute('pattern') || '').trim() === '[0-9]*';
            input.setAttribute('inputmode', input.getAttribute('inputmode') || 'numeric');
            if (!input.getAttribute('maxlength')) {
                input.setAttribute('maxlength', '12');
            }

            const apply = () => {
                if (isStrictNumeric) {
                    input.value = String(input.value || '').replace(/\D/g, '');
                    return;
                }

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