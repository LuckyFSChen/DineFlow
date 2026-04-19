<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('profile.profile_settings') }}
        </h2>
    </x-slot>

    <div class="py-10 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('warning'))
                <div class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    {{ session('warning') }}
                </div>
            @endif

            @if((bool) ($user->must_change_password ?? false))
                <div class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    {{ __('profile.first_login_password_change_required') }}
                </div>
            @endif

            <div class="p-4 sm:p-6 bg-white shadow sm:rounded-lg border border-slate-200">
                <h3 class="text-lg font-semibold text-slate-900">{{ __('profile.role_settings_title') }}</h3>

                <div class="mt-3 text-sm text-slate-700 space-y-1">
                    @if($user->isMerchant())
                        <p>{{ __('profile.merchant_edit_hint') }}</p>
                        <p>{{ __('profile.merchant_store_hint') }}</p>
                    @elseif($user->isAdmin())
                        <p>{{ __('profile.admin_edit_hint') }}</p>
                        <p>{{ __('profile.admin_perm_hint') }}</p>
                    @else
                        <p>{{ __('profile.customer_edit_hint') }}</p>
                    @endif
                </div>

                <div class="mt-4">
                    @if($user->isMerchant())
                        @if($user->hasActiveSubscription())
                            <a href="{{ route('admin.stores.index') }}" class="inline-flex items-center rounded-lg bg-brand-primary px-4 py-2 text-sm font-semibold text-white hover:bg-brand-accent hover:text-brand-dark">
                                {{ __('profile.go_to_store_backend') }}
                            </a>
                        @else
                            <a href="{{ route('merchant.subscription.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                                {{ __('profile.go_to_subscription') }}
                            </a>
                        @endif
                    @elseif($user->isAdmin())
                        <a href="{{ route('super-admin.subscriptions.index') }}" class="inline-flex items-center rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                            {{ __('profile.go_to_super_admin') }}
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
