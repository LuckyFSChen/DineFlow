@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-4xl px-6 py-10 lg:px-8">
        <div class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-slate-900">新增商品</h1>
                <p class="mt-2 text-slate-600">店家：{{ $store->name }}，先填基本資訊，再設定銷售狀態與選配。</p>
            </div>
            <a href="{{ route('admin.stores.products.index', $store) }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">返回商品列表</a>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <form method="POST" action="{{ route('admin.stores.products.store', $store) }}">
                @include('admin.products._form')
            </form>
        </div>
    </div>
</div>
@endsection
