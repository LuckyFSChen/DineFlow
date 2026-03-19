@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="mb-5 text-center">
        <h1 class="fw-bold mb-3">DineFlow 餐廳點餐平台</h1>
        <p class="text-muted mb-0">探索餐廳、查看菜單、快速進入線上點餐</p>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('home') }}" class="row g-3 align-items-center">
                <div class="col-md-10">
                    <input
                        type="text"
                        name="keyword"
                        value="{{ $keyword }}"
                        class="form-control"
                        placeholder="搜尋餐廳名稱、地址或描述">
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-dark">搜尋</button>
                </div>
            </form>
        </div>
    </div>

    @if($stores->count())
        <div class="row g-4">
            @foreach($stores as $store)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body d-flex flex-column">
                            <h4 class="card-title fw-bold">{{ $store->name }}</h4>

                            @if(!empty($store->description))
                                <p class="card-text text-muted">
                                    {{ \Illuminate\Support\Str::limit($store->description, 100) }}
                                </p>
                            @endif

                            <div class="small text-muted mb-3">
                                @if(!empty($store->address))
                                    <div class="mb-1">📍 {{ $store->address }}</div>
                                @endif
                                @if(!empty($store->phone))
                                    <div>📞 {{ $store->phone }}</div>
                                @endif
                            </div>

                            <div class="mt-auto d-flex gap-2">
                                <a href="{{ route('stores.show', $store->slug) }}" class="btn btn-outline-secondary btn-sm">
                                    查看餐廳
                                </a>
                                <a href="{{ route('stores.menu', $store->slug) }}" class="btn btn-primary btn-sm">
                                    查看菜單
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $stores->links() }}
        </div>
    @else
        <div class="alert alert-light border text-center py-5">
            目前沒有符合條件的餐廳
        </div>
    @endif
</div>
@endsection