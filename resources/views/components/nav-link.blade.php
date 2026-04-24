@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex shrink-0 items-center whitespace-nowrap px-1 pt-1 border-b-2 border-brand-accent text-sm font-medium leading-5 text-brand-dark focus:outline-none focus:border-brand-primary transition duration-150 ease-in-out'
            : 'inline-flex shrink-0 items-center whitespace-nowrap px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
