@props([
    'rating' => 0,
    'max' => 5,
    'size' => 'h-4 w-4',
    'gap' => 'gap-0.5',
    'activeClass' => 'text-amber-400',
    'inactiveClass' => 'text-amber-100',
    'strokeClass' => 'text-amber-500/70',
])

@php
    $max = max(1, (int) $max);
    $normalizedRating = max(0, min($max, (float) $rating));
    $starPath = 'M12 3.75l2.67 5.41 5.97.87-4.32 4.21 1.02 5.95L12 17.41l-5.34 2.8 1.02-5.95-4.32-4.21 5.97-.87L12 3.75z';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center {$gap}"]) }} aria-hidden="true">
    @for($index = 1; $index <= $max; $index++)
        @php
            $fill = max(0, min(1, $normalizedRating - ($index - 1)));
        @endphp

        <span class="relative inline-flex shrink-0 {{ $size }}">
            <svg class="{{ $size }} {{ $inactiveClass }}" viewBox="0 0 24 24" fill="currentColor">
                <path d="{{ $starPath }}" />
            </svg>

            @if($fill > 0)
                <span class="absolute inset-y-0 left-0 overflow-hidden" style="width: {{ $fill * 100 }}%">
                    <svg class="{{ $size }} {{ $activeClass }}" viewBox="0 0 24 24" fill="currentColor">
                        <path d="{{ $starPath }}" />
                    </svg>
                </span>
            @endif

            <svg class="pointer-events-none absolute inset-0 {{ $size }} {{ $strokeClass }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.35">
                <path d="{{ $starPath }}" />
            </svg>
        </span>
    @endfor
</span>
