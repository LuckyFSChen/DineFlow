@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-4xl px-6 py-10 lg:px-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">編輯店家</h1>
            <p class="mt-2 text-slate-600">更新 {{ $store->name }} 的資料、營業時間與接單狀態。</p>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <form method="POST" action="{{ route('admin.stores.update', $store) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('admin.stores._form-preview-v2')
            </form>
        </div>
    </div>
</div>
@endsection
