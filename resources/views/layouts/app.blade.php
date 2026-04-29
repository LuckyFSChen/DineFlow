<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php
    $profileUsesAdminShell = request()->routeIs('profile.*')
        && Auth::check()
        && ! Auth::user()?->isCustomer();
    $isAdminArea = request()->routeIs('admin.*')
        || request()->routeIs('super-admin.*')
        || request()->routeIs('merchant.*')
        || $profileUsesAdminShell;
    $isEmbedded = request()->boolean('embedded');
    $workspaceTab = request()->query('tab') === 'boards' ? 'boards' : 'orders';
    $isBoardPage = request()->routeIs('admin.stores.boards*')
        || request()->routeIs('admin.stores.kitchen*')
        || request()->routeIs('admin.stores.cashier*')
        || (request()->routeIs('admin.stores.workspace') && $workspaceTab === 'boards');
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
        <script>
            (() => {
                const storageKey = 'dineflow-admin-font-size';
                const defaultSize = 'sm';
                const allowedSizes = ['xs', 'sm', 'md', 'lg', 'xl'];

                const resolveSize = (value) => allowedSizes.includes(value) ? value : defaultSize;

                const readStoredSize = () => {
                    try {
                        return resolveSize(window.localStorage.getItem(storageKey));
                    } catch (error) {
                        return defaultSize;
                    }
                };

                const applySize = (value) => {
                    const size = resolveSize(value);
                    document.documentElement.dataset.adminFontSize = size;

                    if (document.body) {
                        document.body.dataset.adminFontSize = size;
                    }

                    return size;
                };

                const persistSize = (value) => {
                    const size = applySize(value);

                    try {
                        window.localStorage.setItem(storageKey, size);
                    } catch (error) {
                        // Ignore storage failures so the UI still works.
                    }

                    window.dispatchEvent(new CustomEvent('admin-font-size-changed', {
                        detail: { size },
                    }));

                    return size;
                };

                applySize(readStoredSize());

                window.adminFontPreference = {
                    current() {
                        return resolveSize(document.documentElement.dataset.adminFontSize || document.body?.dataset.adminFontSize);
                    },
                    set(size) {
                        return persistSize(size);
                    },
                };

                const updateAdminNavOffset = () => {
                    const nav = document.querySelector('.admin-nav');
                    const navHeight = nav ? Math.ceil(nav.getBoundingClientRect().height) : 0;
                    document.documentElement.style.setProperty('--admin-nav-offset', `${navHeight}px`);
                };

                document.addEventListener('DOMContentLoaded', () => {
                    applySize(readStoredSize());
                    updateAdminNavOffset();

                    if (window.ResizeObserver) {
                        const nav = document.querySelector('.admin-nav');

                        if (nav) {
                            new ResizeObserver(updateAdminNavOffset).observe(nav);
                        }
                    }
                }, { once: true });

                window.addEventListener('pageshow', () => {
                    applySize(readStoredSize());
                    updateAdminNavOffset();
                });

                window.addEventListener('resize', updateAdminNavOffset);
                window.addEventListener('admin-font-size-changed', () => {
                    requestAnimationFrame(updateAdminNavOffset);
                });
            })();
        </script>
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-light {{ $isAdminArea ? 'is-admin-area' : '' }} {{ $isBoardPage ? 'is-board-page' : '' }}">
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
    <x-global-request-alert />
</body>
</html>
