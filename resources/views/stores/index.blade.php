@extends('layouts.app')

@php
    $storesPageTitle = $keyword !== ''
        ? __('home.search') . ': ' . $keyword . ' | ' . __('home.orderable_stores') . ' | ' . config('app.name', 'DineFlow')
        : __('home.orderable_stores') . ' | ' . config('app.name', 'DineFlow');
    $storesPageDescription = $keyword !== ''
        ? '搜尋「' . $keyword . '」相關的 DineFlow 可點餐店家，快速查看地址、營業時間與外帶點餐入口。'
        : __('home.choose_store_desc');
@endphp

@section('title', $storesPageTitle)
@section('meta_description', $storesPageDescription)
@section('canonical', request()->fullUrl())
@section('meta_image', asset('images/logo-256.png'))

@push('head')
@if ($stores->previousPageUrl())
    <link rel="prev" href="{{ $stores->previousPageUrl() }}">
@endif
@if ($stores->nextPageUrl())
    <link rel="next" href="{{ $stores->nextPageUrl() }}">
@endif
@endpush

@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => $storesPageTitle,
    'description' => $storesPageDescription,
    'url' => request()->fullUrl(),
    'inLanguage' => str_replace('_', '-', app()->getLocale()),
    'mainEntity' => [
        '@type' => 'ItemList',
        'numberOfItems' => $stores->count(),
        'itemListOrder' => 'https://schema.org/ItemListOrderAscending',
        'itemListElement' => $stores->values()->map(fn ($store, $index) => [
            '@type' => 'ListItem',
            'position' => (($stores->currentPage() - 1) * $stores->perPage()) + $index + 1,
            'url' => route('customer.takeout.menu', ['store' => $store]),
            'name' => $store->name,
        ])->all(),
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
<div class="min-h-screen bg-brand-soft/20 text-brand-dark">
    <section class="border-b border-brand-soft/50 bg-white py-10 sm:py-14">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="rounded-[2rem] border border-brand-soft/70 bg-gradient-to-br from-white via-brand-soft/10 to-brand-highlight/10 p-6 shadow-[0_16px_40px_rgba(90,30,14,0.08)] sm:p-8">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-primary/80 transition hover:text-brand-primary">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10.7 4.3a1 1 0 010 1.4L7.41 9H16a1 1 0 110 2H7.41l3.3 3.3a1 1 0 11-1.42 1.4l-5-5a1 1 0 010-1.4l5-5a1 1 0 011.4 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ __('home.full_intro_back_home') }}
                        </a>
                        <h1 class="mt-4 text-3xl font-bold tracking-tight text-brand-dark sm:text-4xl">{{ __('home.orderable_stores') }}</h1>
                        <p class="mt-3 text-lg text-brand-primary/75">{{ __('home.choose_store_desc') }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2 text-sm font-semibold">
                        <span class="inline-flex items-center rounded-full border border-brand-soft/70 bg-white/80 px-3 py-1.5 text-brand-primary">
                            {{ __('home.view_all_stores') }}: {{ number_format($stores->total()) }}
                        </span>
                        @if($hasUserLocation)
                            <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-emerald-700">
                                {{ __('home.use_my_location') }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="sticky top-20 z-20 mx-auto mt-8 max-w-5xl rounded-[1.5rem] border border-brand-soft/80 bg-white/95 p-4 shadow-[0_18px_40px_rgba(90,30,14,0.08)] backdrop-blur">
                <div>
                    <form method="GET" action="{{ route('stores.list') }}" class="flex flex-col gap-3 md:flex-row">
                        <input type="hidden" name="lat" id="store-list-latitude" value="{{ $userLatitude ?? '' }}">
                        <input type="hidden" name="lng" id="store-list-longitude" value="{{ $userLongitude ?? '' }}">
                        <label for="stores-keyword" class="sr-only">{{ __('home.search') }}</label>
                        <input
                            id="stores-keyword"
                            type="text"
                            name="keyword"
                            value="{{ $keyword ?? '' }}"
                            placeholder="{{ __('home.search_placeholder') }}"
                            class="min-w-0 flex-1 rounded-2xl border border-brand-soft/70 px-4 py-3 text-lg text-brand-dark placeholder:text-brand-primary/80 focus:border-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-highlight/40"
                        >
                        <button
                            type="submit"
                            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-2xl bg-brand-primary px-5 py-3 text-base font-semibold text-white transition hover:bg-brand-accent hover:text-brand-dark"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M8.5 3a5.5 5.5 0 014.46 8.72l3.16 3.16a1 1 0 01-1.41 1.41l-3.16-3.16A5.5 5.5 0 118.5 3zm0 2a3.5 3.5 0 100 7 3.5 3.5 0 000-7z" clip-rule="evenodd"/>
                            </svg>
                            {{ __('home.search') }}
                        </button>
                        <button
                            type="button"
                            id="use-current-location"
                            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-2xl border border-brand-soft/70 bg-white px-4 py-3 text-base font-semibold text-brand-primary transition hover:bg-brand-soft/20"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1.07a6.01 6.01 0 014.93 4.93H17a1 1 0 110 2h-1.07a6.01 6.01 0 01-4.93 4.93V17a1 1 0 11-2 0v-1.07a6.01 6.01 0 01-4.93-4.93H3a1 1 0 110-2h1.07A6.01 6.01 0 019 4.07V3a1 1 0 011-1zm0 4a4 4 0 100 8 4 4 0 000-8z" clip-rule="evenodd"/>
                            </svg>
                            <span id="use-current-location-label">{{ __('home.use_my_location') }}</span>
                        </button>
                        @if($keyword !== '' || $hasUserLocation)
                            <a
                                href="{{ route('stores.list') }}"
                                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-2xl border border-brand-soft/70 bg-white px-4 py-3 text-base font-semibold text-brand-primary transition hover:bg-brand-soft/20"
                            >
                                {{ __('home.view_all_stores') }}
                            </a>
                        @endif
                    </form>
                </div>
                @if($hasUserLocation)
                    <p class="mt-3 rounded-xl border border-emerald-100 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700">
                        {{ __('home.location_filtered_hint') }}
                    </p>
                @endif
            </div>
        </div>
    </section>

    <section class="py-12">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            @if($stores->count())
                <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-brand-soft/60 bg-white px-4 py-3 text-sm text-brand-primary/80 shadow-sm">
                    <span>
                        {{ number_format($stores->firstItem() ?? 0) }} - {{ number_format($stores->lastItem() ?? 0) }} / {{ number_format($stores->total()) }}
                    </span>
                    @if($keyword !== '')
                        <span class="rounded-full border border-brand-soft/70 bg-brand-soft/20 px-3 py-1 font-semibold text-brand-primary">
                            "{{ $keyword }}"
                        </span>
                    @endif
                </div>

                <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($stores as $store)
                        @php
                            $avgRating = (float) ($store->reviews_avg_rating ?? 0);
                            $roundedRating = (int) round($avgRating);
                            $reviewCount = (int) ($store->reviews_count ?? 0);
                        @endphp
                        <div class="group flex h-full flex-col overflow-hidden rounded-[1.75rem] border border-brand-soft/60 bg-white shadow-[0_18px_44px_rgba(90,30,14,0.1)] transition duration-300 hover:-translate-y-1 hover:border-brand-primary/30 hover:shadow-[0_24px_60px_rgba(90,30,14,0.16)]">
                            <div class="relative h-48 w-full overflow-hidden">
                                <img
                                    src="{{ $store->banner_image ? asset('storage/' . $store->banner_image) : 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1200&q=80' }}"
                                    alt="{{ $store->name }}"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                >
                                <div class="absolute inset-0 bg-gradient-to-t from-brand-dark/85 via-brand-dark/25 to-transparent"></div>
                                <div class="absolute left-4 top-4">
                                    @if($store->isOrderingAvailable())
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-highlight px-3 py-1 text-xs font-semibold text-brand-dark shadow">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M7.3 10.7a1 1 0 011.4 0l.8.8 2.8-2.8a1 1 0 111.4 1.4l-3.5 3.5a1 1 0 01-1.4 0l-1.5-1.5a1 1 0 010-1.4z" clip-rule="evenodd"/>
                                            </svg>
                                            {{ __('home.status_orderable') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/80 px-3 py-1 text-xs font-semibold text-brand-dark shadow">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M4.3 4.3a1 1 0 011.4 0L10 8.6l4.3-4.3a1 1 0 111.4 1.4L11.4 10l4.3 4.3a1 1 0 01-1.4 1.4L10 11.4l-4.3 4.3a1 1 0 01-1.4-1.4L8.6 10 4.3 5.7a1 1 0 010-1.4z" clip-rule="evenodd"/>
                                            </svg>
                                            {{ __('home.status_closed') }}
                                        </span>
                                    @endif
                                </div>
                                @if(isset($store->distance_km))
                                    <div class="absolute right-4 top-4">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-brand-dark shadow">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M10 2a8 8 0 100 16 8 8 0 000-16zm.75 4.25a.75.75 0 00-1.5 0v3c0 .2.08.39.22.53l1.75 1.75a.75.75 0 101.06-1.06l-1.53-1.53V6.25z" clip-rule="evenodd"/>
                                            </svg>
                                            {{ __('home.distance_km', ['km' => number_format((float) $store->distance_km, 1)]) }}
                                        </span>
                                    </div>
                                @endif
                                <div class="absolute bottom-4 left-4 right-4 text-white">
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-highlight/80">{{ __('home.store_label') }}</p>
                                    <h3 class="mt-2 text-2xl font-bold">{{ $store->name }}</h3>
                                </div>
                            </div>

                            <div class="flex flex-1 flex-col p-6">
                                <p class="text-base leading-7 text-brand-primary/75">
                                    {{ $store->description ? \Illuminate\Support\Str::limit($store->description, 100) : __('home.default_store_desc') }}
                                </p>

                                <div class="mt-5 space-y-2 text-base text-brand-primary/70">
                                    <div class="flex items-center gap-2">
                                        <svg class="h-4 w-4 text-brand-primary/70" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path d="M10 2.8l2.22 4.5 4.96.72-3.59 3.5.85 4.95L10 14.35l-4.44 2.32.85-4.95-3.59-3.5 4.96-.72L10 2.8z"/>
                                        </svg>
                                        @if($reviewCount > 0)
                                            <button
                                                type="button"
                                                class="rounded-lg px-1 text-left underline decoration-brand-primary/35 underline-offset-4 transition hover:text-brand-primary hover:decoration-brand-primary"
                                                data-store-review-trigger
                                                data-store-review-url="{{ route('stores.reviews', ['store' => $store]) }}"
                                                data-store-name="{{ $store->name }}"
                                                data-review-count="{{ $reviewCount }}"
                                            >
                                                {{ str_repeat('★', $roundedRating) }}{{ str_repeat('☆', max(5 - $roundedRating, 0)) }} {{ number_format($avgRating, 1) }} ({{ $reviewCount }} {{ __('home.store_reviews_unit') }})
                                            </button>
                                        @else
                                            <span>{{ __('home.store_rating_empty') }}</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <svg class="h-4 w-4 text-brand-primary/70" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.5a.75.75 0 10-1.5 0v3.25c0 .2.08.39.22.53l2 2a.75.75 0 101.06-1.06l-1.78-1.78V6.5z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>{{ __('home.business_hours') }} {{ $store->businessHoursLabel() }}</span>
                                    </div>
                                    @if(!empty($store->prep_time_minutes))
                                        <div class="flex items-center gap-2">
                                            <svg class="h-4 w-4 text-brand-primary/70" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M10 2a8 8 0 100 16 8 8 0 000-16zm.75 4.25a.75.75 0 00-1.5 0V10c0 .2.08.39.22.53l2.5 2.5a.75.75 0 101.06-1.06l-2.28-2.28V6.25z" clip-rule="evenodd"/>
                                            </svg>
                                            <span>{{ __('home.prep_time_minutes', ['minutes' => (int) $store->prep_time_minutes]) }}</span>
                                        </div>
                                    @endif
                                    @if(!empty($store->address))
                                        <div class="flex items-center gap-2">
                                            <svg class="h-4 w-4 text-brand-primary/70" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M10 2a6 6 0 00-6 6c0 4.03 4.86 8.84 5.07 9.04a1.3 1.3 0 001.86 0c.2-.2 5.07-5.01 5.07-9.04a6 6 0 00-6-6zm0 8a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                            </svg>
                                            <span>{{ __('home.address') }} {{ $store->address }}</span>
                                        </div>
                                    @endif
                                    @if(!empty($store->phone))
                                        <div class="flex items-center gap-2">
                                            <svg class="h-4 w-4 text-brand-primary/70" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path d="M2.4 2.9a1 1 0 011.1-.3l2.2.7a1 1 0 01.7 1v2a1 1 0 01-.3.7l-1 1a12.5 12.5 0 005 5l1-1a1 1 0 01.7-.3h2a1 1 0 011 .7l.7 2.2a1 1 0 01-.3 1.1l-1.4 1.1a2 2 0 01-1.8.3A16.3 16.3 0 012.9 7.1a2 2 0 01.3-1.8L4.3 3.9z"/>
                                            </svg>
                                            <span>{{ __('home.phone') }} {{ $store->phone }}</span>
                                        </div>
                                    @endif
                                    @if(isset($store->distance_km))
                                        <div class="flex items-center gap-2">
                                            <svg class="h-4 w-4 text-brand-primary/70" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M10 2a8 8 0 100 16 8 8 0 000-16zm.75 4.25a.75.75 0 00-1.5 0v3c0 .2.08.39.22.53l1.75 1.75a.75.75 0 101.06-1.06l-1.53-1.53V6.25z" clip-rule="evenodd"/>
                                            </svg>
                                            <span>{{ __('home.distance_km', ['km' => number_format((float) $store->distance_km, 1)]) }}</span>
                                        </div>
                                    @endif
                                </div>

                                <div class="mt-auto pt-6">
                                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                        <a href="{{ route('stores.enter', ['store' => $store]) }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-brand-primary px-4 py-3 text-base font-semibold text-white transition hover:bg-brand-accent hover:text-brand-dark">
                                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M10.3 4.3a1 1 0 011.4 0l5 5a1 1 0 010 1.4l-5 5a1 1 0 01-1.4-1.4L13.59 11H4a1 1 0 110-2h9.59l-3.3-3.3a1 1 0 010-1.4z" clip-rule="evenodd"/>
                                            </svg>
                                            {{ __('home.store_intro') }}
                                        </a>

                                        @if($store->isOrderingAvailable())
                                            <a href="{{ route('customer.takeout.menu', ['store' => $store]) }}" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-brand-primary/20 bg-brand-soft/20 px-4 py-3 text-base font-semibold text-brand-primary transition hover:bg-brand-soft/40">
                                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path d="M3 4.75A1.75 1.75 0 014.75 3h.74a1.75 1.75 0 011.67 1.23l.17.52h8.92a.75.75 0 01.73.91l-1.2 5A1.75 1.75 0 0114.08 12H8.1a1.75 1.75 0 01-1.68-1.23L4.78 5.75h-.03A.25.25 0 004.5 6v7.25a.75.75 0 01-1.5 0V4.75z"/>
                                                    <path d="M8 15.5a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0zM13 15.5a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0z"/>
                                                </svg>
                                                {{ __('home.order_now') }}
                                            </a>
                                        @else
                                            <button disabled class="inline-flex cursor-not-allowed items-center justify-center rounded-2xl bg-slate-200 px-4 py-3 text-base font-semibold text-slate-500">
                                                {{ __('home.not_open_yet') }}
                                            </button>
                                        @endif

                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-10">
                    @if ($stores->hasPages())
                        <nav class="rounded-2xl border border-brand-soft/60 bg-white p-4 shadow-sm" aria-label="{{ __('stores.pagination_nav_label') }}">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="text-sm font-medium text-brand-primary/80">
                                    {{ __('stores.page_indicator', ['current' => number_format($stores->currentPage()), 'last' => number_format($stores->lastPage())]) }}
                                </div>

                                <div class="flex items-center gap-2">
                                    @if ($stores->onFirstPage())
                                        <span class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-400">{{ __('pagination.previous') }}</span>
                                    @else
                                        <a href="{{ $stores->previousPageUrl() }}" class="inline-flex items-center justify-center rounded-xl border border-brand-soft/70 bg-white px-3 py-2 text-sm font-semibold text-brand-primary transition hover:bg-brand-soft/20">{{ __('pagination.previous') }}</a>
                                    @endif

                                    @if ($stores->hasMorePages())
                                        <a href="{{ $stores->nextPageUrl() }}" class="inline-flex items-center justify-center rounded-xl border border-brand-soft/70 bg-white px-3 py-2 text-sm font-semibold text-brand-primary transition hover:bg-brand-soft/20">{{ __('pagination.next') }}</a>
                                    @else
                                        <span class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-400">{{ __('pagination.next') }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                @php
                                    $elements = $stores->onEachSide(1)->linkCollection()->toArray();
                                @endphp

                                @foreach ($elements as $item)
                                    @php
                                        $label = trim(strip_tags((string) ($item['label'] ?? '')));
                                        $isNumeric = ctype_digit($label);
                                        $isDots = $label === '...';
                                    @endphp

                                    @if (! $isNumeric && ! $isDots)
                                        @continue
                                    @endif

                                    @if (!empty($item['url']))
                                        @if (!empty($item['active']))
                                            <span class="inline-flex min-w-9 items-center justify-center rounded-xl bg-brand-primary px-3 py-2 text-sm font-semibold text-white">{{ $label }}</span>
                                        @else
                                            <a href="{{ $item['url'] }}" class="inline-flex min-w-9 items-center justify-center rounded-xl border border-brand-soft/70 bg-white px-3 py-2 text-sm font-semibold text-brand-primary transition hover:bg-brand-soft/20">{{ $label }}</a>
                                        @endif
                                    @else
                                        <span class="inline-flex min-w-9 items-center justify-center rounded-xl border border-slate-200 bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-500">...</span>
                                    @endif
                                @endforeach
                            </div>
                        </nav>
                    @endif
                </div>
            @else
                <div class="rounded-[2rem] border border-brand-soft/60 bg-white px-6 py-16 text-center shadow-[0_20px_40px_rgba(90,30,14,0.08)]">
                    <div class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-full bg-brand-soft/70 text-brand-primary">
                        <svg class="h-7 w-7" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 2a6 6 0 00-6 6c0 4.03 4.86 8.84 5.07 9.04a1.3 1.3 0 001.86 0c.2-.2 5.07-5.01 5.07-9.04a6 6 0 00-6-6zm0 8a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-brand-dark">{{ __('home.no_store_found_title') }}</h3>
                    <p class="mt-3 text-lg text-brand-primary/75">{{ __('home.no_store_found_desc') }}</p>
                    <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                        <a href="{{ route('stores.list') }}" class="inline-flex items-center rounded-2xl border border-brand-soft/70 bg-white px-5 py-3 text-base font-semibold text-brand-primary transition hover:bg-brand-soft/20">
                            {{ __('home.view_all_stores') }}
                        </a>
                        <a href="{{ route('home') }}" class="inline-flex items-center rounded-2xl bg-brand-primary px-5 py-3 text-base font-semibold text-white transition hover:bg-brand-accent hover:text-brand-dark">
                            {{ __('home.full_intro_back_home') }}
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </section>
</div>

@include('partials.store-reviews-modal')

<script>
(() => {
    const locationButton = document.getElementById('use-current-location');
    const locationButtonLabel = document.getElementById('use-current-location-label');
    const latInput = document.getElementById('store-list-latitude');
    const lngInput = document.getElementById('store-list-longitude');
    const form = latInput?.closest('form');
    const hasServerLocation = @json($hasUserLocation);
    const i18n = {
        locating: @json(__('home.locating')),
        useMyLocation: @json(__('home.use_my_location')),
        locationNotSupported: @json(__('home.location_not_supported')),
        locationPermissionDenied: @json(__('home.location_permission_denied')),
        locationUnavailable: @json(__('home.location_unavailable')),
        locationTimeout: @json(__('home.location_timeout')),
    };

    if (!locationButton || !locationButtonLabel || !latInput || !lngInput || !form) {
        return;
    }

    const resetButtonState = () => {
        locationButton.disabled = false;
        locationButtonLabel.textContent = i18n.useMyLocation;
    };

    const updateLocationAndSubmit = () => {
        if (!navigator.geolocation) {
            alert(i18n.locationNotSupported);
            return;
        }

        locationButton.disabled = true;
        locationButtonLabel.textContent = i18n.locating;

        navigator.geolocation.getCurrentPosition((position) => {
            latInput.value = String(position.coords.latitude);
            lngInput.value = String(position.coords.longitude);
            form.submit();
        }, (error) => {
            resetButtonState();

            if (error.code === error.PERMISSION_DENIED) {
                alert(i18n.locationPermissionDenied);
                return;
            }

            if (error.code === error.TIMEOUT) {
                alert(i18n.locationTimeout);
                return;
            }

            alert(i18n.locationUnavailable);
        }, {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 60000,
        });
    };

    locationButton.addEventListener('click', updateLocationAndSubmit);

    if (!hasServerLocation && !sessionStorage.getItem('store-list-location-auto-attempted')) {
        sessionStorage.setItem('store-list-location-auto-attempted', '1');
        updateLocationAndSubmit();
    }
})();
</script>
@endsection
