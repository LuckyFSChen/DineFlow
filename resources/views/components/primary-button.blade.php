<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-brand-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-brand-accent hover:text-brand-dark focus:bg-brand-accent focus:text-brand-dark active:bg-brand-dark focus:outline-none focus:ring-2 focus:ring-brand-highlight focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
