@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-6xl px-6 py-10 lg:px-8">
        <div class="mb-8 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-slate-900">廚師帳號管理</h1>
                <p class="mt-2 text-slate-600">店家：{{ $store->name }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.stores.kitchen', $store) }}" class="inline-flex items-center rounded-2xl border border-orange-300 bg-orange-50 px-4 py-3 text-sm font-semibold text-orange-700 transition hover:bg-orange-100">🍳 後廚看板</a>
                <a href="{{ route('admin.stores.index') }}" class="inline-flex items-center rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">返回店家列表</a>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <ul class="space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">新增廚師帳號</h2>
                <form method="POST" action="{{ route('admin.stores.chefs.store', $store) }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">姓名</label>
                        <input type="text" name="name" value="{{ old('name') }}" required class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">密碼</label>
                        <input type="password" name="password" required class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">確認密碼</label>
                        <input type="password" name="password_confirmation" required class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                    <button type="submit" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">建立廚師帳號</button>
                </form>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">現有廚師帳號</h2>
                <div class="mt-4 space-y-3">
                    @forelse($chefs as $chef)
                        <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div>
                                <div class="font-semibold text-slate-900">{{ $chef->name }}</div>
                                <div class="text-xs text-slate-500">{{ $chef->email }}</div>
                            </div>
                            <form method="POST" action="{{ route('admin.stores.chefs.destroy', [$store, $chef]) }}" onsubmit="return confirm('確定要刪除此廚師帳號嗎？')">
                                @csrf
                                @method('DELETE')
                                <button class="rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-500">刪除</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">尚未建立廚師帳號。</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
