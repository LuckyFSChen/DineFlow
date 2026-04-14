@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-4xl px-6 py-10 lg:px-8">
        <div class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ __('admin.product_create_title') }}</h1>
                <p class="mt-2 text-slate-600">{{ __('admin.product_create_desc', ['store' => $store->name]) }}</p>
            </div>
            <a href="{{ route('admin.stores.products.index', $store) }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">{{ __('admin.back_to_products') }}</a>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <form method="POST" action="{{ route('admin.stores.products.store', $store) }}">
                @include('admin.products._form')
            </form>
        </div>
    </div>
</div>
@endsection
