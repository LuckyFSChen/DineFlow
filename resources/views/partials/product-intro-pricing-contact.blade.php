@php
    $plansByTier = $plansByTier ?? collect();

    $formatPlanCategory = function (?string $category) {
        $value = trim((string) $category);

        if ($value === '') {
            return '-';
        }

        return match (strtolower($value)) {
            'basic' => __('merchant.plan_tier_basic'),
            'growth' => __('merchant.plan_tier_growth'),
            'pro' => __('merchant.plan_tier_pro'),
            default => $value,
        };
    };
@endphp

<section id="pricing-contact" class="border-t border-brand-soft/60 bg-[linear-gradient(180deg,#fff8f1_0%,#fff3e6_48%,#ffffff_100%)] py-16">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="intro-reveal mb-8 text-center" data-reveal>
            <span class="inline-flex items-center rounded-full border border-brand-soft/70 bg-white/80 px-4 py-1 text-sm font-semibold text-brand-primary">
                {{ __('merchant_inquiry.section_badge') }}
            </span>
            <h2 class="mt-4 text-3xl font-bold tracking-tight text-brand-dark sm:text-4xl">{{ __('merchant_inquiry.section_title') }}</h2>
            <p class="mx-auto mt-3 max-w-3xl text-lg leading-8 text-brand-primary/75">{{ __('merchant_inquiry.section_desc') }}</p>
            <p class="mt-3 text-sm font-medium text-brand-primary/70">{{ __('merchant_inquiry.sync_note') }}</p>
        </div>

        @if(session('merchantInquirySuccess'))
            <div class="mb-6 rounded-[1.6rem] border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
                {{ session('merchantInquirySuccess') }}
            </div>
        @endif

        @if(session('merchantInquiryError'))
            <div class="mb-6 rounded-[1.6rem] border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-medium text-rose-700">
                {{ session('merchantInquiryError') }}
            </div>
        @endif

        <div class="grid gap-8 xl:grid-cols-[1.15fr_0.85fr]">
            <div class="space-y-6">
                @if($plansByTier->isEmpty())
                    <div class="intro-reveal rounded-[1.8rem] border border-dashed border-brand-soft/70 bg-white/90 p-8 text-center text-brand-primary/75 shadow-[0_18px_45px_rgba(90,30,14,0.08)]" data-reveal>
                        {{ __('merchant_inquiry.empty_plans') }}
                    </div>
                @else
                    @foreach($plansByTier as $tier => $tierPlans)
                        <div class="intro-reveal rounded-[1.8rem] border border-brand-soft/60 bg-white/90 p-6 shadow-[0_18px_45px_rgba(90,30,14,0.08)]" data-reveal style="--delay: {{ 50 + ($loop->index * 70) }}ms;">
                            <div class="mb-5 flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-brand-primary/50">{{ __('merchant_inquiry.plan_group_label') }}</p>
                                    <h3 class="text-2xl font-bold text-brand-dark">{{ $formatPlanCategory($tier) }}</h3>
                                </div>
                                <a href="{{ route('join.merchant.register') }}" class="inline-flex items-center rounded-2xl border border-brand-soft/80 px-4 py-2 text-sm font-semibold text-brand-primary transition hover:-translate-y-0.5 hover:border-brand-primary hover:bg-brand-soft/40">
                                    {{ __('merchant_inquiry.create_account') }}
                                </a>
                            </div>

                            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                                @foreach($tierPlans as $plan)
                                    @php
                                        $discountTwd = max((int) ($plan->discount_twd ?? 0), 0);
                                        $originalPriceTwd = (int) $plan->price_twd + $discountTwd;
                                    @endphp
                                    <article class="rounded-[1.6rem] border border-brand-soft/60 bg-[#fffdfa] p-5 shadow-[0_14px_34px_rgba(90,30,14,0.06)]">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <h4 class="text-xl font-bold text-brand-dark">{{ $plan->name }}</h4>
                                                <p class="mt-1 text-sm text-brand-primary/70">{{ __('merchant.days', ['days' => $plan->duration_days]) }}</p>
                                            </div>
                                            @if($discountTwd > 0)
                                                <span class="inline-flex rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-600">
                                                    {{ __('merchant_inquiry.discount_badge', ['amount' => number_format($discountTwd)]) }}
                                                </span>
                                            @endif
                                        </div>

                                        @if($discountTwd > 0)
                                            <p class="mt-4 text-sm font-semibold text-brand-primary/40 line-through">{{ __('merchant.original_price') }} NT$ {{ number_format($originalPriceTwd) }}</p>
                                        @endif

                                        <p class="mt-1 text-3xl font-black text-brand-primary">NT$ {{ number_format((int) $plan->price_twd) }}</p>
                                        <p class="mt-1 text-sm text-brand-primary/65">{{ __('merchant_inquiry.public_price_note') }}</p>
                                        <p class="mt-3 text-sm font-medium text-brand-primary/70">
                                            {{ __('merchant.store_count_label', ['count' => $plan->max_stores === null ? __('merchant.unlimited') : __('merchant.store_count_max', ['count' => $plan->max_stores])]) }}
                                        </p>

                                        @if(filled($plan->description))
                                            <p class="mt-4 text-sm leading-7 text-brand-primary/75">{{ $plan->description }}</p>
                                        @endif

                                        @if(!empty($plan->features))
                                            <ul class="mt-4 space-y-2 text-sm leading-6 text-brand-dark/85">
                                                @foreach($plan->features as $feature)
                                                    <li class="flex items-start gap-2">
                                                        <span class="mt-1 inline-block h-2.5 w-2.5 rounded-full bg-brand-highlight"></span>
                                                        <span>{{ $feature }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            <div class="intro-reveal rounded-[2rem] border border-brand-soft/60 bg-white/95 p-6 shadow-[0_22px_50px_rgba(90,30,14,0.12)]" data-reveal style="--delay: 120ms;">
                <div class="mb-6">
                    <span class="inline-flex items-center rounded-full bg-brand-highlight/70 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-brand-dark">
                        {{ __('merchant_inquiry.contact_badge') }}
                    </span>
                    <h3 class="mt-4 text-2xl font-bold text-brand-dark">{{ __('merchant_inquiry.contact_title') }}</h3>
                    <p class="mt-2 text-sm leading-7 text-brand-primary/75">{{ __('merchant_inquiry.contact_desc') }}</p>
                </div>

                <form method="POST" action="{{ route('product.intro.inquiry.submit') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="return_to" value="{{ $pricingContactReturnTo ?? url()->current() }}">

                    <div>
                        <label for="merchant-inquiry-name" class="mb-2 block text-sm font-semibold text-brand-dark">{{ __('merchant_inquiry.field_name') }}</label>
                        <input id="merchant-inquiry-name" name="name" type="text" value="{{ old('name') }}" required class="w-full rounded-2xl border border-brand-soft/70 bg-white px-4 py-3 text-sm text-brand-dark shadow-sm outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-soft/70">
                        @error('name')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="merchant-inquiry-phone" class="mb-2 block text-sm font-semibold text-brand-dark">{{ __('merchant_inquiry.field_phone') }}</label>
                            <input id="merchant-inquiry-phone" name="phone" type="tel" value="{{ old('phone') }}" required class="w-full rounded-2xl border border-brand-soft/70 bg-white px-4 py-3 text-sm text-brand-dark shadow-sm outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-soft/70">
                            @error('phone')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="merchant-inquiry-email" class="mb-2 block text-sm font-semibold text-brand-dark">{{ __('merchant_inquiry.field_email') }}</label>
                            <input id="merchant-inquiry-email" name="email" type="email" value="{{ old('email') }}" required class="w-full rounded-2xl border border-brand-soft/70 bg-white px-4 py-3 text-sm text-brand-dark shadow-sm outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-soft/70">
                            @error('email')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="merchant-inquiry-restaurant-name" class="mb-2 block text-sm font-semibold text-brand-dark">{{ __('merchant_inquiry.field_restaurant_name') }}</label>
                        <input id="merchant-inquiry-restaurant-name" name="restaurant_name" type="text" value="{{ old('restaurant_name') }}" required class="w-full rounded-2xl border border-brand-soft/70 bg-white px-4 py-3 text-sm text-brand-dark shadow-sm outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-soft/70">
                        @error('restaurant_name')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="merchant-inquiry-status" class="mb-2 block text-sm font-semibold text-brand-dark">{{ __('merchant_inquiry.field_status') }}</label>
                            <select id="merchant-inquiry-status" name="status" required class="w-full rounded-2xl border border-brand-soft/70 bg-white px-4 py-3 text-sm text-brand-dark shadow-sm outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-soft/70">
                                @foreach(trans('merchant_inquiry.statuses') as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', 'open') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="merchant-inquiry-country" class="mb-2 block text-sm font-semibold text-brand-dark">{{ __('merchant_inquiry.field_country') }}</label>
                            <select id="merchant-inquiry-country" name="country" required class="w-full rounded-2xl border border-brand-soft/70 bg-white px-4 py-3 text-sm text-brand-dark shadow-sm outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-soft/70">
                                @foreach(trans('merchant_inquiry.countries') as $value => $label)
                                    <option value="{{ $value }}" @selected(old('country', 'tw') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('country')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="merchant-inquiry-address" class="mb-2 block text-sm font-semibold text-brand-dark">{{ __('merchant_inquiry.field_address') }}</label>
                        <input id="merchant-inquiry-address" name="address" type="text" value="{{ old('address') }}" required class="w-full rounded-2xl border border-brand-soft/70 bg-white px-4 py-3 text-sm text-brand-dark shadow-sm outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-soft/70">
                        @error('address')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="merchant-inquiry-contact-time" class="mb-2 block text-sm font-semibold text-brand-dark">{{ __('merchant_inquiry.field_contact_time') }}</label>
                        <input id="merchant-inquiry-contact-time" name="contact_time" type="text" value="{{ old('contact_time') }}" placeholder="{{ __('merchant_inquiry.contact_time_placeholder') }}" class="w-full rounded-2xl border border-brand-soft/70 bg-white px-4 py-3 text-sm text-brand-dark shadow-sm outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-soft/70">
                        @error('contact_time')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="merchant-inquiry-message" class="mb-2 block text-sm font-semibold text-brand-dark">{{ __('merchant_inquiry.field_message') }}</label>
                        <textarea id="merchant-inquiry-message" name="message" rows="5" placeholder="{{ __('merchant_inquiry.message_placeholder') }}" class="w-full rounded-2xl border border-brand-soft/70 bg-white px-4 py-3 text-sm text-brand-dark shadow-sm outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-soft/70">{{ old('message') }}</textarea>
                        @error('message')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-brand-primary px-5 py-3 text-base font-semibold text-white transition hover:-translate-y-0.5 hover:bg-brand-accent hover:text-brand-dark">
                        {{ __('merchant_inquiry.submit') }}
                    </button>

                    <p class="text-xs leading-6 text-brand-primary/65">{{ __('merchant_inquiry.submit_hint') }}</p>
                </form>
            </div>
        </div>
    </div>
</section>
