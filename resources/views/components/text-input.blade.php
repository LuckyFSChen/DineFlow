@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-brand-primary focus:ring-brand-highlight rounded-md shadow-sm']) }}>
