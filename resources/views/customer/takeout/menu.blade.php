@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

        {{-- Top Bar --}}
        <div class="mb-8 flex flex-col gap-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="mb-2">
                    <a href="{{ route('home') }}"
                       class="inline-flex items-center text-sm font-medium text-slate-500 transition hover:text-slate-700">
                        ← {{ __('customer.back_home') }}
                    </a>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <span class="inline-flex rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-200">
                        {{ __('customer.takeout') }}
                    </span>

                    @if($store->is_active)
                        <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                            {{ __('customer.open') }}
                        </span>
                    @endif
                </div>

                <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                    {{ $store->name }}
                </h1>

                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
                    {{ __('customer.welcome_takeout_desc') }}
                </p>
            </div>

            <div class="flex shrink-0 items-center gap-3">
                <a href="{{ route('customer.takeout.cart.show', ['store' => $store]) }}"
                   class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">
                    {{ __('customer.view_cart') }}
                </a>
            </div>
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
                <div class="mb-2 font-semibold">{{ __('customer.confirm_fields') }}</div>
                <ul class="space-y-1">
                    @foreach($errors->all() as $error)
                        <li>• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Empty State --}}
        @if($categories->isEmpty())
            <div class="rounded-3xl border border-slate-200 bg-white px-6 py-16 text-center shadow-sm">
                <div class="mx-auto max-w-md">
                    <div class="text-5xl">🍽️</div>
                    <h2 class="mt-4 text-2xl font-bold text-slate-900">{{ __('customer.no_products_available') }}</h2>
                    <p class="mt-3 text-slate-600">
                        {{ __('customer.no_products_try_later') }}
                    </p>
                    <div class="mt-6">
                        <a href="{{ route('home') }}"
                           class="inline-flex items-center rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                            {{ __('customer.back_home') }}
                        </a>
                    </div>
                </div>
            </div>
        @else

            {{-- Category Nav --}}
            <div class="mb-8 overflow-x-auto rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
                <div class="flex min-w-max items-center gap-3">
                    @foreach($categories as $category)
                        <a href="#category-{{ $category->id }}"
                           class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-100">
                            {{ $category->name }}
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Categories --}}
            <div class="space-y-10">
                @foreach($categories as $category)
                    <section id="category-{{ $category->id }}" class="scroll-mt-24">
                        <div class="mb-5 flex items-end justify-between gap-4">
                            <div>
                                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                                    {{ $category->name }}
                                </h2>
                                <p class="mt-1 text-sm text-slate-500">
                                    {{ __('customer.total_products_prefix') }} {{ $category->products->count() }} {{ __('customer.total_products_suffix') }}
                                </p>
                            </div>
                        </div>

                        @if($category->products->count())
                            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                                @foreach($category->products as $product)
                                    <div class="group overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                                        {{-- Image --}}
                                        <div class="relative h-48 w-full overflow-hidden">
                                            <img src="{{ $product->image ?? 'https://source.unsplash.com/400x300/?food' }}"
                                                class="h-full w-full object-cover transition duration-500 group-hover:scale-110">

                                            {{-- overlay --}}
                                            <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>

                                            {{-- Price --}}
                                            <div class="absolute bottom-3 right-3 rounded-full bg-white/90 px-3 py-1 text-sm font-bold text-brand-primary shadow">
                                                NT$ {{ number_format($product->price) }}
                                            </div>
                                        </div>

                                        {{-- Content --}}
                                        <div class="p-5 flex flex-col h-full">

                                            <div class="flex-1">
                                                <h3 class="text-lg font-bold text-slate-900">
                                                    {{ $product->name }}
                                                </h3>
                                            </div>

                                            {{-- Actions --}}
                                            <div class="mt-4">
                                                @if(!$product->is_sold_out)
                                                    <form method="POST"
                                                        action="{{ route('customer.takeout.cart.items.store', ['store' => $store]) }}"
                                                        class="flex items-center gap-2">
                                                        @csrf

                                                        <input type="hidden" name="product_id" value="{{ $product->id }}">

                                                        <input type="number"
                                                            name="qty"
                                                            value="1"
                                                            min="1"
                                                            class="w-20 rounded-xl border border-slate-300 px-2 py-2 text-center">

                                                        <button type="submit"
                                                                class="flex-1 rounded-xl bg-brand-primary px-3 py-2 text-sm font-semibold text-white transition hover:bg-brand-dark">
                                                            {{ __('customer.add_to_cart') }}
                                                        </button>
                                                    </form>
                                                @else
                                                    <div class="rounded-xl bg-gray-200 px-3 py-2 text-center text-sm text-gray-500">
                                                        {{ __('customer.sold_out') }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="rounded-2xl border border-slate-200 bg-white px-5 py-8 text-sm text-slate-500 shadow-sm">
                                {{ __('customer.no_products_in_category') }}
                            </div>
                        @endif
                    </section>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection