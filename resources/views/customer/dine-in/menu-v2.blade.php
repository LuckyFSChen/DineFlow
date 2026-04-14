<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $store->name }} 內用點餐</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-orange-50 text-gray-900">
    <div class="min-h-screen pb-28">
        <header class="sticky top-0 z-30 border-b border-orange-100 bg-white/95 backdrop-blur">
            <div class="mx-auto max-w-3xl px-4">
                <div class="flex min-h-[152px] flex-col justify-center py-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-orange-500">DineFlow</p>
                            <h1 class="mt-1 text-2xl font-bold tracking-tight">{{ $store->name }}</h1>
                            <p class="mt-1 text-sm text-gray-500">桌號 {{ $table->table_no }}</p>
                            <p class="mt-1 text-sm text-gray-500">營業時間 {{ $store->businessHoursLabel() }}</p>
                        </div>
                        <a href="{{ route('customer.dinein.cart.show', ['store' => $store, 'table' => $table]) }}" class="inline-flex items-center rounded-xl border border-orange-200 bg-orange-50 px-4 py-2 text-sm font-semibold text-orange-600 hover:bg-orange-100">查看購物車</a>
                    </div>

                    @if(! $orderingAvailable)
                        <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ $store->orderingClosedMessage() }}</div>
                    @else
                        <div class="mt-4 rounded-2xl border border-orange-100 bg-orange-50 px-4 py-3 text-sm text-gray-600">選擇餐點後加入購物車，再送出內用訂單。</div>
                    @endif

                    @if(session('success'))
                        <div class="mt-4 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700">{{ session('success') }}</div>
                    @endif

                    @if(session('error'))
                        <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
                    @endif

                    @if($errors->any())
                        <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            @foreach($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </header>

        <nav class="sticky top-[152px] z-20 h-16 border-b border-orange-100 bg-white/95 backdrop-blur">
            <div class="mx-auto flex h-full max-w-3xl items-center overflow-x-auto px-4">
                <div class="flex min-w-max gap-2">
                    @foreach($categories as $category)
                        <a href="#category-{{ $category->id }}" class="inline-flex h-10 items-center whitespace-nowrap rounded-full border border-orange-200 bg-orange-50 px-4 text-sm font-medium text-orange-600 hover:bg-orange-100">{{ $category->name }}</a>
                    @endforeach
                </div>
            </div>
        </nav>

        <main class="mx-auto max-w-3xl px-4 py-6">
            @foreach($categories as $category)
                <section id="category-{{ $category->id }}" class="mb-8 scroll-mt-[230px]">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-xl font-bold">{{ $category->name }}</h2>
                        <span class="text-sm text-gray-400">{{ count($products[$category->id] ?? []) }} 項</span>
                    </div>

                    <div class="space-y-4">
                        @forelse(($products[$category->id] ?? collect()) as $product)
                            <div class="rounded-3xl border border-orange-100 bg-white p-4 shadow-sm">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0 flex-1">
                                        <h3 class="text-lg font-semibold text-gray-900">{{ $product->name }}</h3>
                                        @if($product->description)
                                            <p class="mt-1 text-sm leading-6 text-gray-500">{{ $product->description }}</p>
                                        @endif
                                        <div class="mt-4 flex items-center gap-2">
                                            <span class="text-xl font-bold text-orange-600">NT$ {{ number_format($product->price) }}</span>
                                            @if($product->is_sold_out)
                                                <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-600">已售完</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-5 border-t border-orange-50 pt-4">
                                    @if(! $orderingAvailable)
                                        <div class="rounded-xl bg-slate-100 px-3 py-2 text-center text-sm font-medium text-slate-500">非營業時間，暫停點餐</div>
                                    @elseif($product->is_sold_out)
                                        <div class="rounded-xl bg-slate-100 px-3 py-2 text-center text-sm font-medium text-slate-500">此商品已售完</div>
                                    @else
                                        <form method="POST" action="{{ route('customer.dinein.cart.items.store', ['store' => $store, 'table' => $table]) }}" class="flex items-center justify-between gap-3">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                                            <div class="flex items-center gap-2">
                                                <label for="qty_{{ $product->id }}" class="text-sm font-medium text-gray-600">數量</label>
                                                <input id="qty_{{ $product->id }}" type="number" name="qty" value="1" min="1" class="w-20 rounded-xl border border-gray-300 px-3 py-2 text-center text-sm focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-200">
                                            </div>
                                            <button type="submit" class="inline-flex h-11 items-center justify-center rounded-xl bg-orange-500 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-600">加入購物車</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="rounded-3xl border border-dashed border-orange-200 bg-white px-5 py-8 text-center text-sm text-gray-500">這個分類目前沒有可點餐點。</div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </main>

        <div class="fixed inset-x-0 bottom-0 z-40 border-t border-orange-100 bg-white/95 px-4 py-4 backdrop-blur">
            <div class="mx-auto flex max-w-3xl items-center justify-between gap-3 rounded-2xl bg-gray-900 px-4 py-3 text-white shadow-lg">
                <div>
                    <p class="text-xs text-gray-300">桌號 {{ $table->table_no }}</p>
                    <p class="text-sm font-semibold">{{ $orderingAvailable ? '前往購物車並送出內用訂單' : '目前暫停點餐' }}</p>
                </div>
                <a href="{{ route('customer.dinein.cart.show', ['store' => $store, 'table' => $table]) }}" class="inline-flex h-11 items-center justify-center rounded-xl bg-orange-500 px-4 text-sm font-semibold text-white hover:bg-orange-600">查看購物車</a>
            </div>
        </div>
    </div>
</body>
</html>
