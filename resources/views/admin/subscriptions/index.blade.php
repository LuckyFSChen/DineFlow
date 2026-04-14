@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">最終後台：商家訂閱管理</h1>
            <p class="mt-2 text-slate-600">由管理員啟用、續期或停用商家訂閱。</p>
        </div>

        @if(session('success'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">商家帳號</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">目前方案</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">到期日</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">狀態</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">管理</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($merchants as $merchant)
                            <tr>
                                <td class="px-4 py-4">
                                    <div class="font-semibold text-slate-900">{{ $merchant->name }}</div>
                                    <div class="text-slate-500">{{ $merchant->email }}</div>
                                </td>
                                <td class="px-4 py-4 text-slate-700">{{ $merchant->subscriptionPlan?->name ?? '-' }}</td>
                                <td class="px-4 py-4 text-slate-700">{{ $merchant->subscription_ends_at ? $merchant->subscription_ends_at->format('Y-m-d H:i') : '-' }}</td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $merchant->hasActiveSubscription() ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                        {{ $merchant->hasActiveSubscription() ? '有效' : '未啟用/已到期' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <form method="POST" action="{{ route('super-admin.subscriptions.update', $merchant) }}" class="flex flex-wrap items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <select name="plan_id" class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                                            @foreach($plans as $plan)
                                                <option value="{{ $plan->id }}" @selected($merchant->subscription_plan_id === $plan->id)>
                                                    {{ $plan->name }} ({{ $plan->duration_days }}天)
                                                </option>
                                            @endforeach
                                        </select>

                                        <select name="action" class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                                            <option value="activate">啟用/續期</option>
                                            <option value="expire">設為到期</option>
                                        </select>

                                        <button type="submit" class="rounded-lg bg-slate-900 px-3 py-1.5 text-sm font-semibold text-white hover:bg-slate-800">更新</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500">目前沒有商家帳號。</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-4 py-3">
                {{ $merchants->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
