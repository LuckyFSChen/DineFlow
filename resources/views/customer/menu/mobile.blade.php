<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('customer.menu') }} - {{ $store->name }}</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
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

        @media (min-width: 1024px) {
            .view-list .menu-grid {
                grid-template-columns: 1fr;
                gap: 0.9rem;
            }

            .view-list .product-card {
                display: grid;
                grid-template-columns: 14rem minmax(0, 1fr);
                border-radius: 1.1rem;
            }

            .view-list .product-media {
                height: 100%;
            }

            .view-list .product-media img {
                height: 100%;
                min-height: 10.5rem;
            }

            .view-list .product-content {
                display: flex;
                flex-direction: column;
                padding: 0.95rem 1.05rem;
            }

            .view-list .product-title {
                font-size: 1rem;
                line-height: 1.45rem;
            }

            .view-list .product-desc {
                -webkit-line-clamp: 1;
            }

            .view-list .product-actions {
                margin-top: auto;
                padding-top: 0.75rem;
            }
        }

        .view-toggle-btn[data-active='1'] {
            background-color: #F6AE2D !important;
            border-color: #F6AE2D !important;
            color: #3b1f10 !important;
            box-shadow: 0 8px 20px rgba(246, 174, 45, 0.35);
        }

        .view-toggle-btn[data-active='1'] .view-toggle-icon {
            filter: drop-shadow(0 1px 0 rgba(255, 255, 255, 0.35));
        }

        .view-toggle-btn[data-active='0'] {
            color: #6b4b3e;
        }
    </style>
</head>
@php
    $isDineIn = ($mode ?? 'takeout') === 'dine_in';
    $currencyCode = strtolower((string) ($store->currency ?? 'twd'));
    $currencySymbol = match ($currencyCode) {
        'vnd' => 'VND',
        'cny' => 'CNY',
        'usd' => 'USD',
        default => 'NT$',
    };

    $cartUrl = $isDineIn
        ? route('customer.dinein.cart.show', ['store' => $store, 'table' => $table])
        : route('customer.takeout.cart.show', ['store' => $store]);

    $addToCartUrl = $isDineIn
        ? route('customer.dinein.cart.items.store', ['store' => $store, 'table' => $table])
        : route('customer.takeout.cart.items.store', ['store' => $store]);
@endphp
<body class="bg-brand-soft/20 text-brand-dark">
    <div class="min-h-screen bg-brand-soft/20">
        <div class="mx-auto max-w-7xl px-4 py-8 pb-32 sm:px-6 lg:px-8">
            <div class="mb-8 overflow-hidden rounded-[2rem] border border-brand-soft/60 bg-white shadow-[0_24px_60px_rgba(90,30,14,0.12)]">
                <div class="relative isolate overflow-hidden bg-brand-dark px-6 py-8 text-white sm:px-8">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(255,214,112,0.28),_transparent_34%),linear-gradient(135deg,_rgba(90,30,14,0.96),_rgba(236,144,87,0.88))]"></div>
                    <div class="absolute -right-12 -top-10 h-36 w-36 rounded-full bg-brand-highlight/20 blur-3xl"></div>
                    <div class="absolute -bottom-14 left-10 h-32 w-32 rounded-full bg-brand-accent/20 blur-3xl"></div>
                    <div class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                        <div class="max-w-3xl">
                            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-brand-dark shadow-lg shadow-black/20 transition hover:-translate-y-0.5 hover:bg-brand-highlight">
                                <span aria-hidden="true">&larr;</span>
                                <span>{{ __('customer.back_home_btn') }}</span>
                            </a>
                            <div class="mt-4 flex flex-wrap items-center gap-3">
                                <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold tracking-[0.2em] text-brand-highlight">{{ $isDineIn ? __('customer.dine_in') : __('customer.takeout') }}</span>
                                @if($isDineIn && isset($table))
                                    <span class="inline-flex rounded-full border border-brand-soft/30 bg-white/10 px-3 py-1 text-xs font-semibold text-white/80">{{ __('customer.table_no') }} {{ $table->table_no }}</span>
                                @endif
                                <span class="inline-flex rounded-full border border-brand-soft/30 bg-white/10 px-3 py-1 text-xs font-semibold text-white/80">{{ __('customer.business_hours') }} {{ $store->businessHoursLabel() }}</span>
                            </div>
                            <h1 class="mt-5 text-3xl font-bold tracking-tight sm:text-4xl">{{ $store->name }}</h1>
                            <p class="mt-3 max-w-2xl text-sm leading-7 text-white/75 sm:text-base">{{ $store->description ?: ($isDineIn ? __('customer.select_instruction_short') : __('customer.welcome_takeout_desc')) }}</p>
                        </div>

                        <div class="hidden md:flex">
                            <div class="flex gap-3">
                                @if(isset($orderHistory) && $orderHistory->isNotEmpty())
                                    <div class="flex items-center gap-2">
                                        @foreach($orderHistory->take(3) as $historyOrder)
                                            <a href="{{ route('customer.order.success', ['store' => $store, 'order' => $historyOrder]) }}" class="inline-flex items-center justify-center rounded-2xl border border-white/25 bg-white/10 px-4 py-3 text-xs font-semibold text-white transition hover:-translate-y-0.5 hover:bg-white/20">{{ __('customer.status_prefix') }} {{ $historyOrder->order_no }}</a>
                                        @endforeach
                                    </div>
                                @endif
                                <a href="{{ $cartUrl }}" class="inline-flex items-center justify-center rounded-2xl bg-brand-highlight px-5 py-3 text-sm font-semibold text-brand-dark shadow-lg shadow-brand-highlight/30 transition hover:-translate-y-0.5 hover:bg-brand-soft">{{ __('customer.view_cart') }}</a>
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

            <div class="mb-8 hidden overflow-x-auto rounded-[1.75rem] border border-brand-soft/60 bg-white px-4 py-4 shadow-[0_12px_32px_rgba(90,30,14,0.08)] md:block">
                <div class="flex min-w-max items-center gap-3">
                    @foreach($categories as $category)
                        <a href="#category-{{ $category->id }}" class="inline-flex rounded-full border border-brand-soft/70 bg-brand-soft/20 px-4 py-2 text-sm font-medium text-brand-primary transition hover:-translate-y-0.5 hover:border-brand-accent hover:bg-brand-highlight/60">{{ $category->name }}</a>
                    @endforeach
                </div>
            </div>

            <div class="mb-5 hidden items-center justify-end gap-2 lg:flex">
                <button
                    type="button"
                    data-view-toggle
                    data-mode="detailed"
                    class="view-toggle-btn inline-flex h-10 w-10 items-center justify-center rounded-full border border-brand-soft/70 bg-white text-brand-primary transition hover:border-brand-accent hover:bg-brand-soft/40"
                    title="詳細卡片"
                    aria-label="詳細卡片"
                    aria-pressed="false"
                    data-active="0"
                >
                    <svg viewBox="0 0 24 24" class="view-toggle-icon h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <rect x="3.5" y="3.5" width="7" height="7" rx="1.2"></rect>
                        <rect x="13.5" y="3.5" width="7" height="7" rx="1.2"></rect>
                        <rect x="3.5" y="13.5" width="7" height="7" rx="1.2"></rect>
                        <rect x="13.5" y="13.5" width="7" height="7" rx="1.2"></rect>
                    </svg>
                </button>
                <button
                    type="button"
                    data-view-toggle
                    data-mode="list"
                    class="view-toggle-btn inline-flex h-10 w-10 items-center justify-center rounded-full border border-brand-soft/70 bg-white text-brand-primary transition hover:border-brand-accent hover:bg-brand-soft/40"
                    title="純列表"
                    aria-label="純列表"
                    aria-pressed="false"
                    data-active="0"
                >
                    <svg viewBox="0 0 24 24" class="view-toggle-icon h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <line x1="5" y1="7" x2="21" y2="7"></line>
                        <line x1="5" y1="12" x2="21" y2="12"></line>
                        <line x1="5" y1="17" x2="21" y2="17"></line>
                        <circle cx="3" cy="7" r="0.9" fill="currentColor" stroke="none"></circle>
                        <circle cx="3" cy="12" r="0.9" fill="currentColor" stroke="none"></circle>
                        <circle cx="3" cy="17" r="0.9" fill="currentColor" stroke="none"></circle>
                    </svg>
                </button>
            </div>

            <main>
            <div class="relative grid grid-cols-[5.5rem,minmax(0,1fr)] items-start gap-4 md:block">
                <aside class="self-stretch md:hidden">
                    <div class="sticky top-5">
                        <div class="h-[calc(100vh-2.5rem)] overflow-hidden rounded-[1.75rem] border border-brand-soft/60 bg-white shadow-[0_18px_40px_rgba(90,30,14,0.08)]">
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

                <div class="min-w-0">
                    @foreach($categories as $category)
                        <section id="category-{{ $category->id }}" class="mb-8 scroll-mt-24 md:scroll-mt-[230px]">
                            <div class="mb-4 flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-accent">Table Menu</p>
                                    <h2 class="mt-2 text-2xl font-bold text-brand-dark">{{ $category->name }}</h2>
                                </div>
                                <span class="text-sm text-brand-primary/70">{{ $category->products->count() }} {{ __('customer.items_in_menu') }}</span>
                            </div>

                            <div class="menu-grid grid gap-5 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
                                @forelse($category->products as $product)
                                    @php
                                        $productImage = filled($product->image)
                                            ? (\Illuminate\Support\Str::startsWith($product->image, ['http://', 'https://']) ? $product->image : asset('storage/' . ltrim($product->image, '/')))
                                            : 'https://images.unsplash.com/photo-1515003197210-e0cd71810b5f?auto=format&fit=crop&w=900&q=80';
                                    @endphp
                                    <div class="product-card group overflow-hidden rounded-[1.75rem] border border-brand-soft/60 bg-white shadow-[0_18px_44px_rgba(90,30,14,0.1)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_24px_60px_rgba(90,30,14,0.16)]">
                                        <div class="product-media relative overflow-hidden">
                                            <img src="{{ $productImage }}" alt="{{ $product->name }}" class="h-44 w-full object-cover transition duration-500 group-hover:scale-105">
                                            <div class="absolute inset-0 bg-gradient-to-t from-brand-dark/85 via-brand-dark/20 to-transparent"></div>
                                            <div class="absolute left-4 top-4 inline-flex rounded-full border border-white/20 bg-white/15 px-3 py-1 text-xs font-semibold text-white backdrop-blur">{{ $category->name }}</div>
                                            <div class="absolute bottom-4 right-4 rounded-full bg-brand-highlight px-3 py-1.5 text-sm font-bold text-brand-dark shadow-lg">{{ $currencySymbol }} {{ number_format($product->price) }}</div>
                                        </div>

                                        <div class="product-content p-5">
                                            <div class="flex items-start justify-between gap-4">
                                                <div class="min-w-0 flex-1">
                                                    <h3 class="product-title text-lg font-semibold text-brand-dark">{{ $product->name }}</h3>
                                                    <p class="product-desc mt-2 line-clamp-2 text-sm leading-6 text-brand-primary/75">{{ $product->description ?: __('customer.fresh_made') }}</p>
                                                </div>
                                                @if($product->is_sold_out)
                                                    <span class="shrink-0 rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-600">{{ __('customer.sold_out') }}</span>
                                                @endif
                                            </div>

                                            <div class="product-actions mt-5 border-t border-brand-soft/50 pt-4">
                                                @if(! $orderingAvailable)
                                                    <div class="rounded-2xl bg-brand-soft/25 px-3 py-3 text-center text-sm font-medium text-brand-dark">{{ __('customer.ordering_closed') }}</div>
                                                @elseif($product->is_sold_out)
                                                    <div class="rounded-2xl bg-slate-100 px-3 py-3 text-center text-sm font-medium text-slate-500">{{ __('customer.item_not_available') }}</div>
                                                @else
                                                    <form method="POST" action="{{ $addToCartUrl }}" class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between" data-add-to-cart-form>
                                                        @csrf
                                                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                                                        <input type="hidden" name="option_payload" value="" data-option-payload>
                                                        <input type="hidden" name="item_note" value="" data-item-note>
                                                        <div class="qty-box inline-flex h-10 w-full items-center justify-between self-start rounded-2xl border border-brand-soft bg-brand-soft/20 p-0.5 shadow-sm sm:w-auto sm:justify-start">
                                                            <button type="button" class="flex h-8 w-8 items-center justify-center rounded-xl text-sm font-bold text-brand-primary transition hover:bg-white" data-qty-decrement>-</button>
                                                            <input type="hidden" name="qty" value="1" data-qty-input>
                                                            <span class="flex min-w-[2.25rem] items-center justify-center text-xs font-semibold text-brand-dark sm:min-w-[2rem]" data-qty-display>1</span>
                                                            <button type="button" class="flex h-8 w-8 items-center justify-center rounded-xl text-sm font-bold text-brand-primary transition hover:bg-white" data-qty-increment>+</button>
                                                        </div>
                                                        <button type="submit" class="add-button inline-flex h-10 w-full min-w-[7.5rem] shrink-0 items-center justify-center whitespace-nowrap rounded-2xl bg-brand-primary px-5 text-sm font-semibold text-white shadow-lg shadow-brand-primary/20 transition hover:-translate-y-0.5 hover:bg-brand-accent hover:text-brand-dark sm:w-auto sm:min-w-[8rem]" data-add-to-cart-button data-option-groups='@json($product->option_groups ?? [])' data-allow-item-note="{{ $product->allow_item_note ? '1' : '0' }}" data-product-name="{{ $product->name }}" data-product-image="{{ $productImage }}">{{ __('customer.add_to_cart') }}</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-[1.75rem] border border-brand-soft/60 bg-white px-5 py-8 text-center text-sm text-brand-primary/70 shadow-[0_18px_40px_rgba(90,30,14,0.08)] md:col-span-2">{{ __('customer.no_products_in_cat2') }}</div>
                                @endforelse
                            </div>
                        </section>
                    @endforeach
                </div>
            </div>
        </main>

        </div>

        @if($categories->isNotEmpty())
            <aside class="fixed right-6 top-24 z-30 hidden w-80 xl:block">
                <div class="overflow-hidden rounded-[1.5rem] border border-brand-soft/70 bg-white shadow-[0_18px_40px_rgba(90,30,14,0.1)]">
                    <div class="border-b border-brand-soft/60 bg-brand-dark px-4 py-4 text-white">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-highlight/80">{{ __('customer.cart') }}</p>
                        <p class="mt-1 text-sm font-semibold">{{ $cartCount > 0 ? __('customer.cart_bar_total', ['count' => $cartCount, 'currency' => $currencySymbol, 'total' => number_format($cartTotal)]) : __('customer.cart_bar_empty') }}</p>
                    </div>

                    <div class="max-h-[52vh] space-y-3 overflow-y-auto px-4 py-4">
                        @forelse($cartPreviewItems->take(6) as $item)
                            <article class="rounded-2xl border border-brand-soft/70 bg-brand-soft/15 px-3 py-3">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-sm font-semibold text-brand-dark">{{ $item['product_name'] ?? __('customer.product_default_name') }}</p>
                                    <p class="shrink-0 text-xs font-semibold text-brand-primary">x{{ $item['qty'] ?? 1 }}</p>
                                </div>
                                @if(!empty($item['option_label']))
                                    <p class="mt-1 text-xs text-brand-primary/75">{{ $item['option_label'] }}</p>
                                @endif
                                <p class="mt-2 text-xs font-semibold text-brand-accent">{{ __('customer.subtotal') }} {{ $currencySymbol }} {{ number_format((int) ($item['subtotal'] ?? 0)) }}</p>
                            </article>
                        @empty
                            <div class="rounded-2xl border border-dashed border-brand-soft/80 bg-brand-soft/10 px-3 py-8 text-center text-sm text-brand-primary/75">
                                {{ __('customer.no_products_available') }}
                            </div>
                        @endforelse

                        @if($cartPreviewItems->count() > 6)
                            <p class="text-center text-xs font-semibold text-brand-primary/70">{{ __('customer.more_items_in_cart', ['count' => $cartPreviewItems->count() - 6]) }}</p>
                        @endif
                    </div>

                    <div class="border-t border-brand-soft/60 p-4">
                        <a href="{{ $cartUrl }}" class="inline-flex h-11 w-full items-center justify-center rounded-2xl bg-brand-highlight px-4 text-sm font-semibold text-brand-dark transition hover:bg-brand-soft">{{ __('customer.view_cart') }}{{ $cartCount > 0 ? ' (' . $cartCount . ')' : '' }}</a>
                    </div>
                </div>
            </aside>
        @endif

        <div class="fixed inset-x-0 bottom-0 z-40 bg-gradient-to-r from-brand-dark to-brand-primary px-4 py-4 shadow-[0_-12px_30px_rgba(90,30,14,0.35)]">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 rounded-[1.75rem] border border-white/15 bg-black/15 px-4 py-3 text-white backdrop-blur-sm transition-transform duration-200" data-cart-bar>
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-brand-highlight/80">{{ $isDineIn && isset($table) ? __('customer.table_no') . ' ' . $table->table_no : ($orderingAvailable ? __('customer.open') : __('customer.ordering_closed')) }}</p>
                    <p class="text-sm font-semibold">{{ $orderingAvailable ? __('customer.cart_bar_ordering_available') : __('customer.cart_bar_not_available') }}</p>
                    <p class="mt-1 text-xs text-white/70">{{ $cartCount > 0 ? __('customer.cart_bar_total', ['count' => $cartCount, 'currency' => $currencySymbol, 'total' => number_format($cartTotal)]) : __('customer.cart_bar_empty') }}</p>
                    @if(isset($orderHistory) && $orderHistory->isNotEmpty())
                        <div class="mt-1 flex flex-wrap gap-2">
                            @foreach($orderHistory->take(2) as $historyOrder)
                                <a href="{{ route('customer.order.success', ['store' => $store, 'order' => $historyOrder]) }}" class="inline-flex text-xs font-semibold text-brand-highlight underline-offset-2 hover:underline">{{ __('customer.status_prefix') }} {{ $historyOrder->order_no }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
                <a href="{{ $cartUrl }}" class="inline-flex h-11 items-center justify-center rounded-2xl bg-brand-highlight px-4 text-sm font-semibold text-brand-dark transition hover:bg-brand-soft" data-cart-target>{{ __('customer.view_cart') }}{{ $cartCount > 0 ? ' (' . $cartCount . ')' : '' }}</a>
            </div>
        </div>

        <div id="option-modal" class="fixed inset-0 z-[90] hidden items-center justify-center bg-black/45 p-4">
            <div class="w-full max-w-lg rounded-3xl bg-white p-5 shadow-2xl">
                <div id="option-modal-media" class="mb-3 hidden overflow-hidden rounded-2xl border border-slate-200">
                    <img id="option-modal-image" src="" alt="" class="h-40 w-full object-cover">
                </div>
                <div class="flex items-center justify-between">
                    <h3 id="option-modal-title" class="text-lg font-bold text-brand-dark">{{ __('customer.select_options_title') }}</h3>
                    <button type="button" id="option-modal-close" class="rounded-full p-2 text-slate-500 hover:bg-slate-100">&times;</button>
                </div>
                <div id="option-modal-body" class="mt-4 max-h-[60vh] space-y-4 overflow-y-auto"></div>
                <div class="mt-5 flex gap-3">
                    <button type="button" id="option-modal-cancel" class="inline-flex flex-1 items-center justify-center rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">{{ __('customer.cancel') }}</button>
                    <button type="button" id="option-modal-confirm" class="inline-flex flex-1 items-center justify-center rounded-2xl bg-brand-primary px-4 py-3 text-sm font-semibold text-white hover:bg-brand-accent hover:text-brand-dark">{{ __('customer.confirm_add') }}</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    (() => {
        const forms = document.querySelectorAll('[data-add-to-cart-form]');
        const viewToggleButtons = document.querySelectorAll('[data-view-toggle]');
        const cartTarget = document.querySelector('[data-cart-target]');
        const cartBar = document.querySelector('[data-cart-bar]');
        const modal = document.getElementById('option-modal');
        const modalMedia = document.getElementById('option-modal-media');
        const modalImage = document.getElementById('option-modal-image');
        const modalTitle = document.getElementById('option-modal-title');
        const modalBody = document.getElementById('option-modal-body');
        const modalClose = document.getElementById('option-modal-close');
        const modalCancel = document.getElementById('option-modal-cancel');
        const modalConfirm = document.getElementById('option-modal-confirm');
        const i18n = {
            optionsTitle: @json(__('customer.select_options_title')),
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

        let activeForm = null;
        let activeGroups = [];
        const viewStorageKey = `menu_view_mode_{{ $store->id }}`;

        const applyViewMode = (mode) => {
            const normalized = mode === 'list' ? 'list' : 'detailed';
            document.body.classList.toggle('view-list', normalized === 'list');

            viewToggleButtons.forEach((button) => {
                const active = button.dataset.mode === normalized;
                button.dataset.active = active ? '1' : '0';
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
            });

            return normalized;
        };

        if (viewToggleButtons.length > 0) {
            let initialMode = 'detailed';
            try {
                const savedMode = window.localStorage.getItem(viewStorageKey);
                if (savedMode === 'list' || savedMode === 'detailed') {
                    initialMode = savedMode;
                }
            } catch (_e) {
                initialMode = 'detailed';
            }

            applyViewMode(initialMode);

            viewToggleButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const currentMode = applyViewMode(button.dataset.mode || 'detailed');
                    try {
                        window.localStorage.setItem(viewStorageKey, currentMode);
                    } catch (_e) {
                        // Ignore localStorage failures; UI switch should still work.
                    }
                });
            });
        }

        const closeModal = () => {
            modal?.classList.add('hidden');
            modal?.classList.remove('flex');
            if (modalImage) {
                modalImage.src = '';
                modalImage.alt = '';
            }
            modalMedia?.classList.add('hidden');
            modalBody.innerHTML = '';
            activeForm = null;
            activeGroups = [];
        };

        const openModal = (form, productName, productImage, groups, allowItemNote, currentNote) => {
            activeForm = form;
            activeGroups = groups;
            modalTitle.textContent = i18n.optionsTitleWithProduct.replace('__product__', productName);
            if (modalImage && productImage && String(productImage).trim() !== '') {
                modalImage.src = String(productImage);
                modalImage.alt = productName;
                modalMedia?.classList.remove('hidden');
            } else {
                if (modalImage) {
                    modalImage.src = '';
                    modalImage.alt = '';
                }
                modalMedia?.classList.add('hidden');
            }
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
                    input.dataset.choiceName = String(choice.name || choiceId);
                    input.dataset.choicePrice = String(Number(choice.price || 0));
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
                if (!cartTarget || !submitButton || form.dataset.animating === 'true') {
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
                    openModal(
                        form,
                        submitButton.dataset.productName || i18n.unnamedProduct,
                        submitButton.dataset.productImage || '',
                        groups,
                        allowItemNote,
                        itemNoteInput?.value || ''
                    );
                    return;
                }

                form.dataset.confirmed = '';

                if (!allowItemNote && itemNoteInput) {
                    itemNoteInput.value = '';
                }

                event.preventDefault();
                form.dataset.animating = 'true';

                const sourceRect = submitButton.getBoundingClientRect();
                const targetRect = cartTarget.getBoundingClientRect();
                const clone = document.createElement('div');
                clone.className = 'cart-fly-clone';
                clone.style.left = `${sourceRect.left + sourceRect.width / 2 - 12}px`;
                clone.style.top = `${sourceRect.top + sourceRect.height / 2 - 12}px`;
                clone.style.width = '24px';
                clone.style.height = '24px';
                clone.style.opacity = '1';
                document.body.appendChild(clone);

                cartBar?.classList.add('scale-[1.02]');

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
                    form.submit();
                }, 620);
            });
        });
    })();
    </script>
</body>
</html>




