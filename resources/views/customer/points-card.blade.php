<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    {{ __('customer.points_card_page_title') }}
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    {{ __('customer.points_card_page_hint') }}
                </p>
            </div>
            <a
                href="{{ route('customer.order.history') }}"
                class="inline-flex items-center rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700 transition hover:bg-amber-100"
            >
                {{ __('customer.view_my_order_history') }}
            </a>
        </div>
    </x-slot>

    <div class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(245,158,11,0.20),_transparent_38%),linear-gradient(180deg,#fffaf0_0%,#fff7ed_44%,#fffdf8_100%)] py-10">
        <div class="mx-auto max-w-6xl space-y-8 px-4 sm:px-6 lg:px-8">
            <section class="relative overflow-hidden rounded-[2rem] border border-amber-200/80 bg-white/85 p-6 shadow-[0_28px_80px_rgba(180,83,9,0.10)] backdrop-blur-sm sm:p-8">
                <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(249,115,22,0.16),_transparent_28%),radial-gradient(circle_at_bottom_left,_rgba(251,191,36,0.18),_transparent_34%)]"></div>
                <div class="relative grid gap-6 lg:grid-cols-[1.2fr_0.8fr] lg:items-end">
                    <div>
                        <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">
                            {{ __('customer.points_card_label') }}
                        </span>
                        <h1 class="mt-4 max-w-2xl text-3xl font-black tracking-tight text-gray-900 sm:text-4xl">
                            {{ __('customer.points_card_page_title') }}
                        </h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-gray-600 sm:text-base">
                            {{ __('customer.points_card_page_hint') }}
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
                        <div class="rounded-3xl border border-amber-200 bg-white/90 p-5 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-amber-700">{{ __('customer.points_card_total_balance') }}</p>
                            <p class="mt-3 text-3xl font-black tracking-tight text-gray-900">{{ number_format($totalPointsBalance) }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ __('customer.points_unit') }}</p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-2">
                            <div class="rounded-3xl border border-orange-200 bg-white/90 p-5 shadow-sm">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-orange-700">{{ __('customer.points_card_store_count') }}</p>
                                <p class="mt-3 text-2xl font-black tracking-tight text-gray-900">{{ number_format($memberPointSummaries->count()) }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ __('customer.points_card_store_count_hint') }}</p>
                            </div>

                            <div class="rounded-3xl border border-emerald-200 bg-white/90 p-5 shadow-sm">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700">{{ __('customer.points_card_active_store_count') }}</p>
                                <p class="mt-3 text-2xl font-black tracking-tight text-gray-900">{{ number_format($activeStoreCount) }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ __('customer.points_card_active_store_count_hint') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            @if ($memberPointSummaries->isEmpty())
                <section class="rounded-[2rem] border border-dashed border-amber-200 bg-white/90 p-10 text-center shadow-sm">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-3xl">
                        &#9733;
                    </div>
                    <h3 class="mt-4 text-lg font-bold text-gray-900">{{ __('customer.points_card_empty_title') }}</h3>
                    <p class="mt-2 text-sm text-gray-600">{{ __('customer.points_card_empty_hint') }}</p>
                    <a
                        href="{{ route('stores.list') }}"
                        class="mt-5 inline-flex items-center rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-600"
                    >
                        {{ __('customer.back_home') }}
                    </a>
                </section>
            @else
                <section class="grid gap-6 lg:grid-cols-2">
                    @foreach ($memberPointSummaries as $memberPointSummary)
                        @php
                            $pointStore = $memberPointSummary->store;
                            $storeCoupons = $pointStore ? ($storeCouponsByStoreId->get((int) $pointStore->id) ?? collect()) : collect();
                            $pointCurrencyCode = strtolower((string) ($pointStore->currency ?? 'twd'));
                            $pointCurrencySymbol = match ($pointCurrencyCode) {
                                'vnd' => 'VND',
                                'cny' => 'CNY',
                                'usd' => 'USD',
                                default => 'NT$',
                            };
                            $bannerUrl = $pointStore?->banner_image
                                ? asset('storage/' . ltrim((string) $pointStore->banner_image, '/'))
                                : null;
                        @endphp
                        <article class="group overflow-hidden rounded-[2rem] border border-amber-200/80 bg-white shadow-[0_22px_60px_rgba(180,83,9,0.10)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_28px_70px_rgba(180,83,9,0.16)]">
                            <div class="relative overflow-hidden">
                                <div class="relative aspect-[16/8]">
                                    @if ($bannerUrl)
                                        <img
                                            src="{{ $bannerUrl }}"
                                            alt="{{ $pointStore?->name ?? __('customer.store') }}"
                                            class="h-full w-full object-cover transition duration-700 group-hover:scale-105"
                                        >
                                        <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(15,23,42,0.08)_0%,rgba(15,23,42,0.34)_42%,rgba(15,23,42,0.84)_100%)]"></div>
                                    @else
                                        <div class="absolute inset-0 bg-[linear-gradient(135deg,#f59e0b_0%,#fb923c_46%,#ea580c_100%)]"></div>
                                        <div class="absolute -right-10 top-6 h-32 w-32 rounded-full bg-white/16 blur-2xl"></div>
                                        <div class="absolute bottom-0 left-8 h-24 w-24 rounded-full bg-amber-200/30 blur-2xl"></div>
                                        <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(255,255,255,0.02)_0%,rgba(120,53,15,0.24)_50%,rgba(120,53,15,0.72)_100%)]"></div>
                                    @endif

                                    <div class="absolute inset-x-0 bottom-0 p-6 text-white">
                                        <div class="flex items-end justify-between gap-4">
                                            <div class="min-w-0">
                                                <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-white/75">
                                                    {{ __('customer.points_card_label') }}
                                                </p>
                                                <h3 class="mt-2 truncate text-2xl font-black tracking-tight sm:text-[1.9rem]">
                                                    {{ $pointStore?->name ?? __('customer.store') }}
                                                </h3>
                                                <p class="mt-2 text-sm text-white/80">
                                                    {{ __('customer.current_balance', ['balance' => number_format((int) $memberPointSummary->points_balance), 'unit' => __('customer.points_unit')]) }}
                                                </p>
                                            </div>

                                            <div class="shrink-0 rounded-[1.4rem] border border-white/25 bg-white/14 px-4 py-3 text-right shadow-lg backdrop-blur-md">
                                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-white/75">{{ __('customer.points_balance_label') }}</p>
                                                <p class="mt-1 text-2xl font-black text-white">{{ number_format((int) $memberPointSummary->points_balance) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-5 px-6 py-6">
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-[1.6rem] border border-amber-100 bg-[linear-gradient(180deg,#fffaf0_0%,#fffbeb_100%)] px-4 py-4">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-amber-700">{{ __('customer.points_card_spent_hint') }}</p>
                                        <p class="mt-2 text-lg font-bold tracking-tight text-gray-900">
                                            {{ __('customer.points_total_spent', ['currency' => $pointCurrencySymbol, 'amount' => number_format((int) ($memberPointSummary->monthly_total_spent ?? 0))]) }}
                                        </p>
                                    </div>

                                    <div class="rounded-[1.6rem] border border-orange-100 bg-[linear-gradient(180deg,#fff7ed_0%,#fff1e6_100%)] px-4 py-4">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-orange-700">{{ __('customer.points_card_orders_hint') }}</p>
                                        <p class="mt-2 text-lg font-bold tracking-tight text-gray-900">
                                            {{ __('customer.points_total_orders', ['count' => number_format((int) $memberPointSummary->total_orders)]) }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700">
                                        {{ __('customer.points_total_inline', ['balance' => number_format((int) $memberPointSummary->points_balance), 'unit' => __('customer.points_unit')]) }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-medium text-gray-600">
                                        @if ($memberPointSummary->last_order_at)
                                            {{ __('customer.points_last_order_at', ['date' => $memberPointSummary->last_order_at->format('Y-m-d H:i')]) }}
                                        @else
                                            {{ __('customer.points_card_no_order_yet') }}
                                        @endif
                                    </span>
                                </div>

                                @if ($pointStore)
                                    <div class="flex flex-wrap gap-2 pt-1">
                                        <a
                                            href="{{ route('stores.enter', ['store' => $pointStore]) }}"
                                            class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
                                        >
                                            {{ __('customer.points_card_visit_store') }}
                                        </a>
                                        <a
                                            href="{{ route('customer.order.history') }}"
                                            class="inline-flex items-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                                        >
                                            {{ __('customer.view_my_order_history') }}
                                        </a>
                                        <button
                                            type="button"
                                            class="inline-flex items-center rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-700 transition hover:bg-amber-100"
                                            data-coupon-modal-open
                                            data-store-name="{{ $pointStore->name }}"
                                            data-coupons='@json($storeCoupons->values()->all())'
                                        >
                                            {{ __('customer.points_card_view_coupons') }}
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </section>
            @endif
        </div>
    </div>

    <div class="fixed inset-0 z-50 hidden items-end justify-center bg-slate-950/55 p-4 sm:items-center" data-coupon-modal>
        <div class="w-full max-w-2xl rounded-[2rem] border border-amber-200 bg-white shadow-[0_30px_90px_rgba(15,23,42,0.22)]">
            <div class="flex items-start justify-between gap-4 border-b border-amber-100 px-6 py-5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">{{ __('customer.points_card_coupon_modal_eyebrow') }}</p>
                    <h3 class="mt-2 text-xl font-black tracking-tight text-gray-900" data-coupon-modal-title>{{ __('customer.points_card_coupon_modal_title', ['store' => '']) }}</h3>
                </div>
                <button type="button" class="rounded-full p-2 text-slate-500 transition hover:bg-slate-100" data-coupon-modal-close aria-label="Close">
                    ✕
                </button>
            </div>

            <div class="max-h-[65vh] overflow-y-auto px-6 py-5" data-coupon-modal-body></div>

            <div class="border-t border-amber-100 px-6 py-4">
                <button
                    type="button"
                    class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
                    data-coupon-modal-close
                >
                    {{ __('customer.cancel') }}
                </button>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
(() => {
    const modal = document.querySelector('[data-coupon-modal]');
    const modalTitle = document.querySelector('[data-coupon-modal-title]');
    const modalBody = document.querySelector('[data-coupon-modal-body]');
    const closeButtons = document.querySelectorAll('[data-coupon-modal-close]');
    const openButtons = document.querySelectorAll('[data-coupon-modal-open]');
    const modalTitleTemplate = @json(__('customer.points_card_coupon_modal_title', ['store' => '__STORE__']));
    const noCouponsLabel = @json(__('customer.points_card_coupon_empty'));
    const couponCodeLabel = @json(__('customer.points_card_coupon_code'));
    const couponSummaryLabel = @json(__('customer.points_card_coupon_summary'));

    if (!modal || !modalTitle || !modalBody || openButtons.length === 0) {
        return;
    }

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modalBody.innerHTML = '';
    };

    const openModal = (storeName, coupons) => {
        modalTitle.textContent = modalTitleTemplate.replace('__STORE__', storeName);

        if (!Array.isArray(coupons) || coupons.length === 0) {
            modalBody.innerHTML = `
                <div class="rounded-2xl border border-dashed border-amber-200 bg-amber-50/70 px-4 py-8 text-center text-sm text-amber-800">
                    ${noCouponsLabel}
                </div>
            `;
        } else {
            modalBody.innerHTML = coupons.map((coupon) => `
                <article class="rounded-2xl border border-amber-100 bg-[linear-gradient(180deg,#fffdf8_0%,#fff7ed_100%)] p-4 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-amber-700">${couponCodeLabel}</p>
                            <p class="mt-2 text-lg font-black tracking-[0.08em] text-slate-900">${String(coupon.code || '')}</p>
                        </div>
                    </div>
                    <div class="mt-4 rounded-xl bg-white px-4 py-3 text-sm text-slate-700 ring-1 ring-amber-100">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">${couponSummaryLabel}</p>
                        <p class="mt-2 leading-6">${String(coupon.summary || '')}</p>
                    </div>
                </article>
            `).join('');
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    openButtons.forEach((button) => {
        button.addEventListener('click', () => {
            let coupons = [];
            try {
                coupons = JSON.parse(button.getAttribute('data-coupons') || '[]');
            } catch (_error) {
                coupons = [];
            }

            openModal(button.getAttribute('data-store-name') || '', coupons);
        });
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });
})();
</script>
