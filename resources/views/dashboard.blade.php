<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('admin.account_center') }}
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

                    @if(auth()->user()->isAdmin() || auth()->user()->hasActiveSubscription())
                        <a href="{{ route('admin.stores.index') }}"
                           class="inline-flex items-center rounded-lg bg-brand-primary px-4 py-2 font-semibold text-white hover:bg-brand-accent hover:text-brand-dark">
                            {{ __('admin.go_to_store_backend') }}
                        </a>
                    @elseif(auth()->user()->isMerchant())
                        <p class="text-sm text-slate-600">{{ __('admin.need_subscription') }}</p>
                        <a href="{{ route('merchant.subscription.index') }}"
                           class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 font-semibold text-slate-700 hover:bg-slate-100">
                            {{ __('admin.go_to_subscription') }}
                        </a>
                    @else
                        <p class="text-sm text-slate-600">{{ __('admin.customer_no_backend') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
