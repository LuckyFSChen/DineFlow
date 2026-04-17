<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('customer.order_history_title') }}｜DineFlow</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-orange-50 text-gray-900">
    @php
        $currencyCode = strtolower((string) ($store->currency ?? 'twd'));
        $currencySymbol = match ($currencyCode) {
            'vnd' => 'VND',
            'cny' => 'CNY',
            'usd' => 'USD',
            default => 'NT$',
        };
    @endphp
    <div class="min-h-screen">
        <main class="mx-auto max-w-4xl px-4 py-8 sm:py-12">
            <section class="rounded-3xl border border-orange-100 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">{{ __('customer.order_history_title') }}</h1>
                        <p class="mt-2 text-sm text-gray-500">{{ $store->name }}</p>
                    </div>
                    <a href="{{ route('customer.takeout.menu', ['store' => $store]) }}" class="inline-flex items-center rounded-xl border border-orange-200 bg-orange-50 px-4 py-2 text-sm font-semibold text-orange-700 transition hover:bg-orange-100">
                        {{ __('customer.back_to_menu') }}
                    </a>
                </div>

                <form method="GET" action="{{ route('customer.order.history', ['store' => $store]) }}" class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-gray-700">Email</label>
                        <input type="email" name="customer_email" value="{{ old('customer_email', $customerEmail ?? '') }}" placeholder="{{ __('customer.email_placeholder') }}" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-100">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-gray-700">{{ __('customer.phone') }}</label>
                        <input type="text" name="customer_phone" value="{{ old('customer_phone', $customerPhone ?? '') }}" placeholder="{{ __('customer.phone_placeholder') }}" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-100">
                    </div>
                    <div class="sm:col-span-2 flex items-center gap-3">
                        <button type="submit" class="inline-flex h-11 items-center justify-center rounded-xl bg-orange-500 px-5 text-sm font-semibold text-white transition hover:bg-orange-600">
                            {{ __('customer.search_order_history') }}
                        </button>
                        <p class="text-xs text-gray-500">{{ __('customer.order_history_hint') }}</p>
                    </div>
                </form>

                @if ($errors->any())
                    <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                @if ($requiresBothIdentifiers ?? false)
                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        {{ __('customer.order_history_requires_email_and_phone') }}
                    </div>
                @endif
            </section>

            <section class="mt-6 rounded-3xl border border-orange-100 bg-white p-5 shadow-sm">
                @if (!($hasFilters ?? false))
                    <p class="text-sm text-gray-600">{{ __('customer.order_history_empty_filter') }}</p>
                @elseif ($orders->isEmpty())
                    <p class="text-sm text-gray-600">{{ __('customer.order_history_no_results') }}</p>
                @else
                    <div class="space-y-3">
                        @foreach ($orders as $historyOrder)
                            <article class="rounded-2xl border border-orange-100 bg-orange-50/60 px-4 py-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-bold text-gray-900">#{{ $historyOrder->order_no }}</p>
                                        <p class="text-xs text-gray-500">{{ $historyOrder->created_at?->format('Y-m-d H:i') }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-orange-700">{{ $historyOrder->customer_status_label }}</p>
                                        <p class="text-xs text-gray-600">{{ $currencySymbol }} {{ number_format((int) $historyOrder->total) }}</p>
                                    </div>
                                </div>

                                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-gray-600">
                                    <span class="rounded-full bg-white px-2.5 py-1">{{ $historyOrder->order_type === 'takeout' ? __('customer.takeout') : __('customer.table_no') . ' ' . ($historyOrder->table->table_no ?? '-') }}</span>
                                    <span class="rounded-full bg-white px-2.5 py-1">{{ $historyOrder->payment_status === 'paid' ? __('customer.payment_status_paid') : __('customer.payment_status_unpaid') }}</span>
                                </div>

                                <a href="{{ route('customer.order.success', ['store' => $store, 'order' => $historyOrder]) }}" class="mt-3 inline-flex items-center rounded-xl border border-orange-200 bg-white px-3 py-1.5 text-xs font-semibold text-orange-700 transition hover:bg-orange-100">
                                    {{ __('customer.view_order_detail') }}
                                </a>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </main>
    </div>
</body>
</html>
