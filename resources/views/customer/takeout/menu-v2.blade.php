@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="mb-2">
                    <a href="{{ route('home') }}" class="inline-flex items-center text-sm font-medium text-slate-500 transition hover:text-slate-700">返回首頁</a>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <span class="inline-flex rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-200">外帶點餐</span>
                    @php($status = $store->orderingStatusLabel())
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset {{ $status === '營業中' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : '' }} {{ $status === '非營業時間' ? 'bg-amber-50 text-amber-700 ring-amber-200' : '' }} {{ $status === '停用' ? 'bg-slate-100 text-slate-600 ring-slate-200' : '' }}">{{ $status }}</span>
                    <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">營業時間 {{ $store->businessHoursLabel() }}</span>
                </div>

                <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">{{ $store->name }}</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">{{ $store->description ?: '歡迎使用外帶點餐，選好餐點後即可送出訂單。' }}</p>
            </div>

            <div class="flex shrink-0 items-center gap-3">
                <a href="{{ route('customer.takeout.cart.show', ['store' => $store]) }}" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">查看購物車</a>
            </div>
        </div>

        @if(! $orderingAvailable)
            <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 shadow-sm">{{ $store->orderingClosedMessage() }}</div>
        @endif

        @if(session('success'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 shadow-sm">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800 shadow-sm">
                <div class="mb-2 font-semibold">請先確認以下欄位</div>
                <ul class="space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($categories->isEmpty())
            <div class="rounded-3xl border border-slate-200 bg-white px-6 py-16 text-center shadow-sm">
                <h2 class="text-2xl font-bold text-slate-900">目前還沒有可點的餐點</h2>
                <p class="mt-3 text-slate-600">請稍後再回來看看。</p>
            </div>
        @else
            <div class="mb-8 overflow-x-auto rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
                <div class="flex min-w-max items-center gap-3">
                    @foreach($categories as $category)
                        <a href="#category-{{ $category->id }}" class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-100">{{ $category->name }}</a>
                    @endforeach
                </div>
            </div>

            <div class="space-y-10">
                @foreach($categories as $category)
                    <section id="category-{{ $category->id }}" class="scroll-mt-24">
                        <div class="mb-5 flex items-end justify-between gap-4">
                            <div>
                                <h2 class="text-2xl font-bold tracking-tight text-slate-900">{{ $category->name }}</h2>
                                <p class="mt-1 text-sm text-slate-500">{{ $category->products->count() }} 道餐點</p>
                            </div>
                        </div>

                        @if($category->products->count())
                            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                                @foreach($category->products as $product)
                                    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                                        <div class="p-5">
                                            <div class="flex items-start justify-between gap-4">
                                                <div>
                                                    <h3 class="text-lg font-bold text-slate-900">{{ $product->name }}</h3>
                                                    @if($product->description)
                                                        <p class="mt-2 text-sm text-slate-600">{{ $product->description }}</p>
                                                    @endif
                                                </div>

                                                <div class="text-right">
                                                    <div class="text-lg font-bold text-slate-900">NT$ {{ number_format($product->price) }}</div>
                                                    @if($product->is_sold_out)
                                                        <div class="mt-2 rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">已售完</div>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="mt-4">
                                                @if(!$orderingAvailable)
                                                    <div class="rounded-xl bg-slate-100 px-3 py-2 text-center text-sm font-medium text-slate-500">非營業時間，暫停點餐</div>
                                                @elseif($product->is_sold_out)
                                                    <div class="rounded-xl bg-slate-100 px-3 py-2 text-center text-sm font-medium text-slate-500">此商品已售完</div>
                                                @else
                                                    <form method="POST" action="{{ route('customer.takeout.cart.items.store', ['store' => $store]) }}" class="flex items-center gap-2">
                                                        @csrf
                                                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                                                        <input type="number" name="qty" value="1" min="1" class="w-20 rounded-xl border border-slate-300 px-2 py-2 text-center">
                                                        <button type="submit" class="flex-1 rounded-xl bg-indigo-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500">加入購物車</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="rounded-2xl border border-slate-200 bg-white px-5 py-8 text-sm text-slate-500 shadow-sm">這個分類目前沒有可點餐點。</div>
                        @endif
                    </section>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
