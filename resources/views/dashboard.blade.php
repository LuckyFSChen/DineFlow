<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            帳號中心
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-4">
                    @if(session('error'))
                        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div>
                        <p class="text-sm text-slate-500">目前帳號角色</p>
                        <p class="mt-1 text-lg font-semibold text-slate-900">{{ strtoupper(auth()->user()->role) }}</p>
                    </div>

                    @if(auth()->user()->isMerchant())
                        <div>
                            <p class="text-sm text-slate-500">商家訂閱到期日</p>
                            <p class="mt-1 text-lg font-semibold text-slate-900">
                                {{ auth()->user()->subscription_ends_at ? auth()->user()->subscription_ends_at->format('Y-m-d H:i') : '尚未啟用' }}
                            </p>
                        </div>
                    @endif

                    @if(auth()->user()->isAdmin() || auth()->user()->hasActiveSubscription())
                        <a href="{{ route('admin.stores.index') }}"
                           class="inline-flex items-center rounded-lg bg-brand-primary px-4 py-2 font-semibold text-white hover:bg-brand-accent hover:text-brand-dark">
                            進入商家後台
                        </a>
                    @elseif(auth()->user()->isMerchant())
                        <p class="text-sm text-slate-600">商家帳號需先完成訂閱，才可進入商家後台。</p>
                        <a href="{{ route('merchant.subscription.index') }}"
                           class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 font-semibold text-slate-700 hover:bg-slate-100">
                            前往訂閱方案
                        </a>
                    @else
                        <p class="text-sm text-slate-600">顧客帳號可使用點餐流程，不可進入商家管理。</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
