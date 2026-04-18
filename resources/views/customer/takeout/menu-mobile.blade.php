@extends('layouts.app')

@php
    $takeoutMetaTitle = __('customer.takeout') . ' - ' . $store->name . ' | ' . config('app.name', 'DineFlow');
    $takeoutMetaDescription = $store->description ?: __('customer.welcome_takeout_desc');
    $takeoutCanonical = route('customer.takeout.menu', ['store' => $store]);
    $takeoutMetaImage = $store->banner_image
        ? asset('storage/' . $store->banner_image)
        : asset('images/logo-256.png');
@endphp

@section('title', $takeoutMetaTitle)
@section('meta_description', \Illuminate\Support\Str::limit($takeoutMetaDescription, 160))
@section('canonical', $takeoutCanonical)
@section('meta_image', $takeoutMetaImage)

@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Restaurant',
    'name' => $store->name,
    'description' => \Illuminate\Support\Str::limit($takeoutMetaDescription, 160),
    'url' => $takeoutCanonical,
    'image' => $takeoutMetaImage,
    'telephone' => $store->phone,
    'address' => $store->address,
    'hasMenu' => $takeoutCanonical,
    'inLanguage' => str_replace('_', '-', app()->getLocale()),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
@php
    $currencyCode = strtolower((string) ($store->currency ?? 'twd'));
    $currencySymbol = match ($currencyCode) {
        'vnd' => 'VND',
        'cny' => 'CNY',
        'usd' => 'USD',
        default => 'NT$',
    };
    $storeDescription = $store->description ?: __('customer.welcome_takeout_desc');
    $totalProducts = $categories->sum(fn ($category) => $category->products->count());
    $storePhone = trim((string) ($store->phone ?? ''));
    $storePhoneHref = preg_replace('/\D+/', '', $storePhone);
@endphp
<style>
    .store-overview-card {
        position: relative;
        overflow: hidden;
        border-radius: 1.5rem;
        border: 1px solid rgba(255, 255, 255, 0.14);
        background: linear-gradient(155deg, rgba(255, 255, 255, 0.18), rgba(255, 255, 255, 0.08));
        box-shadow: 0 22px 54px rgba(40, 15, 8, 0.18);
        backdrop-filter: blur(16px);
    }

    .store-overview-card::after {
        content: '';
        position: absolute;
        inset: auto -10% -35% auto;
        width: 9rem;
        height: 9rem;
        border-radius: 9999px;
        background: radial-gradient(circle, rgba(246, 174, 45, 0.22), rgba(246, 174, 45, 0));
        pointer-events: none;
    }

    .menu-section-panel {
        position: relative;
        overflow: hidden;
    }

    .menu-section-panel > * {
        position: relative;
        z-index: 1;
    }

    .menu-section-panel::before {
        content: '';
        position: absolute;
        inset: 0;
        pointer-events: none;
    }

    .menu-section-panel--soft::before {
        background:
            radial-gradient(circle at top right, rgba(246, 174, 45, 0.16), transparent 34%),
            linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(250, 245, 236, 0.92));
    }

    .menu-section-panel--accent::before {
        background:
            radial-gradient(circle at top right, rgba(236, 144, 87, 0.18), transparent 34%),
            linear-gradient(180deg, rgba(255, 249, 245, 0.98), rgba(255, 255, 255, 0.94));
    }

    .cart-fly-clone {
        position: fixed;
        z-index: 80;
        pointer-events: none;
        border-radius: 9999px;
        background: linear-gradient(135deg, #ec9057, #F6AE2D);
        box-shadow: 0 16px 34px rgba(90, 30, 14, 0.24);
        transition:
            transform 620ms cubic-bezier(0.22, 1, 0.36, 1),
            opacity 620ms ease,
            width 620ms cubic-bezier(0.22, 1, 0.36, 1),
            height 620ms cubic-bezier(0.22, 1, 0.36, 1),
            left 620ms cubic-bezier(0.22, 1, 0.36, 1),
            top 620ms cubic-bezier(0.22, 1, 0.36, 1);
    }

    .cart-preview-window {
        transition:
            left 220ms ease,
            top 220ms ease,
            transform 220ms ease,
            box-shadow 220ms ease,
            opacity 260ms ease;
        transform: translate3d(0, 18px, 0) scale(0.96);
        opacity: 0;
        will-change: left, top, transform;
    }

    .cart-preview-window.is-ready {
        transform: translate3d(0, 0, 0) scale(1);
        opacity: 1;
    }

    .cart-preview-window.is-dragging {
        transition: none;
        transform: scale(1.02);
        box-shadow: 0 28px 64px rgba(90, 30, 14, 0.24);
        cursor: grabbing;
        user-select: none;
    }

    .cart-preview-window.is-pulse {
        animation: cartPreviewPulse 720ms cubic-bezier(0.22, 1, 0.36, 1);
    }

    .cart-preview-handle {
        cursor: grab;
        touch-action: none;
    }

    .cart-preview-item-shell {
        position: relative;
        overflow: hidden;
        border-radius: 1rem;
        --swipe-progress: 0;
        --shell-height: auto;
        max-height: var(--shell-height);
        transition:
            max-height 220ms ease,
            opacity 220ms ease,
            transform 220ms ease;
    }

    .cart-preview-item-shell.is-collapsing {
        max-height: 0;
        opacity: 0;
        transform: translateY(-6px);
    }

    .cart-preview-item-delete {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        padding-right: 0.75rem;
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.58), rgba(220, 38, 38, 0.68));
        border-left: 1px solid rgba(220, 38, 38, 0.3);
        color: white;
        opacity: clamp(0, var(--swipe-progress), 1);
        transition: opacity 140ms ease;
    }

    .cart-preview-item-delete span {
        opacity: clamp(0, var(--swipe-progress), 1);
        transform: translateX(calc((1 - clamp(0, var(--swipe-progress), 1)) * 10px));
        transition:
            opacity 140ms ease,
            transform 140ms ease;
    }

    .cart-preview-item {
        position: relative;
        z-index: 1;
        touch-action: pan-y;
        width: 100%;
        transition:
            transform 180ms ease,
            opacity 180ms ease,
            box-shadow 180ms ease;
        will-change: transform;
    }

    .cart-preview-item.is-swiping {
        transition: none;
        box-shadow: 0 20px 38px rgba(90, 30, 14, 0.12);
    }

    .cart-preview-item.is-removing {
        opacity: 0;
        transform: translateX(-110%);
    }

    @keyframes cartPreviewPulse {
        0% {
            transform: scale(1);
            box-shadow: 0 18px 40px rgba(90, 30, 14, 0.1);
        }
        35% {
            transform: scale(1.04);
            box-shadow: 0 24px 56px rgba(236, 144, 87, 0.28);
        }
        100% {
            transform: scale(1);
            box-shadow: 0 18px 40px rgba(90, 30, 14, 0.1);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .cart-fly-clone,
        .cart-preview-window {
            transition: none;
        }

        .cart-preview-window.is-pulse {
            animation: none;
        }

        .cart-preview-item {
            transition: none;
        }
    }
</style>

<div class="min-h-screen bg-brand-soft/20">
    <div class="mx-auto max-w-7xl px-4 py-8 pb-32 sm:px-6 lg:px-8">
        <div class="mb-8 overflow-hidden rounded-[2rem] border border-brand-soft/60 bg-white shadow-[0_24px_60px_rgba(90,30,14,0.12)]">
            <div class="relative isolate overflow-hidden bg-brand-dark px-6 py-8 text-white sm:px-8">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(255,214,112,0.28),_transparent_34%),linear-gradient(135deg,_rgba(90,30,14,0.96),_rgba(236,144,87,0.88))]"></div>
                <div class="absolute -right-12 -top-10 h-36 w-36 rounded-full bg-brand-highlight/20 blur-3xl"></div>
                <div class="absolute -bottom-14 left-10 h-32 w-32 rounded-full bg-brand-accent/20 blur-3xl"></div>
                <div class="relative grid gap-6 xl:grid-cols-[minmax(0,1.25fr),minmax(18rem,22rem)] xl:items-start">
                    <div class="max-w-3xl">
                        <a href="{{ route('home') }}" class="inline-flex items-center text-sm font-medium text-white/70 transition hover:text-white">{{ __('customer.back_home_btn') }}</a>
                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold tracking-[0.2em] text-brand-highlight">{{ __('customer.takeout') }}</span>
                            <span class="inline-flex rounded-full border border-brand-soft/30 bg-white/10 px-3 py-1 text-xs font-semibold text-white/80">{{ __('customer.business_hours') }} {{ $store->businessHoursLabel() }}</span>
                        </div>
                        <h1 class="mt-5 text-3xl font-bold tracking-tight sm:text-4xl">{{ $store->name }}</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-white/75 sm:text-base">{{ $storeDescription }}</p>

                        <div class="mt-6 grid gap-3 sm:grid-cols-2">
                            <div class="store-overview-card px-4 py-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-brand-highlight/90">{{ __('customer.business_hours') }}</p>
                                <p class="mt-2 text-base font-semibold text-white">{{ $store->businessHoursLabel() }}</p>
                                <p class="mt-1 text-xs text-white/65">{{ $orderingAvailable ? __('customer.open') : __('customer.ordering_closed') }}</p>
                            </div>
                            <div class="store-overview-card px-4 py-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-brand-highlight/90">{{ __('customer.menu_section') }}</p>
                                <p class="mt-2 text-2xl font-bold text-white">{{ number_format($categories->count()) }}</p>
                                <p class="mt-1 text-xs text-white/65">{{ number_format($totalProducts) }} {{ __('customer.items_in_menu') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="store-overview-card px-5 py-5">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-brand-highlight/90">{{ __('customer.takeout') }}</p>
                            <p class="mt-2 text-lg font-semibold text-white">{{ $orderingAvailable ? __('customer.open') : __('customer.ordering_closed') }}</p>
                            <p class="mt-2 text-sm leading-6 text-white/70">{{ \Illuminate\Support\Str::limit($storeDescription, 96) }}</p>

                            @if($storePhone !== '')
                                <div class="mt-4 rounded-2xl border border-white/12 bg-black/10 px-4 py-3">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-white/60">{{ __('customer.phone') }}</p>
                                    <a href="{{ $storePhoneHref !== '' ? 'tel:' . $storePhoneHref : '#' }}" class="mt-1 inline-flex text-base font-semibold text-white transition hover:text-brand-highlight">{{ $storePhone }}</a>
                                </div>
                            @endif
                        </div>

                        <div class="hidden md:flex">
                            <div class="flex flex-wrap gap-3">
                            @if(isset($orderHistory) && $orderHistory->isNotEmpty())
                                    <div class="flex flex-wrap items-center gap-2">
                                    @foreach($orderHistory->take(3) as $historyOrder)
                                        <a href="{{ route('customer.order.success', ['store' => $store, 'order' => $historyOrder]) }}" class="inline-flex items-center justify-center rounded-2xl border border-white/25 bg-white/10 px-4 py-3 text-xs font-semibold text-white transition hover:-translate-y-0.5 hover:bg-white/20">{{ __('customer.status_prefix') }} {{ $historyOrder->order_no }}</a>
                                    @endforeach
                                </div>
                            @endif
                                <a href="{{ route('customer.takeout.cart.show', ['store' => $store]) }}" class="inline-flex items-center justify-center rounded-2xl bg-brand-highlight px-5 py-3 text-sm font-semibold text-brand-dark shadow-lg shadow-brand-highlight/30 transition hover:-translate-y-0.5 hover:bg-brand-soft">{{ __('customer.view_cart') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(! $orderingAvailable)
            <div class="mb-6 rounded-2xl border border-brand-soft bg-brand-soft/35 px-4 py-3 text-sm text-brand-dark shadow-sm">{{ $store->orderingClosedMessage() }}</div>
        @endif

        @if(session('success'))
            <div class="mb-6 rounded-2xl border border-brand-accent/30 bg-brand-accent/10 px-4 py-3 text-sm text-brand-primary shadow-sm">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 shadow-sm">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="mb-6 rounded-2xl border border-brand-soft bg-brand-soft/30 px-4 py-4 text-sm text-brand-dark shadow-sm">
                <div class="mb-2 font-semibold">{{ __('customer.confirm_fields') }}</div>
                <ul class="space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($categories->isEmpty())
            <div class="rounded-[2rem] border border-brand-soft/60 bg-white px-6 py-16 text-center shadow-[0_20px_40px_rgba(90,30,14,0.08)]">
                <h2 class="text-2xl font-bold text-brand-dark">{{ __('customer.no_products_available') }}</h2>
                <p class="mt-3 text-brand-primary/80">{{ __('customer.no_products_try_later') }}</p>
            </div>
        @else
            <div class="mb-8 hidden overflow-x-auto rounded-[1.75rem] border border-brand-soft/60 bg-gradient-to-r from-white via-brand-soft/15 to-brand-highlight/10 px-4 py-4 shadow-[0_12px_32px_rgba(90,30,14,0.08)] md:block">
                <div class="flex min-w-max items-center gap-4">
                    <div class="min-w-[9.5rem] pr-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-accent">{{ __('customer.menu_section') }}</p>
                        <p class="mt-1 text-sm font-semibold text-brand-dark">{{ number_format($categories->count()) }} {{ __('customer.menu_section') }}</p>
                    </div>
                    @foreach($categories as $category)
                        <a href="#category-{{ $category->id }}" class="inline-flex rounded-full border border-brand-soft/70 bg-brand-soft/20 px-4 py-2 text-sm font-medium text-brand-primary transition hover:-translate-y-0.5 hover:border-brand-accent hover:bg-brand-highlight/60">{{ $category->name }}</a>
                    @endforeach
                </div>
            </div>

            <div class="relative grid grid-cols-[5.5rem,minmax(0,1fr)] items-start gap-4 md:block">
                <aside class="self-stretch md:hidden">
                    <div class="sticky top-5">
                        <div class="h-[calc(100vh-2.5rem)] overflow-hidden rounded-[1.75rem] border border-brand-soft/60 bg-gradient-to-b from-white to-brand-soft/20 shadow-[0_18px_40px_rgba(90,30,14,0.08)]">
                            <div class="h-full overflow-y-auto p-2">
                                <div class="flex flex-col gap-2">
                                    @foreach($categories as $category)
                                        <a href="#category-{{ $category->id }}" class="rounded-2xl border border-brand-soft/70 bg-brand-soft/15 px-2 py-3 text-center text-xs font-semibold leading-4 text-brand-primary transition hover:border-brand-accent hover:bg-brand-highlight/50">
                                            {{ $category->name }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>

                <div class="min-w-0 space-y-10">
                    @foreach($categories as $category)
                        <section id="category-{{ $category->id }}" class="menu-section-panel {{ $loop->odd ? 'menu-section-panel--soft border-brand-soft/60' : 'menu-section-panel--accent border-brand-highlight/40' }} scroll-mt-24 rounded-[2rem] border px-5 py-6 shadow-[0_20px_52px_rgba(90,30,14,0.08)] md:scroll-mt-24 md:px-6">
                            <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand-accent via-brand-highlight to-transparent"></div>
                            <div class="mb-6 flex flex-col gap-4 border-b border-brand-soft/60 pb-5 sm:flex-row sm:items-end sm:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-3">
                                        <span class="inline-flex h-9 min-w-9 items-center justify-center rounded-full border border-brand-soft/70 bg-white/85 px-3 text-xs font-semibold tracking-[0.18em] text-brand-primary shadow-sm">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-accent">{{ __('customer.menu_section') }}</p>
                                    </div>
                                    <h2 class="mt-3 text-2xl font-bold tracking-tight text-brand-dark">{{ $category->name }}</h2>
                                    <p class="mt-1 text-sm text-brand-primary/70">{{ $category->products->count() }} {{ __('customer.items_in_menu') }}</p>
                                </div>
                                <div class="inline-flex items-center rounded-2xl border border-brand-soft/70 bg-white/85 px-4 py-3 text-sm font-semibold text-brand-dark shadow-sm">
                                    {{ $category->products->count() }} {{ __('customer.items_in_menu') }}
                                </div>
                            </div>

                            @if($category->products->count())
                                <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                                    @foreach($category->products as $product)
                                        @php
                                            $productImage = filled($product->image)
                                                ? (\Illuminate\Support\Str::startsWith($product->image, ['http://', 'https://']) ? $product->image : asset('storage/' . ltrim($product->image, '/')))
                                                : 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=900&q=80';
                                        @endphp
                                        <div class="group overflow-hidden rounded-[1.75rem] border border-brand-soft/60 bg-white shadow-[0_18px_44px_rgba(90,30,14,0.1)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_24px_60px_rgba(90,30,14,0.16)]">
                                            <div class="relative overflow-hidden rounded-t-[1.75rem]">
                                                <img src="{{ $productImage }}" alt="{{ $product->name }}" class="h-48 w-full rounded-t-[1.75rem] object-cover transition duration-500 group-hover:scale-105">
                                                <div class="absolute inset-0 bg-gradient-to-t from-brand-dark/85 via-brand-dark/20 to-transparent"></div>
                                                <div class="absolute left-4 top-4 inline-flex rounded-full border border-black/80 bg-black/50 px-3 py-1 text-xs font-semibold text-white backdrop-blur">{{ $category->name }}</div>
                                                <div class="absolute bottom-4 right-4 rounded-full bg-brand-highlight px-3 py-1.5 text-sm font-bold text-brand-dark shadow-lg">{{ $currencySymbol }} {{ number_format($product->price) }}</div>
                                            </div>

                                            <div class="p-5">
                                                <div class="flex items-start justify-between gap-4">
                                                    <div class="min-w-0">
                                                        <h3 class="text-lg font-bold text-brand-dark">{{ $product->name }}</h3>
                                                        <p class="mt-2 line-clamp-2 text-sm leading-6 text-brand-primary/75">{{ $product->description ?: __('customer.fresh_made') }}</p>
                                                    </div>
                                                    @if($product->is_sold_out)
                                                        <div class="shrink-0 rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-600">{{ __('customer.sold_out') }}</div>
                                                    @endif
                                                </div>

                                                <div class="mt-5">
                                                    @if(! $orderingAvailable)
                                                        <div class="rounded-2xl bg-brand-soft/25 px-3 py-3 text-center text-sm font-medium text-brand-dark">{{ __('customer.ordering_closed') }}</div>
                                                    @elseif($product->is_sold_out)
                                                        <div class="rounded-2xl bg-slate-100 px-3 py-3 text-center text-sm font-medium text-slate-500">{{ __('customer.item_not_available') }}</div>
                                                    @else
                                                        <form method="POST" action="{{ route('customer.takeout.cart.items.store', ['store' => $store]) }}" class="flex items-center gap-2" data-add-to-cart-form>
                                                            @csrf
                                                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                                                            <input type="hidden" name="option_payload" value="" data-option-payload>
                                                            <input type="hidden" name="item_note" value="" data-item-note>
                                                            <div class="inline-flex h-12 items-center rounded-2xl border border-brand-soft bg-brand-soft/20 p-1 shadow-sm">
                                                                <button type="button" class="flex h-10 w-10 items-center justify-center rounded-xl text-lg font-bold text-brand-primary transition hover:bg-white" data-qty-decrement>-</button>
                                                                <input type="hidden" name="qty" value="1" data-qty-input>
                                                                <span class="flex min-w-[2.8rem] items-center justify-center text-sm font-semibold text-brand-dark" data-qty-display>1</span>
                                                                <button type="button" class="flex h-10 w-10 items-center justify-center rounded-xl text-lg font-bold text-brand-primary transition hover:bg-white" data-qty-increment>+</button>
                                                            </div>
                                                            <button type="submit" class="flex-1 rounded-2xl bg-brand-primary px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-primary/20 transition hover:-translate-y-0.5 hover:bg-brand-accent hover:text-brand-dark" data-add-to-cart-button data-option-groups='@json($product->option_groups ?? [])' data-allow-item-note="{{ $product->allow_item_note ? '1' : '0' }}" data-product-name="{{ $product->name }}">{{ __('customer.add_to_cart') }}</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="rounded-[1.75rem] border border-brand-soft/60 bg-white px-5 py-8 text-sm text-brand-primary/70 shadow-[0_18px_40px_rgba(90,30,14,0.08)]">{{ __('customer.no_products_in_cat2') }}</div>
                            @endif
                        </section>
                    @endforeach
                </div>

            </div>
        @endif
    </div>
</div>

@if($categories->isNotEmpty())
<aside
    class="cart-preview-window fixed right-6 top-24 z-30 hidden w-80 xl:block"
    data-cart-preview-window
    data-preview-key="takeout-cart-preview:{{ $store->id }}"
>
    <div class="overflow-hidden rounded-[1.5rem] border border-brand-soft/70 bg-white shadow-[0_18px_40px_rgba(90,30,14,0.1)]">
        <div class="border-b border-brand-soft/60 bg-brand-dark px-4 py-4 text-white">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-highlight/80">{{ __('customer.cart') }}</p>
                    <p class="mt-1 text-sm font-semibold" data-cart-summary-text>{{ $cartCount > 0 ? __('customer.cart_bar_total', ['count' => $cartCount, 'currency' => $currencySymbol, 'total' => number_format($cartTotal)]) : __('customer.cart_bar_empty') }}</p>
                </div>
                <button
                    type="button"
                    class="cart-preview-handle inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-white/15 bg-white/10 text-white/80 transition hover:bg-white/20 hover:text-white"
                    data-cart-preview-handle
                    aria-label="Move cart preview"
                >
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                        <path d="M8 6h8"></path>
                        <path d="M8 12h8"></path>
                        <path d="M8 18h8"></path>
                    </svg>
                </button>
            </div>
        </div>

        <div class="max-h-[52vh] space-y-3 overflow-y-auto px-4 py-4" data-cart-preview-list>
            @forelse($cartPreviewItems->take(6) as $item)
                <div class="cart-preview-item-shell" data-cart-preview-shell data-line-key="{{ $item['line_key'] }}">
                    <div class="cart-preview-item-delete" data-cart-preview-delete>
                        <span class="rounded-full border border-white/40 bg-white/22 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-rose-50 shadow-sm">
                            {{ __('customer.remove_item') }}
                        </span>
                    </div>
                    <article class="cart-preview-item rounded-2xl border border-brand-soft/70 bg-brand-soft/15 px-3 py-3" data-cart-preview-item>
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-brand-dark">{{ $item['product_name'] ?? __('customer.product_default_name') }}</p>
                                @if(!empty($item['option_label']))
                                    <p class="mt-1 text-xs text-brand-primary/75">{{ $item['option_label'] }}</p>
                                @endif
                                @if(!empty($item['item_note']))
                                    <p class="mt-1 text-xs text-amber-700">{{ __('customer.item_note_prefix') }} {{ $item['item_note'] }}</p>
                                @endif
                            </div>
                            <p class="shrink-0 text-xs font-semibold text-brand-accent">{{ $currencySymbol }} {{ number_format((int) ($item['subtotal'] ?? 0)) }}</p>
                        </div>

                        <div class="mt-3 flex items-center justify-between gap-3">
                            <div class="inline-flex shrink-0 items-center gap-2 rounded-xl border border-brand-soft/70 bg-white/80 px-2 py-1 shadow-sm">
                                <form method="POST" action="{{ route('customer.takeout.cart.items.update', ['store' => $store, 'lineKey' => $item['line_key']]) }}" data-cart-preview-update-form>
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="action" value="decrease">
                                    <button type="submit" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-brand-soft bg-white text-sm font-bold text-brand-primary transition hover:bg-brand-soft/30" aria-label="{{ __('customer.decrease_qty') }}">-</button>
                                </form>
                                <span class="min-w-[1.8rem] text-center text-sm font-semibold text-brand-dark">{{ $item['qty'] ?? 1 }}</span>
                                <form method="POST" action="{{ route('customer.takeout.cart.items.update', ['store' => $store, 'lineKey' => $item['line_key']]) }}" data-cart-preview-update-form>
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="action" value="increase">
                                    <button type="submit" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-brand-soft bg-white text-sm font-bold text-brand-primary transition hover:bg-brand-soft/30" aria-label="{{ __('customer.increase_qty') }}">+</button>
                                </form>
                            </div>

                            <form method="POST" action="{{ route('customer.takeout.cart.items.destroy', ['store' => $store, 'lineKey' => $item['line_key']]) }}" data-cart-preview-remove-form>
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex shrink-0 items-center justify-center whitespace-nowrap rounded-lg border border-rose-200 bg-white px-2.5 py-1 text-xs font-semibold text-rose-600 transition hover:bg-rose-50">{{ __('customer.remove_item') }}</button>
                            </form>
                        </div>
                    </article>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-brand-soft/80 bg-brand-soft/10 px-3 py-8 text-center text-sm text-brand-primary/75">
                    {{ __('customer.no_products_available') }}
                </div>
            @endforelse

            @if($cartPreviewItems->count() > 6)
                <p class="text-center text-xs font-semibold text-brand-primary/70" data-cart-preview-more>{{ __('customer.more_items_in_cart', ['count' => $cartPreviewItems->count() - 6]) }}</p>
            @endif
        </div>

        <div class="border-t border-brand-soft/60 p-4">
            <a href="{{ route('customer.takeout.cart.show', ['store' => $store]) }}" class="inline-flex h-11 w-full items-center justify-center rounded-2xl bg-brand-highlight px-4 text-sm font-semibold text-brand-dark transition hover:bg-brand-soft" data-cart-preview-target>{{ __('customer.view_cart') }}{{ $cartCount > 0 ? ' (' . $cartCount . ')' : '' }}</a>
        </div>
    </div>
</aside>
@endif

<div class="fixed inset-x-0 bottom-0 z-40 border-t border-brand-soft/60 bg-white/95 px-4 py-4 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 rounded-[1.75rem] bg-brand-dark px-4 py-3 text-white shadow-[0_18px_44px_rgba(90,30,14,0.24)] transition-transform duration-200" data-cart-bar>
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-brand-highlight/80">{{ $orderingAvailable ? __('customer.open') : __('customer.ordering_closed') }}</p>
            <p class="mt-1 text-sm font-semibold" data-cart-summary-text>{{ $cartCount > 0 ? __('customer.cart_bar_total', ['count' => $cartCount, 'currency' => $currencySymbol, 'total' => number_format($cartTotal)]) : __('customer.cart_bar_empty') }}</p>
            @if(isset($orderHistory) && $orderHistory->isNotEmpty())
                <div class="mt-1 flex flex-wrap gap-2">
                    @foreach($orderHistory->take(2) as $historyOrder)
                        <a href="{{ route('customer.order.success', ['store' => $store, 'order' => $historyOrder]) }}" class="inline-flex text-xs font-semibold text-brand-highlight underline-offset-2 hover:underline">{{ __('customer.status_prefix') }} {{ $historyOrder->order_no }}</a>
                    @endforeach
                </div>
            @endif
        </div>
        <a href="{{ route('customer.takeout.cart.show', ['store' => $store]) }}" class="inline-flex h-11 items-center justify-center rounded-2xl bg-brand-highlight px-4 text-sm font-semibold text-brand-dark transition hover:bg-brand-soft" data-cart-target>{{ __('customer.view_cart') }}{{ $cartCount > 0 ? ' (' . $cartCount . ')' : '' }}</a>
    </div>
</div>

<div id="option-modal" class="fixed inset-0 z-[90] hidden items-center justify-center bg-black/45 p-4">
    <div class="w-full max-w-lg rounded-3xl bg-white p-5 shadow-2xl">
        <div class="flex items-center justify-between">
            <h3 id="option-modal-title" class="text-lg font-bold text-brand-dark">{{ __('customer.select_options_title') }}</h3>
            <button type="button" id="option-modal-close" class="rounded-full p-2 text-slate-500 hover:bg-slate-100">✕</button>
        </div>
        <div id="option-modal-body" class="mt-4 max-h-[60vh] space-y-4 overflow-y-auto"></div>
        <div class="mt-5 flex gap-3">
            <button type="button" id="option-modal-cancel" class="inline-flex flex-1 items-center justify-center rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">{{ __('customer.cancel') }}</button>
            <button type="button" id="option-modal-confirm" class="inline-flex flex-1 items-center justify-center rounded-2xl bg-brand-primary px-4 py-3 text-sm font-semibold text-white hover:bg-brand-accent hover:text-brand-dark">{{ __('customer.confirm_add') }}</button>
        </div>
    </div>
</div>

<script>
(() => {
    const forms = document.querySelectorAll('[data-add-to-cart-form]');
    const cartTarget = document.querySelector('[data-cart-target]');
    const cartPreviewTarget = document.querySelector('[data-cart-preview-target]');
    const cartBar = document.querySelector('[data-cart-bar]');
    const cartPreviewWindow = document.querySelector('[data-cart-preview-window]');
    const cartPreviewHandle = document.querySelector('[data-cart-preview-handle]');
    const cartSummaryTexts = document.querySelectorAll('[data-cart-summary-text]');
    const cartPreviewList = document.querySelector('[data-cart-preview-list]');
    const modal = document.getElementById('option-modal');
    const modalTitle = document.getElementById('option-modal-title');
    const modalBody = document.getElementById('option-modal-body');
    const modalClose = document.getElementById('option-modal-close');
    const modalCancel = document.getElementById('option-modal-cancel');
    const modalConfirm = document.getElementById('option-modal-confirm');
    const i18n = {
        optionsTitleWithProduct: @json(__('customer.select_options_title_with_product', ['product' => '__product__'])),
        requiredSuffix: @json(__('customer.required_suffix')),
        free: @json(__('customer.free')),
        currencySymbol: @json($currencySymbol),
        requiredError: @json(__('customer.option_required_error', ['group' => '__group__'])),
        maxSelectError: @json(__('customer.option_max_select_error', ['group' => '__group__', 'max' => '__max__'])),
        unnamedProduct: @json(__('customer.product_default_name')),
        itemNoteLabel: @json(__('customer.item_note_label')),
        itemNotePlaceholder: @json(__('customer.item_note_placeholder')),
    };
    const cartPreviewEmptyClasses = 'rounded-2xl border border-dashed border-brand-soft/80 bg-brand-soft/10 px-3 py-8 text-center text-sm text-brand-primary/75';

    let activeForm = null;
    let activeGroups = [];
    let cartPreviewPulseTimer = null;
    let activeSwipeItem = null;

    const getCartFlyTarget = () => {
        if (cartPreviewTarget && cartPreviewWindow && cartPreviewWindow.offsetParent !== null) {
            return cartPreviewTarget;
        }

        return cartTarget;
    };

    const pulseCartPreview = () => {
        if (!cartPreviewWindow) {
            return;
        }

        cartPreviewWindow.classList.remove('is-pulse');
        window.clearTimeout(cartPreviewPulseTimer);

        window.requestAnimationFrame(() => {
            cartPreviewWindow.classList.add('is-pulse');
            cartPreviewPulseTimer = window.setTimeout(() => {
                cartPreviewWindow.classList.remove('is-pulse');
            }, 720);
        });
    };

    const sendCartRequest = async (form) => {
        const formData = new FormData(form);
        const csrf = form.querySelector('input[name="_token"]')?.value || '';
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
            },
        });

        if (!response.ok) {
            throw new Error(`Cart request failed: ${response.status}`);
        }

        return response.json();
    };

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');

    const syncCartSummary = (cart) => {
        if (!cart) {
            return;
        }

        cartSummaryTexts.forEach((element) => {
            element.textContent = cart.bar_text || '';
        });

        if (cartTarget) {
            cartTarget.textContent = cart.view_cart_label || '';
            cartTarget.href = cart.cart_url || cartTarget.href;
        }

        if (cartPreviewTarget) {
            cartPreviewTarget.textContent = cart.view_cart_label || '';
            cartPreviewTarget.href = cart.cart_url || cartPreviewTarget.href;
        }
    };

    const renderPreviewItem = (item) => `
        <div class="cart-preview-item-shell" data-cart-preview-shell data-line-key="${item.line_key}">
            <div class="cart-preview-item-delete" data-cart-preview-delete>
                <span class="rounded-full border border-white/40 bg-white/22 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-rose-50 shadow-sm">
                    {{ __('customer.remove_item') }}
                </span>
            </div>
            <article class="cart-preview-item rounded-2xl border border-brand-soft/70 bg-brand-soft/15 px-3 py-3" data-cart-preview-item>
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-brand-dark">${escapeHtml(item.product_name)}</p>
                        ${item.option_label ? `<p class="mt-1 text-xs text-brand-primary/75">${escapeHtml(item.option_label)}</p>` : ''}
                        ${item.item_note_display ? `<p class="mt-1 text-xs text-amber-700">${escapeHtml(item.item_note_display)}</p>` : ''}
                    </div>
                    <p class="shrink-0 text-xs font-semibold text-brand-accent">${escapeHtml(item.subtotal_display)}</p>
                </div>

                <div class="mt-3 flex items-center justify-between gap-3">
                    <div class="inline-flex shrink-0 items-center gap-2 rounded-xl border border-brand-soft/70 bg-white/80 px-2 py-1 shadow-sm">
                        <form method="POST" action="${item.update_urls.decrease}" data-cart-preview-update-form>
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="hidden" name="_method" value="PATCH">
                            <input type="hidden" name="action" value="decrease">
                            <button type="submit" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-brand-soft bg-white text-sm font-bold text-brand-primary transition hover:bg-brand-soft/30" aria-label="{{ __('customer.decrease_qty') }}">-</button>
                        </form>
                        <span class="min-w-[1.8rem] text-center text-sm font-semibold text-brand-dark">${item.qty}</span>
                        <form method="POST" action="${item.update_urls.increase}" data-cart-preview-update-form>
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="hidden" name="_method" value="PATCH">
                            <input type="hidden" name="action" value="increase">
                            <button type="submit" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-brand-soft bg-white text-sm font-bold text-brand-primary transition hover:bg-brand-soft/30" aria-label="{{ __('customer.increase_qty') }}">+</button>
                        </form>
                    </div>

                    <form method="POST" action="${item.remove_url}" data-cart-preview-remove-form>
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="inline-flex shrink-0 items-center justify-center whitespace-nowrap rounded-lg border border-rose-200 bg-white px-2.5 py-1 text-xs font-semibold text-rose-600 transition hover:bg-rose-50">{{ __('customer.remove_item') }}</button>
                    </form>
                </div>
            </article>
        </div>
    `;

    const renderCartPreview = (cart) => {
        if (!cart || !cartPreviewList) {
            return;
        }

        const previewItems = Array.isArray(cart.preview_items) ? cart.preview_items : [];
        const previewHtml = previewItems.length > 0
            ? previewItems.map(renderPreviewItem).join('')
            : `<div class="${cartPreviewEmptyClasses}">${cart.empty_preview_text || ''}</div>`;
        const moreHtml = cart.remaining_preview_text
            ? `<p class="text-center text-xs font-semibold text-brand-primary/70" data-cart-preview-more>${cart.remaining_preview_text}</p>`
            : '';

        cartPreviewList.innerHTML = `${previewHtml}${moreHtml}`;
        initCartPreviewForms();
        initCartPreviewSwipe();
    };

    const applyCartState = (payload) => {
        const cart = payload?.cart;
        if (!cart) {
            return;
        }

        syncCartSummary(cart);
        renderCartPreview(cart);
        pulseCartPreview();
    };

    const initCartPreviewForms = () => {
        document.querySelectorAll('[data-cart-preview-update-form], [data-cart-preview-remove-form]').forEach((form) => {
            if (form.dataset.bound === '1') {
                return;
            }

            form.dataset.bound = '1';
            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                if (form.dataset.submitting === '1') {
                    return;
                }

                form.dataset.submitting = '1';

                try {
                    const payload = await sendCartRequest(form);
                    applyCartState(payload);
                } catch (_error) {
                    window.location.reload();
                } finally {
                    form.dataset.submitting = '0';
                }
            });
        });
    };

    const initCartPreviewSwipe = () => {
        const items = document.querySelectorAll('[data-cart-preview-item]');

        items.forEach((item) => {
            const removeForm = item.parentElement?.querySelector('[data-cart-preview-remove-form]');
            const shell = item.parentElement;
            if (!removeForm) {
                return;
            }

            const getSwipeMetrics = () => {
                const width = Math.max(1, Math.round(item.getBoundingClientRect().width));
                const maxSwipe = Math.max(140, width - 6);
                const removeThreshold = Math.max(110, Math.round(maxSwipe * 0.92));

                return {
                    maxSwipe,
                    removeThreshold,
                };
            };

            const swipeState = {
                pointerId: null,
                startX: 0,
                startY: 0,
                currentX: 0,
                offsetX: 0,
                axis: null,
            };

            const applyOffset = (offset) => {
                const metrics = getSwipeMetrics();
                swipeState.offsetX = Math.min(0, offset);
                swipeState.offsetX = Math.max(swipeState.offsetX, -metrics.maxSwipe);
                item.style.transform = `translateX(${swipeState.offsetX}px)`;
                const reveal = Math.min(1, Math.abs(swipeState.offsetX) / metrics.maxSwipe);
                shell?.style.setProperty('--swipe-progress', reveal.toFixed(3));
            };

            const resetItem = () => {
                item.classList.remove('is-swiping');
                applyOffset(0);
                swipeState.axis = null;
                if (activeSwipeItem === item) {
                    activeSwipeItem = null;
                }
            };

            const submitRemove = () => {
                item.classList.remove('is-swiping');
                shell?.style.setProperty('--swipe-progress', '1');
                item.classList.add('is-removing');

                if (shell) {
                    const shellHeight = Math.max(1, Math.round(shell.getBoundingClientRect().height));
                    shell.style.setProperty('--shell-height', `${shellHeight}px`);
                }

                const requestDelete = async () => {
                    const payload = await sendCartRequest(removeForm);
                    applyCartState(payload);
                };

                window.setTimeout(() => {
                    shell?.classList.add('is-collapsing');

                    window.setTimeout(() => {
                        shell?.remove();

                        requestDelete().catch(() => {
                            window.location.reload();
                        });
                    }, 220);
                }, 180);
            };

            item.addEventListener('pointerdown', (event) => {
                if (event.button !== 0) {
                    return;
                }

                if (event.target.closest('button, a, form, input, textarea, select, label')) {
                    return;
                }

                if (activeSwipeItem && activeSwipeItem !== item) {
                    activeSwipeItem.style.transform = 'translateX(0)';
                    activeSwipeItem.classList.remove('is-swiping');
                    activeSwipeItem.parentElement?.style.setProperty('--swipe-progress', '0');
                    activeSwipeItem = null;
                }

                swipeState.pointerId = event.pointerId;
                swipeState.startX = event.clientX;
                swipeState.startY = event.clientY;
                swipeState.currentX = event.clientX;
                swipeState.axis = null;
                item.classList.add('is-swiping');
                item.setPointerCapture(event.pointerId);
            });

            item.addEventListener('pointermove', (event) => {
                if (swipeState.pointerId !== event.pointerId) {
                    return;
                }

                swipeState.currentX = event.clientX;
                const deltaX = swipeState.currentX - swipeState.startX;
                const deltaY = event.clientY - swipeState.startY;
                const absX = Math.abs(deltaX);
                const absY = Math.abs(deltaY);

                if (swipeState.axis === null) {
                    if (absX < 8 && absY < 8) {
                        return;
                    }

                    swipeState.axis = absX > absY ? 'x' : 'y';
                }

                if (swipeState.axis === 'y') {
                    resetItem();
                    if (item.hasPointerCapture(event.pointerId)) {
                        item.releasePointerCapture(event.pointerId);
                    }
                    return;
                }

                if (deltaX > 0) {
                    applyOffset(0);
                    return;
                }

                activeSwipeItem = item;
                applyOffset(deltaX);
            });

            const finishSwipe = (event) => {
                if (swipeState.pointerId !== event.pointerId) {
                    return;
                }

                if (item.hasPointerCapture(event.pointerId)) {
                    item.releasePointerCapture(event.pointerId);
                }

                const traveled = Math.abs(swipeState.offsetX);
                const metrics = getSwipeMetrics();
                swipeState.pointerId = null;

                if (traveled >= metrics.removeThreshold) {
                    submitRemove();
                    return;
                }

                resetItem();
            };

            item.addEventListener('pointerup', finishSwipe);
            item.addEventListener('pointercancel', finishSwipe);
        });

        if (document.body.dataset.cartPreviewOutsideBound !== '1') {
            document.body.dataset.cartPreviewOutsideBound = '1';
            document.addEventListener('pointerdown', (event) => {
                if (!activeSwipeItem) {
                    return;
                }

                if (event.target.closest('[data-cart-preview-item]')) {
                    return;
                }

                activeSwipeItem.style.transform = 'translateX(0)';
                activeSwipeItem.classList.remove('is-swiping');
                activeSwipeItem.parentElement?.style.setProperty('--swipe-progress', '0');
                activeSwipeItem = null;
            });
        }
    };

    const initCartPreviewWindow = () => {
        if (!cartPreviewWindow || !cartPreviewHandle || window.innerWidth < 1280) {
            cartPreviewWindow?.classList.add('is-ready');
            return;
        }

        const storageKey = cartPreviewWindow.dataset.previewKey || 'takeout-cart-preview';
        const rect = cartPreviewWindow.getBoundingClientRect();
        const state = {
            width: rect.width,
            height: rect.height,
            left: Math.max(16, window.innerWidth - rect.width - 24),
            top: Math.max(16, rect.top),
            pointerId: null,
            offsetX: 0,
            offsetY: 0,
        };

        const clamp = (left, top) => {
            const maxLeft = Math.max(16, window.innerWidth - state.width - 16);
            const maxTop = Math.max(16, window.innerHeight - state.height - 16);

            return {
                left: Math.min(Math.max(16, left), maxLeft),
                top: Math.min(Math.max(16, top), maxTop),
            };
        };

        const applyPosition = (left, top) => {
            const next = clamp(left, top);
            state.left = next.left;
            state.top = next.top;
            cartPreviewWindow.style.left = `${next.left}px`;
            cartPreviewWindow.style.top = `${next.top}px`;
            cartPreviewWindow.style.right = 'auto';
        };

        const savePosition = () => {
            try {
                window.sessionStorage.setItem(storageKey, JSON.stringify({
                    left: state.left,
                    top: state.top,
                }));
            } catch (_error) {
                // Ignore storage failures and keep drag interaction working.
            }
        };

        const restorePosition = () => {
            try {
                const raw = window.sessionStorage.getItem(storageKey);
                if (!raw) {
                    applyPosition(state.left, state.top);
                    return;
                }

                const saved = JSON.parse(raw);
                applyPosition(Number(saved.left), Number(saved.top));
            } catch (_error) {
                applyPosition(state.left, state.top);
            }
        };

        restorePosition();
        window.requestAnimationFrame(() => cartPreviewWindow.classList.add('is-ready'));

        cartPreviewHandle.addEventListener('pointerdown', (event) => {
            if (event.button !== 0) {
                return;
            }

            const currentRect = cartPreviewWindow.getBoundingClientRect();
            state.width = currentRect.width;
            state.height = currentRect.height;
            state.pointerId = event.pointerId;
            state.offsetX = event.clientX - currentRect.left;
            state.offsetY = event.clientY - currentRect.top;
            cartPreviewWindow.classList.add('is-dragging');
            cartPreviewHandle.setPointerCapture(event.pointerId);
            event.preventDefault();
        });

        cartPreviewHandle.addEventListener('pointermove', (event) => {
            if (state.pointerId !== event.pointerId) {
                return;
            }

            applyPosition(event.clientX - state.offsetX, event.clientY - state.offsetY);
        });

        const finishDrag = (event) => {
            if (state.pointerId !== event.pointerId) {
                return;
            }

            state.pointerId = null;
            cartPreviewWindow.classList.remove('is-dragging');
            savePosition();

            if (cartPreviewHandle.hasPointerCapture(event.pointerId)) {
                cartPreviewHandle.releasePointerCapture(event.pointerId);
            }
        };

        cartPreviewHandle.addEventListener('pointerup', finishDrag);
        cartPreviewHandle.addEventListener('pointercancel', finishDrag);

        window.addEventListener('resize', () => {
            const currentRect = cartPreviewWindow.getBoundingClientRect();
            state.width = currentRect.width;
            state.height = currentRect.height;
            applyPosition(state.left, state.top);
            savePosition();
        });
    };

    const closeModal = () => {
        modal?.classList.add('hidden');
        modal?.classList.remove('flex');
        modalBody.innerHTML = '';
        activeForm = null;
        activeGroups = [];
    };

    const openModal = (form, productName, groups, allowItemNote, currentNote) => {
        activeForm = form;
        activeGroups = groups;
        modalTitle.textContent = i18n.optionsTitleWithProduct.replace('__product__', productName);
        modalBody.innerHTML = '';

        groups.forEach((group) => {
            const groupId = String(group.id || '');
            if (!groupId) {
                return;
            }

            const type = group.type === 'multiple' ? 'multiple' : 'single';
            const required = !!group.required;
            const maxSelect = Number(group.max_select || 99);

            const wrapper = document.createElement('div');
            wrapper.className = 'rounded-2xl border border-slate-200 p-4';
            wrapper.dataset.groupId = groupId;
            wrapper.dataset.groupType = type;
            wrapper.dataset.groupRequired = required ? '1' : '0';
            wrapper.dataset.groupMax = String(maxSelect);

            const title = document.createElement('div');
            title.className = 'mb-2 text-sm font-semibold text-slate-800';
            title.textContent = `${group.name || groupId}${required ? i18n.requiredSuffix : ''}`;
            wrapper.appendChild(title);

            const choices = Array.isArray(group.choices) ? group.choices : [];
            choices.forEach((choice, index) => {
                const choiceId = String(choice.id || '');
                if (!choiceId) {
                    return;
                }

                const row = document.createElement('label');
                row.className = 'mb-2 flex cursor-pointer items-center justify-between rounded-xl border border-slate-200 px-3 py-2 text-sm last:mb-0 hover:bg-slate-50';

                const left = document.createElement('div');
                left.className = 'flex items-center gap-2';

                const input = document.createElement('input');
                input.type = type === 'single' ? 'radio' : 'checkbox';
                input.name = `opt_${groupId}` + (type === 'multiple' ? '[]' : '');
                input.value = choiceId;
                if (required && type === 'single' && index === 0) {
                    input.checked = true;
                }

                left.appendChild(input);
                const text = document.createElement('span');
                text.textContent = String(choice.name || choiceId);
                left.appendChild(text);

                const price = document.createElement('span');
                const p = Number(choice.price || 0);
                price.className = 'text-xs font-semibold ' + (p > 0 ? 'text-brand-primary' : 'text-slate-500');
                price.textContent = p > 0 ? `+${i18n.currencySymbol} ${p}` : i18n.free;

                row.appendChild(left);
                row.appendChild(price);
                wrapper.appendChild(row);
            });

            modalBody.appendChild(wrapper);
        });

        if (allowItemNote) {
            const noteWrapper = document.createElement('div');
            noteWrapper.className = 'rounded-2xl border border-slate-200 p-4';

            const noteTitle = document.createElement('div');
            noteTitle.className = 'mb-2 text-sm font-semibold text-slate-800';
            noteTitle.textContent = i18n.itemNoteLabel;

            const noteInput = document.createElement('textarea');
            noteInput.rows = 3;
            noteInput.maxLength = 255;
            noteInput.value = String(currentNote || '');
            noteInput.placeholder = i18n.itemNotePlaceholder;
            noteInput.className = 'w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-soft';
            noteInput.setAttribute('data-item-note-input', '1');

            noteWrapper.appendChild(noteTitle);
            noteWrapper.appendChild(noteInput);
            modalBody.appendChild(noteWrapper);
        }

        modal?.classList.remove('hidden');
        modal?.classList.add('flex');
    };

    modalClose?.addEventListener('click', closeModal);
    modalCancel?.addEventListener('click', closeModal);

    modalConfirm?.addEventListener('click', () => {
        if (!activeForm) {
            closeModal();
            return;
        }

        const payload = {};

        for (const group of activeGroups) {
            const groupId = String(group.id || '');
            if (!groupId) {
                continue;
            }

            const wrapper = modalBody.querySelector(`[data-group-id="${groupId}"]`);
            if (!wrapper) {
                continue;
            }

            const type = wrapper.dataset.groupType;
            const required = wrapper.dataset.groupRequired === '1';
            const maxSelect = Number(wrapper.dataset.groupMax || 99);

            const checked = Array.from(wrapper.querySelectorAll('input:checked')).map((input) => input.value);

            if (required && checked.length === 0) {
                alert(i18n.requiredError.replace('__group__', group.name || groupId));
                return;
            }

            if (type === 'multiple' && checked.length > maxSelect) {
                alert(i18n.maxSelectError.replace('__group__', group.name || groupId).replace('__max__', maxSelect));
                return;
            }

            if (checked.length > 0) {
                payload[groupId] = type === 'single' ? [checked[0]] : checked;
            }
        }

        const payloadInput = activeForm.querySelector('[data-option-payload]');
        if (payloadInput) {
            payloadInput.value = JSON.stringify(payload);
        }

        const noteInput = modalBody.querySelector('[data-item-note-input]');
        const itemNoteInput = activeForm.querySelector('[data-item-note]');
        if (itemNoteInput) {
            itemNoteInput.value = noteInput ? String(noteInput.value || '').trim() : '';
        }

        const confirmedForm = activeForm;
        confirmedForm.dataset.confirmed = '1';
        closeModal();
        confirmedForm.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
    });

    forms.forEach((form) => {
        const input = form.querySelector('[data-qty-input]');
        const display = form.querySelector('[data-qty-display]');
        const decrement = form.querySelector('[data-qty-decrement]');
        const increment = form.querySelector('[data-qty-increment]');
        const submitButton = form.querySelector('[data-add-to-cart-button]');

        const syncQty = (nextValue) => {
            const safeValue = Math.max(1, Number(nextValue) || 1);
            input.value = safeValue;
            display.textContent = safeValue;
            decrement.disabled = safeValue <= 1;
            decrement.classList.toggle('opacity-40', safeValue <= 1);
        };

        decrement?.addEventListener('click', () => syncQty(Number(input.value) - 1));
        increment?.addEventListener('click', () => syncQty(Number(input.value) + 1));
        syncQty(input.value);

        form.addEventListener('submit', (event) => {
            const activeCartTarget = getCartFlyTarget();

            if (!activeCartTarget || !submitButton || form.dataset.animating === 'true') {
                return;
            }

            const groupsRaw = submitButton.dataset.optionGroups || '[]';
            const allowItemNote = submitButton.dataset.allowItemNote === '1';
            const itemNoteInput = form.querySelector('[data-item-note]');
            let groups = [];
            try {
                groups = JSON.parse(groupsRaw);
            } catch (_e) {
                groups = [];
            }

            if ((Array.isArray(groups) && groups.length > 0 || allowItemNote) && form.dataset.confirmed !== '1') {
                event.preventDefault();
                openModal(form, submitButton.dataset.productName || i18n.unnamedProduct, groups, allowItemNote, itemNoteInput?.value || '');
                return;
            }

            form.dataset.confirmed = '';

            if (!allowItemNote && itemNoteInput) {
                itemNoteInput.value = '';
            }

            event.preventDefault();
            form.dataset.animating = 'true';

            const sourceRect = submitButton.getBoundingClientRect();
            const targetRect = activeCartTarget.getBoundingClientRect();
            const clone = document.createElement('div');
            clone.className = 'cart-fly-clone';
            clone.style.left = `${sourceRect.left + sourceRect.width / 2 - 12}px`;
            clone.style.top = `${sourceRect.top + sourceRect.height / 2 - 12}px`;
            clone.style.width = '24px';
            clone.style.height = '24px';
            clone.style.opacity = '1';
            document.body.appendChild(clone);

            cartBar?.classList.add('scale-[1.02]');
            pulseCartPreview();

            requestAnimationFrame(() => {
                clone.style.left = `${targetRect.left + targetRect.width / 2 - 10}px`;
                clone.style.top = `${targetRect.top + targetRect.height / 2 - 10}px`;
                clone.style.width = '20px';
                clone.style.height = '20px';
                clone.style.opacity = '0.2';
                clone.style.transform = 'scale(0.8)';
            });

            window.setTimeout(() => {
                clone.remove();
                cartBar?.classList.remove('scale-[1.02]');

                sendCartRequest(form)
                    .then((payload) => {
                        applyCartState(payload);
                    })
                    .catch(() => {
                        form.submit();
                    })
                    .finally(() => {
                        form.dataset.animating = 'false';
                    });
            }, 620);
        });
    });

    initCartPreviewWindow();
    initCartPreviewForms();
    initCartPreviewSwipe();
})();
</script>
@endsection
