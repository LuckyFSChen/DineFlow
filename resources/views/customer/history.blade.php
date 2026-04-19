<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('customer.order_history_title') }} | {{ config('app.name', 'DineFlow') }}</title>
    <meta name="robots" content="noindex,nofollow,noarchive">
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-orange-50 text-gray-900">
    <div class="min-h-screen">
        <main class="mx-auto max-w-5xl px-4 py-8 sm:py-12">
            <section class="rounded-3xl border border-orange-100 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">{{ __('customer.order_history_title') }}</h1>
                        <p class="mt-1 text-xs text-gray-500">
                            已自動顯示你帳號的訂單紀錄。
                        </p>
                    </div>
                    <a
                        href="{{ route('stores.list') }}"
                        class="inline-flex items-center rounded-xl border border-orange-200 bg-orange-50 px-4 py-2 text-sm font-semibold text-orange-700 transition hover:bg-orange-100"
                    >
                        {{ __('customer.back_home') }}
                    </a>
                </div>
            </section>

            <section class="mt-6 rounded-3xl border border-orange-100 bg-white p-5 shadow-sm">
                @if (session('success'))
                    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ session('error') }}
                    </div>
                @endif

                @if ($orders->isEmpty())
                    <p class="text-sm text-gray-600">
                        目前沒有你的訂單紀錄。
                    </p>
                @else
                    <div class="mb-4 flex flex-wrap items-center gap-2 text-xs text-gray-600">
                        <span class="rounded-full border border-orange-200 bg-orange-50 px-3 py-1">
                            {{ $orders->count() }} orders
                        </span>
                        <span class="rounded-full border border-orange-200 bg-orange-50 px-3 py-1">
                            {{ $orders->pluck('store_id')->unique()->count() }} stores
                        </span>
                    </div>

                    <div class="space-y-3">
                        @foreach ($orders as $historyOrder)
                            @php
                                $review = $historyOrder->review;
                                $orderStore = $historyOrder->store;
                                $isCompleted = in_array(strtolower((string) $historyOrder->status), ['complete', 'completed', 'ready', 'ready_for_pickup', 'picked_up', 'collected', 'served'], true);
                                $storeRating = (int) ($review->rating ?? 0);
                                $orderRating = (int) ($review->order_rating ?? 0);
                                $orderCurrencyCode = strtolower((string) ($orderStore->currency ?? 'twd'));
                                $orderCurrencySymbol = match ($orderCurrencyCode) {
                                    'vnd' => 'VND',
                                    'cny' => 'CNY',
                                    'usd' => 'USD',
                                    default => 'NT$',
                                };
                            @endphp
                            <article class="rounded-2xl border border-orange-100 bg-orange-50/60 px-4 py-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-bold text-gray-900">#{{ $historyOrder->order_no }}</p>
                                        <p class="text-xs font-medium text-gray-700">{{ $orderStore?->name ?? '-' }}</p>
                                        <p class="text-xs text-gray-500">{{ $historyOrder->created_at?->format('Y-m-d H:i') }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-orange-700">{{ $historyOrder->customer_status_label }}</p>
                                        <p class="text-xs text-gray-600">{{ $orderCurrencySymbol }} {{ number_format((int) $historyOrder->total) }}</p>
                                    </div>
                                </div>

                                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-gray-600">
                                    <span class="rounded-full bg-white px-2.5 py-1">
                                        {{ $historyOrder->order_type === 'takeout' ? __('customer.takeout') : __('customer.table_no') . ' ' . ($historyOrder->table->table_no ?? '-') }}
                                    </span>
                                    <span class="rounded-full bg-white px-2.5 py-1">
                                        {{ $historyOrder->payment_status === 'paid' ? __('customer.payment_status_paid') : __('customer.payment_status_unpaid') }}
                                    </span>
                                </div>

                                @if ($historyOrder->items->isNotEmpty())
                                    <div class="mt-3 rounded-xl border border-orange-100 bg-white/80 px-3 py-2">
                                        <ul class="space-y-1 text-sm text-gray-700">
                                            @foreach ($historyOrder->items->take(3) as $item)
                                                <li class="flex items-center justify-between gap-3">
                                                    <span class="min-w-0 truncate">
                                                        <span class="font-medium">{{ $item->product_name }}</span>
                                                        <span class="text-gray-500">x {{ $item->qty }}</span>
                                                    </span>
                                                    <span class="shrink-0 text-xs text-gray-500">{{ $orderCurrencySymbol }} {{ number_format((int) $item->subtotal) }}</span>
                                                </li>
                                                @if (!empty($item->note))
                                                    <li class="text-xs text-gray-500">{{ $item->note }}</li>
                                                @endif
                                            @endforeach
                                        </ul>
                                        @if ($historyOrder->items->count() > 3)
                                            <p class="mt-2 text-xs text-gray-500">
                                                +{{ $historyOrder->items->count() - 3 }} more items
                                            </p>
                                        @endif
                                    </div>
                                @endif

                                @if($orderStore)
                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        <a
                                            href="{{ route('customer.order.success', ['store' => $orderStore, 'order' => $historyOrder]) }}"
                                            class="inline-flex items-center rounded-xl border border-orange-200 bg-white px-3 py-1.5 text-xs font-semibold text-orange-700 transition hover:bg-orange-100"
                                        >
                                            {{ __('customer.view_order_detail') }}
                                        </a>

                                        @auth
                                            <form method="POST" action="{{ route('customer.order.reorder', ['order' => $historyOrder]) }}">
                                                @csrf
                                                <button
                                                    type="submit"
                                                    class="inline-flex items-center rounded-xl border border-orange-200 bg-white px-3 py-1.5 text-xs font-semibold text-orange-700 transition hover:bg-orange-100"
                                                >
                                                    {{ __('customer.reorder_to_cart') }}
                                                </button>
                                            </form>
                                        @endauth
                                    </div>
                                @endif

                                @if($isCompleted)
                                    <div class="mt-3 rounded-xl border border-orange-200 bg-white px-3 py-3">
                                        @if($review)
                                            <p class="text-xs font-semibold text-emerald-700">{{ __('customer.review_already_submitted') }}</p>
                                            <div class="mt-1 flex flex-wrap items-center gap-4 text-xs text-gray-700">
                                                <span>{{ __('customer.review_store_rating_label') }}: <span class="text-amber-500">{{ str_repeat('*', $storeRating) }}{{ str_repeat('-', max(5 - $storeRating, 0)) }}</span></span>
                                                <span>{{ __('customer.review_order_rating_label') }}: <span class="text-amber-500">{{ str_repeat('*', $orderRating) }}{{ str_repeat('-', max(5 - $orderRating, 0)) }}</span></span>
                                            </div>
                                            @if(!empty($review->comment))
                                                <p class="mt-2 text-sm text-gray-700">{{ $review->comment }}</p>
                                            @endif
                                        @elseif($orderStore)
                                            <form method="POST" action="{{ route('customer.order.review.store', ['store' => $orderStore, 'order' => $historyOrder]) }}" class="space-y-3">
                                                @csrf

                                                <div>
                                                    <p class="mb-1 text-xs font-semibold text-gray-700">{{ __('customer.review_store_rating_label') }}</p>
                                                    <div class="flex flex-wrap gap-2">
                                                        @for($score = 5; $score >= 1; $score--)
                                                            <label class="inline-flex items-center gap-1 rounded-lg border border-orange-200 bg-orange-50 px-2 py-1 text-xs font-medium text-orange-700">
                                                                <input type="radio" name="store_rating" value="{{ $score }}" class="h-3.5 w-3.5" {{ $score === 5 ? 'checked' : '' }}>
                                                                <span>{{ str_repeat('*', $score) }}</span>
                                                            </label>
                                                        @endfor
                                                    </div>
                                                </div>

                                                <div>
                                                    <p class="mb-1 text-xs font-semibold text-gray-700">{{ __('customer.review_order_rating_label') }}</p>
                                                    <div class="flex flex-wrap gap-2">
                                                        @for($score = 5; $score >= 1; $score--)
                                                            <label class="inline-flex items-center gap-1 rounded-lg border border-orange-200 bg-orange-50 px-2 py-1 text-xs font-medium text-orange-700">
                                                                <input type="radio" name="order_rating" value="{{ $score }}" class="h-3.5 w-3.5" {{ $score === 5 ? 'checked' : '' }}>
                                                                <span>{{ str_repeat('*', $score) }}</span>
                                                            </label>
                                                        @endfor
                                                    </div>
                                                </div>

                                                <input type="hidden" name="customer_name" value="{{ $historyOrder->customer_name }}">
                                                <input type="hidden" name="customer_email" value="{{ $historyOrder->customer_email }}">
                                                <input type="hidden" name="customer_phone" value="{{ $historyOrder->customer_phone }}">

                                                <div>
                                                    <label class="mb-1 block text-xs font-semibold text-gray-700">{{ __('customer.review_comment_label') }}</label>
                                                    <textarea name="comment" rows="2" maxlength="1000" placeholder="{{ __('customer.review_comment_placeholder') }}" class="w-full rounded-lg border border-gray-300 px-2.5 py-2 text-xs focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-100"></textarea>
                                                </div>

                                                <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg bg-orange-500 px-4 text-xs font-semibold text-white hover:bg-orange-600">
                                                    {{ __('customer.review_submit') }}
                                                </button>
                                            </form>
                                        @else
                                            <p class="text-xs text-gray-600">{{ __('customer.review_store_unavailable') }}</p>
                                        @endif
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </main>
    </div>
</body>
</html>

