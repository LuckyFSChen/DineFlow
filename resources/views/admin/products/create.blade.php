@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-4xl px-6 py-10 lg:px-8">
        <x-backend-header
            :title="__('admin.product_create_title')"
            :subtitle="__('admin.product_create_desc', ['store' => $store->name])"
        >
            <x-slot name="actions">
                <a href="{{ route('admin.stores.products.index', $store) }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-300/70 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20">{{ __('admin.back_to_products') }}</a>
            </x-slot>
        </x-backend-header>

        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <form method="POST" action="{{ route('admin.stores.products.store', $store) }}">
                @include('admin.products._form')
            </form>
        </div>
    </div>
</div>
@endsection
