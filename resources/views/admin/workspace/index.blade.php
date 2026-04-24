@extends('layouts.app')

@section('title', $store->name.' | '.__('nav.merchant_order_short').'/'.__('admin.board_all_title'))

@php
    $storeRouteValue = static function ($value) {
        if ($value instanceof \App\Models\Store) {
            return $value->getRouteKey();
        }

        if (is_array($value)) {
            return $value['slug'] ?? $value['id'] ?? null;
        }

        return is_string($value) || is_int($value) ? $value : null;
    };

    $storeRoute = $storeRouteValue($store);
    $storeUrls = collect($availableStores ?? [])
        ->mapWithKeys(fn ($availableStore) => [
            (string) $storeRouteValue($availableStore) => route('admin.stores.workspace', ['store' => $storeRouteValue($availableStore)]),
        ])
        ->all();
@endphp

@section('content')
<div
    class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(14,165,233,0.14),_rgba(248,250,252,1)_38%),linear-gradient(180deg,_#f8fafc_0%,_#eef2ff_100%)]"
    x-data="merchantWorkspace({
        initialTab: @js($initialTab),
    })"
    x-init="init()"
>
    <div class="w-full px-0 py-0 min-h-screen flex flex-col">
        <div class="w-full flex-1 flex flex-col">
            <div class="border-b border-slate-200 bg-white/80 px-4 py-4 sm:px-6">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex items-start gap-3">
                        <a href="{{ route('admin.stores.index') }}" class="inline-flex items-center gap-1.5 rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <path d="M8 5 3 10l5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M4 10h13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                            {{ __('merchant_order.back_to_stores') }}
                        </a>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-700">{{ $store->name }}</p>
                            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ __('nav.merchant_order_short') }}/{{ __('admin.board_all_title') }}</h1>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        @if(($availableStores ?? collect())->count() > 1)
                            <label class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">
                                <span class="font-semibold text-slate-500">{{ __('admin.board_store') }}</span>
                                <div class="relative">
                                    <select
                                        class="appearance-none rounded-xl border border-slate-300 bg-white px-3 py-2 pr-10 text-sm font-semibold text-slate-800 focus:border-cyan-500 focus:outline-none"
                                        @change="goToStore($event.target.value)"
                                    >
                                        @foreach($availableStores as $availableStore)
                                            @php($availableStoreRoute = $storeRouteValue($availableStore))
                                            <option value="{{ $storeUrls[(string) $availableStoreRoute] ?? '' }}" @selected((string) $availableStoreRoute === (string) $storeRoute)>
                                                {{ $availableStore->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                        <path d="m6 8 4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                            </label>
                        @endif

                        <div class="inline-flex rounded-[1.1rem] border border-slate-200 bg-slate-100 p-1 shadow-inner">
                            <button
                                type="button"
                                @click="activate('orders')"
                                class="rounded-[0.9rem] px-4 py-2 text-sm font-semibold transition"
                                :class="isActive('orders') ? 'bg-cyan-600 text-white shadow-sm' : 'text-slate-600 hover:bg-white hover:text-slate-900'"
                            >
                                {{ __('nav.merchant_order') }}
                            </button>
                            <button
                                type="button"
                                @click="activate('boards')"
                                class="rounded-[0.9rem] px-4 py-2 text-sm font-semibold transition"
                                :class="isActive('boards') ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:bg-white hover:text-slate-900'"
                            >
                                {{ __('admin.board_all_title') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-0 flex-1 flex flex-col">
                <div
                    x-cloak
                    x-show="isActive('orders')"
                    style="{{ $initialTab === 'orders' ? '' : 'display: none;' }}"
                    class="flex-1"
                >
                    {!! $ordersPanelHtml !!}
                </div>

                <div
                    x-cloak
                    x-show="isActive('boards')"
                    style="{{ $initialTab === 'boards' ? '' : 'display: none;' }}"
                    class="flex flex-1 flex-col overflow-hidden bg-slate-950"
                >
                    {!! $boardsPanelHtml !!}
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function merchantWorkspace(config) {
        return {
            activeTab: config.initialTab === 'boards' ? 'boards' : 'orders',
            _navHandler: null,
            _popstateHandler: null,

            init() {
                this._navHandler = (event) => {
                    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                        return;
                    }

                    const link = event.target.closest('a[href]');
                    if (!link) {
                        return;
                    }

                    const targetUrl = new URL(link.href, window.location.origin);
                    if (targetUrl.origin !== window.location.origin || targetUrl.pathname !== window.location.pathname) {
                        return;
                    }

                    const tab = targetUrl.searchParams.get('tab');
                    if (tab !== 'orders' && tab !== 'boards') {
                        return;
                    }

                    event.preventDefault();
                    this.activate(tab);
                };

                document.addEventListener('click', this._navHandler);

                this._popstateHandler = () => {
                    const currentUrl = new URL(window.location.href);
                    this.activate(currentUrl.searchParams.get('tab'), false);
                };

                window.addEventListener('popstate', this._popstateHandler);
            },

            isActive(tab) {
                return this.activeTab === tab;
            },

            activate(tab, syncUrl = true) {
                const safeTab = tab === 'boards' ? 'boards' : 'orders';
                this.activeTab = safeTab;
                window.dispatchEvent(new CustomEvent('merchant-workspace-tab-changed', {
                    detail: { tab: safeTab },
                }));

                if (!syncUrl) {
                    return;
                }

                const nextUrl = new URL(window.location.href);
                nextUrl.searchParams.set('tab', safeTab);
                window.history.pushState({ tab: safeTab }, '', nextUrl.toString());
            },

            goToStore(baseUrl) {
                if (!baseUrl) {
                    return;
                }

                const nextUrl = new URL(baseUrl, window.location.origin);
                nextUrl.searchParams.set('tab', this.activeTab);
                window.location.href = nextUrl.toString();
            },
        };
    }
</script>
@endsection
