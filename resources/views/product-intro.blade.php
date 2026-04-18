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
@php
    $heroSnapshots = [
        [
            'title' => __('home.full_intro_flow_step_1_title'),
            'image' => asset('images/product-intro/productManagement.png'),
        ],
        [
            'title' => __('home.full_intro_flow_step_4_title'),
            'image' => asset('images/product-intro/billboard.png'),
        ],
        [
            'title' => __('home.full_intro_flow_step_5_title'),
            'image' => asset('images/product-intro/ticket.png'),
        ],
    ];

    $kpiCards = [
        [
            'number' => '01',
            'title' => __('home.full_intro_kpi_1_title'),
            'desc' => __('home.full_intro_kpi_1_desc'),
        ],
        [
            'number' => '02',
            'title' => __('home.full_intro_kpi_2_title'),
            'desc' => __('home.full_intro_kpi_2_desc'),
        ],
        [
            'number' => '03',
            'title' => __('home.full_intro_kpi_3_title'),
            'desc' => __('home.full_intro_kpi_3_desc'),
        ],
    ];

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
            'image' => asset('images/product-intro/ticket.png'),
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

    $featureCards = [
        [
            'title' => __('home.full_intro_feature_1_title'),
            'desc' => __('home.full_intro_feature_1_desc'),
        ],
        [
            'title' => __('home.full_intro_feature_2_title'),
            'desc' => __('home.full_intro_feature_2_desc'),
        ],
        [
            'title' => __('home.full_intro_feature_3_title'),
            'desc' => __('home.full_intro_feature_3_desc'),
        ],
        [
            'title' => __('home.full_intro_feature_4_title'),
            'desc' => __('home.full_intro_feature_4_desc'),
        ],
    ];
@endphp
<style>
    .intro-reveal {
        opacity: 0;
        transform: translateY(30px) scale(0.98);
        transition:
            opacity 700ms ease,
            transform 700ms cubic-bezier(0.22, 1, 0.36, 1);
        transition-delay: var(--delay, 0ms);
    }

    .intro-reveal.is-visible {
        opacity: 1;
        transform: translateY(0) scale(1);
    }

    .intro-hero-orb {
        animation: introFloat 16s ease-in-out infinite;
    }

    .intro-hero-orb--slow {
        animation-duration: 22s;
        animation-delay: -5s;
    }

    .intro-hero-strip {
        position: relative;
        overflow: hidden;
    }

    .intro-hero-strip::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(130deg, rgba(255, 255, 255, 0.22), transparent 45%, rgba(255, 255, 255, 0.1));
        pointer-events: none;
    }

    .intro-kpi-card {
        transition:
            transform 260ms ease,
            box-shadow 260ms ease,
            border-color 260ms ease;
    }

    .intro-kpi-card:hover {
        transform: translateY(-6px);
        border-color: rgba(236, 144, 87, 0.45);
        box-shadow: 0 26px 52px rgba(90, 30, 14, 0.14);
    }

    .intro-flow-card {
        position: relative;
        overflow: hidden;
        transition:
            transform 280ms ease,
            box-shadow 280ms ease,
            border-color 280ms ease;
    }

    .intro-flow-card::before {
        content: '';
        position: absolute;
        inset: 0;
        pointer-events: none;
        background: radial-gradient(circle at top right, rgba(246, 174, 45, 0.18), transparent 36%);
        opacity: 0;
        transition: opacity 280ms ease;
    }

    .intro-flow-card:hover {
        transform: translateY(-4px);
        border-color: rgba(236, 144, 87, 0.4);
        box-shadow: 0 30px 56px rgba(90, 30, 14, 0.14);
    }

    .intro-flow-card:hover::before {
        opacity: 1;
    }

    .intro-flow-image-wrap {
        position: relative;
        overflow: hidden;
    }

    .intro-flow-image-wrap::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(160deg, rgba(255, 255, 255, 0.22), transparent 48%);
        pointer-events: none;
    }

    .intro-flow-image {
        transition: transform 800ms cubic-bezier(0.22, 1, 0.36, 1);
    }

    .intro-flow-card:hover .intro-flow-image {
        transform: scale(1.04);
    }

    .intro-feature-card {
        transition:
            transform 260ms ease,
            box-shadow 260ms ease;
    }

    .intro-feature-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 22px 48px rgba(90, 30, 14, 0.12);
    }

    .intro-cta-pulse {
        animation: introPulse 2.9s ease-in-out infinite;
    }

    @keyframes introFloat {
        0%, 100% {
            transform: translate3d(0, 0, 0) scale(1);
        }
        50% {
            transform: translate3d(0, -14px, 0) scale(1.04);
        }
    }

    @keyframes introPulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.03);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .intro-reveal,
        .intro-kpi-card,
        .intro-flow-card,
        .intro-flow-image,
        .intro-feature-card,
        .intro-hero-orb,
        .intro-cta-pulse {
            animation: none;
            transition: none;
            transform: none;
            opacity: 1;
        }
    }
</style>
<div class="min-h-screen bg-brand-soft/20 text-brand-dark">
    <section class="relative isolate overflow-hidden border-b border-brand-soft/70 bg-brand-dark text-white">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(255,214,112,0.2),_transparent_36%),linear-gradient(130deg,_rgba(90,30,14,0.98),_rgba(236,144,87,0.9))]"></div>
        <div class="intro-hero-orb absolute -left-12 top-20 h-52 w-52 rounded-full bg-brand-highlight/20 blur-3xl"></div>
        <div class="intro-hero-orb intro-hero-orb--slow absolute -right-10 bottom-6 h-48 w-48 rounded-full bg-brand-soft/20 blur-3xl"></div>

        <div class="relative mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-24">
            <span class="intro-reveal inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-1.5 text-sm font-semibold tracking-[0.2em] text-brand-highlight" data-reveal>
                {{ __('home.full_intro_badge') }}
            </span>
            <h1 class="intro-reveal mt-6 max-w-4xl text-4xl font-bold tracking-tight sm:text-5xl" data-reveal style="--delay: 80ms;">
                {{ __('home.full_intro_title') }}
            </h1>
            <p class="intro-reveal mt-5 max-w-3xl text-lg leading-8 text-white/80 sm:text-xl sm:leading-9" data-reveal style="--delay: 140ms;">
                {{ __('home.full_intro_desc') }}
            </p>

            <div class="intro-reveal mt-8 flex flex-wrap gap-4" data-reveal style="--delay: 220ms;">
                @auth
                    <a href="{{ route('dashboard') }}" class="intro-cta-pulse inline-flex items-center gap-2 rounded-2xl bg-brand-highlight px-5 py-3 text-base font-semibold text-brand-dark transition hover:-translate-y-0.5 hover:bg-brand-soft">
                        {{ __('home.full_intro_join_button') }}
                    </a>
                @else
                    <a href="{{ route('register') }}" class="intro-cta-pulse inline-flex items-center gap-2 rounded-2xl bg-brand-highlight px-5 py-3 text-base font-semibold text-brand-dark transition hover:-translate-y-0.5 hover:bg-brand-soft">
                        {{ __('home.full_intro_join_button') }}
                    </a>
                @endauth
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-2xl border border-white/20 bg-white/10 px-5 py-3 text-base font-semibold text-white transition hover:bg-white/15">
                    {{ __('home.full_intro_back_home') }}
                </a>
            </div>

            <div class="mt-10 grid max-w-5xl gap-4 md:grid-cols-3">
                @foreach ($heroSnapshots as $snapshot)
                    <div class="intro-reveal intro-hero-strip overflow-hidden rounded-2xl border border-white/15 bg-white/10 p-2 backdrop-blur" data-reveal style="--delay: {{ 280 + ($loop->index * 70) }}ms;">
                        <div class="overflow-hidden rounded-xl border border-white/10 bg-black/20">
                            <img
                                src="{{ $snapshot['image'] }}"
                                alt="{{ $snapshot['title'] }}"
                                loading="lazy"
                                class="h-32 w-full object-cover object-top"
                            >
                        </div>
                        <p class="mt-2 text-xs font-semibold text-white/80">{{ $snapshot['title'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-14">
        <div class="mx-auto grid max-w-7xl gap-6 px-6 md:grid-cols-3 lg:px-8">
            @foreach ($kpiCards as $kpi)
                <div class="intro-reveal intro-kpi-card rounded-[1.6rem] border border-brand-soft/70 bg-white p-6 shadow-[0_16px_36px_rgba(90,30,14,0.08)]" data-reveal style="--delay: {{ 40 + ($loop->index * 90) }}ms;">
                    <div class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-primary/70">{{ $kpi['number'] }}</div>
                    <h2 class="mt-3 text-xl font-bold text-brand-dark">{{ $kpi['title'] }}</h2>
                    <p class="mt-2 text-base leading-7 text-brand-primary/75">{{ $kpi['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="border-t border-brand-soft/60 bg-white py-16">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="intro-reveal mb-10 text-center" data-reveal>
                <h2 class="text-3xl font-bold tracking-tight text-brand-dark sm:text-4xl">{{ __('home.full_intro_flow_title') }}</h2>
                <p class="mt-3 text-lg text-brand-primary/75">{{ __('home.full_intro_flow_desc') }}</p>
            </div>

            <div class="relative space-y-6 lg:space-y-7">
                <div class="pointer-events-none absolute left-5 top-6 hidden h-[calc(100%-3.5rem)] w-[2px] bg-gradient-to-b from-brand-primary/35 via-brand-highlight/70 to-transparent lg:block"></div>
                @foreach ($flowSteps as $step)
                    <div class="intro-reveal intro-flow-card rounded-[1.7rem] border border-brand-soft/60 bg-brand-soft/20 p-6 sm:p-7" data-reveal style="--delay: {{ 40 + ($loop->index * 70) }}ms;">
                        <div class="grid gap-5 lg:grid-cols-[1fr_420px] lg:items-center">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start {{ $loop->even ? 'lg:order-2' : '' }}">
                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full font-bold {{ $step['badge'] }}">{{ $step['number'] }}</span>
                                <div>
                                    <h3 class="text-xl font-bold text-brand-dark">{{ $step['title'] }}</h3>
                                    <p class="mt-2 text-base leading-7 text-brand-primary/80">{{ $step['desc'] }}</p>
                                </div>
                            </div>
                            <div class="intro-flow-image-wrap overflow-hidden rounded-2xl border border-brand-soft/70 bg-white/80 {{ $loop->even ? 'lg:order-1' : '' }}">
                                <img
                                    src="{{ $step['image'] }}"
                                    alt="{{ $step['title'] }}"
                                    loading="lazy"
                                    class="intro-flow-image h-full w-full object-cover object-top"
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
            <div class="intro-reveal mb-8 text-center" data-reveal>
                <h2 class="text-3xl font-bold tracking-tight text-brand-dark sm:text-4xl">{{ __('home.full_intro_feature_title') }}</h2>
                <p class="mt-3 text-lg text-brand-primary/75">{{ __('home.full_intro_feature_desc') }}</p>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                @foreach ($featureCards as $feature)
                    <div class="intro-reveal intro-feature-card rounded-[1.6rem] border border-brand-soft/60 bg-white p-6 shadow-[0_14px_34px_rgba(90,30,14,0.06)]" data-reveal style="--delay: {{ 50 + ($loop->index * 80) }}ms;">
                        <h3 class="text-xl font-bold text-brand-dark">{{ $feature['title'] }}</h3>
                        <p class="mt-2 text-base leading-7 text-brand-primary/75">{{ $feature['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="border-t border-brand-soft/60 bg-brand-dark py-16 text-white">
        <div class="intro-reveal mx-auto max-w-5xl px-6 text-center lg:px-8" data-reveal>
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
<script>
    (function () {
        const nodes = document.querySelectorAll('[data-reveal]');
        if (!nodes.length) {
            return;
        }

        if (!('IntersectionObserver' in window)) {
            nodes.forEach((node) => node.classList.add('is-visible'));
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            });
        }, { threshold: 0.16, rootMargin: '0px 0px -8% 0px' });

        nodes.forEach((node) => observer.observe(node));
    })();
</script>
@endsection
