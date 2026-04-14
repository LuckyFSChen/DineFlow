@extends('layouts.app')

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
        $previewCards = [
            [
                'title' => __('home.full_intro_flow_step_1_title'),
                'tag' => 'Store Setup',
                'accent' => 'bg-brand-primary/15',
            ],
            [
                'title' => __('home.full_intro_flow_step_2_title'),
                'tag' => 'QR Entry',
                'accent' => 'bg-brand-highlight/25',
            ],
            [
                'title' => __('home.full_intro_flow_step_3_title'),
                'tag' => 'Order Board',
                'accent' => 'bg-emerald-200/45',
            ],
            [
                'title' => __('home.full_intro_flow_step_4_title'),
                'tag' => 'Kitchen Sync',
                'accent' => 'bg-orange-200/40',
            ],
            [
                'title' => __('home.full_intro_flow_step_5_title'),
                'tag' => 'Reports',
                'accent' => 'bg-sky-200/45',
            ],
            [
                'title' => __('home.full_intro_feature_title'),
                'tag' => 'Feature View',
                'accent' => 'bg-rose-200/35',
            ],
        ];
    @endphp

    <section class="border-t border-brand-soft/60 bg-white py-16">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mb-8 flex items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-primary/70">Product Preview</p>
                    <h2 class="mt-2 text-3xl font-bold tracking-tight text-brand-dark sm:text-4xl">{{ __('home.full_intro_flow_title') }}</h2>
                </div>
                <p class="hidden text-sm font-medium text-brand-primary/70 md:block">6 screens</p>
            </div>

            <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($previewCards as $index => $previewCard)
                    <article class="group overflow-hidden rounded-[1.6rem] border border-brand-soft/60 bg-white shadow-[0_14px_36px_rgba(90,30,14,0.08)] transition hover:-translate-y-1 hover:shadow-[0_18px_44px_rgba(90,30,14,0.13)]">
                        <div class="aspect-[16/10] border-b border-brand-soft/60 bg-brand-soft/25 p-4">
                            <div class="flex h-full flex-col rounded-2xl border border-brand-soft/80 bg-white p-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <span class="h-2 w-2 rounded-full bg-brand-primary/30"></span>
                                        <span class="h-2 w-2 rounded-full bg-brand-primary/45"></span>
                                        <span class="h-2 w-2 rounded-full bg-brand-primary/60"></span>
                                    </div>
                                    <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.15em] text-brand-primary/80 {{ $previewCard['accent'] }}">
                                        {{ $previewCard['tag'] }}
                                    </span>
                                </div>

                                <div class="mt-3 grid grow grid-cols-3 gap-2">
                                    <div class="col-span-1 rounded-xl bg-brand-soft/60"></div>
                                    <div class="col-span-2 rounded-xl bg-brand-primary/10 p-2">
                                        <div class="h-2.5 w-3/4 rounded bg-brand-primary/25"></div>
                                        <div class="mt-2 h-2 w-full rounded bg-brand-primary/15"></div>
                                        <div class="mt-1.5 h-2 w-5/6 rounded bg-brand-primary/15"></div>
                                        <div class="mt-2.5 grid grid-cols-2 gap-1.5">
                                            <div class="h-9 rounded-lg bg-white"></div>
                                            <div class="h-9 rounded-lg bg-white"></div>
                                            <div class="h-9 rounded-lg bg-white"></div>
                                            <div class="h-9 rounded-lg bg-white"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-5">
                            <div class="text-xs font-semibold uppercase tracking-[0.15em] text-brand-primary/65">Preview {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</div>
                            <h3 class="mt-2 text-lg font-bold text-brand-dark">{{ $previewCard['title'] }}</h3>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

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
                <div class="rounded-[1.7rem] border border-brand-soft/60 bg-brand-soft/20 p-6 sm:p-7">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-primary font-bold text-white">1</span>
                        <div>
                            <h3 class="text-xl font-bold text-brand-dark">{{ __('home.full_intro_flow_step_1_title') }}</h3>
                            <p class="mt-2 text-base leading-7 text-brand-primary/80">{{ __('home.full_intro_flow_step_1_desc') }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-[1.7rem] border border-brand-soft/60 bg-brand-soft/20 p-6 sm:p-7">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-primary font-bold text-white">2</span>
                        <div>
                            <h3 class="text-xl font-bold text-brand-dark">{{ __('home.full_intro_flow_step_2_title') }}</h3>
                            <p class="mt-2 text-base leading-7 text-brand-primary/80">{{ __('home.full_intro_flow_step_2_desc') }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-[1.7rem] border border-brand-soft/60 bg-brand-soft/20 p-6 sm:p-7">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-primary font-bold text-white">3</span>
                        <div>
                            <h3 class="text-xl font-bold text-brand-dark">{{ __('home.full_intro_flow_step_3_title') }}</h3>
                            <p class="mt-2 text-base leading-7 text-brand-primary/80">{{ __('home.full_intro_flow_step_3_desc') }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-[1.7rem] border border-brand-soft/60 bg-brand-soft/20 p-6 sm:p-7">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-primary font-bold text-white">4</span>
                        <div>
                            <h3 class="text-xl font-bold text-brand-dark">{{ __('home.full_intro_flow_step_4_title') }}</h3>
                            <p class="mt-2 text-base leading-7 text-brand-primary/80">{{ __('home.full_intro_flow_step_4_desc') }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-[1.7rem] border border-brand-soft/60 bg-brand-soft/20 p-6 sm:p-7">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-highlight font-bold text-brand-dark">5</span>
                        <div>
                            <h3 class="text-xl font-bold text-brand-dark">{{ __('home.full_intro_flow_step_5_title') }}</h3>
                            <p class="mt-2 text-base leading-7 text-brand-primary/80">{{ __('home.full_intro_flow_step_5_desc') }}</p>
                        </div>
                    </div>
                </div>
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
