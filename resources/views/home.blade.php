@extends('layouts.app')

@section('content')
<div class="container py-5">

    {{-- Header --}}
    <div class="text-center mb-5">
        <h1 class="fw-bold mb-3">🍽️ DineFlow</h1>
        <p class="text-muted mb-0">
            選擇餐廳，立即開始點餐
        </p>
    </div>

    {{-- 搜尋 --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('home') }}" class="row g-3 align-items-center">
                <div class="col-md-10">
                    <input
                        type="text"
                        name="keyword"
                        value="{{ $keyword }}"
                        class="form-control"
                        placeholder="搜尋餐廳名稱 / 地址 / 描述">
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-dark">
                        搜尋
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- 餐廳列表 --}}
    @if($stores->count())
        <div class="row g-4">

            @foreach($stores as $store)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm border-0">

                        <div class="card-body d-flex flex-column">

                            {{-- 店名 --}}
                            <h4 class="fw-bold mb-2">
                                {{ $store->name }}
                            </h4>

                            {{-- 描述 --}}
                            @if(!empty($store->description))
                                <p class="text-muted small mb-3">
                                    {{ \Illuminate\Support\Str::limit($store->description, 100) }}
                                </p>
                            @endif

                            {{-- 資訊 --}}
                            <div class="small text-muted mb-3">
                                @if(!empty($store->address))
                                    <div class="mb-1">📍 {{ $store->address }}</div>
                                @endif

                                @if(!empty($store->phone))
                                    <div>📞 {{ $store->phone }}</div>
                                @endif
                            </div>

                            {{-- 狀態 --}}
                            <div class="mb-3">
                                @if($store->is_active)
                                    <span class="badge bg-success">營業中</span>
                                @else
                                    <span class="badge bg-secondary">未開放</span>
                                @endif
                            </div>

                            {{-- CTA --}}
                            <div class="mt-auto">
                                @if($store->is_active)
                                    <a href="{{ route('stores.enter', ['store' => $store->slug]) }}"
                                       class="btn btn-primary w-100">
                                        立即點餐
                                    </a>
                                @else
                                    <button class="btn btn-outline-secondary w-100" disabled>
                                        尚未開放
                                    </button>
                                @endif
                            </div>

                        </div>
                    </div>
                </div>
            @endforeach

        </div>

        {{-- 分頁 --}}
        <div class="mt-5">
            {{ $stores->links() }}
        </div>

    @else
        {{-- 無資料 --}}
        <div class="text-center py-5">
            <h5 class="text-muted mb-3">找不到餐廳</h5>
            <p class="text-muted">請嘗試不同關鍵字</p>
        </div>
    @endif

</div>
@endsection