@props([
    'title',
    'subtitle' => null,
    'align' => 'start',
])

<div class="backend-hero mb-8 overflow-hidden rounded-3xl border border-slate-700/60 bg-slate-900 shadow-2xl">
    <div class="relative">
        <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(150deg,rgba(8,22,28,0.96),rgba(17,24,39,0.96))]"></div>

        <div class="relative px-5 py-5 md:px-7 md:py-6">
            <div class="flex flex-col gap-4 md:flex-row {{ $align === 'center' ? 'md:items-center' : 'md:items-start' }} md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight !text-white">{{ $title }}</h1>
                    @if($subtitle)
                        <p class="mt-2 text-sm md:text-base !text-slate-200">{{ $subtitle }}</p>
                    @endif
                </div>

                @if (isset($actions))
                    <div class="flex flex-wrap items-center gap-2">
                        {{ $actions }}
                    </div>
                @endif
            </div>

            @if (isset($extra))
                <div class="mt-4">
                    {{ $extra }}
                </div>
            @endif
        </div>
    </div>
</div>
