@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="mb-8 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-2">
                <a href="{{ route('customer.takeout.menu', ['store' => $store]) }}"
                   class="inline-flex items-center text-sm font-medium text-slate-500 transition hover:text-slate-700">
                    ← 回外帶菜單
                </a>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <span class="inline-flex rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-200">
                    外帶購物車
                </span>
            </div>

            <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                {{ $store->name }}
            </h1>

            <p class="mt-3 text-sm leading-6 text-slate-600 sm:text-base">
                確認餐點內容後即可送出外帶訂單。
            </p>
        </div>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800 shadow-sm">
                <div class="mb-2 font-semibold">請先確認以下欄位：</div>
                <ul class="space-y-1">
                    @foreach($errors->all() as $error)
                        <li>• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(!empty($cart))
            <div class="grid gap-8 lg:grid-cols-3">
                {{-- Cart Items --}}
                <div class="lg:col-span-2">
                    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-6 py-5">
                            <h2 class="text-xl font-bold text-slate-900">購物車內容</h2>
                            <p class="mt-1 text-sm text-slate-500">
                                共 {{ count($cart) }} 項商品
                            </p>
                        </div>

                        <div class="divide-y divide-slate-200">
                            @foreach($cart as $item)
                                <div class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0">
                                        <h3 class="truncate text-lg font-semibold text-slate-900">
                                            {{ $item['product_name'] }}
                                        </h3>
                                        <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-slate-500">
                                            <span>單價 NT$ {{ number_format($item['price']) }}</span>
                                            <span>數量 × {{ $item['qty'] }}</span>
                                        </div>
                                    </div>

                                    <div class="text-lg font-bold text-slate-900">
                                        NT$ {{ number_format($item['subtotal']) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Summary + Checkout --}}
                <div class="lg:col-span-1">
                    <div class="sticky top-6 space-y-6">
                        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 class="text-xl font-bold text-slate-900">訂單摘要</h2>

                            <div class="mt-5 space-y-4 text-sm">
                                <div class="flex items-center justify-between text-slate-600">
                                    <span>商品小計</span>
                                    <span>NT$ {{ number_format($total) }}</span>
                                </div>

                                <div class="border-t border-slate-200 pt-4">
                                    <div class="flex items-center justify-between text-lg font-bold text-slate-900">
                                        <span>總計</span>
                                        <span>NT$ {{ number_format($total) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 class="text-xl font-bold text-slate-900">填寫訂單資訊</h2>

                            <form method="POST"
                                  action="{{ route('customer.takeout.cart.checkout', ['store' => $store]) }}"
                                  class="mt-6 space-y-5">
                                @csrf

                                <div>
                                    <label for="customer_name" class="mb-2 block text-sm font-medium text-slate-700">
                                        姓名
                                    </label>
                                    <input id="customer_name"
                                           type="text"
                                           name="customer_name"
                                           value="{{ old('customer_name') }}"
                                           class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                                </div>

                                <div>
                                    <label for="customer_email" class="mb-2 block text-sm font-medium text-slate-700">
                                        Email
                                    </label>
                                    <input id="customer_email"
                                           type="email"
                                           name="customer_email"
                                           value="{{ old('customer_email') }}"
                                           class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                                </div>

                                <div>
                                    <label for="customer_phone" class="mb-2 block text-sm font-medium text-slate-700">
                                        電話
                                    </label>
                                    <input id="customer_phone"
                                           type="text"
                                           name="customer_phone"
                                           value="{{ old('customer_phone') }}"
                                           class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                                </div>

                                <div>
                                    <label for="note" class="mb-2 block text-sm font-medium text-slate-700">
                                        備註
                                    </label>
                                    <textarea id="note"
                                              name="note"
                                              rows="4"
                                              class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100">{{ old('note') }}</textarea>
                                </div>

                                <button type="submit"
                                        class="inline-flex w-full items-center justify-center rounded-2xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                                    送出外帶訂單
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @else
            {{-- Empty Cart --}}
            <div class="rounded-3xl border border-slate-200 bg-white px-6 py-16 text-center shadow-sm">
                <div class="mx-auto max-w-md">
                    <div class="text-5xl">🛒</div>
                    <h2 class="mt-4 text-2xl font-bold text-slate-900">購物車目前是空的</h2>
                    <p class="mt-3 text-slate-600">
                        先回到菜單挑選幾樣餐點，再回來確認訂單內容。
                    </p>

                    <div class="mt-6">
                        <a href="{{ route('customer.takeout.menu', ['store' => $store]) }}"
                           class="inline-flex items-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                            回到菜單
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection