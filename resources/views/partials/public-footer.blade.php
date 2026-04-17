<footer class="border-t border-brand-soft/50 bg-brand-dark">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-center gap-x-3 gap-y-1 px-6 py-3 text-center text-xs text-white/75 lg:px-8 lg:text-sm">
        <span class="font-semibold text-white">DineFlow</span>
        <span class="text-white/40">|</span>
        <span>{{ __('home.footer_subtitle') }}</span>
        <span class="text-white/30">|</span>
        <span>{{ __('footer.contact_label') }}</span>
        <a href="tel:0979300504" class="font-semibold text-brand-highlight transition hover:text-brand-soft">0979-300-504</a>
        <span class="text-white/35">/</span>
        <a href="mailto:bigtw178@gmail.com" class="font-semibold text-brand-highlight transition hover:text-brand-soft">bigtw178@gmail.com</a>
        <span class="text-white/30">|</span>
        <a href="{{ route('privacy.policy') }}" class="font-semibold text-brand-highlight transition hover:text-brand-soft">
            {{ __('footer.privacy_policy') }}
        </a>
        <span class="text-white/30">|</span>
        <span class="tracking-[0.08em] text-white/45">{{ __('footer.copyright', ['year' => now()->year]) }}</span>
    </div>
</footer>
