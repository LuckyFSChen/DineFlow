@extends('layouts.app')

@section('content')
@php
    $currencyCode = strtolower((string) ($store->currency ?? 'twd'));
    $currencySymbol = match ($currencyCode) {
        'vnd' => 'VND',
        'cny' => 'CNY',
        'usd' => 'USD',
        default => 'NT$',
    };
@endphp

<div class="min-h-screen bg-slate-50" data-uber-mapping-page>
    <div class="mx-auto max-w-7xl px-6 py-10 lg:px-8">
        <div class="admin-hero mb-6 rounded-3xl px-5 py-5 md:px-7">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ __('uber_eats.menu_mapping_title') }}</h1>
                    <p class="mt-2 text-slate-600">{{ __('uber_eats.menu_mapping_subtitle', ['store' => $store->name]) }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.stores.products.index', $store) }}"
                       class="inline-flex items-center justify-center rounded-2xl border border-slate-900 bg-slate-800 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700">
                        {{ __('uber_eats.products') }}
                    </a>
                    <form method="POST" action="{{ route('admin.stores.uber-eats-menu.sync', $store) }}" data-loading-text="{{ __('uber_eats.syncing') }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500">
                            {{ __('uber_eats.sync_menu') }}
                        </button>
                    </form>
                </div>
            </div>

            <div class="mt-5 grid gap-4 md:grid-cols-4">
                <div class="admin-kpi rounded-2xl p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('uber_eats.uber_items') }}</p>
                    <p class="value mt-2 text-slate-900">{{ $mappings->count() }}</p>
                </div>
                <div class="admin-kpi rounded-2xl p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('uber_eats.mapped') }}</p>
                    <p class="value mt-2 text-emerald-700">{{ $mappedCount }}</p>
                </div>
                <div class="admin-kpi rounded-2xl p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('uber_eats.needs_mapping') }}</p>
                    <p class="value mt-2 text-amber-700">{{ $unmappedCount }}</p>
                </div>
                <div class="admin-kpi rounded-2xl p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('uber_eats.store_id') }}</p>
                    <p class="mt-2 truncate font-mono text-sm font-bold text-slate-900">{{ $store->uber_eats_store_id ?: '-' }}</p>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">
                {{ session('error') }}
            </div>
        @endif

        <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-bold text-slate-900">{{ __('uber_eats.how_mapping_works') }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ __('uber_eats.how_mapping_works_desc') }}</p>
            </div>
            <p class="rounded-full bg-cyan-50 px-3 py-1 text-xs font-bold text-cyan-700">{{ __('uber_eats.map_every_active_item') }}</p>
        </div>

        @if ($mappings->isEmpty())
            <section class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center shadow-sm">
                <h2 class="text-xl font-bold text-slate-900">{{ __('uber_eats.no_menu_items_title') }}</h2>
                <p class="mx-auto mt-2 max-w-2xl text-sm text-slate-500">{{ __('uber_eats.no_menu_items_desc') }}</p>
                <form method="POST" action="{{ route('admin.stores.uber-eats-menu.sync', $store) }}" class="mt-5" data-loading-text="{{ __('uber_eats.syncing') }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500">
                        {{ __('uber_eats.sync_menu') }}
                    </button>
                </form>
            </section>
        @else
            <form method="POST" action="{{ route('admin.stores.uber-eats-menu.update', $store) }}" class="space-y-5" data-loading-text="{{ __('uber_eats.saving') }}">
                @csrf
                @method('PUT')

                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                        <div class="grid gap-3 text-xs font-bold uppercase tracking-wide text-slate-500 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)_140px_minmax(240px,1fr)_120px]">
                            <div>{{ __('uber_eats.uber_item') }}</div>
                            <div>{{ __('uber_eats.uber_category') }}</div>
                            <div>{{ __('uber_eats.uber_price') }}</div>
                            <div>{{ __('uber_eats.dineflow_product') }}</div>
                            <div>{{ __('uber_eats.status') }}</div>
                        </div>
                    </div>

                    <div class="divide-y divide-slate-100">
                        @foreach ($mappings as $mapping)
                            <div class="grid gap-3 px-5 py-4 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)_140px_minmax(240px,1fr)_120px] lg:items-center">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-slate-900">{{ $mapping->external_item_name ?: __('uber_eats.unnamed_item') }}</p>
                                    <p class="mt-1 truncate font-mono text-xs text-slate-500">{{ $mapping->external_item_id }}</p>
                                </div>

                                <div class="min-w-0">
                                    <p class="truncate text-sm text-slate-700">{{ $mapping->external_category_name ?: '-' }}</p>
                                    @if($mapping->last_seen_at)
                                        <p class="mt-1 text-xs text-slate-400">{{ __('uber_eats.seen_at', ['time' => $mapping->last_seen_at->format('Y-m-d H:i')]) }}</p>
                                    @endif
                                </div>

                                <div>
                                    <p class="text-sm font-semibold text-slate-800">
                                        @if($mapping->external_price !== null)
                                            {{ $mapping->external_currency ?: $currencySymbol }} {{ number_format((int) $mapping->external_price) }}
                                        @else
                                            -
                                        @endif
                                    </p>
                                </div>

                                <div>
                                    <select name="mappings[{{ $mapping->id }}]"
                                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                                        <option value="">{{ __('uber_eats.unmapped') }}</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}" @selected((int) $mapping->product_id === (int) $product->id)>
                                                {{ $product->name }} | {{ $currencySymbol }} {{ number_format((int) $product->price) }}
                                                @if($product->category)
                                                    | {{ $product->category->name }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    @if($mapping->product_id)
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">{{ __('uber_eats.mapped') }}</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700">{{ __('uber_eats.needs_map') }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="sticky bottom-4 z-10 flex justify-end">
                    <button type="submit"
                            class="rounded-2xl bg-indigo-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-indigo-200 transition hover:bg-indigo-500">
                        {{ __('uber_eats.save_mapping') }}
                    </button>
                </div>
            </form>
        @endif
    </div>

</div>
@endsection
