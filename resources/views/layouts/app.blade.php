<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php
    $isAdminArea = request()->routeIs('admin.*') || request()->routeIs('super-admin.*') || request()->routeIs('merchant.*');
@endphp
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

    @include('partials.favicon')
    @stack('structured-data')
    @if ($isAdminArea)
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+TC:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
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
</body>
</html>
