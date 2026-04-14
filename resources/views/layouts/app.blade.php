<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-light">
    <div class="min-vh-100">
        @include('layouts.navigation')

        {{-- 給 extends / section 用 --}}
        @hasSection('content')
            @yield('content')
        @else
            {{-- 給 x-app-layout 用 --}}
            {{ $slot ?? '' }}
        @endif
    </div>
</body>
</html>