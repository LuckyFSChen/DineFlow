@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-7xl px-6 py-10 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-slate-900">店家管理</h1>
                <p class="mt-2 text-slate-600">管理店家基本資訊、營業時間與接單狀態。</p>
            </div>

            <a href="{{ route('admin.stores.create') }}"
               class="inline-flex items-center justify-center rounded-2xl bg-brand-primary px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-accent hover:text-brand-dark">
                新增店家
            </a>
        </div>

        @if(session('success'))
            <div class="mb-6 rounded-2xl border border-brand-accent/40 bg-brand-highlight/25 px-4 py-3 text-sm font-medium text-brand-dark">
                {{ session('success') }}
            </div>
        @endif

        <div class="mb-6 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('admin.stores.index') }}" class="flex flex-col gap-3 md:flex-row">
                <input type="text"
                       name="keyword"
                       value="{{ $keyword ?? '' }}"
                       placeholder="搜尋店家名稱、slug、地址或電話"
                       class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                <button type="submit"
                        class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                    搜尋
                </button>
            </form>
        </div>

        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-4 text-left font-semibold text-slate-700">店家</th>
                            <th class="px-6 py-4 text-left font-semibold text-slate-700">Slug</th>
                            <th class="px-6 py-4 text-left font-semibold text-slate-700">電話</th>
                            <th class="px-6 py-4 text-left font-semibold text-slate-700">營業時間</th>
                            <th class="px-6 py-4 text-left font-semibold text-slate-700">目前狀態</th>
                            <th class="px-6 py-4 text-right font-semibold text-slate-700">操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($stores as $store)
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-900">{{ $store->name }}</div>
                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ \Illuminate\Support\Str::limit($store->description, 50) ?: '尚未填寫描述' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-600">{{ $store->slug }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $store->phone ?: '-' }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $store->businessHoursLabel() }}</td>
                                <td class="px-6 py-4">
                                    @php($status = $store->orderingStatusLabel())
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset
                                        {{ $status === '營業中' ? 'bg-brand-highlight/30 text-brand-dark ring-brand-accent/40' : '' }}
                                        {{ $status === '非營業時間' ? 'bg-amber-50 text-amber-700 ring-amber-200' : '' }}
                                        {{ $status === '停用' ? 'bg-slate-100 text-slate-600 ring-slate-200' : '' }}">
                                        {{ $status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.stores.edit', $store) }}"
                                           class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                            編輯
                                        </a>

                                        <form method="POST" action="{{ route('admin.stores.destroy', $store) }}" onsubmit="return confirm('確定要刪除此店家嗎？')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-500">
                                                刪除
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                    目前沒有符合條件的店家。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-6 py-4">
                {{ $stores->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
