<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            個人設定
        </h2>
    </x-slot>

    <div class="py-10 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-6 bg-white shadow sm:rounded-lg border border-slate-200">
                <h3 class="text-lg font-semibold text-slate-900">角色設定說明</h3>
                <p class="mt-1 text-sm text-slate-600">目前角色：<span class="font-semibold text-slate-800">{{ strtoupper((string) $user->role) }}</span></p>

                <div class="mt-3 text-sm text-slate-700 space-y-1">
                    @if($user->isMerchant())
                        <p>你可以在此修改：姓名、Email、密碼。</p>
                        <p>門市名稱、聯絡方式、營業時間等店家資料，請到商家後台管理。</p>
                    @elseif($user->isAdmin())
                        <p>你可以在此修改：姓名、Email、密碼。</p>
                        <p>管理員權限與訂閱管理功能由系統控管，不可在此頁修改。</p>
                    @else
                        <p>你可以在此修改：姓名、Email、密碼。</p>
                    @endif
                </div>

                <div class="mt-4">
                    @if($user->isMerchant())
                        @if($user->hasActiveSubscription())
                            <a href="{{ route('admin.stores.index') }}" class="inline-flex items-center rounded-lg bg-brand-primary px-4 py-2 text-sm font-semibold text-white hover:bg-brand-accent hover:text-brand-dark">
                                前往商家後台
                            </a>
                        @else
                            <a href="{{ route('merchant.subscription.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                                前往訂閱方案
                            </a>
                        @endif
                    @elseif($user->isAdmin())
                        <a href="{{ route('super-admin.subscriptions.index') }}" class="inline-flex items-center rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                            前往最終後台
                        </a>
                    @endif
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            @if($user->isCustomer())
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <div class="max-w-xl">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
