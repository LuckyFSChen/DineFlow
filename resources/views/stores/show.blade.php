@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="mb-4">
        <a href="{{ route('home') }}" class="btn btn-link ps-0 text-decoration-none">
            ← 返回首頁
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h1 class="fw-bold mb-3">{{ $store->name }}</h1>

            @if(!empty($store->description))
                <p class="text-muted mb-4">{{ $store->description }}</p>
            @endif

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="fw-semibold mb-2">餐廳資訊</div>
                        <div class="text-muted mb-1">地址：{{ $store->address ?? '未提供' }}</div>
                        <div class="text-muted mb-1">電話：{{ $store->phone ?? '未提供' }}</div>
                        <div class="text-muted">狀態：{{ $store->is_active ? '營業中' : '未開放' }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="fw-semibold mb-2">菜單概況</div>
                        <div class="text-muted mb-1">分類數：{{ $store->active_categories_count }}</div>
                        <div class="text-muted">可點商品數：{{ $store->active_products_count }}</div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('stores.menu', $store->slug) }}" class="btn btn-primary">
                    查看公開菜單
                </a>
            </div>
        </div>
    </div>
</div>
@endsection