@extends('layouts.app')

@php
    $storeMetaTitle = $store->name . ' | ' . config('app.name', 'DineFlow');
    $storeMetaDescription = $store->description
        ?: __('stores.meta_fallback_description', ['store' => $store->name]);
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
    $mapsUrl = ! empty($store->address)
        ? 'https://www.google.com/maps/search/?api=1&query=' . urlencode($store->address)
        : null;
    $phoneHref = filled($store->phone)
        ? 'tel:' . preg_replace('/\D+/', '', (string) $store->phone)
        : null;
    $statusLabel = $orderingAvailable ? __('home.status_orderable') : __('home.status_closed');
    $quickLinks = array_values(array_filter([
        ['href' => '#store-overview', 'label' => __('stores.store_info')],
        $categories->isNotEmpty() ? ['href' => '#menu-categories', 'label' => __('customer.menu_section')] : null,
        $featuredProducts->isNotEmpty() ? ['href' => '#featured-products', 'label' => __('stores.featured_dishes')] : null,
        ['href' => '#contact-store', 'label' => __('stores.contact_store')],
    ]));
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
<div class="min-h-screen bg-[linear-gradient(180deg,rgba(250,244,239,0.95),rgba(255,255,255,1))] pb-24 text-brand-dark md:pb-0">
    <section class="relative isolate overflow-hidden border-b border-brand-soft/60 bg-brand-dark text-white">
        <div class="absolute inset-0">
            <img
                src="{{ $storeMetaImage }}"
                alt="{{ $store->name }}"
                class="h-full w-full object-cover opacity-20"
            >
            <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(53,19,10,0.96),rgba(90,30,14,0.94),rgba(236,144,87,0.78))]"></div>
        </div>
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(255,255,255,0.14),transparent_30%),radial-gradient(circle_at_bottom_right,rgba(255,215,181,0.18),transparent_28%)]"></div>
        <div class="absolute -left-10 top-24 h-44 w-44 rounded-full bg-brand-highlight/15 blur-3xl"></div>
        <div class="absolute -right-10 bottom-0 h-56 w-56 rounded-full bg-brand-soft/20 blur-3xl"></div>

        <div class="relative mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-24">
            <div class="grid gap-10 lg:grid-cols-[1.15fr_0.85fr] lg:items-start">
                <div>
                    <a href="{{ route('stores.list') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-white/75 transition hover:text-white">
                        {{ __('home.view_all_stores') }}
                    </a>

                    <div class="mt-5 flex flex-wrap items-center gap-3 text-sm font-semibold">
                        <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-brand-highlight">
                            {{ $statusLabel }}
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

                    <h1 class="mt-6 text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">{{ $store->name }}</h1>
                    <p class="mt-5 max-w-3xl text-lg leading-8 text-white/80">
                        {{ $store->description ?: __('home.default_store_desc') }}
                    </p>

                    @unless($orderingAvailable)
                        <div class="mt-6 max-w-3xl rounded-[1.5rem] border border-white/15 bg-white/10 px-5 py-4 text-sm leading-7 text-white/85 backdrop-blur">
                            {{ $store->orderingClosedMessage() ?: __('customer.ordering_closed') }}
                        </div>
                    @endunless

                    @if($store->address)
                        <div class="mt-5 inline-flex max-w-3xl items-start gap-2 rounded-2xl border border-white/20 bg-white/10 px-4 py-3 text-sm text-white/85 backdrop-blur">
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
                        @if($phoneHref)
                            <a href="{{ $phoneHref }}" class="inline-flex items-center justify-center rounded-2xl border border-white/20 bg-white/10 px-5 py-3 text-base font-semibold text-white transition hover:bg-white/15">
                                {{ __('stores.call_store') }}
                            </a>
                        @endif
                    </div>

                    <div class="mt-10 grid gap-4 sm:grid-cols-3">
                        <div class="rounded-[1.6rem] border border-white/10 bg-white/10 p-5 backdrop-blur">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-white/55">{{ __('customer.menu_section') }}</div>
                            <div class="mt-3 text-3xl font-bold text-white">{{ number_format((int) $store->active_categories_count) }}</div>
                        </div>
                        <div class="rounded-[1.6rem] border border-white/10 bg-white/10 p-5 backdrop-blur">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-white/55">{{ __('customer.items_in_menu') }}</div>
                            <div class="mt-3 text-3xl font-bold text-white">{{ number_format((int) $store->active_products_count) }}</div>
                        </div>
                        <div class="rounded-[1.6rem] border border-white/10 bg-white/10 p-5 backdrop-blur">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-white/55">{{ __('customer.takeout') }}</div>
                            <div class="mt-3 text-xl font-bold text-white">{{ $takeoutUrl ? __('home.order_now') : __('home.status_closed') }}</div>
                        </div>
                    </div>
                </div>

                <div class="rounded-[2rem] border border-white/10 bg-white/10 p-6 shadow-[0_24px_60px_rgba(0,0,0,0.22)] backdrop-blur-xl">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-white/55">{{ __('stores.store_info') }}</p>
                            <h2 class="mt-3 text-2xl font-bold text-white">{{ $store->name }}</h2>
                        </div>
                        <span class="inline-flex rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold text-white/90">
                            {{ $statusLabel }}
                        </span>
                    </div>

                    <div class="mt-6 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-[1.4rem] border border-white/10 bg-black/10 p-4">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-white/50">{{ __('home.business_hours') }}</div>
                            <div class="mt-2 text-lg font-bold text-white">{{ $store->businessHoursLabel() }}</div>
                        </div>
                        <div class="rounded-[1.4rem] border border-white/10 bg-black/10 p-4">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-white/50">{{ __('home.phone') }}</div>
                            <div class="mt-2 text-lg font-bold text-white">{{ $store->phone ?: '-' }}</div>
                        </div>
                        <div class="rounded-[1.4rem] border border-white/10 bg-black/10 p-4">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-white/50">{{ __('customer.menu_section') }}</div>
                            <div class="mt-2 text-lg font-bold text-white">{{ number_format((int) $store->active_categories_count) }}</div>
                        </div>
                        <div class="rounded-[1.4rem] border border-white/10 bg-black/10 p-4">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-white/50">{{ __('customer.items_in_menu') }}</div>
                            <div class="mt-2 text-lg font-bold text-white">{{ number_format((int) $store->active_products_count) }}</div>
                        </div>
                    </div>

                    @if($store->address)
                        <div class="mt-3 rounded-[1.4rem] border border-white/10 bg-black/10 p-4 text-sm leading-7 text-white/80">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-white/50">{{ __('home.address') }}</div>
                            <div class="mt-2">{{ $store->address }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @if(count($quickLinks) > 0)
        <section class="relative z-20 -mt-8 px-6 lg:-mt-10 lg:px-8">
            <div class="mx-auto max-w-6xl rounded-[1.8rem] border border-brand-soft/70 bg-white/95 p-4 shadow-[0_20px_60px_rgba(90,30,14,0.14)] backdrop-blur">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-primary/60">{{ __('stores.quick_jump') }}</p>
                        <p class="mt-1 text-sm text-brand-primary/70">{{ __('stores.quick_jump_text') }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($quickLinks as $link)
                            <a href="{{ $link['href'] }}" class="inline-flex items-center rounded-full border border-brand-soft/70 bg-brand-soft/20 px-4 py-2 text-sm font-semibold text-brand-primary transition hover:-translate-y-0.5 hover:bg-brand-soft/40">
                                {{ $link['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    <section id="store-overview" class="scroll-mt-24 bg-[linear-gradient(180deg,rgba(255,249,243,1),rgba(251,241,233,0.76))] py-16">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="rounded-[2.25rem] border border-brand-soft/70 bg-white/80 p-8 shadow-[0_20px_50px_rgba(90,30,14,0.08)] backdrop-blur sm:p-10">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-3xl">
                        <span class="inline-flex rounded-full border border-brand-soft/80 bg-brand-soft/30 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary/70">
                            {{ __('stores.section_intro_badge') }}
                        </span>
                        <h2 class="mt-4 text-3xl font-bold tracking-tight text-brand-dark sm:text-4xl">{{ __('stores.section_intro_title') }}</h2>
                        <p class="mt-4 text-base leading-8 text-brand-primary/75 sm:text-lg">
                            {{ __('stores.section_intro_text') }}
                        </p>
                    </div>

                    @if($takeoutUrl || $mapsUrl || $phoneHref)
                        <div class="flex flex-wrap gap-3">
                            @if($takeoutUrl)
                                <a href="{{ $takeoutUrl }}" class="inline-flex items-center justify-center rounded-2xl bg-brand-primary px-5 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:bg-brand-accent hover:text-brand-dark">
                                    {{ __('home.order_now') }}
                                </a>
                            @endif
                            @if($mapsUrl)
                                <a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-2xl border border-brand-soft/70 bg-white px-5 py-3 text-sm font-semibold text-brand-primary transition hover:bg-brand-soft/25">
                                    {{ __('stores.open_map') }}
                                </a>
                            @endif
                            @if($phoneHref)
                                <a href="{{ $phoneHref }}" class="inline-flex items-center justify-center rounded-2xl border border-brand-soft/70 bg-white px-5 py-3 text-sm font-semibold text-brand-primary transition hover:bg-brand-soft/25">
                                    {{ __('stores.call_store') }}
                                </a>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="mt-8 grid gap-4 md:grid-cols-3">
                    <div class="rounded-[1.7rem] border border-brand-soft/60 bg-[linear-gradient(135deg,rgba(255,255,255,1),rgba(255,245,236,0.92))] p-6 shadow-sm">
                        <div class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-primary/65">01</div>
                        <h3 class="mt-3 text-xl font-bold text-brand-dark">{{ __('customer.business_hours') }}</h3>
                        <p class="mt-3 text-base leading-7 text-brand-primary/75">{{ $store->businessHoursLabel() }}</p>
                        @if($store->prep_time_minutes)
                            <p class="mt-4 inline-flex rounded-full border border-brand-soft/70 bg-white px-3 py-1 text-sm font-semibold text-brand-primary">
                                {{ __('home.prep_time_minutes', ['minutes' => (int) $store->prep_time_minutes]) }}
                            </p>
                        @endif
                    </div>

                    <div class="rounded-[1.7rem] border border-brand-soft/60 bg-[linear-gradient(135deg,rgba(255,255,255,1),rgba(252,246,241,0.95))] p-6 shadow-sm">
                        <div class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-primary/65">02</div>
                        <h3 class="mt-3 text-xl font-bold text-brand-dark">{{ __('customer.menu_section') }}</h3>
                        <p class="mt-3 text-base leading-7 text-brand-primary/75">
                            {{ number_format((int) $store->active_categories_count) }} {{ __('customer.menu_section') }}
                        </p>
                        <p class="mt-2 text-sm leading-7 text-brand-primary/65">
                            {{ number_format((int) $store->active_products_count) }} {{ __('customer.items_in_menu') }}
                        </p>
                    </div>

                    <div class="rounded-[1.7rem] border border-brand-soft/60 bg-[linear-gradient(135deg,rgba(90,30,14,0.96),rgba(140,62,31,0.92))] p-6 text-white shadow-[0_16px_34px_rgba(90,30,14,0.18)]">
                        <div class="text-sm font-semibold uppercase tracking-[0.18em] text-white/60">03</div>
                        <h3 class="mt-3 text-xl font-bold">{{ __('customer.takeout') }}</h3>
                        <p class="mt-3 text-base leading-7 text-white/80">
                            {{ $takeoutUrl ? __('customer.welcome_takeout_desc') : __('customer.ordering_closed') }}
                        </p>
                        <div class="mt-4">
                            <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-sm font-semibold text-white">
                                {{ $takeoutUrl ? __('home.order_now') : __('home.status_closed') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if($categories->isNotEmpty())
        <section id="menu-categories" class="scroll-mt-24 bg-white py-16">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="rounded-[2.25rem] border border-brand-soft/70 bg-white p-8 shadow-[0_18px_48px_rgba(90,30,14,0.08)] sm:p-10">
                    <div class="mb-8 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                        <div class="max-w-3xl">
                            <span class="inline-flex rounded-full border border-brand-soft/80 bg-brand-soft/25 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary/70">
                                {{ __('customer.menu_section') }}
                            </span>
                            <h2 class="mt-4 text-3xl font-bold tracking-tight text-brand-dark sm:text-4xl">{{ __('customer.menu_section') }}</h2>
                            <p class="mt-4 text-base leading-8 text-brand-primary/75 sm:text-lg">
                                {{ __('stores.categories_intro', ['store' => $store->name]) }}
                            </p>
                        </div>
                        @if($takeoutUrl)
                            <a href="{{ $takeoutUrl }}" class="inline-flex items-center rounded-2xl border border-brand-soft/70 bg-brand-soft/20 px-4 py-3 text-sm font-semibold text-brand-primary transition hover:bg-brand-soft/35">
                                {{ __('home.order_now') }}
                            </a>
                        @endif
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach($categories as $category)
                            <article class="group rounded-[1.7rem] border border-brand-soft/60 bg-[linear-gradient(180deg,rgba(255,255,255,1),rgba(252,247,243,0.96))] p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-[0_16px_32px_rgba(90,30,14,0.12)]">
                                <div class="flex items-start justify-between gap-4">
                                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-soft/40 text-sm font-bold text-brand-primary">
                                        {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}
                                    </span>
                                    <span class="inline-flex rounded-full border border-brand-soft/70 bg-white px-3 py-1 text-sm font-semibold text-brand-primary">
                                        {{ number_format((int) $category->products_count) }} {{ __('customer.items_in_menu') }}
                                    </span>
                                </div>
                                <h3 class="mt-6 text-2xl font-bold text-brand-dark">{{ $category->name }}</h3>
                                <p class="mt-3 text-sm leading-7 text-brand-primary/70">{{ __('stores.category_card_hint') }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if($featuredProducts->isNotEmpty())
        <section id="featured-products" class="scroll-mt-24 bg-[radial-gradient(circle_at_top_left,rgba(255,214,170,0.14),transparent_28%),linear-gradient(145deg,rgba(47,16,8,1),rgba(73,24,12,0.98),rgba(104,38,20,0.96))] py-16 text-white">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="mb-8 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-3xl">
                        <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-white/70">
                            {{ __('stores.featured_dishes') }}
                        </span>
                        <h2 class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl">{{ $store->name }} {{ __('stores.featured_dishes') }}</h2>
                        <p class="mt-4 text-base leading-8 text-white/75 sm:text-lg">
                            {{ __('stores.featured_intro') }}
                        </p>
                    </div>
                    @if($takeoutUrl)
                        <a href="{{ $takeoutUrl }}" class="inline-flex items-center rounded-2xl bg-white px-4 py-3 text-sm font-semibold text-brand-dark transition hover:-translate-y-0.5 hover:bg-brand-highlight">
                            {{ __('home.order_now') }}
                        </a>
                    @endif
                </div>

                <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($featuredProducts as $product)
                        <article class="group overflow-hidden rounded-[1.75rem] border border-white/10 bg-white/10 shadow-[0_20px_44px_rgba(0,0,0,0.22)] backdrop-blur transition hover:-translate-y-1 hover:bg-white/12">
                            <div class="relative h-52 overflow-hidden bg-white/5">
                                <img
                                    src="{{ $product->seo_image_url ?: 'https://images.unsplash.com/photo-1515003197210-e0cd71810b5f?auto=format&fit=crop&w=900&q=80' }}"
                                    alt="{{ $product->name }}"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                >
                                <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent"></div>
                                @if($product->category)
                                    <div class="absolute left-4 top-4 inline-flex rounded-full border border-white/20 bg-black/45 px-3 py-1 text-xs font-semibold text-white backdrop-blur">
                                        {{ $product->category->name }}
                                    </div>
                                @endif
                            </div>
                            <div class="p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h3 class="text-xl font-bold text-white">{{ $product->name }}</h3>
                                        <p class="mt-2 text-sm leading-6 text-white/70">
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

    <section id="contact-store" class="scroll-mt-24 bg-[linear-gradient(180deg,rgba(255,255,255,1),rgba(251,241,233,0.82))] py-16">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="rounded-[2.25rem] border border-brand-soft/70 bg-white/90 p-8 shadow-[0_20px_50px_rgba(90,30,14,0.1)] backdrop-blur sm:p-10">
                <div class="grid gap-8 lg:grid-cols-[0.9fr_1.1fr] lg:items-start">
                    <div>
                        <span class="inline-flex rounded-full border border-brand-soft/80 bg-brand-soft/25 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary/70">
                            {{ __('stores.contact_store') }}
                        </span>
                        <h2 class="mt-4 text-3xl font-bold tracking-tight text-brand-dark sm:text-4xl">{{ $store->name }}</h2>
                        <p class="mt-4 text-base leading-8 text-brand-primary/75 sm:text-lg">
                            {{ __('stores.contact_intro') }}
                        </p>

                        @if($takeoutUrl || $mapsUrl || $phoneHref)
                            <div class="mt-8 flex flex-wrap gap-3">
                                @if($takeoutUrl)
                                    <a href="{{ $takeoutUrl }}" class="inline-flex items-center justify-center rounded-2xl bg-brand-primary px-5 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:bg-brand-accent hover:text-brand-dark">
                                        {{ __('home.order_now') }}
                                    </a>
                                @endif
                                @if($mapsUrl)
                                    <a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-2xl border border-brand-soft/70 bg-white px-5 py-3 text-sm font-semibold text-brand-primary transition hover:bg-brand-soft/25">
                                        {{ __('stores.open_map') }}
                                    </a>
                                @endif
                                @if($phoneHref)
                                    <a href="{{ $phoneHref }}" class="inline-flex items-center justify-center rounded-2xl border border-brand-soft/70 bg-white px-5 py-3 text-sm font-semibold text-brand-primary transition hover:bg-brand-soft/25">
                                        {{ __('stores.call_store') }}
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-[1.6rem] border border-brand-soft/60 bg-[linear-gradient(180deg,rgba(255,255,255,1),rgba(252,247,243,0.96))] p-5 shadow-sm">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-primary/60">{{ __('stores.store_status') }}</div>
                            <div class="mt-3 text-xl font-bold text-brand-dark">{{ $statusLabel }}</div>
                        </div>
                        <div class="rounded-[1.6rem] border border-brand-soft/60 bg-[linear-gradient(180deg,rgba(255,255,255,1),rgba(252,247,243,0.96))] p-5 shadow-sm">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-primary/60">{{ __('customer.business_hours') }}</div>
                            <div class="mt-3 text-xl font-bold text-brand-dark">{{ $store->businessHoursLabel() }}</div>
                        </div>
                        <div class="rounded-[1.6rem] border border-brand-soft/60 bg-[linear-gradient(180deg,rgba(255,255,255,1),rgba(252,247,243,0.96))] p-5 shadow-sm sm:col-span-2">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-primary/60">{{ __('home.address') }}</div>
                            <div class="mt-3 text-base leading-7 text-brand-dark">{{ $store->address ?: '-' }}</div>
                        </div>
                        <div class="rounded-[1.6rem] border border-brand-soft/60 bg-[linear-gradient(180deg,rgba(255,255,255,1),rgba(252,247,243,0.96))] p-5 shadow-sm sm:col-span-2">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-primary/60">{{ __('home.phone') }}</div>
                            <div class="mt-3 text-base leading-7 text-brand-dark">{{ $store->phone ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if($takeoutUrl || $mapsUrl || $phoneHref)
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
                @if($phoneHref)
                    <a href="{{ $phoneHref }}" class="inline-flex flex-1 items-center justify-center rounded-xl border border-brand-soft/70 bg-white px-3 py-3 text-sm font-semibold text-brand-primary">
                        {{ __('home.phone') }}
                    </a>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
