<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    @if(Auth::user()?->isMerchant())
                        <x-nav-link :href="route('merchant.subscription.index')" :active="request()->routeIs('merchant.subscription.*')">
                            {{ __('nav.subscription') }}
                        </x-nav-link>

                        <x-nav-link :href="route('merchant.reports.financial')" :active="request()->routeIs('merchant.reports.*')">
                            {{ __('nav.financial_report') }}
                        </x-nav-link>
                    @endif

                    @if(Auth::user()?->isAdmin() || Auth::user()?->hasActiveSubscription())
                        <x-nav-link :href="route('admin.stores.index')" :active="request()->routeIs('admin.stores.*')">
                            {{ __('nav.store_backend') }}
                        </x-nav-link>
                    @endif

                    @if(Auth::user()?->isAdmin())
                        <x-nav-link :href="route('super-admin.subscriptions.index')" :active="request()->routeIs('super-admin.subscriptions.*')">
                            {{ __('nav.super_admin') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 sm:gap-3">
                {{-- Language Switcher --}}
                @php $localeName = ['zh_TW' => 'ZH', 'en' => 'EN', 'vi' => 'VI'][app()->getLocale()] ?? 'EN'; @endphp
                <div x-data="{ langOpen: false }" class="relative">
                    <button @click="langOpen = !langOpen" class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-2.5 py-2 text-xs font-semibold text-slate-600 shadow-sm hover:bg-slate-50 focus:outline-none">
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
                        <a href="{{ route('locale.switch', 'zh_TW') }}" class="flex items-center gap-2 px-3 py-2 text-xs font-medium {{ app()->getLocale() === 'zh_TW' ? 'bg-slate-50 font-bold text-slate-900' : 'text-slate-700 hover:bg-slate-50' }}">🇹🇼 {{ __('nav.lang_zh_TW') }}</a>
                        <a href="{{ route('locale.switch', 'en') }}" class="flex items-center gap-2 px-3 py-2 text-xs font-medium {{ app()->getLocale() === 'en' ? 'bg-slate-50 font-bold text-slate-900' : 'text-slate-700 hover:bg-slate-50' }}">🇺🇸 {{ __('nav.lang_en') }}</a>
                        <a href="{{ route('locale.switch', 'vi') }}" class="flex items-center gap-2 px-3 py-2 text-xs font-medium {{ app()->getLocale() === 'vi' ? 'bg-slate-50 font-bold text-slate-900' : 'text-slate-700 hover:bg-slate-50' }}">🇻🇳 {{ __('nav.lang_vi') }}</a>
                    </div>
                </div>
                <x-dropdown align="right" width="56" contentClasses="p-2 bg-white">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 focus:outline-none">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-700">
                                {{ strtoupper(substr((string) (Auth::user()?->name ?? 'U'), 0, 1)) }}
                            </span>
                            <span class="max-w-[140px] leading-tight text-left">
                                <span class="block truncate">{{ Auth::user()?->name ?? __('nav.account_center') }}</span>
                                <span class="block text-[11px] font-medium text-slate-500">{{ strtoupper((string) Auth::user()?->role) }}</span>
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
                            <p class="mt-0.5 truncate text-sm font-semibold text-slate-800">{{ Auth::user()?->name }}</p>
                            @if(Auth::user()?->email)
                                <p class="truncate text-xs text-slate-500">{{ Auth::user()?->email }}</p>
                            @endif
                        </div>

                        <div class="my-2 border-t border-slate-200"></div>

                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('nav.profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
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

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
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
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @if(Auth::user()?->isMerchant())
                <x-responsive-nav-link :href="route('merchant.subscription.index')" :active="request()->routeIs('merchant.subscription.*')">
                    {{ __('nav.subscription') }}
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('merchant.reports.financial')" :active="request()->routeIs('merchant.reports.*')">
                    {{ __('nav.financial_report') }}
                </x-responsive-nav-link>
            @endif

            @if(Auth::user()?->isAdmin() || Auth::user()?->hasActiveSubscription())
                <x-responsive-nav-link :href="route('admin.stores.index')" :active="request()->routeIs('admin.stores.*')">
                    {{ __('nav.store_backend') }}
                </x-responsive-nav-link>
            @endif

            @if(Auth::user()?->isAdmin())
                <x-responsive-nav-link :href="route('super-admin.subscriptions.index')" :active="request()->routeIs('super-admin.subscriptions.*')">
                    {{ __('nav.super_admin') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-sm text-gray-700">{{ strtoupper((string) Auth::user()?->role) }}</div>
                @if(Auth::user()?->isMerchant())
                    <div class="font-medium text-xs text-gray-500 mt-1">
                        {{ __('nav.expires') }} {{ Auth::user()?->subscription_ends_at ? Auth::user()?->subscription_ends_at->format('Y-m-d H:i') : __('nav.not_activated') }}
                    </div>
                @endif
                {{-- <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div> --}}
                {{-- <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div> --}}
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('nav.profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('nav.logout') }}
                    </x-responsive-nav-link>

                {{-- Responsive Language Switcher --}}
                <div class="px-4 py-3 border-t border-gray-200">
                    <p class="text-xs font-semibold text-gray-500 mb-2">{{ __('nav.language') }}</p>
                    <div class="flex gap-2">
                        <a href="{{ route('locale.switch', 'zh_TW') }}" class="rounded-lg border px-3 py-1.5 text-xs font-semibold {{ app()->getLocale() === 'zh_TW' ? 'border-indigo-400 bg-indigo-50 text-indigo-700' : 'border-slate-300 text-slate-600 hover:bg-slate-50' }}">ZH</a>
                        <a href="{{ route('locale.switch', 'en') }}" class="rounded-lg border px-3 py-1.5 text-xs font-semibold {{ app()->getLocale() === 'en' ? 'border-indigo-400 bg-indigo-50 text-indigo-700' : 'border-slate-300 text-slate-600 hover:bg-slate-50' }}">EN</a>
                        <a href="{{ route('locale.switch', 'vi') }}" class="rounded-lg border px-3 py-1.5 text-xs font-semibold {{ app()->getLocale() === 'vi' ? 'border-indigo-400 bg-indigo-50 text-indigo-700' : 'border-slate-300 text-slate-600 hover:bg-slate-50' }}">VI</a>
                    </div>
                </div>
                </form>
            </div>
        </div>
    </div>
</nav>
