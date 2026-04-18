@extends('layouts.app')

@section('title', __('home.full_intro_title') . ' | ' . config('app.name', 'DineFlow'))
@section('meta_description', __('home.full_intro_desc'))
@section('canonical', route('product.intro'))
@section('meta_robots', 'index,follow,max-image-preview:large')
@section('meta_image', asset('images/product-intro/productManagement.png'))

@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => __('home.full_intro_title'),
    'description' => __('home.full_intro_desc'),
    'url' => route('product.intro'),
    'inLanguage' => str_replace('_', '-', app()->getLocale()),
    'isPartOf' => [
        '@type' => 'WebSite',
        'name' => config('app.name', 'DineFlow'),
        'url' => url('/'),
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
<div class="min-h-screen bg-brand-soft/20 text-brand-dark">
    <section class="relative isolate overflow-hidden border-b border-brand-soft/70 bg-brand-dark text-white">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(255,214,112,0.2),_transparent_36%),linear-gradient(130deg,_rgba(90,30,14,0.98),_rgba(236,144,87,0.9))]"></div>
        <div class="absolute -left-12 top-20 h-52 w-52 rounded-full bg-brand-highlight/20 blur-3xl"></div>
        <div class="absolute -right-10 bottom-6 h-48 w-48 rounded-full bg-brand-soft/20 blur-3xl"></div>

        <div class="relative mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-24">
            <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-1.5 text-sm font-semibold tracking-[0.2em] text-brand-highlight">
                {{ __('home.full_intro_badge') }}
            </span>
            <h1 class="mt-6 max-w-4xl text-4xl font-bold tracking-tight sm:text-5xl">
                {{ __('home.full_intro_title') }}
            </h1>
            <p class="mt-5 max-w-3xl text-lg leading-8 text-white/80 sm:text-xl sm:leading-9">
                {{ __('home.full_intro_desc') }}
            </p>

            <div class="mt-8 flex flex-wrap gap-4">
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 rounded-2xl bg-brand-highlight px-5 py-3 text-base font-semibold text-brand-dark transition hover:-translate-y-0.5 hover:bg-brand-soft">
                        {{ __('home.full_intro_join_button') }}
                    </a>
                @else
                    <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-2xl bg-brand-highlight px-5 py-3 text-base font-semibold text-brand-dark transition hover:-translate-y-0.5 hover:bg-brand-soft">
                        {{ __('home.full_intro_join_button') }}
                    </a>
                @endauth
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-2xl border border-white/20 bg-white/10 px-5 py-3 text-base font-semibold text-white transition hover:bg-white/15">
                    {{ __('home.full_intro_back_home') }}
                </a>
            </div>
        </div>
    </section>

    @php
        $flowSteps = [
            [
                'number' => 1,
                'title' => __('home.full_intro_flow_step_1_title'),
                'desc' => __('home.full_intro_flow_step_1_desc'),
                'image' => asset('images/product-intro/productManagement.png'),
                'badge' => 'bg-brand-primary text-white',
            ],
            [
                'number' => 2,
                'title' => __('home.full_intro_flow_step_2_title'),
                'desc' => __('home.full_intro_flow_step_2_desc'),
                'image' => asset('images/product-intro/qrcode.png'),
                'badge' => 'bg-brand-primary text-white',
            ],
            [
                'number' => 3,
                'title' => __('home.full_intro_flow_step_3_title'),
                'desc' => __('home.full_intro_flow_step_3_desc'),
                'image' => asset('images/product-intro/menu.png'),
                'badge' => 'bg-brand-primary text-white',
            ],
            [
                'number' => 4,
                'title' => __('home.full_intro_flow_step_4_title'),
                'desc' => __('home.full_intro_flow_step_4_desc'),
                'image' => asset('images/product-intro/billboard.png'),
                'badge' => 'bg-brand-primary text-white',
            ],
            [
                'number' => 5,
                'title' => __('home.full_intro_flow_step_5_title'),
                'desc' => __('home.full_intro_flow_step_5_desc'),
                'image' => asset('images/product-intro/circleProductTier.png'),
                'badge' => 'bg-brand-highlight text-brand-dark',
            ],
            [
                'number' => 6,
                'title' => __('home.full_intro_flow_step_6_title'),
                'desc' => __('home.full_intro_flow_step_6_desc'),
                'image' => asset('images/product-intro/financial.png'),
                'badge' => 'bg-brand-primary text-white',
            ],
        ];
    @endphp

    <section class="py-14">
        <div class="mx-auto grid max-w-7xl gap-6 px-6 md:grid-cols-3 lg:px-8">
            <div class="rounded-[1.6rem] border border-brand-soft/70 bg-white p-6 shadow-[0_16px_36px_rgba(90,30,14,0.08)]">
                <div class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-primary/70">01</div>
                <h2 class="mt-3 text-xl font-bold text-brand-dark">{{ __('home.full_intro_kpi_1_title') }}</h2>
                <p class="mt-2 text-base leading-7 text-brand-primary/75">{{ __('home.full_intro_kpi_1_desc') }}</p>
            </div>
            <div class="rounded-[1.6rem] border border-brand-soft/70 bg-white p-6 shadow-[0_16px_36px_rgba(90,30,14,0.08)]">
                <div class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-primary/70">02</div>
                <h2 class="mt-3 text-xl font-bold text-brand-dark">{{ __('home.full_intro_kpi_2_title') }}</h2>
                <p class="mt-2 text-base leading-7 text-brand-primary/75">{{ __('home.full_intro_kpi_2_desc') }}</p>
            </div>
            <div class="rounded-[1.6rem] border border-brand-soft/70 bg-white p-6 shadow-[0_16px_36px_rgba(90,30,14,0.08)]">
                <div class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-primary/70">03</div>
                <h2 class="mt-3 text-xl font-bold text-brand-dark">{{ __('home.full_intro_kpi_3_title') }}</h2>
                <p class="mt-2 text-base leading-7 text-brand-primary/75">{{ __('home.full_intro_kpi_3_desc') }}</p>
            </div>
        </div>
    </section>

    <section class="border-t border-brand-soft/60 bg-white py-16">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mb-10 text-center">
                <h2 class="text-3xl font-bold tracking-tight text-brand-dark sm:text-4xl">{{ __('home.full_intro_flow_title') }}</h2>
                <p class="mt-3 text-lg text-brand-primary/75">{{ __('home.full_intro_flow_desc') }}</p>
            </div>

            <div class="space-y-6">
                @foreach ($flowSteps as $step)
                    <div class="rounded-[1.7rem] border border-brand-soft/60 bg-brand-soft/20 p-6 sm:p-7">
                        <div class="grid gap-5 lg:grid-cols-[1fr_360px] lg:items-center">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full font-bold {{ $step['badge'] }}">{{ $step['number'] }}</span>
                                <div>
                                    <h3 class="text-xl font-bold text-brand-dark">{{ $step['title'] }}</h3>
                                    <p class="mt-2 text-base leading-7 text-brand-primary/80">{{ $step['desc'] }}</p>
                                </div>
                            </div>
                            <div class="overflow-hidden rounded-2xl border border-brand-soft/70 bg-white/80">
                                <img
                                    src="{{ $step['image'] }}"
                                    alt="{{ $step['title'] }}"
                                    loading="lazy"
                                    class="h-full w-full object-cover object-top"
                                >
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="border-t border-brand-soft/60 bg-brand-soft/16 py-16">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mb-8 text-center">
                <h2 class="text-3xl font-bold tracking-tight text-brand-dark sm:text-4xl">{{ __('home.full_intro_feature_title') }}</h2>
                <p class="mt-3 text-lg text-brand-primary/75">{{ __('home.full_intro_feature_desc') }}</p>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <div class="rounded-[1.6rem] border border-brand-soft/60 bg-white p-6">
                    <h3 class="text-xl font-bold text-brand-dark">{{ __('home.full_intro_feature_1_title') }}</h3>
                    <p class="mt-2 text-base leading-7 text-brand-primary/75">{{ __('home.full_intro_feature_1_desc') }}</p>
                </div>
                <div class="rounded-[1.6rem] border border-brand-soft/60 bg-white p-6">
                    <h3 class="text-xl font-bold text-brand-dark">{{ __('home.full_intro_feature_2_title') }}</h3>
                    <p class="mt-2 text-base leading-7 text-brand-primary/75">{{ __('home.full_intro_feature_2_desc') }}</p>
                </div>
                <div class="rounded-[1.6rem] border border-brand-soft/60 bg-white p-6">
                    <h3 class="text-xl font-bold text-brand-dark">{{ __('home.full_intro_feature_3_title') }}</h3>
                    <p class="mt-2 text-base leading-7 text-brand-primary/75">{{ __('home.full_intro_feature_3_desc') }}</p>
                </div>
                <div class="rounded-[1.6rem] border border-brand-soft/60 bg-white p-6">
                    <h3 class="text-xl font-bold text-brand-dark">{{ __('home.full_intro_feature_4_title') }}</h3>
                    <p class="mt-2 text-base leading-7 text-brand-primary/75">{{ __('home.full_intro_feature_4_desc') }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="border-t border-brand-soft/60 bg-brand-dark py-16 text-white">
        <div class="mx-auto max-w-5xl px-6 text-center lg:px-8">
            <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('home.full_intro_join_title') }}</h2>
            <p class="mx-auto mt-4 max-w-3xl text-lg leading-8 text-white/80">{{ __('home.full_intro_join_desc') }}</p>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 rounded-2xl bg-brand-highlight px-5 py-3 text-base font-semibold text-brand-dark transition hover:-translate-y-0.5 hover:bg-brand-soft">
                        {{ __('home.full_intro_join_button') }}
                    </a>
                @else
                    <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-2xl bg-brand-highlight px-5 py-3 text-base font-semibold text-brand-dark transition hover:-translate-y-0.5 hover:bg-brand-soft">
                        {{ __('home.full_intro_join_button') }}
                    </a>
                @endauth
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-2xl border border-white/20 bg-white/10 px-5 py-3 text-base font-semibold text-white transition hover:bg-white/15">
                    {{ __('home.full_intro_back_home') }}
                </a>
            </div>
        </div>
    </section>
</div>
@endsection
