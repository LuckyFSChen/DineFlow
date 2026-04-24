@php
    $profileUsesAdminShell = request()->routeIs('profile.*')
        && Auth::check()
        && ! Auth::user()?->isCustomer();
    $isAdminArea = request()->routeIs('admin.*')
        || request()->routeIs('super-admin.*')
        || request()->routeIs('merchant.*')
        || $profileUsesAdminShell;
    $isDineInCartPage = request()->routeIs('customer.dinein.cart.*');
    $isWorkspacePage = request()->routeIs('admin.stores.workspace');
    $workspaceTab = request()->query('tab') === 'boards' ? 'boards' : 'orders';
    $isBoardPage = request()->routeIs('admin.stores.boards*')
        || request()->routeIs('admin.stores.kitchen*')
        || request()->routeIs('admin.stores.cashier*')
        || ($isWorkspacePage && $workspaceTab === 'boards');
    $isMerchantOrderPage = request()->routeIs('admin.stores.orders.*')
        || ($isWorkspacePage && $workspaceTab === 'orders');
    $isStoreBackendPage = request()->routeIs('admin.stores.*')
        && ! $isBoardPage
        && ! $isMerchantOrderPage
        && ! $isWorkspacePage;

    $navUser = Auth::user();
    $navFeatures = \App\Support\NavFeature::all();
    $subscriptionFeatureEnabled = $navUser?->isAdmin()
        ? true
        : ($navFeatures[\App\Support\NavFeature::SUBSCRIPTION] ?? true);
    $financialReportFeatureEnabled = $navFeatures[\App\Support\NavFeature::FINANCIAL_REPORT] ?? true;
    $orderHistoryFeatureEnabled = $navFeatures[\App\Support\NavFeature::ORDER_HISTORY] ?? true;
    $invoiceCenterFeatureEnabled = $navFeatures[\App\Support\NavFeature::INVOICE_CENTER] ?? true;
    $loyaltyFeatureEnabled = $navFeatures[\App\Support\NavFeature::LOYALTY] ?? true;
    $storeBackendFeatureEnabled = $navFeatures[\App\Support\NavFeature::STORE_BACKEND] ?? true;
    $boardsFeatureEnabled = $navFeatures[\App\Support\NavFeature::BOARDS] ?? true;
    $routeStoreParam = request()->route('store');

    $resolvedRouteStore = null;
    if ($routeStoreParam instanceof \App\Models\Store) {
        $resolvedRouteStore = $routeStoreParam;
    } elseif (is_numeric($routeStoreParam)) {
        $resolvedRouteStore = \App\Models\Store::query()->find((int) $routeStoreParam);
    }

    $resolvedMerchantOrderStore = $resolvedRouteStore;

    if ($resolvedRouteStore && ! $resolvedRouteStore->is_active) {
        $resolvedRouteStore = null;
    }

    $storeRouteValue = static function ($store) {
        if ($store instanceof \App\Models\Store) {
            return $store->getRouteKey();
        }

        if (is_array($store)) {
            return $store['slug'] ?? $store['id'] ?? null;
        }

        if (is_string($store) || is_int($store)) {
            return $store;
        }

        return null;
    };

    $firstStore = fn () => \App\Models\Store::query()
        ->orderBy('id')
        ->first();

    $firstOpenStore = fn () => \App\Models\Store::query()
        ->where('is_active', true)
        ->orderBy('id')
        ->first();

    $merchantFirstStore = $navUser?->isMerchant()
        ? $navUser->stores()->orderBy('id')->first()
        : null;

    $merchantOpenStore = $navUser?->isMerchant()
        ? $navUser->stores()->where('is_active', true)->orderBy('id')->first()
        : null;

    $chefOpenStore = ($navUser?->isChef() && $navUser->store && $navUser->store->is_active)
        ? $navUser->store
        : null;

    $cashierOpenStore = ($navUser?->isCashier() && $navUser->store && $navUser->store->is_active)
        ? $navUser->store
        : null;

    $boardNavStore = null;
    if ($navUser?->isMerchant()) {
        $boardNavStore = $merchantOpenStore;
    } elseif ($navUser?->isChef()) {
        $boardNavStore = $chefOpenStore;
    } elseif ($navUser?->isCashier()) {
        $boardNavStore = $cashierOpenStore;
    } elseif ($navUser?->isAdmin() || $navUser?->hasActiveSubscription()) {
        $boardNavStore = $resolvedRouteStore ?: $firstOpenStore();
    }

    $showBoardNav = $boardsFeatureEnabled
        && $boardNavStore
        && ($navUser?->isAdmin()
            || $navUser?->hasActiveSubscription()
            || $navUser?->isChef()
            || $navUser?->isCashier());

    $boardNavStoreRoute = $storeRouteValue($boardNavStore);

    $merchantOrderNavStore = null;
    if ($navUser?->isMerchant()) {
        $merchantOrderNavStore = $resolvedMerchantOrderStore;

        if ($merchantOrderNavStore && (int) $merchantOrderNavStore->user_id !== (int) $navUser->id) {
            $merchantOrderNavStore = null;
        }

        $merchantOrderNavStore = $merchantOrderNavStore ?: $merchantFirstStore;
    } elseif ($navUser?->isAdmin()) {
        $merchantOrderNavStore = $resolvedMerchantOrderStore ?: $firstStore();
    }

    $showStoreBackendNav = $storeBackendFeatureEnabled
        && ($navUser?->isAdmin() || $navUser?->hasActiveSubscription());
    $showMerchantOrderNav = $showStoreBackendNav && $merchantOrderNavStore;
    $merchantOrderNavStoreRoute = $storeRouteValue($merchantOrderNavStore);
    $useMerchantWorkspaceNav = $boardsFeatureEnabled
        && $showMerchantOrderNav
        && $showBoardNav
        && ($navUser?->isAdmin() || $navUser?->isMerchant());
    $merchantOrderNavHref = $showMerchantOrderNav && $merchantOrderNavStoreRoute
        ? ($useMerchantWorkspaceNav
            ? route('admin.stores.workspace', ['store' => $merchantOrderNavStoreRoute, 'tab' => 'orders'])
            : route('admin.stores.orders.create', ['store' => $merchantOrderNavStoreRoute]))
        : null;
    $showCombinedMerchantWorkspaceNav = $useMerchantWorkspaceNav && (bool) $merchantOrderNavStoreRoute;
    $combinedMerchantWorkspaceTab = $isBoardPage ? 'boards' : 'orders';
    $combinedMerchantWorkspaceHref = $showCombinedMerchantWorkspaceNav
        ? route('admin.stores.workspace', ['store' => $merchantOrderNavStoreRoute, 'tab' => $combinedMerchantWorkspaceTab])
        : null;
    $isCombinedMerchantWorkspacePage = $isMerchantOrderPage || $isBoardPage;
    $boardNavHref = $showBoardNav && $boardNavStoreRoute
        ? ($useMerchantWorkspaceNav
            ? route('admin.stores.workspace', ['store' => $boardNavStoreRoute, 'tab' => 'boards'])
            : route('admin.stores.boards', ['store' => $boardNavStoreRoute]))
        : null;

    $canAccessMerchantConsole = $navUser?->isMerchant() || $navUser?->isAdmin();
    $subscriptionNavHref = null;
    $subscriptionNavActive = false;

    if ($navUser?->isAdmin()) {
        $subscriptionNavHref = route('super-admin.subscriptions.index', ['tab' => 'features']);
        $subscriptionNavActive = request()->routeIs('super-admin.subscriptions.*');
    } elseif ($navUser?->isMerchant()) {
        $subscriptionNavHref = route('merchant.subscription.index');
        $subscriptionNavActive = request()->routeIs('merchant.subscription.*');
    }

    $merchantConsoleStoresQuery = null;
    if ($navUser?->isAdmin()) {
        $merchantConsoleStoresQuery = \App\Models\Store::query();
    } elseif ($navUser?->isMerchant()) {
        $merchantConsoleStoresQuery = $navUser->stores();
    }

    $merchantConsoleHasStores = $merchantConsoleStoresQuery
        ? (clone $merchantConsoleStoresQuery)->exists()
        : false;
    $settingsDropdownActiveClasses = 'bg-slate-100 font-semibold text-slate-900';

    $navFeatureConfigurations = \App\Support\NavFeature::configurations();
    $defaultNavFeatureLayouts = \App\Support\NavFeature::defaultLayouts();

    $featurePlacement = static function (string $feature) use ($navFeatureConfigurations, $defaultNavFeatureLayouts): string {
        return (string) ($navFeatureConfigurations[$feature]['placement']
            ?? $defaultNavFeatureLayouts[$feature]['placement']
            ?? \App\Support\NavFeature::PLACEMENT_DROPDOWN);
    };

    $featureOrder = static function (string $feature) use ($navFeatureConfigurations, $defaultNavFeatureLayouts): int {
        return (int) ($navFeatureConfigurations[$feature]['order']
            ?? $defaultNavFeatureLayouts[$feature]['order']
            ?? 999);
    };

    $featureNavItems = [
        \App\Support\NavFeature::STORE_BACKEND => [
            'label' => __('nav.store_backend'),
            'href' => $showStoreBackendNav ? route('admin.stores.index') : null,
            'active' => $isStoreBackendPage,
            'enabled' => $showStoreBackendNav,
            'requires_dropdown_disabled_state' => false,
            'disabled_reason' => null,
        ],
        \App\Support\NavFeature::ORDER_HISTORY => [
            'label' => __('nav.order_history'),
            'href' => $orderHistoryFeatureEnabled ? route('merchant.orders.index') : null,
            'active' => request()->routeIs('merchant.orders.*'),
            'enabled' => $canAccessMerchantConsole && $orderHistoryFeatureEnabled,
            'requires_dropdown_disabled_state' => false,
            'disabled_reason' => null,
        ],
        \App\Support\NavFeature::LOYALTY => [
            'label' => __('nav.loyalty'),
            'href' => ($loyaltyFeatureEnabled && $merchantConsoleHasStores) ? route('merchant.loyalty.index') : null,
            'active' => request()->routeIs('merchant.loyalty.*'),
            'enabled' => $canAccessMerchantConsole && $loyaltyFeatureEnabled,
            'requires_dropdown_disabled_state' => true,
            'disabled_reason' => __('merchant.error_store_required_for_loyalty'),
        ],
        \App\Support\NavFeature::INVOICE_CENTER => [
            'label' => __('nav.invoice_center'),
            'href' => $invoiceCenterFeatureEnabled ? route('merchant.invoices.index') : null,
            'active' => request()->routeIs('merchant.invoices.*'),
            'enabled' => $canAccessMerchantConsole && $invoiceCenterFeatureEnabled,
            'requires_dropdown_disabled_state' => false,
            'disabled_reason' => null,
        ],
        \App\Support\NavFeature::SUBSCRIPTION => [
            'label' => __('nav.subscription'),
            'href' => ($subscriptionFeatureEnabled && $subscriptionNavHref) ? $subscriptionNavHref : null,
            'active' => $subscriptionNavActive,
            'enabled' => $canAccessMerchantConsole && $subscriptionFeatureEnabled && (bool) $subscriptionNavHref,
            'requires_dropdown_disabled_state' => false,
            'disabled_reason' => null,
        ],
        \App\Support\NavFeature::FINANCIAL_REPORT => [
            'label' => __('nav.financial_report'),
            'href' => $financialReportFeatureEnabled ? route('merchant.reports.financial') : null,
            'active' => request()->routeIs('merchant.reports.*'),
            'enabled' => $canAccessMerchantConsole && $financialReportFeatureEnabled,
            'requires_dropdown_disabled_state' => false,
            'disabled_reason' => null,
        ],
        \App\Support\NavFeature::BOARDS => [
            'label' => __('admin.board_all_title'),
            'href' => $boardNavHref,
            'active' => $isBoardPage,
            'enabled' => $showBoardNav && (bool) $boardNavHref && ! $showCombinedMerchantWorkspaceNav,
            'requires_dropdown_disabled_state' => false,
            'disabled_reason' => null,
        ],
    ];

    $featureNavItems = collect($featureNavItems)
        ->map(function (array $item, string $feature) use ($featurePlacement, $featureOrder): array {
            $placement = $featurePlacement($feature);
            $item['feature'] = $feature;
            $item['placement'] = $placement;
            $item['order'] = $featureOrder($feature);
            $item['show_as_link'] = $item['enabled'] && $placement === \App\Support\NavFeature::PLACEMENT_LINKS && (bool) $item['href'];
            $item['show_in_dropdown'] = $item['enabled'] && $placement === \App\Support\NavFeature::PLACEMENT_DROPDOWN;

            return $item;
        })
        ->sortBy(fn (array $item): string => sprintf('%03d-%s', (int) $item['order'], (string) $item['feature']))
        ->values();

    $primaryFeatureNavItems = $featureNavItems
        ->filter(fn (array $item): bool => $item['show_as_link'])
        ->values();

    $dropdownFeatureNavItems = $featureNavItems
        ->filter(fn (array $item): bool => $item['show_in_dropdown'])
        ->values();

@endphp

@if($isDineInCartPage)
<nav class="sticky top-0 z-40 border-b border-gray-100 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <div class="shrink-0 flex items-center">
                <a href="{{ route('customer.dinein.menu', ['store' => request()->route('store'), 'table' => request()->route('table')]) }}">
                    <x-application-logo class="block h-11 w-auto fill-current text-gray-800" />
                </a>
            </div>

            <x-lang-switcher />
        </div>
    </div>
</nav>
@else
<nav
    x-data="{
        open: false,
        fontSizes: ['xs', 'sm', 'md', 'lg', 'xl'],
        fontSizeLabels: {
            xs: 'A-',
            sm: 'A',
            md: 'A+',
            lg: 'A++',
            xl: 'A+++',
        },
        fontSize: window.adminFontPreference?.current?.() ?? 'sm',
        setFontSize(size) {
            this.fontSize = window.adminFontPreference?.set?.(size) ?? size;
        },
        fontSizeIndex() {
            const index = this.fontSizes.indexOf(this.fontSize);

            return index >= 0 ? index : 1;
        },
        setFontSizeByIndex(index) {
            this.setFontSize(this.fontSizes[Number(index)] ?? 'sm');
        },
        fontSizeLabel() {
            return this.fontSizeLabels[this.fontSize] ?? 'A';
        },
        init() {
            window.addEventListener('admin-font-size-changed', (event) => {
                this.fontSize = event.detail?.size ?? (window.adminFontPreference?.current?.() ?? 'sm');
            });
        },
    }"
    x-init="init()"
    class="{{ $isAdminArea ? 'admin-nav sticky top-0 z-40 border-b border-slate-200 bg-white' : 'bg-white border-b border-gray-100' }}"
>
    <!-- Primary Navigation Menu -->
    <div class="{{ $isAdminArea ? 'mx-auto w-full max-w-[96rem] px-4 sm:px-6 lg:px-8' : 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8' }}">
        <div class="admin-nav-row flex min-w-0 items-center justify-between gap-3 py-3">
            <div class="admin-nav-primary flex min-w-0 items-center">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('home') }}">
                        <x-application-logo class="block h-11 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                @if($isAdminArea)
                    <div class="hidden items-center pl-3 sm:flex">
                        <span class="inline-flex items-center rounded-full border border-cyan-300/80 bg-cyan-50 px-2.5 py-1 text-[11px] font-bold uppercase tracking-[0.18em] text-cyan-700">
                            {{ __('nav.admin_console') }}
                        </span>
                    </div>
                @endif

                <!-- Navigation Links -->
                <div class="admin-nav-links hidden sm:-my-px sm:ms-8 sm:flex sm:min-w-0 sm:items-center sm:gap-x-3 sm:overflow-x-auto sm:overflow-y-hidden">
                    @guest
                        @unless($isAdminArea)
                            <x-nav-link :href="route('product.pricing-contact')" :active="request()->routeIs('product.pricing-contact')">
                                {{ __('nav.pricing_contact') }}
                            </x-nav-link>
                        @endunless
                    @endguest

                    @if(Auth::user()?->isCustomer())
                        <x-nav-link :href="route('customer.points.index')" :active="request()->routeIs('customer.points.*')">
                            {{ __('nav.points_card') }}
                        </x-nav-link>
                        <x-nav-link :href="route('customer.order.history')" :active="request()->routeIs('customer.order.history')">
                            {{ __('nav.order_history') }}
                        </x-nav-link>
                    @endif

                    @if($showCombinedMerchantWorkspaceNav && $combinedMerchantWorkspaceHref)
                        <x-nav-link :href="$combinedMerchantWorkspaceHref" :active="$isCombinedMerchantWorkspacePage">
                            {{ __('nav.merchant_order_short') }}/{{ __('admin.board_all_title') }}
                        </x-nav-link>
                    @elseif($showMerchantOrderNav && $merchantOrderNavHref)
                        <x-nav-link :href="$merchantOrderNavHref" :active="$isMerchantOrderPage">
                            {{ __('nav.merchant_order') }}
                        </x-nav-link>
                    @endif

                    @foreach($primaryFeatureNavItems as $navItem)
                        <x-nav-link :href="$navItem['href']" :active="$navItem['active']">
                            {{ $navItem['label'] }}
                        </x-nav-link>
                    @endforeach

                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="admin-nav-controls hidden sm:ms-6 sm:flex sm:min-w-0 sm:items-center sm:justify-end sm:gap-3">
                @if($isAdminArea)
                    <div class="admin-font-switcher inline-flex w-52 items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
                        <span class="text-xs font-semibold text-slate-500">A-</span>
                        <div class="min-w-0 flex-1">
                            <input
                                type="range"
                                min="0"
                                max="4"
                                step="1"
                                :value="fontSizeIndex()"
                                @input="setFontSizeByIndex($event.target.value)"
                                class="h-2 w-full cursor-pointer accent-cyan-600"
                                aria-label="Adjust admin font size"
                            >
                        </div>
                        <span class="min-w-8 text-right text-xs font-bold text-cyan-700" x-text="fontSizeLabel()"></span>
                    </div>
                @endif

                {{-- Language Switcher --}}
                @php $localeName = ['zh_TW' => 'TW', 'zh_CN' => 'CN', 'en' => 'EN', 'vi' => 'VI'][app()->getLocale()] ?? 'EN'; @endphp
                <div x-data="{ langOpen: false }" class="relative admin-nav-locale">
                    <button @click="langOpen = !langOpen" class="inline-flex w-full items-center justify-center gap-1 rounded-xl border border-slate-200 bg-white px-2.5 py-2 text-xs font-semibold text-slate-600 shadow-sm hover:bg-slate-50 focus:outline-none">
                        🌐 {{ $localeName }}
                        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </button>
                    <div x-show="langOpen" @click.outside="langOpen = false"
                         class="absolute right-0 z-50 mt-1 w-36 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95">
                        <a href="{{ route('locale.switch', 'zh_TW') }}" class="flex items-center gap-2 px-3 py-2 text-xs font-medium {{ app()->getLocale() === 'zh_TW' ? 'bg-slate-50 font-bold text-slate-900' : 'text-slate-700 hover:bg-slate-50' }}">ZH {{ __('nav.lang_zh_TW') }}</a>
                        <a href="{{ route('locale.switch', 'zh_CN') }}" class="flex items-center gap-2 px-3 py-2 text-xs font-medium {{ app()->getLocale() === 'zh_CN' ? 'bg-slate-50 font-bold text-slate-900' : 'text-slate-700 hover:bg-slate-50' }}">🇨🇳 {{ __('nav.lang_zh_CN') }}</a>
                        <a href="{{ route('locale.switch', 'en') }}" class="flex items-center gap-2 px-3 py-2 text-xs font-medium {{ app()->getLocale() === 'en' ? 'bg-slate-50 font-bold text-slate-900' : 'text-slate-700 hover:bg-slate-50' }}">🇺🇸 {{ __('nav.lang_en') }}</a>
                        <a href="{{ route('locale.switch', 'vi') }}" class="flex items-center gap-2 px-3 py-2 text-xs font-medium {{ app()->getLocale() === 'vi' ? 'bg-slate-50 font-bold text-slate-900' : 'text-slate-700 hover:bg-slate-50' }}">🇻🇳 {{ __('nav.lang_vi') }}</a>
                    </div>
                </div>
                @auth
                    <div class="min-w-0 max-w-full">
                    <x-dropdown align="right" width="56" contentClasses="p-2 bg-white">
                        <x-slot name="trigger">
                            <button class="inline-flex max-w-full items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 focus:outline-none">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-700">
                                    {{ strtoupper(substr((string) Auth::user()->name, 0, 1)) }}
                                </span>
                                <span class="max-w-[132px] leading-tight text-left xl:max-w-[168px]">
                                    <span class="block truncate">{{ Auth::user()->name }}</span>
                                    <span class="block truncate text-[11px] font-medium text-slate-500">{{ Auth::user()->localizedRoleLabel() }}</span>
                                </span>
                                <div>
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                                <p class="text-xs font-semibold tracking-wide text-slate-500">{{ __('nav.currently_logged_in') }}</p>
                                <p class="mt-0.5 truncate text-sm font-semibold text-slate-800">{{ Auth::user()->name }}</p>
                                @if(Auth::user()->email)
                                    <p class="truncate text-xs text-slate-500">{{ Auth::user()->email }}</p>
                                @endif
                            </div>

                            <div class="my-2 border-t border-slate-200"></div>

                            <x-dropdown-link :href="route('profile.edit')" class="{{ request()->routeIs('profile.*') ? $settingsDropdownActiveClasses : '' }}">
                                {{ __('nav.profile') }}
                            </x-dropdown-link>

                            @if($canAccessMerchantConsole)
                                @foreach($dropdownFeatureNavItems as $navItem)
                                    @if($navItem['href'])
                                        <x-dropdown-link :href="$navItem['href']" class="{{ $navItem['active'] ? $settingsDropdownActiveClasses : '' }}">
                                            {{ $navItem['label'] }}
                                        </x-dropdown-link>
                                    @elseif($navItem['requires_dropdown_disabled_state'])
                                        <span class="block w-full cursor-not-allowed px-4 py-2 text-left text-sm leading-5 text-gray-400" title="{{ $navItem['disabled_reason'] }}">
                                            {{ $navItem['label'] }}
                                        </span>
                                    @endif
                                @endforeach
                            @endif

                            @if(Auth::user()?->isCustomer())
                                <x-dropdown-link :href="route('customer.points.index')" class="{{ request()->routeIs('customer.points.*') ? $settingsDropdownActiveClasses : '' }}">
                                    {{ __('nav.points_card') }}
                                </x-dropdown-link>

                                <x-dropdown-link :href="route('customer.order.history')" class="{{ request()->routeIs('customer.order.history') ? $settingsDropdownActiveClasses : '' }}">
                                    {{ __('nav.order_history') }}
                                </x-dropdown-link>
                            @endif

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <x-dropdown-link :href="route('logout')"
                                   onclick="event.preventDefault(); this.closest('form').submit();"
                                   class="mt-1 font-semibold text-rose-600 hover:bg-rose-50 focus:bg-rose-50">
                                    {{ __('nav.logout') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                        {{ __('nav.login') }}
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="inline-flex items-center rounded-xl bg-brand-primary px-3 py-2 text-sm font-semibold text-white hover:bg-brand-accent hover:text-brand-dark">
                            {{ __('nav.register') }}
                        </a>
                    @endif
                @endauth
            </div>

            <!-- Hamburger -->
            <div class="admin-nav-toggle -me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="mobile-responsive-menu hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @guest
                @unless($isAdminArea)
                    <x-responsive-nav-link :href="route('product.pricing-contact')" :active="request()->routeIs('product.pricing-contact')">
                        {{ __('nav.pricing_contact') }}
                    </x-responsive-nav-link>
                @endunless
            @endguest

            @if(Auth::user()?->isCustomer())
                <x-responsive-nav-link :href="route('customer.points.index')" :active="request()->routeIs('customer.points.*')">
                    {{ __('nav.points_card') }}
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('customer.order.history')" :active="request()->routeIs('customer.order.history')">
                    {{ __('nav.order_history') }}
                </x-responsive-nav-link>
            @endif

            @if($showCombinedMerchantWorkspaceNav && $combinedMerchantWorkspaceHref)
                <x-responsive-nav-link :href="$combinedMerchantWorkspaceHref" :active="$isCombinedMerchantWorkspacePage">
                    {{ __('nav.merchant_order_short') }}/{{ __('admin.board_all_title') }}
                </x-responsive-nav-link>
            @elseif($showMerchantOrderNav && $merchantOrderNavHref)
                <x-responsive-nav-link :href="$merchantOrderNavHref" :active="$isMerchantOrderPage">
                    {{ __('nav.merchant_order') }}
                </x-responsive-nav-link>
            @endif

            @foreach($primaryFeatureNavItems as $navItem)
                <x-responsive-nav-link :href="$navItem['href']" :active="$navItem['active']">
                    {{ $navItem['label'] }}
                </x-responsive-nav-link>
            @endforeach

        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            @auth
                <div class="px-4">
                    <div class="font-medium text-sm text-gray-700">{{ Auth::user()->localizedRoleLabel() }}</div>
                    @if(Auth::user()->isMerchant())
                        <div class="font-medium text-xs text-gray-500 mt-1">
                            {{ __('nav.expires') }} {{ Auth::user()->subscription_ends_at ? Auth::user()->subscription_ends_at->format('Y-m-d H:i') : __('nav.not_activated') }}
                        </div>
                    @endif
                </div>

                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')">
                        {{ __('nav.profile') }}
                    </x-responsive-nav-link>

                    @if($canAccessMerchantConsole)
                        @foreach($dropdownFeatureNavItems as $navItem)
                            @if($navItem['href'])
                                <x-responsive-nav-link :href="$navItem['href']" :active="$navItem['active']">
                                    {{ $navItem['label'] }}
                                </x-responsive-nav-link>
                            @elseif($navItem['requires_dropdown_disabled_state'])
                                <span class="block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-400 cursor-not-allowed" title="{{ $navItem['disabled_reason'] }}">
                                    {{ $navItem['label'] }}
                                </span>
                            @endif
                        @endforeach
                    @endif

                    @if(Auth::user()?->isCustomer())
                        <x-responsive-nav-link :href="route('customer.points.index')" :active="request()->routeIs('customer.points.*')">
                            {{ __('nav.points_card') }}
                        </x-responsive-nav-link>

                        <x-responsive-nav-link :href="route('customer.order.history')" :active="request()->routeIs('customer.order.history')">
                            {{ __('nav.order_history') }}
                        </x-responsive-nav-link>
                    @endif

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-responsive-nav-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                            {{ __('nav.logout') }}
                        </x-responsive-nav-link>
                    </form>
                </div>
            @else
                <div class="mt-2 space-y-1 px-2">
                    <x-responsive-nav-link :href="route('login')">
                        {{ __('nav.login') }}
                    </x-responsive-nav-link>
                    @if (Route::has('register'))
                        <x-responsive-nav-link :href="route('register')">
                            {{ __('nav.register') }}
                        </x-responsive-nav-link>
                    @endif
                </div>
            @endauth

            <div class="px-4 py-3 border-t border-gray-200">
                @if($isAdminArea)
                    <div class="mb-3">
                        <p class="mb-2 text-xs font-semibold text-gray-500">Text Size</p>
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-semibold text-slate-500">A-</span>
                            <input
                                type="range"
                                min="0"
                                max="4"
                                step="1"
                                :value="fontSizeIndex()"
                                @input="setFontSizeByIndex($event.target.value)"
                                class="h-2 flex-1 cursor-pointer accent-cyan-600"
                                aria-label="Adjust admin font size"
                            >
                            <span class="min-w-8 text-right text-xs font-bold text-cyan-700" x-text="fontSizeLabel()"></span>
                        </div>
                    </div>
                @endif

                <p class="text-xs font-semibold text-gray-500 mb-2">{{ __('nav.language') }}</p>
                <div class="flex gap-2">
                    <a href="{{ route('locale.switch', 'zh_TW') }}" class="rounded-lg border px-3 py-1.5 text-xs font-semibold {{ app()->getLocale() === 'zh_TW' ? 'border-indigo-400 bg-indigo-50 text-indigo-700' : 'border-slate-300 text-slate-600 hover:bg-slate-50' }}">ZH</a>
                    <a href="{{ route('locale.switch', 'zh_CN') }}" class="rounded-lg border px-3 py-1.5 text-xs font-semibold {{ app()->getLocale() === 'zh_CN' ? 'border-indigo-400 bg-indigo-50 text-indigo-700' : 'border-slate-300 text-slate-600 hover:bg-slate-50' }}">CN</a>
                    <a href="{{ route('locale.switch', 'en') }}" class="rounded-lg border px-3 py-1.5 text-xs font-semibold {{ app()->getLocale() === 'en' ? 'border-indigo-400 bg-indigo-50 text-indigo-700' : 'border-slate-300 text-slate-600 hover:bg-slate-50' }}">EN</a>
                    <a href="{{ route('locale.switch', 'vi') }}" class="rounded-lg border px-3 py-1.5 text-xs font-semibold {{ app()->getLocale() === 'vi' ? 'border-indigo-400 bg-indigo-50 text-indigo-700' : 'border-slate-300 text-slate-600 hover:bg-slate-50' }}">VI</a>
                </div>
            </div>
        </div>
    </div>
</nav>
@endif

@auth
    @if($isAdminArea)
        <div class="mobile-admin-dock">
            @if($showStoreBackendNav)
                <a href="{{ route('admin.stores.index') }}" class="{{ $isStoreBackendPage ? 'active' : '' }}">{{ __('nav.stores_short') }}</a>
            @endif

            @if($showCombinedMerchantWorkspaceNav && $combinedMerchantWorkspaceHref)
                <a href="{{ $combinedMerchantWorkspaceHref }}" class="{{ $isCombinedMerchantWorkspacePage ? 'active' : '' }}">{{ __('nav.merchant_order_short') }}/{{ __('admin.board_all_title') }}</a>
            @else
                @if($showMerchantOrderNav && $merchantOrderNavHref)
                    <a href="{{ $merchantOrderNavHref }}" class="{{ $isMerchantOrderPage ? 'active' : '' }}">{{ __('nav.merchant_order_short') }}</a>
                @endif

                @if($showBoardNav && $boardNavHref)
                    <a href="{{ $boardNavHref }}" class="{{ $isBoardPage ? 'active' : '' }}">{{ __('admin.board_all_title') }}</a>
                @endif
            @endif

            @if(Auth::user()?->isMerchant() && $subscriptionFeatureEnabled)
                <a href="{{ route('merchant.subscription.index') }}" class="{{ request()->routeIs('merchant.subscription.*') ? 'active' : '' }}">{{ __('nav.plan_short') }}</a>
            @elseif(Auth::user()?->isAdmin())
                <a href="{{ route('super-admin.subscriptions.index', ['tab' => 'features']) }}" class="{{ request()->routeIs('super-admin.subscriptions.*') ? 'active' : '' }}">{{ __('nav.subscription_short') }}</a>
            @endif
        </div>
    @endif
@endauth
