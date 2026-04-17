@extends('layouts.app')

@section('title', __('privacy.title') . ' | ' . config('app.name', 'DineFlow'))
@section('meta_description', __('privacy.meta_description'))
@section('canonical', route('privacy.policy'))
@section('meta_robots', 'index,follow,max-image-preview:large')

@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => __('privacy.title'),
    'description' => __('privacy.meta_description'),
    'url' => route('privacy.policy'),
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
<div class="bg-brand-soft/20 text-brand-dark">
    <section class="relative isolate overflow-hidden border-b border-brand-soft/60 bg-brand-dark text-white">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(255,214,112,0.18),_transparent_34%),linear-gradient(135deg,_rgba(90,30,14,0.98),_rgba(236,144,87,0.9))]"></div>
        <div class="relative mx-auto max-w-5xl px-6 py-16 lg:px-8 lg:py-20">
            <span class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-4 py-1.5 text-sm font-semibold tracking-[0.18em] text-brand-highlight">
                {{ __('privacy.badge') }}
            </span>
            <h1 class="mt-6 text-4xl font-bold tracking-tight sm:text-5xl">{{ __('privacy.title') }}</h1>
            <p class="mt-5 max-w-3xl text-lg leading-8 text-white/80">
                {{ __('privacy.intro') }}
            </p>
            <p class="mt-4 text-sm text-white/65">{{ __('privacy.last_updated', ['date' => '2026-04-18']) }}</p>
        </div>
    </section>

    <section class="py-14">
        <div class="mx-auto max-w-5xl px-6 lg:px-8">
            <div class="space-y-6">
                @foreach (__('privacy.sections') as $section)
                    <article class="rounded-[1.75rem] border border-brand-soft/60 bg-white p-7 shadow-[0_18px_40px_rgba(90,30,14,0.08)]">
                        <h2 class="text-2xl font-bold tracking-tight text-brand-dark">{{ $section['title'] }}</h2>
                        <p class="mt-3 text-base leading-8 text-brand-primary/80">{{ $section['body'] }}</p>
                    </article>
                @endforeach
            </div>

            <div class="mt-8 rounded-[1.75rem] border border-brand-soft/60 bg-brand-soft/25 p-7">
                <h2 class="text-2xl font-bold tracking-tight text-brand-dark">{{ __('privacy.contact_title') }}</h2>
                <p class="mt-3 text-base leading-8 text-brand-primary/80">{{ __('privacy.contact_body') }}</p>
                <div class="mt-4 flex flex-wrap gap-4 text-base font-semibold">
                    <a href="mailto:bigtw178@gmail.com" class="text-brand-primary transition hover:text-brand-accent">bigtw178@gmail.com</a>
                    <a href="tel:0979300504" class="text-brand-primary transition hover:text-brand-accent">0979-300-504</a>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
