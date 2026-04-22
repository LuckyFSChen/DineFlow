@extends('layouts.app')

@section('title', __('merchant_inquiry.section_title') . ' | ' . config('app.name', 'DineFlow'))
@section('meta_description', __('merchant_inquiry.section_desc'))
@section('canonical', route('product.pricing-contact'))
@section('meta_robots', 'index,follow,max-image-preview:large')
@section('meta_image', asset('images/product-intro/financial.png'))

@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => __('merchant_inquiry.section_title'),
    'description' => __('merchant_inquiry.section_desc'),
    'url' => route('product.pricing-contact'),
    'inLanguage' => str_replace('_', '-', app()->getLocale()),
    'isPartOf' => [
        '@type' => 'WebSite',
        'name' => config('app.name', 'DineFlow'),
        'url' => url('/'),
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@php
    $heroCards = [
        [
            'title' => __('merchant_inquiry.section_badge'),
            'desc' => __('merchant_inquiry.sync_note'),
        ],
        [
            'title' => __('merchant_inquiry.public_price_note'),
            'desc' => __('merchant_inquiry.section_desc'),
        ],
        [
            'title' => __('merchant_inquiry.contact_badge'),
            'desc' => __('merchant_inquiry.contact_desc'),
        ],
    ];
@endphp

@section('content')
<div class="min-h-screen bg-[linear-gradient(180deg,#fffdf8_0%,#fff6ee_30%,#ffffff_100%)] text-brand-dark">
    <section class="relative isolate overflow-hidden border-b border-brand-soft/60 bg-brand-dark text-white">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(255,214,112,0.24),_transparent_34%),linear-gradient(135deg,_rgba(90,30,14,0.98),_rgba(236,144,87,0.9))]"></div>
        <div class="absolute -left-12 top-16 h-44 w-44 rounded-full bg-brand-accent/20 blur-3xl"></div>
        <div class="absolute right-0 top-0 h-64 w-64 rounded-full bg-brand-highlight/12 blur-3xl"></div>

        <div class="relative mx-auto grid max-w-7xl gap-10 px-6 py-16 lg:grid-cols-[minmax(0,1.1fr)_minmax(24rem,0.9fr)] lg:px-8 lg:py-24">
            <div class="max-w-3xl">
                <span class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-4 py-1.5 text-sm font-semibold tracking-[0.2em] text-brand-highlight">
                    {{ __('merchant_inquiry.section_badge') }}
                </span>
                <h1 class="mt-6 text-4xl font-bold tracking-tight sm:text-5xl">
                    {{ __('merchant_inquiry.section_title') }}
                </h1>
                <p class="mt-5 max-w-2xl text-lg leading-8 text-white/78 sm:text-xl sm:leading-9">
                    {{ __('merchant_inquiry.section_desc') }}
                </p>
                <p class="mt-4 max-w-2xl text-sm font-medium tracking-wide text-brand-highlight/85">
                    {{ __('merchant_inquiry.sync_note') }}
                </p>

                <div class="mt-8 flex flex-wrap gap-4">
                    <a href="#pricing-contact" class="inline-flex items-center gap-2 rounded-2xl bg-brand-highlight px-5 py-3 text-base font-semibold text-brand-dark shadow-lg shadow-brand-highlight/25 transition hover:-translate-y-0.5 hover:bg-brand-soft">
                        {{ __('merchant_inquiry.submit') }}
                    </a>
                    <a href="{{ route('product.intro') }}" class="inline-flex items-center gap-2 rounded-2xl border border-white/20 bg-white/10 px-5 py-3 text-base font-semibold text-white transition hover:bg-white/15">
                        {{ __('home.merchant_intro_cta') }}
                    </a>
                </div>
            </div>

            <div class="grid gap-4">
                @foreach($heroCards as $card)
                    <article class="rounded-[1.8rem] border border-white/10 bg-white/10 p-6 shadow-[0_18px_45px_rgba(0,0,0,0.18)] backdrop-blur">
                        <p class="text-sm font-semibold uppercase tracking-[0.22em] text-brand-highlight/85">{{ $card['title'] }}</p>
                        <p class="mt-3 text-base leading-8 text-white/78">{{ $card['desc'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    @include('partials.product-intro-pricing-contact', [
        'plansByTier' => $plansByTier ?? collect(),
        'pricingContactReturnTo' => route('product.pricing-contact'),
    ])
</div>
@endsection
