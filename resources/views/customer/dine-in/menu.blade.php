<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('customer.menu_page_title', ['store' => $store->name]) }}</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $currencyCode = strtolower((string) ($store->currency ?? 'twd'));
    $currencySymbol = match ($currencyCode) {
        'vnd' => 'VND',
        'cny' => 'CNY',
        'usd' => 'USD',
        default => 'NT$',
    };
@endphp
<body class="bg-orange-50 text-gray-900">
    <div class="min-h-screen pb-28">
        {{-- Header --}}
        <header class="sticky top-0 z-30 border-b border-orange-100 bg-white/95 backdrop-blur">
            <div class="mx-auto max-w-3xl px-4">
                <div class="flex min-h-[152px] flex-col justify-center py-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-orange-500">DineFlow</p>
                            <h1 class="mt-1 text-2xl font-bold tracking-tight">{{ $store->name }}</h1>
                            <p class="mt-1 text-sm text-gray-500">{{ __('customer.table_no') }}：{{ $table->table_no }}</p>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <a href="{{ route('customer.cart', $table->qr_token) }}"
                            class="inline-flex items-center rounded-xl border border-orange-200 bg-orange-50 px-4 py-2 text-sm font-semibold text-orange-600 hover:bg-orange-100">
                                {{ __('customer.cart') }}
                            </a>
                            <x-lang-switcher />
                        </div>
                    </div>

                    <div class="mt-4 rounded-2xl border border-orange-100 bg-orange-50 px-4 py-3 text-sm text-gray-600">
                        {{ __('customer.select_instruction') }}
                    </div>

                    @if (session('success'))
                        <div class="mt-4 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </header>

        {{-- Category Nav --}}
        <nav class="sticky top-[152px] z-20 h-16 border-b border-orange-100 bg-white/95 backdrop-blur">
            <div class="mx-auto flex h-full max-w-3xl items-center overflow-x-auto px-4">
                <div class="flex min-w-max gap-2">
                    @foreach ($categories as $category)
                        <a href="#category-{{ $category->id }}"
                        class="inline-flex h-10 items-center rounded-full border border-orange-200 bg-orange-50 px-4 text-sm font-medium text-orange-600 whitespace-nowrap hover:bg-orange-100">
                            {{ $category->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        </nav>

        {{-- Content --}}
        <main class="mx-auto max-w-3xl px-4 py-6">
            @foreach ($categories as $category)
                <section id="category-{{ $category->id }}" class="mb-8 scroll-mt-[230px]">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-xl font-bold">{{ $category->name }}</h2>
                        <span class="text-sm text-gray-400">
                            {{ count($products[$category->id] ?? []) }}{{ __('customer.items') }}
                        </span>
                    </div>

                    <div class="space-y-4">
                        @forelse (($products[$category->id] ?? collect()) as $product)
                            <div class="rounded-3xl border border-orange-100 bg-white p-4 shadow-sm">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0 flex-1">
                                        <h3 class="text-lg font-semibold text-gray-900">
                                            {{ $product->name }}
                                        </h3>

                                        @if (!empty($product->description))
                                            <p class="mt-1 text-sm leading-6 text-gray-500">
                                                {{ $product->description }}
                                            </p>
                                        @else
                                            <p class="mt-1 text-sm leading-6 text-gray-400">
                                                {{ __('customer.popular_item') }}
                                            </p>
                                        @endif

                                        <div class="mt-4 flex items-center gap-2">
                                            <span class="text-xl font-bold text-orange-600">
                                                {{ $currencySymbol }} {{ number_format($product->price) }}
                                            </span>

                                            @if ($product->is_sold_out)
                                                <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-600">
                                                    {{ __('customer.sold_out') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <form method="POST"
                                      action="{{ route('customer.cart.add') }}"
                                      class="mt-5 flex items-center justify-between gap-3 border-t border-orange-50 pt-4">
                                    @csrf
                                    <input type="hidden" name="token" value="{{ $table->qr_token }}">
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">

                                    <div class="flex items-center gap-2">
                                        <label for="qty_{{ $product->id }}" class="text-sm font-medium text-gray-600">
                                            {{ __('customer.qty') }}
                                        </label>
                                        <input id="qty_{{ $product->id }}"
                                               type="number"
                                               name="qty"
                                               value="1"
                                               min="1"
                                               class="w-20 rounded-xl border border-gray-300 px-3 py-2 text-center text-sm focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-200">
                                    </div>

                                    <button type="submit"
                                            class="inline-flex h-11 items-center justify-center rounded-xl bg-orange-500 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-600 active:scale-[0.98]">
                                        {{ __('customer.add_to_cart') }}
                                    </button>
                                </form>
                            </div>
                        @empty
                            <div class="rounded-3xl border border-dashed border-orange-200 bg-white px-5 py-8 text-center text-sm text-gray-500">
                                {{ __('customer.no_products_in_category') }}
                            </div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </main>

        {{-- Bottom Cart Bar --}}
        <div class="fixed inset-x-0 bottom-0 z-40 border-t border-orange-100 bg-white/95 px-4 py-4 backdrop-blur">
            <div class="mx-auto flex max-w-3xl items-center justify-between gap-3 rounded-2xl bg-gray-900 px-4 py-3 text-white shadow-lg">
                <div>
                    <p class="text-xs text-gray-300">{{ __('customer.ready_go_to_cart') }}</p>
                    <p class="text-sm font-semibold">{{ __('customer.view_selected_items') }}</p>
                </div>

                <a href="{{ route('customer.cart', $table->qr_token) }}"
                   class="inline-flex h-11 items-center justify-center rounded-xl bg-orange-500 px-4 text-sm font-semibold text-white hover:bg-orange-600">
                    {{ __('customer.view_cart') }}
                </a>
            </div>
        </div>
    </div>
</body>
</html>