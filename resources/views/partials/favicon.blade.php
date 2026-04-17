@php
    $faviconVersion = file_exists(public_path('favicon.ico'))
        ? (string) filemtime(public_path('favicon.ico'))
        : (string) config('app.asset_version', '1');
@endphp
<link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}?v={{ $faviconVersion }}">
<link rel="alternate icon" href="{{ asset('favicon.ico') }}?v={{ $faviconVersion }}">
<link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v={{ $faviconVersion }}">
