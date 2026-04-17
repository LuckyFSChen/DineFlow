@extends('layouts.app')

@section('content')
@php
    $currencyCode = strtolower((string) ($selectedStore->currency ?? 'twd'));
    $currencySymbol = match ($currencyCode) {
        'vnd' => 'VND',
        'cny' => 'CNY',
        'usd' => 'USD',
        default => 'NT$',
    };
@endphp
<div class="min-h-screen bg-slate-50 py-8">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-bold text-slate-900">會員與優惠管理</h1>
            <p class="mt-2 text-sm text-slate-600">集點規則、會員消費分析、優惠券設定都在這裡。</p>

            @if(session('status'))
                <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <form method="GET" class="grid gap-3 md:grid-cols-5">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">店家</label>
                    <select name="store_id" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" @selected((int) $selectedStore->id === (int) $store->id)>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">起始日</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">結束日</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">會員搜尋</label>
                    <input type="text" name="keyword" value="{{ $keyword }}" placeholder="姓名 / 手機 / Email" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">套用篩選</button>
                </div>
            </form>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs text-slate-500">會員總數</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($totalMembers) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs text-slate-500">新增會員</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($newMembers) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs text-slate-500">回購會員</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($repeatMembers) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs text-slate-500">會員平均消費</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $currencySymbol }} {{ number_format($avgSpentPerMember) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs text-slate-500">發放點數</p>
                <p class="mt-2 text-2xl font-bold text-emerald-700">{{ number_format($pointsIssued) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs text-slate-500">兌換點數</p>
                <p class="mt-2 text-2xl font-bold text-rose-700">{{ number_format($pointsRedeemed) }}</p>
                <p class="mt-1 text-xs text-slate-500">Coupon 使用單數：{{ number_format($couponOrders) }}</p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">集點規則</h2>
                <form method="POST" action="{{ route('merchant.loyalty.settings.update') }}" class="mt-4 space-y-4">
                    @csrf
                    <input type="hidden" name="store_id" value="{{ $selectedStore->id }}">
                    <input type="hidden" name="start_date" value="{{ $startDate }}">
                    <input type="hidden" name="end_date" value="{{ $endDate }}">
                    <input type="hidden" name="keyword" value="{{ $keyword }}">

                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="loyalty_enabled" value="1" @checked($selectedStore->loyalty_enabled) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        啟用會員集點
                    </label>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">每消費多少金額回饋 1 點</label>
                        <input type="number" min="1" max="100000" name="points_per_amount" value="{{ old('points_per_amount', $selectedStore->points_per_amount ?? 100) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">儲存規則</button>
                </form>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">建立優惠券</h2>
                <form method="POST" action="{{ route('merchant.loyalty.coupons.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                    @csrf
                    <input type="hidden" name="store_id" value="{{ $selectedStore->id }}">
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-semibold text-slate-600">名稱</label>
                        <input type="text" name="name" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="新客首單優惠">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">代碼</label>
                        <input type="text" name="code" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm uppercase" placeholder="WELCOME100">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">折扣類型</label>
                        <select name="discount_type" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            <option value="fixed">固定金額</option>
                            <option value="percent">百分比</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">折扣值</label>
                        <input type="number" min="1" name="discount_value" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">最低消費</label>
                        <input type="number" min="0" name="min_order_amount" value="0" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">兌換點數成本（選填）</label>
                        <input type="number" min="0" name="points_cost" value="0" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">使用次數上限（選填）</label>
                        <input type="number" min="1" name="usage_limit" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">開始時間（選填）</label>
                        <input type="datetime-local" name="starts_at" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">結束時間（選填）</label>
                        <input type="datetime-local" name="ends_at" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <label class="sm:col-span-2 inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        建立後立即啟用
                    </label>
                    <div class="sm:col-span-2">
                        <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">新增優惠券</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Top 會員消費排行</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100 text-slate-700">
                        <tr>
                            <th class="px-3 py-2 text-left">會員</th>
                            <th class="px-3 py-2 text-right">總消費</th>
                            <th class="px-3 py-2 text-right">訂單數</th>
                            <th class="px-3 py-2 text-right">點數餘額</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topMembers as $member)
                            <tr class="border-t border-slate-100">
                                <td class="px-3 py-2">{{ $member->displayName() }}</td>
                                <td class="px-3 py-2 text-right">{{ $currencySymbol }} {{ number_format((int) $member->total_spent) }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format((int) $member->total_orders) }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format((int) $member->points_balance) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-6 text-center text-slate-500">目前還沒有會員資料</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">會員清單</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100 text-slate-700">
                        <tr>
                            <th class="px-3 py-2 text-left">姓名</th>
                            <th class="px-3 py-2 text-left">Email</th>
                            <th class="px-3 py-2 text-left">手機</th>
                            <th class="px-3 py-2 text-right">點數</th>
                            <th class="px-3 py-2 text-right">消費總額</th>
                            <th class="px-3 py-2 text-right">訂單數</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($members as $member)
                            <tr class="border-t border-slate-100">
                                <td class="px-3 py-2">{{ $member->name ?: '-' }}</td>
                                <td class="px-3 py-2">{{ $member->email ?: '-' }}</td>
                                <td class="px-3 py-2">{{ $member->phone ?: '-' }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format((int) $member->points_balance) }}</td>
                                <td class="px-3 py-2 text-right">{{ $currencySymbol }} {{ number_format((int) $member->total_spent) }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format((int) $member->total_orders) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-6 text-center text-slate-500">沒有符合條件的會員</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $members->links() }}
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">優惠券清單</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100 text-slate-700">
                        <tr>
                            <th class="px-3 py-2 text-left">名稱</th>
                            <th class="px-3 py-2 text-left">代碼</th>
                            <th class="px-3 py-2 text-left">折扣</th>
                            <th class="px-3 py-2 text-left">門檻/點數成本</th>
                            <th class="px-3 py-2 text-left">使用次數</th>
                            <th class="px-3 py-2 text-left">狀態</th>
                            <th class="px-3 py-2 text-right">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($coupons as $coupon)
                            <tr class="border-t border-slate-100">
                                <td class="px-3 py-2">{{ $coupon->name }}</td>
                                <td class="px-3 py-2 font-semibold">{{ $coupon->code }}</td>
                                <td class="px-3 py-2">{{ $coupon->discount_type === 'percent' ? $coupon->discount_value.'%' : $currencySymbol.' '.number_format((int) $coupon->discount_value) }}</td>
                                <td class="px-3 py-2">
                                    最低 {{ $currencySymbol }} {{ number_format((int) $coupon->min_order_amount) }}<br>
                                    點數成本 {{ number_format((int) $coupon->points_cost) }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ number_format((int) $coupon->used_count) }}
                                    @if($coupon->usage_limit !== null)
                                        / {{ number_format((int) $coupon->usage_limit) }}
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $coupon->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $coupon->is_active ? '啟用中' : '停用' }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <div class="inline-flex gap-2">
                                        <form method="POST" action="{{ route('merchant.loyalty.coupons.toggle', $coupon) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="rounded-lg border border-slate-300 bg-white px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                                                {{ $coupon->is_active ? '停用' : '啟用' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-6 text-center text-slate-500">尚未建立優惠券</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $coupons->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

