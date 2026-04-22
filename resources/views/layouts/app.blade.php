<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php
    $isAdminArea = request()->routeIs('admin.*') || request()->routeIs('super-admin.*') || request()->routeIs('merchant.*');
    $isEmbedded = request()->boolean('embedded');
@endphp
<head>
    @php
        $appName = config('app.name', 'DineFlow');
        $seoTitle = trim((string) $__env->yieldContent('title', $appName));
        $seoDescription = trim((string) $__env->yieldContent('meta_description', 'DineFlow 提供餐廳 QR 掃碼點餐、外帶點餐與多店管理功能，協助商家快速上線、提升點餐效率與營運體驗。'));
        $seoCanonical = trim((string) $__env->yieldContent('canonical', url()->current()));
        $defaultRobots = $isAdminArea ? 'noindex,nofollow,noarchive' : 'index,follow,max-image-preview:large';
        $seoRobots = trim((string) $__env->yieldContent('meta_robots', $defaultRobots));
        $seoImage = trim((string) $__env->yieldContent('meta_image', asset('images/logo-256.png')));
        $seoType = trim((string) $__env->yieldContent('og_type', 'website'));
        $seoLocale = str_replace('_', '-', app()->getLocale());
        $alternateLocales = collect(['zh_TW', 'zh_CN', 'en', 'vi'])
            ->reject(fn (string $locale) => $locale === app()->getLocale())
            ->map(fn (string $locale) => str_replace('_', '-', $locale))
            ->values();
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="{{ $seoRobots }}">
    <meta name="theme-color" content="#5A1E0E">
    <link rel="canonical" href="{{ $seoCanonical }}">
    @stack('head')

    <meta property="og:type" content="{{ $seoType }}">
    <meta property="og:site_name" content="{{ $appName }}">
    <meta property="og:locale" content="{{ $seoLocale }}">
    @foreach ($alternateLocales as $alternateLocale)
        <meta property="og:locale:alternate" content="{{ $alternateLocale }}">
    @endforeach
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $seoCanonical }}">
    <meta property="og:image" content="{{ $seoImage }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $seoImage }}">

    @include('partials.favicon')
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $appName,
        'url' => url('/'),
        'inLanguage' => $seoLocale,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
    @stack('structured-data')
    @if ($isAdminArea)
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+TC:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-light {{ $isAdminArea ? 'is-admin-area' : '' }}">
    <div class="min-h-screen flex flex-col app-shell">
        @if (! $isEmbedded)
            @include('layouts.navigation')
        @endif

        <main class="app-main flex-1 {{ $isAdminArea && ! $isEmbedded ? 'admin-stage' : '' }}">
            {{-- For extends/section templates --}}
            @hasSection('content')
                @yield('content')
            @else
                {{-- For x-app-layout slot content --}}
                {{ $slot ?? '' }}
            @endif
        </main>

        @if (! $isEmbedded)
            @include('partials.public-footer')
        @endif
    </div>
</body>
</html>
