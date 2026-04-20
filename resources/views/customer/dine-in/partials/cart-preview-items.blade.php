@forelse($cartPreviewItems->take(6) as $item)
    <div class="cart-preview-item-shell" data-cart-preview-shell>
        <div class="cart-preview-item-delete" data-cart-preview-delete>
            <span class="rounded-full border border-white/40 bg-white/22 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-rose-50 shadow-sm">
                {{ __('customer.remove_item') }}
            </span>
        </div>
        <article class="cart-preview-item rounded-2xl border border-brand-soft/70 bg-brand-soft/15 px-3 py-3" data-cart-preview-item>
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-brand-dark">{{ $item['product_name'] ?? __('customer.product_default_name') }}</p>
                    @if(!empty($item['option_label']))
                        <p class="mt-1 text-xs text-brand-primary/75">{{ $item['option_label'] }}</p>
                    @endif
                    @if(!empty($item['item_note']))
                        <p class="mt-1 text-xs text-amber-700">{{ __('customer.item_note_prefix') }} {{ $item['item_note'] }}</p>
                    @endif
                </div>
                <p class="shrink-0 text-xs font-semibold text-brand-accent">{{ $currencySymbol }} {{ number_format((int) ($item['subtotal'] ?? 0)) }}</p>
            </div>

            <div class="mt-3 flex items-center justify-between gap-3">
                <div class="inline-flex shrink-0 items-center gap-2 rounded-xl border border-brand-soft/70 bg-white/80 px-2 py-1 shadow-sm">
                    <form method="POST" action="{{ route('customer.dinein.cart.items.update', ['store' => $store, 'table' => $table, 'lineKey' => $item['line_key']]) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="action" value="decrease">
                        <button type="submit" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-brand-soft bg-white text-sm font-bold text-brand-primary transition hover:bg-brand-soft/30" aria-label="{{ __('customer.decrease_qty') }}">-</button>
                    </form>
                    <span class="min-w-[1.8rem] text-center text-sm font-semibold text-brand-dark">{{ $item['qty'] ?? 1 }}</span>
                    <form method="POST" action="{{ route('customer.dinein.cart.items.update', ['store' => $store, 'table' => $table, 'lineKey' => $item['line_key']]) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="action" value="increase">
                        <button type="submit" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-brand-soft bg-white text-sm font-bold text-brand-primary transition hover:bg-brand-soft/30" aria-label="{{ __('customer.increase_qty') }}">+</button>
                    </form>
                </div>

                <form method="POST" action="{{ route('customer.dinein.cart.items.destroy', ['store' => $store, 'table' => $table, 'lineKey' => $item['line_key']]) }}" data-cart-preview-remove-form>
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex shrink-0 items-center justify-center whitespace-nowrap rounded-lg border border-rose-200 bg-white px-2.5 py-1 text-xs font-semibold text-rose-600 transition hover:bg-rose-50">{{ __('customer.remove_item') }}</button>
                </form>
            </div>
        </article>
    </div>
@empty
    <div class="rounded-2xl border border-dashed border-brand-soft/80 bg-brand-soft/10 px-3 py-8 text-center text-sm text-brand-primary/75">
        {{ __('customer.no_products_available') }}
    </div>
@endforelse

@if($cartPreviewItems->count() > 6)
    <p class="text-center text-xs font-semibold text-brand-primary/70">{{ __('customer.more_items_in_cart', ['count' => $cartPreviewItems->count() - 6]) }}</p>
@endif
