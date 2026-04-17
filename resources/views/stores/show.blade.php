@extends('layouts.app')

@php
    $storeMetaTitle = $store->name . ' | ' . config('app.name', 'DineFlow');
    $storeMetaDescription = $store->description
        ?: $store->name . ' 提供線上菜單、外帶點餐與店家資訊，快速查看營業時間、地址與熱門餐點。';
    $storeCanonical = route('stores.enter', ['store' => $store]);
    $storeMetaImage = $store->banner_image
        ? asset('storage/' . $store->banner_image)
        : asset('images/logo-256.png');
    $currencyCode = strtolower((string) ($store->currency ?? 'twd'));
    $currencySymbol = match ($currencyCode) {
        'vnd' => 'VND',
        'cny' => 'CNY',
        'usd' => 'USD',
        default => 'NT$',
    };
    $mapsUrl = !empty($store->address)
        ? 'https://www.google.com/maps/search/?api=1&query=' . urlencode($store->address)
        : null;
@endphp

@section('title', $storeMetaTitle)
@section('meta_description', \Illuminate\Support\Str::limit($storeMetaDescription, 160))
@section('canonical', $storeCanonical)
@section('meta_image', $storeMetaImage)

@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Restaurant',
    'name' => $store->name,
    'description' => \Illuminate\Support\Str::limit($storeMetaDescription, 160),
    'url' => $storeCanonical,
    'image' => $storeMetaImage,
    'telephone' => $store->phone,
    'address' => $store->address,
    'hasMenu' => $takeoutUrl,
    'servesCuisine' => $categories->pluck('name')->take(3)->values()->all(),
    'geo' => $store->latitude !== null && $store->longitude !== null ? [
        '@type' => 'GeoCoordinates',
        'latitude' => $store->latitude,
        'longitude' => $store->longitude,
    ] : null,
    'inLanguage' => str_replace('_', '-', app()->getLocale()),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
<div class="min-h-screen bg-brand-soft/20 pb-24 text-brand-dark md:pb-0">
    <section class="relative isolate overflow-hidden border-b border-brand-soft/60 bg-brand-dark text-white">
        <div class="absolute inset-0">
            <img
                src="{{ $storeMetaImage }}"
                alt="{{ $store->name }}"
                class="h-full w-full object-cover opacity-25"
            >
            <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(90,30,14,0.94),rgba(236,144,87,0.84))]"></div>
        </div>
        <div class="absolute -left-10 top-24 h-44 w-44 rounded-full bg-brand-highlight/15 blur-3xl"></div>
        <div class="absolute -right-10 bottom-0 h-56 w-56 rounded-full bg-brand-soft/20 blur-3xl"></div>

        <div class="relative mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-24">
            <div class="grid gap-10 lg:grid-cols-[1.2fr_0.8fr] lg:items-end">
                <div>
                    <a href="{{ route('stores.list') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-white/75 transition hover:text-white">
                        {{ __('home.view_all_stores') }}
                    </a>
                    <div class="mt-5 flex flex-wrap items-center gap-3 text-sm font-semibold">
                        <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-brand-highlight">
                            {{ $orderingAvailable ? __('home.status_orderable') : __('home.status_closed') }}
                        </span>
                        <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-white/85">
                            {{ __('home.business_hours') }} {{ $store->businessHoursLabel() }}
                        </span>
                        @if($store->prep_time_minutes)
                            <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-white/85">
                                {{ __('home.prep_time_minutes', ['minutes' => (int) $store->prep_time_minutes]) }}
                            </span>
                        @endif
                    </div>
                    <h1 class="mt-6 text-4xl font-bold tracking-tight sm:text-5xl">{{ $store->name }}</h1>
                    <p class="mt-5 max-w-3xl text-lg leading-8 text-white/80">
                        {{ $store->description ?: __('home.default_store_desc') }}
                    </p>

                    @if($store->address)
                        <div class="mt-4 inline-flex max-w-3xl items-start gap-2 rounded-2xl border border-white/20 bg-white/10 px-4 py-3 text-sm text-white/85 backdrop-blur">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-highlight" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 2a6 6 0 00-6 6c0 4.03 4.86 8.84 5.07 9.04a1.3 1.3 0 001.86 0c.2-.2 5.07-5.01 5.07-9.04a6 6 0 00-6-6zm0 8a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                            </svg>
                            <span>{{ __('home.address') }} {{ $store->address }}</span>
                        </div>
                    @endif

                    <div class="mt-8 flex flex-wrap gap-4">
                        @if($takeoutUrl)
                            <a href="{{ $takeoutUrl }}" class="inline-flex items-center justify-center rounded-2xl bg-brand-highlight px-5 py-3 text-base font-semibold text-brand-dark shadow-lg shadow-brand-highlight/30 transition hover:-translate-y-0.5 hover:bg-brand-soft">
                                {{ __('home.order_now') }}
                            </a>
                        @endif
                        @if($mapsUrl)
                            <a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-2xl border border-white/20 bg-white/10 px-5 py-3 text-base font-semibold text-white transition hover:bg-white/15">
                                {{ __('stores.open_map') }}
                            </a>
                        @endif
                        @if($store->phone)
                            <a href="tel:{{ preg_replace('/\D+/', '', (string) $store->phone) }}" class="inline-flex items-center justify-center rounded-2xl border border-white/20 bg-white/10 px-5 py-3 text-base font-semibold text-white transition hover:bg-white/15">
                                {{ __('home.phone') }}
                            </a>
                        @endif
                    </div>
                </div>

                <div class="rounded-[2rem] border border-white/10 bg-white/10 p-6 shadow-[0_24px_60px_rgba(0,0,0,0.22)] backdrop-blur">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-bold text-white">{{ $store->name }}</h2>
                        <span class="inline-flex rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold text-white/90">
                            {{ $orderingAvailable ? __('home.status_orderable') : __('home.status_closed') }}
                        </span>
                    </div>

                    <div class="mt-5 space-y-4 text-sm text-white/85">
                        <div class="flex items-start gap-3">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-highlight" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 2a6 6 0 00-6 6c0 4.03 4.86 8.84 5.07 9.04a1.3 1.3 0 001.86 0c.2-.2 5.07-5.01 5.07-9.04a6 6 0 00-6-6zm0 8a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                            </svg>
                            <div>{{ __('home.address') }} {{ $store->address ?: '-' }}</div>
                        </div>

                        <div class="flex items-start gap-3">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-highlight" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M2.4 2.9a1 1 0 011.1-.3l2.2.7a1 1 0 01.7 1v2a1 1 0 01-.3.7l-1 1a12.5 12.5 0 005 5l1-1a1 1 0 01.7-.3h2a1 1 0 011 .7l.7 2.2a1 1 0 01-.3 1.1l-1.4 1.1a2 2 0 01-1.8.3A16.3 16.3 0 012.9 7.1a2 2 0 01.3-1.8L4.3 3.9z"/>
                            </svg>
                            <div>{{ __('home.phone') }} {{ $store->phone ?: '-' }}</div>
                        </div>

                        <div class="flex items-start gap-3">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-highlight" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 2a8 8 0 100 16 8 8 0 000-16zm.75 4.25a.75.75 0 00-1.5 0v3c0 .2.08.39.22.53l1.75 1.75a.75.75 0 101.06-1.06l-1.53-1.53V6.25z" clip-rule="evenodd"/>
                            </svg>
                            <div>{{ __('home.business_hours') }} {{ $store->businessHoursLabel() }}</div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 pt-1 text-white">
                            <div class="rounded-xl border border-white/15 bg-white/10 px-3 py-2">
                                <div class="text-xs text-white/70">{{ __('customer.menu_section') }}</div>
                                <div class="mt-1 text-base font-bold">{{ number_format((int) $store->active_categories_count) }}</div>
                            </div>
                            <div class="rounded-xl border border-white/15 bg-white/10 px-3 py-2">
                                <div class="text-xs text-white/70">{{ __('customer.items_in_menu') }}</div>
                                <div class="mt-1 text-base font-bold">{{ number_format((int) $store->active_products_count) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="store-overview" class="border-b border-brand-soft/50 bg-white py-14">
        <div class="mx-auto grid max-w-7xl gap-6 px-6 md:grid-cols-3 lg:px-8">
            <div class="rounded-[1.6rem] border border-brand-soft/60 bg-brand-soft/18 p-6 transition hover:-translate-y-0.5 hover:shadow-sm">
                <div class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-primary/70">01</div>
                <h2 class="mt-3 text-xl font-bold text-brand-dark">{{ __('customer.business_hours') }}</h2>
                <p class="mt-2 text-base leading-7 text-brand-primary/75">{{ $store->businessHoursLabel() }}</p>
            </div>
            <div class="rounded-[1.6rem] border border-brand-soft/60 bg-brand-soft/18 p-6 transition hover:-translate-y-0.5 hover:shadow-sm">
                <div class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-primary/70">02</div>
                <h2 class="mt-3 text-xl font-bold text-brand-dark">{{ __('customer.items_in_menu') }}</h2>
                <p class="mt-2 text-base leading-7 text-brand-primary/75">{{ number_format((int) $store->active_products_count) }} {{ __('customer.items_in_menu') }}</p>
            </div>
            <div class="rounded-[1.6rem] border border-brand-soft/60 bg-brand-soft/18 p-6 transition hover:-translate-y-0.5 hover:shadow-sm">
                <div class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-primary/70">03</div>
                <h2 class="mt-3 text-xl font-bold text-brand-dark">{{ __('customer.takeout') }}</h2>
                <p class="mt-2 text-base leading-7 text-brand-primary/75">
                    {{ $takeoutUrl ? __('customer.welcome_takeout_desc') : __('customer.ordering_closed') }}
                </p>
            </div>
        </div>
    </section>

    @if($categories->isNotEmpty())
        <section id="menu-categories" class="border-b border-brand-soft/60 bg-white py-16">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="mb-8 flex items-end justify-between gap-4">
                    <div>
                        <h2 class="text-3xl font-bold tracking-tight text-brand-dark">{{ __('customer.menu_section') }}</h2>
                        <p class="mt-3 text-lg text-brand-primary/75">{{ $store->name }} 的熱門分類與可點餐內容。</p>
                    </div>
                    @if($takeoutUrl)
                        <a href="{{ $takeoutUrl }}" class="inline-flex items-center rounded-2xl border border-brand-soft/70 bg-brand-soft/20 px-4 py-3 text-sm font-semibold text-brand-primary transition hover:bg-brand-soft/40">
                            {{ __('home.order_now') }}
                        </a>
                    @endif
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($categories as $category)
                        <div class="rounded-[1.6rem] border border-brand-soft/60 bg-brand-soft/18 p-6 shadow-sm transition hover:-translate-y-0.5 hover:bg-brand-soft/30">
                            <h3 class="text-xl font-bold text-brand-dark">{{ $category->name }}</h3>
                            <p class="mt-2 text-base text-brand-primary/75">{{ number_format((int) $category->products_count) }} {{ __('customer.items_in_menu') }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if($featuredProducts->isNotEmpty())
        <section id="featured-products" class="bg-white py-16">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="mb-8">
                    <h2 class="text-3xl font-bold tracking-tight text-brand-dark">{{ $store->name }} 精選餐點</h2>
                    <p class="mt-3 text-lg text-brand-primary/75">先看看熱門餐點，再決定是否直接進入點餐頁。</p>
                </div>

                <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($featuredProducts as $product)
                        <article class="overflow-hidden rounded-[1.75rem] border border-brand-soft/60 bg-white shadow-[0_18px_44px_rgba(90,30,14,0.1)] transition hover:-translate-y-1 hover:shadow-[0_24px_56px_rgba(90,30,14,0.14)]">
                            <div class="relative h-48 overflow-hidden bg-brand-soft/30">
                                <img
                                    src="{{ $product->seo_image_url ?: 'https://images.unsplash.com/photo-1515003197210-e0cd71810b5f?auto=format&fit=crop&w=900&q=80' }}"
                                    alt="{{ $product->name }}"
                                    class="h-full w-full object-cover"
                                >
                                @if($product->category)
                                    <div class="absolute left-4 top-4 inline-flex rounded-full border border-black/80 bg-black/50 px-3 py-1 text-xs font-semibold text-white backdrop-blur">
                                        {{ $product->category->name }}
                                    </div>
                                @endif
                            </div>
                            <div class="p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h3 class="text-lg font-bold text-brand-dark">{{ $product->name }}</h3>
                                        <p class="mt-2 text-sm leading-6 text-brand-primary/75">
                                            {{ $product->description ?: __('customer.fresh_made') }}
                                        </p>
                                    </div>
                                    <div class="shrink-0 rounded-full bg-brand-highlight px-3 py-1.5 text-sm font-bold text-brand-dark">
                                        {{ $currencySymbol }} {{ number_format((int) $product->price) }}
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if($takeoutUrl || $mapsUrl || $store->phone)
        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-brand-soft/60 bg-white/95 p-3 shadow-[0_-8px_24px_rgba(90,30,14,0.12)] backdrop-blur md:hidden">
            <div class="mx-auto flex max-w-7xl items-center gap-2">
                @if($takeoutUrl)
                    <a href="{{ $takeoutUrl }}" class="inline-flex flex-1 items-center justify-center rounded-xl bg-brand-primary px-3 py-3 text-sm font-semibold text-white">
                        {{ __('home.order_now') }}
                    </a>
                @endif
                @if($mapsUrl)
                    <a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex flex-1 items-center justify-center rounded-xl border border-brand-soft/70 bg-brand-soft/20 px-3 py-3 text-sm font-semibold text-brand-primary">
                        {{ __('stores.open_map') }}
                    </a>
                @endif
                @if($store->phone)
                    <a href="tel:{{ preg_replace('/\D+/', '', (string) $store->phone) }}" class="inline-flex flex-1 items-center justify-center rounded-xl border border-brand-soft/70 bg-white px-3 py-3 text-sm font-semibold text-brand-primary">
                        {{ __('home.phone') }}
                    </a>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
