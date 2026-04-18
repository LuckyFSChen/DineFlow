@extends('layouts.app')

@section('content')
@php
    $currencyCode = strtolower((string) ($store->currency ?? 'twd'));
    $currencySymbol = match ($currencyCode) {
        'vnd' => 'VND',
        'cny' => 'CNY',
        'usd' => 'USD',
        default => 'NT$',
    };
@endphp
<div class="min-h-screen bg-slate-50" x-data="{}">
    <div class="mx-auto max-w-7xl px-6 py-10 lg:px-8">
        <div class="admin-hero mb-6 rounded-3xl px-5 py-5 md:px-7">
            <div class="mb-5 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ __('admin.products_page_title') }}</h1>
                    <p class="mt-2 text-slate-600">{{ $store->name }} · {{ __('admin.products_page_description') }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.stores.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-900 bg-slate-800 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700">{{ __('admin.products_back_to_stores') }}</a>
                    @if($store->is_active)
                        <a href="{{ route('admin.stores.kitchen', $store) }}" class="inline-flex items-center justify-center rounded-2xl border border-orange-300 bg-orange-50 px-4 py-3 text-sm font-semibold text-orange-700 transition hover:bg-orange-100">🍳 {{ __('admin.kitchen') }}</a>
                    @endif
                    <button type="button" id="create-category-btn" class="inline-flex items-center justify-center rounded-2xl border border-emerald-300 bg-emerald-50 px-5 py-3 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">{{ __('admin.products_btn_add_category') }}</button>
                    <button type="button" id="create-product-btn" class="inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500">{{ __('admin.products_btn_add_product') }}</button>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
            <div class="admin-kpi rounded-2xl p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('admin.products_stats_total') }}</p>
                <p class="value mt-2 text-slate-900">{{ $totalProducts }}</p>
            </div>
            <div class="admin-kpi rounded-2xl p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('admin.products_stats_available') }}</p>
                <p class="value mt-2 text-emerald-700">{{ $activeProducts }}</p>
            </div>
            <div class="admin-kpi rounded-2xl p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('admin.products_stats_with_options') }}</p>
                <p class="value mt-2 text-indigo-700">{{ $optionEnabledProducts }}</p>
            </div>
            </div>
        </div>

        <div class="mb-4">
            <div class="admin-pill-nav inline-flex items-center gap-2 rounded-full px-3 py-2 text-xs font-semibold text-slate-700">
                <span class="rounded-full bg-cyan-100 px-2 py-1 text-cyan-700">{{ $store->name }}</span>
                <span>{{ __('admin.products_sorting_hint') }}</span>
            </div>
        </div>

        <div id="product-flash" class="mb-6 hidden rounded-2xl border px-4 py-3 text-sm"></div>

        <div class="space-y-6">
            @forelse($categories as $category)
                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm" data-category-section data-category-id="{{ $category->id }}">
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-slate-900">{{ $category->name }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ $category->products->count() }} {{ __('admin.products_items_count_suffix') }} ・ {{ __('admin.products_sort_label') }} {{ $category->sort ?? 1 }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100" data-edit-category="{{ $category->id }}">{{ __('admin.products_btn_edit_category') }}</button>
                            <button type="button" class="inline-flex items-center justify-center rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700 transition hover:bg-amber-100" data-disable-category="{{ $category->id }}" data-category-name="{{ $category->name }}">{{ __('admin.products_btn_disable_category') }}</button>
                            <button type="button" class="inline-flex items-center justify-center rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-100" data-delete-category="{{ $category->id }}" data-category-name="{{ $category->name }}">{{ __('admin.products_btn_delete_category') }}</button>
                            <button type="button" class="inline-flex items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100" data-create-in-category="{{ $category->id }}">{{ __('admin.products_btn_add_product') }}</button>
                        </div>
                    </div>

                    @if($category->products->isNotEmpty())
                        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3" data-category-products data-category-id="{{ $category->id }}">
                            @foreach($category->products as $product)
                                @php
                                    $productImageUrl = filled($product->image)
                                        ? (\Illuminate\Support\Str::startsWith($product->image, ['http://', 'https://'])
                                            ? $product->image
                                            : asset('storage/' . ltrim($product->image, '/')))
                                        : null;
                                @endphp
                                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4" data-product-card data-product-id="{{ $product->id }}">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex min-w-0 flex-1 items-start gap-3">
                                            @if($productImageUrl)
                                                <img src="{{ $productImageUrl }}" alt="{{ $product->name }}" class="h-14 w-14 rounded-xl object-cover ring-1 ring-slate-200">
                                            @endif
                                            <div class="min-w-0">
                                            <h3 class="text-base font-semibold text-slate-900">{{ $product->name }}</h3>
                                            <p class="mt-1 text-sm text-slate-500">{{ $currencySymbol }} {{ number_format($product->price) }} ・ <span data-product-sort>{{ __('admin.products_sort_label') }} {{ $product->sort }}</span></p>
                                            <p class="mt-1 text-xs text-slate-500">{{ __('admin.products_form_cost') }}: {{ $currencySymbol }} {{ number_format((int) ($product->cost ?? 0)) }}</p>
                                            <p class="mt-1 text-xs text-slate-500">{{ __('admin.products_gross_margin_rate') }}: {{ (int) $product->price > 0 ? number_format((((int) $product->price - (int) ($product->cost ?? 0)) / (int) $product->price) * 100, 1) : '0.0' }}%</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex cursor-grab select-none touch-none rounded-lg border border-slate-300 bg-white px-2 py-1 text-[11px] font-semibold text-slate-600 active:cursor-grabbing" data-drag-product-handle>{{ __('admin.products_drag_to_sort') }}</span>
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $product->is_active && ! $product->is_sold_out ? 'bg-emerald-50 text-emerald-700' : ($product->is_sold_out ? 'bg-amber-50 text-amber-700' : 'bg-slate-200 text-slate-600') }}">
                                                {{ $product->is_active && ! $product->is_sold_out ? __('admin.products_stats_available') : ($product->is_sold_out ? __('admin.products_status_sold_out') : __('admin.products_status_inactive')) }}
                                            </span>
                                        </div>
                                    </div>

                                    <p class="mt-3 line-clamp-2 text-sm text-slate-600">{{ $product->description ?: __('admin.products_empty_no_description') }}</p>
                                    <p class="mt-2 text-xs font-medium text-indigo-700">{{ !empty($product->option_groups) ? __('admin.products_status_has_options') : __('admin.products_status_no_options') }}</p>

                                    <div class="mt-4 flex gap-2">
                                        <button type="button" class="inline-flex flex-1 items-center justify-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100" data-edit-product="{{ $product->id }}">{{ __('admin.products_btn_edit') }}</button>
                                        <button type="button" class="inline-flex flex-1 items-center justify-center rounded-xl bg-rose-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-rose-500" data-delete-product="{{ $product->id }}" data-product-name="{{ $product->name }}">{{ __('admin.products_btn_delete') }}</button>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500" data-category-products data-category-id="{{ $category->id }}">
                            <div data-empty-placeholder>{{ __('admin.products_empty_no_products_in_category') }}</div>
                        </div>
                    @endif
                </section>
            @empty
                <div class="rounded-3xl border border-slate-200 bg-white px-6 py-12 text-center shadow-sm">
                    <p class="text-slate-600">{{ __('admin.products_empty_no_categories') }}</p>
                </div>
            @endforelse

            @if($inactiveCategories->isNotEmpty())
                <section class="rounded-3xl border border-amber-200 bg-amber-50/40 p-5 shadow-sm">
                    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-amber-900">{{ __('admin.products_inactive_categories_title') }}</h2>
                            <p class="mt-1 text-sm text-amber-700">{{ __('admin.products_inactive_categories_description') }}</p>
                        </div>
                        <span class="inline-flex w-fit items-center rounded-full border border-amber-200 bg-white px-3 py-1 text-xs font-semibold text-amber-700">{{ $inactiveCategories->count() }} {{ __('admin.products_unit_count') }}</span>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @foreach($inactiveCategories as $inactiveCategory)
                            <article class="rounded-2xl border border-amber-200 bg-white p-4">
                                <h3 class="text-base font-semibold text-slate-900">{{ $inactiveCategory->name }}</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ __('admin.products_sort_label') }} {{ $inactiveCategory->sort ?? 1 }} ・ {{ $inactiveCategory->products_count }} {{ __('admin.products_items_count_suffix') }}</p>
                                <div class="mt-3 flex gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex flex-1 items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100"
                                        data-enable-category="{{ $inactiveCategory->id }}"
                                        data-category-name="{{ $inactiveCategory->name }}"
                                    >
                                        {{ __('admin.products_btn_reactivate_category') }}
                                    </button>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
</div>

<div id="product-modal" class="fixed inset-0 z-[120] hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-3xl rounded-3xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <div>
                <h3 id="product-modal-title" class="text-lg font-bold text-slate-900">{{ __('admin.products_modal_product_title') }}</h3>
                <p class="text-xs text-slate-500">{{ __('admin.products_modal_product_description') }}</p>
            </div>
            <button type="button" id="product-modal-close" class="rounded-full p-2 text-slate-500 hover:bg-slate-100">✕</button>
        </div>

        <form id="product-modal-form" class="max-h-[75vh] overflow-y-auto px-6 py-5">
            <input type="hidden" name="_method" id="product-modal-method" value="POST">

            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('admin.products_form_product_name') }}</label>
                    <input type="text" name="name" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100" required>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('admin.products_form_category') }}</label>
                    <select name="category_id" id="modal-category" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100" required>
                        @foreach($categoryOptions as $option)
                            <option value="{{ $option['id'] }}">{{ $option['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('admin.products_form_price') }} ({{ $currencySymbol }})</label>
                    <input type="number" name="price" min="0" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100" required>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('admin.products_form_cost') }} ({{ $currencySymbol }})</label>
                    <input type="number" name="cost" min="0" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100" required>
                </div>



                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-600">商品圖片</label>
                    <div class="rounded-2xl border border-slate-300 bg-slate-50 p-3">
                        <input type="hidden" name="remove_image" id="modal-remove-image" value="0">
                        <div class="grid gap-4 md:grid-cols-[200px,1fr]">
                            <div>
                                <canvas id="modal-image-crop-preview" width="320" height="320" class="h-40 w-40 rounded-xl border border-slate-300 bg-white"></canvas>
                                <p id="modal-image-helper" class="mt-2 text-xs text-slate-500">尚未選擇圖片</p>
                            </div>
                            <div class="space-y-3">
                                <input type="file" id="modal-image-upload" accept="image/png,image/jpeg,image/webp" class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100">
                                <div>
                                    <label for="modal-image-zoom" class="mb-1 block text-xs font-semibold text-slate-600">縮放</label>
                                    <input id="modal-image-zoom" type="range" min="1" max="3" step="0.05" value="1" class="w-full">
                                </div>
                                <p class="text-xs text-slate-500">在預覽框內拖曳可調整裁切位置，儲存時會以方形裁切後上傳。</p>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" id="modal-image-reset" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">重設位置</button>
                                    <button type="button" id="modal-image-remove" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100">移除圖片</button>
                                </div>
                                <p class="text-[11px] text-slate-500">支援 JPG / PNG / WEBP，若超過 2MB 會自動壓縮後上傳。</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('admin.products_form_description') }}</label>
                    <textarea name="description" rows="3" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"></textarea>
                </div>

                <div class="md:col-span-2 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                    <input type="hidden" name="option_groups_json" id="option-groups-json-input" value="[]">

                    <div class="mb-2 flex flex-wrap gap-2">
                        <button type="button" data-option-template="steak" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">{{ __('admin.products_options_template_steak') }}</button>
                        <button type="button" data-option-template="combo" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">{{ __('admin.products_options_template_combo') }}</button>
                        <button type="button" data-option-clear-all class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100">{{ __('admin.products_options_clear_all') }}</button>
                        <button type="button" data-option-add-group class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500">{{ __('admin.products_options_add_group') }}</button>
                    </div>

                    <div class="mb-2 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-600">
                        <p id="option-editor-summary" class="font-medium">0 groups / 0 choices</p>
                        <div class="flex gap-2">
                            <button type="button" data-option-expand-all class="rounded-md border border-slate-300 bg-white px-2.5 py-1 font-semibold text-slate-700 hover:bg-slate-100">Expand all</button>
                            <button type="button" data-option-collapse-all class="rounded-md border border-slate-300 bg-white px-2.5 py-1 font-semibold text-slate-700 hover:bg-slate-100">Collapse all</button>
                        </div>
                    </div>

                    <p class="mb-2 text-xs text-slate-600">{{ __('admin.products_options_tree_edit_hint') }}</p>
                    <div id="option-groups-editor" class="space-y-3"></div>
                </div>

                <div>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="is_active" id="modal-is-active" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        {{ __('admin.products_form_published') }}
                    </label>
                </div>

                <div>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="is_sold_out" id="modal-is-sold-out" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        {{ __('admin.products_form_sold_out') }}
                    </label>
                </div>

                <div class="md:col-span-2">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="allow_item_note" id="modal-allow-item-note" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        {{ __('admin.products_form_allow_item_note') }}
                    </label>
                </div>
            </div>

            <div id="product-modal-error" class="mt-4 hidden rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700"></div>

            <div class="mt-6 flex gap-2">
                <button type="submit" id="product-modal-submit" class="inline-flex flex-1 items-center justify-center rounded-2xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500">{{ __('admin.products_btn_save_product') }}</button>
                <button type="button" id="product-modal-cancel" class="inline-flex flex-1 items-center justify-center rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">{{ __('admin.products_btn_cancel') }}</button>
            </div>
        </form>
    </div>
</div>

<div id="category-modal" class="fixed inset-0 z-[130] hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-xl rounded-3xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <div>
                <h3 id="category-modal-title" class="text-lg font-bold text-slate-900">{{ __('admin.products_modal_category_title') }}</h3>
                <p class="text-xs text-slate-500">{{ __('admin.products_modal_category_description') }}</p>
            </div>
            <button type="button" id="category-modal-close" class="rounded-full p-2 text-slate-500 hover:bg-slate-100">✕</button>
        </div>

        <form id="category-modal-form" class="px-6 py-5">
            <input type="hidden" name="_method" id="category-modal-method" value="POST">

            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('admin.products_form_category_name') }}</label>
                    <input type="text" name="name" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" required>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('admin.products_form_sort') }}</label>
                    <input type="number" name="sort" min="1" value="1" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </div>
            </div>

            <div id="category-modal-error" class="mt-4 hidden rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700"></div>

            <div class="mt-6 flex gap-2">
                <button type="submit" id="category-modal-submit" class="inline-flex flex-1 items-center justify-center rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-500">{{ __('admin.products_btn_save_category') }}</button>
                <button type="button" id="category-modal-cancel" class="inline-flex flex-1 items-center justify-center rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">{{ __('admin.products_btn_cancel') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
(() => {
    const csrfToken = '{{ csrf_token() }}';

    const createUrl = '{{ route('admin.stores.products.store', $store) }}';
    const editUrlTemplate = '{{ route('admin.stores.products.edit', [$store, '__PRODUCT__']) }}';
    const updateUrlTemplate = '{{ route('admin.stores.products.update', [$store, '__PRODUCT__']) }}';
    const deleteUrlTemplate = '{{ route('admin.stores.products.destroy', [$store, '__PRODUCT__']) }}';
    const reorderUrl = '{{ route('admin.stores.products.reorder', $store) }}';
    const moveUrl = '{{ route('admin.stores.products.move', $store) }}';
    const createCategoryUrl = '{{ route('admin.stores.categories.store', $store) }}';
    const editCategoryUrlTemplate = '{{ route('admin.stores.categories.edit', [$store, '__CATEGORY__']) }}';
    const updateCategoryUrlTemplate = '{{ route('admin.stores.categories.update', [$store, '__CATEGORY__']) }}';
    const disableCategoryUrlTemplate = '{{ route('admin.stores.categories.disable', [$store, '__CATEGORY__']) }}';
    const enableCategoryUrlTemplate = '{{ route('admin.stores.categories.enable', [$store, '__CATEGORY__']) }}';
    const deleteCategoryUrlTemplate = '{{ route('admin.stores.categories.destroy', [$store, '__CATEGORY__']) }}';

    const i18n = {
        modalCategoryTitleCreate: @json(__('admin.products_modal_category_title')),
        modalCategoryTitleEdit: @json(__('admin.products_btn_edit_category')),
        modalCategorySubmitCreate: @json(__('admin.products_btn_create_category')),
        modalCategorySubmitEdit: @json(__('admin.products_btn_update_category')),
        modalProductTitleCreate: @json(__('admin.products_modal_product_title')),
        modalProductTitleEdit: @json(__('admin.products_modal_product_title_edit')),
        modalProductSubmitCreate: @json(__('admin.products_btn_create_product')),
        modalProductSubmitEdit: @json(__('admin.products_btn_update_product')),
        readCategoryFailed: @json(__('admin.products_error_read_category_failed')),
        saveCategoryFailed: @json(__('admin.products_error_save_category_failed')),
        deleteCategoryFailed: @json(__('admin.products_error_delete_category_failed')),
        disableCategoryFailed: @json(__('admin.products_error_disable_category_failed')),
        enableCategoryFailed: @json(__('admin.products_error_enable_category_failed')),
        readProductFailed: @json(__('admin.products_error_read_product_failed')),
        sortUpdateFailed: @json(__('admin.products_error_sort_update_failed')),
        moveAcrossCategoryFailed: @json(__('admin.products_error_move_across_categories_failed')),
        moveUpdateFailed: @json(__('admin.products_error_move_update_failed')),
        saveFailed: @json(__('admin.products_error_save_failed')),
        deleteFailed: @json(__('admin.products_error_delete_failed')),
        categorySaved: @json(__('admin.products_success_category_saved')),
        categoryDeleted: @json(__('admin.products_success_category_deleted')),
        categoryDisabled: @json(__('admin.products_success_category_disabled')),
        categoryEnabled: @json(__('admin.products_success_category_enabled')),
        sortUpdated: @json(__('admin.products_success_sort_updated')),
        moveUpdated: @json(__('admin.products_success_move_updated')),
        saved: @json(__('admin.products_success_saved')),
        productDeleted: @json(__('admin.products_success_product_deleted')),
        sortLabel: @json(__('admin.products_sort_label')),
        dragShort: @json(__('admin.products_drag_short')),
        groupLabel: @json(__('admin.products_group_label')),
        removeGroup: @json(__('admin.products_group_delete')),
        groupNamePlaceholder: @json(__('admin.products_group_name_placeholder')),
        singleChoice: @json(__('admin.products_group_type_single')),
        multipleChoice: @json(__('admin.products_group_type_multiple')),
        requiredGroup: @json(__('admin.products_group_required')),
        optionsLabel: @json(__('admin.products_group_choices_list')),
        addChoice: @json(__('admin.products_group_add_choice')),
        choiceName: @json(__('admin.products_choice_name')),
        choicePrice: @json(__('admin.products_choice_price')),
        delete: @json(__('admin.products_btn_delete')),
        fallbackProduct: @json(__('admin.products_fallback_product')),
        fallbackCategory: @json(__('admin.products_fallback_category')),
        confirmDeleteCategory: @json(__('admin.products_confirm_delete_category', ['name' => '__name__'])),
        confirmDisableCategory: @json(__('admin.products_confirm_disable_category', ['name' => '__name__'])),
        confirmEnableCategory: @json(__('admin.products_confirm_enable_category', ['name' => '__name__'])),
        confirmDeleteProduct: @json(__('admin.products_confirm_delete_product', ['name' => '__name__'])),
        confirmClearAllOptions: @json(__('admin.products_confirm_clear_all_options')),
        confirmApplyTemplate: @json(__('admin.products_confirm_apply_template')),
        templateSteakDoneness: @json(__('admin.products_template_steak_doneness')),
        templateSteakRare: @json(__('admin.products_template_steak_rare')),
        templateSteakMedium: @json(__('admin.products_template_steak_medium')),
        templateSteakWell: @json(__('admin.products_template_steak_well')),
        templateSteakExtras: @json(__('admin.products_template_steak_extras')),
        templateSteakEgg: @json(__('admin.products_template_steak_egg')),
        templateSteakCheese: @json(__('admin.products_template_steak_cheese')),
        templateSteakSauce: @json(__('admin.products_template_steak_sauce')),
        templateComboMain: @json(__('admin.products_template_combo_main_choice')),
        templateComboChicken: @json(__('admin.products_template_combo_chicken')),
        templateComboPork: @json(__('admin.products_template_combo_pork')),
        templateComboFish: @json(__('admin.products_template_combo_fish')),
        templateComboSide: @json(__('admin.products_template_combo_side_choice')),
        templateComboFries: @json(__('admin.products_template_combo_fries')),
        templateComboSalad: @json(__('admin.products_template_combo_salad')),
        templateComboSoup: @json(__('admin.products_template_combo_soup')),
        templateComboDrink: @json(__('admin.products_template_combo_drink_choice')),
        templateComboBlackTea: @json(__('admin.products_template_combo_black_tea')),
        templateComboGreenTea: @json(__('admin.products_template_combo_green_tea')),
        templateComboMilkTea: @json(__('admin.products_template_combo_milk_tea')),
        imageOnly: @json(__('admin.error_image_only')),
        imageTooLarge: @json(__('admin.error_image_too_large_2')),
        imageReadFailed: @json(__('admin.error_image_read_failed')),
        imageConvertFailed: @json(__('admin.error_image_convert_failed')),
    };

    const flash = document.getElementById('product-flash');
    const modal = document.getElementById('product-modal');
    const modalTitle = document.getElementById('product-modal-title');
    const modalClose = document.getElementById('product-modal-close');
    const modalCancel = document.getElementById('product-modal-cancel');
    const modalForm = document.getElementById('product-modal-form');
    const modalMethod = document.getElementById('product-modal-method');
    const modalCategory = document.getElementById('modal-category');
    const modalError = document.getElementById('product-modal-error');
    const modalSubmit = document.getElementById('product-modal-submit');
    const optionGroupsInput = document.getElementById('option-groups-json-input');
    const optionEditor = document.getElementById('option-groups-editor');
    const optionEditorSummary = document.getElementById('option-editor-summary');
    const addGroupBtn = document.querySelector('[data-option-add-group]');
    const clearAllBtn = document.querySelector('[data-option-clear-all]');
    const expandAllBtn = document.querySelector('[data-option-expand-all]');
    const collapseAllBtn = document.querySelector('[data-option-collapse-all]');
    const templateButtons = document.querySelectorAll('[data-option-template]');
    const imageUploadInput = document.getElementById('modal-image-upload');
    const imagePreviewCanvas = document.getElementById('modal-image-crop-preview');
    const imageZoomInput = document.getElementById('modal-image-zoom');
    const imageResetBtn = document.getElementById('modal-image-reset');
    const imageRemoveBtn = document.getElementById('modal-image-remove');
    const imageHelper = document.getElementById('modal-image-helper');
    const removeImageInput = document.getElementById('modal-remove-image');

    const categoryModal = document.getElementById('category-modal');
    const categoryModalTitle = document.getElementById('category-modal-title');
    const categoryModalClose = document.getElementById('category-modal-close');
    const categoryModalCancel = document.getElementById('category-modal-cancel');
    const categoryModalForm = document.getElementById('category-modal-form');
    const categoryModalMethod = document.getElementById('category-modal-method');
    const categoryModalSubmit = document.getElementById('category-modal-submit');
    const categoryModalError = document.getElementById('category-modal-error');
    const createCategoryBtn = document.getElementById('create-category-btn');

    let currentMode = 'create';
    let currentProductId = null;
    let optionGroups = [];
    const reorderAbortControllers = new Map();
    let categoryMode = 'create';
    let currentCategoryId = null;
    const imageState = {
        sourceImage: null,
        sourceObjectUrl: null,
        sourceFileName: 'product-image.png',
        hasNewUpload: false,
        removeRequested: false,
        zoom: 1,
        offsetX: 0,
        offsetY: 0,
        dragging: false,
        lastX: 0,
        lastY: 0,
    };
    const maxUploadImageBytes = 2 * 1024 * 1024;

    const optionTemplates = {
        steak: [
            { name: i18n.templateSteakDoneness, type: 'single', required: true, choices: [
                { name: i18n.templateSteakRare, price: 0 },
                { name: i18n.templateSteakMedium, price: 0 },
                { name: i18n.templateSteakWell, price: 0 },
            ] },
            { name: i18n.templateSteakExtras, type: 'multiple', required: false, max_select: 3, choices: [
                { name: i18n.templateSteakEgg, price: 20 },
                { name: i18n.templateSteakCheese, price: 25 },
                { name: i18n.templateSteakSauce, price: 15 },
            ] },
        ],
        combo: [
            { name: i18n.templateComboMain, type: 'single', required: true, choices: [
                { name: i18n.templateComboChicken, price: 0 },
                { name: i18n.templateComboPork, price: 0 },
                { name: i18n.templateComboFish, price: 20 },
            ] },
            { name: i18n.templateComboSide, type: 'single', required: true, choices: [
                { name: i18n.templateComboFries, price: 0 },
                { name: i18n.templateComboSalad, price: 0 },
                { name: i18n.templateComboSoup, price: 0 },
            ] },
            { name: i18n.templateComboDrink, type: 'single', required: true, choices: [
                { name: i18n.templateComboBlackTea, price: 0 },
                { name: i18n.templateComboGreenTea, price: 0 },
                { name: i18n.templateComboMilkTea, price: 10 },
            ] },
        ],
    };

    const esc = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

    const createGroup = () => ({
        name: '',
        type: 'single',
        required: false,
        max_select: 1,
        choices: [],
        collapsed: false,
    });

    const createChoice = () => ({
        name: '',
        price: 0,
    });

    const normalizeOptionGroups = () => {
        optionGroups = optionGroups
            .filter((group) => group && typeof group === 'object')
            .map((group) => {
                const type = group.type === 'multiple' ? 'multiple' : 'single';
                const choices = Array.isArray(group.choices) ? group.choices : [];

                return {
                    name: String(group.name || '').trim(),
                    type,
                    required: !!group.required,
                    collapsed: !!group.collapsed,
                    max_select: type === 'multiple' ? Math.max(Number(group.max_select || 1), 1) : 1,
                    choices: choices
                        .filter((choice) => choice && typeof choice === 'object')
                        .map((choice) => ({
                            name: String(choice.name || '').trim(),
                            price: Math.max(Number(choice.price || 0), 0),
                        })),
                };
            });
    };

    const syncOptionGroups = () => {
        normalizeOptionGroups();
        optionGroupsInput.value = JSON.stringify(optionGroups);

        if (optionEditorSummary) {
            const totalChoices = optionGroups.reduce((sum, group) => {
                const choices = Array.isArray(group.choices) ? group.choices.length : 0;
                return sum + choices;
            }, 0);

            optionEditorSummary.textContent = `${optionGroups.length} groups / ${totalChoices} choices`;
        }
    };

    const renderOptionEditor = () => {
        if (!optionEditor) {
            return;
        }

        optionEditor.innerHTML = '';

        optionGroups.forEach((group, groupIndex) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'rounded-2xl border border-slate-200 bg-white p-3';
            wrapper.dataset.groupIndex = String(groupIndex);

            const groupName = (group.name || '').trim() || `${i18n.groupLabel} #${groupIndex + 1}`;
            const choicesCount = Array.isArray(group.choices) ? group.choices.length : 0;
            const typeLabel = group.type === 'multiple' ? i18n.multipleChoice : i18n.singleChoice;
            const isCollapsed = !!group.collapsed;
            const maxSelectText = group.type === 'multiple' ? ` / max ${Math.max(Number(group.max_select || 1), 1)}` : '';
            const requiredBadge = group.required ? '<span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700">Required</span>' : '';

            wrapper.innerHTML = `
                <div class="mb-3 flex items-center justify-between">
                    <div class="flex min-w-0 flex-1 items-center gap-2">
                        <button type="button" data-toggle-group class="rounded-md border border-slate-300 bg-white px-2 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-100">${isCollapsed ? 'Expand' : 'Collapse'}</button>
                        <p class="truncate text-xs font-semibold text-slate-800">${esc(groupName)}</p>
                        <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600">${esc(typeLabel)}${esc(maxSelectText)}</span>
                        <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600">${choicesCount} choices</span>
                        ${requiredBadge}
                    </div>
                    <div class="ml-2 flex items-center gap-1">
                        <button type="button" data-move-group-up class="rounded-md border border-slate-300 bg-white px-2 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-100">Up</button>
                        <button type="button" data-move-group-down class="rounded-md border border-slate-300 bg-white px-2 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-100">Down</button>
                        <button type="button" data-remove-group class="rounded-lg bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-100">${i18n.removeGroup}</button>
                    </div>
                </div>
                <div data-group-body class="${isCollapsed ? 'hidden ' : ''}space-y-3">
                    <div class="grid gap-2 md:grid-cols-2">
                    <input type="text" value="${esc(group.name || '')}" data-group-field="name" placeholder="${esc(i18n.groupNamePlaceholder)}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <select data-group-field="type" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="single" ${group.type === 'single' ? 'selected' : ''}>${i18n.singleChoice}</option>
                        <option value="multiple" ${group.type === 'multiple' ? 'selected' : ''}>${i18n.multipleChoice}</option>
                    </select>
                    <input type="number" min="1" value="${group.max_select || 1}" data-group-field="max_select" ${group.type === 'single' ? 'disabled' : ''} class="rounded-lg border border-slate-300 px-3 py-2 text-sm disabled:bg-slate-100">
                </div>
                <label class="mt-2 inline-flex items-center gap-2 text-xs text-slate-700">
                    <input type="checkbox" data-group-field="required" ${group.required ? 'checked' : ''} class="h-4 w-4 rounded border-slate-300 text-indigo-600">
                    ${i18n.requiredGroup}
                </label>
                <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-2">
                    <div class="mb-2 flex items-center justify-between">
                        <p class="text-xs font-semibold text-slate-600">${i18n.optionsLabel}</p>
                        <button type="button" data-add-choice class="rounded-md bg-slate-900 px-2.5 py-1 text-xs font-semibold text-white hover:bg-slate-800">${i18n.addChoice}</button>
                    </div>
                    <div class="space-y-2" data-choices-list="1"></div>
                </div>
                </div>
            `;

            const choicesList = wrapper.querySelector('[data-choices-list]');
            (Array.isArray(group.choices) ? group.choices : []).forEach((choice, choiceIndex) => {
                const row = document.createElement('div');
                row.className = 'grid gap-2 rounded-lg border border-slate-200 bg-white p-2 md:grid-cols-[1fr,130px,auto]';
                row.dataset.choiceIndex = String(choiceIndex);
                row.innerHTML = `
                    <input type="text" value="${esc(choice.name || '')}" data-choice-field="name" placeholder="${esc(i18n.choiceName)}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <input type="number" min="0" value="${Number(choice.price || 0)}" data-choice-field="price" placeholder="${esc(i18n.choicePrice)}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <button type="button" data-remove-choice class="rounded-lg bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">${i18n.delete}</button>
                `;
                choicesList.appendChild(row);
            });

            optionEditor.appendChild(wrapper);
        });

        syncOptionGroups();
    };

    const parseOptionGroups = (raw) => {
        if (!raw || String(raw).trim() === '') {
            return [];
        }

        try {
            const parsed = JSON.parse(raw);
            return Array.isArray(parsed) ? parsed : [];
        } catch (_e) {
            return [];
        }
    };

    const revokeImageObjectUrl = () => {
        if (!imageState.sourceObjectUrl) {
            return;
        }

        URL.revokeObjectURL(imageState.sourceObjectUrl);
        imageState.sourceObjectUrl = null;
    };

    const clampImageOffset = () => {
        if (!imageState.sourceImage || !imagePreviewCanvas) {
            imageState.offsetX = 0;
            imageState.offsetY = 0;
            return;
        }

        const canvasSize = imagePreviewCanvas.width;
        const baseScale = Math.max(
            canvasSize / imageState.sourceImage.naturalWidth,
            canvasSize / imageState.sourceImage.naturalHeight,
        );
        const scale = baseScale * imageState.zoom;
        const drawWidth = imageState.sourceImage.naturalWidth * scale;
        const drawHeight = imageState.sourceImage.naturalHeight * scale;

        const minX = canvasSize - drawWidth;
        const minY = canvasSize - drawHeight;

        imageState.offsetX = Math.min(0, Math.max(minX, imageState.offsetX));
        imageState.offsetY = Math.min(0, Math.max(minY, imageState.offsetY));
    };

    const renderImagePreview = () => {
        if (!imagePreviewCanvas) {
            return;
        }

        const ctx = imagePreviewCanvas.getContext('2d');
        if (!ctx) {
            return;
        }

        const canvasSize = imagePreviewCanvas.width;
        ctx.clearRect(0, 0, canvasSize, canvasSize);
        ctx.fillStyle = '#f8fafc';
        ctx.fillRect(0, 0, canvasSize, canvasSize);

        if (!imageState.sourceImage) {
            ctx.strokeStyle = '#cbd5e1';
            ctx.setLineDash([10, 8]);
            ctx.strokeRect(10, 10, canvasSize - 20, canvasSize - 20);
            ctx.setLineDash([]);
            return;
        }

        clampImageOffset();

        const baseScale = Math.max(
            canvasSize / imageState.sourceImage.naturalWidth,
            canvasSize / imageState.sourceImage.naturalHeight,
        );
        const scale = baseScale * imageState.zoom;
        const drawWidth = imageState.sourceImage.naturalWidth * scale;
        const drawHeight = imageState.sourceImage.naturalHeight * scale;
        const drawX = imageState.offsetX;
        const drawY = imageState.offsetY;

        ctx.drawImage(imageState.sourceImage, drawX, drawY, drawWidth, drawHeight);
    };

    const resetImageState = () => {
        revokeImageObjectUrl();
        imageState.sourceImage = null;
        imageState.sourceFileName = 'product-image.png';
        imageState.hasNewUpload = false;
        imageState.removeRequested = false;
        imageState.zoom = 1;
        imageState.offsetX = 0;
        imageState.offsetY = 0;
        imageState.dragging = false;

        if (imageZoomInput) {
            imageZoomInput.value = '1';
        }
        if (imageUploadInput) {
            imageUploadInput.value = '';
        }
        if (removeImageInput) {
            removeImageInput.value = '0';
        }
        if (imageHelper) {
            imageHelper.textContent = '尚未選擇圖片';
        }

        renderImagePreview();
    };

    const setImageFromUrl = (url, helperText = '目前圖片') => {
        if (!url) {
            resetImageState();
            return;
        }

        revokeImageObjectUrl();

        const image = new Image();
        image.onload = () => {
            imageState.sourceImage = image;
            imageState.hasNewUpload = false;
            imageState.removeRequested = false;
            imageState.zoom = 1;
            imageState.offsetX = 0;
            imageState.offsetY = 0;

            if (imageZoomInput) {
                imageZoomInput.value = '1';
            }
            if (removeImageInput) {
                removeImageInput.value = '0';
            }
            if (imageHelper) {
                imageHelper.textContent = helperText;
            }

            renderImagePreview();
        };
        image.onerror = () => {
            resetImageState();
        };
        image.src = url;
    };

    const setImageFromFile = (file) => {
        if (!file.type.startsWith('image/')) {
            showFlash(i18n.imageOnly, 'error');
            return;
        }

        revokeImageObjectUrl();
        const url = URL.createObjectURL(file);
        imageState.sourceObjectUrl = url;

        const image = new Image();
        image.onload = () => {
            imageState.sourceImage = image;
            imageState.sourceFileName = file.name || 'product-image.png';
            imageState.hasNewUpload = true;
            imageState.removeRequested = false;
            imageState.zoom = 1;
            imageState.offsetX = 0;
            imageState.offsetY = 0;

            if (imageZoomInput) {
                imageZoomInput.value = '1';
            }
            if (removeImageInput) {
                removeImageInput.value = '0';
            }
            if (imageHelper) {
                imageHelper.textContent = `已選擇：${file.name}`;
            }

            renderImagePreview();
        };
        image.onerror = () => {
            showFlash(i18n.imageReadFailed, 'error');
        };
        image.src = url;
    };

    const canvasToBlob = (canvas, type, quality) => new Promise((resolve, reject) => {
        canvas.toBlob((blob) => {
            if (blob) {
                resolve(blob);
                return;
            }

            reject(new Error(i18n.imageConvertFailed));
        }, type, quality);
    });

    const ensureCanvasBlobWithinLimit = async (canvas, type = 'image/jpeg', maxBytes = maxUploadImageBytes) => {
        let quality = 0.92;
        let outputCanvas = canvas;
        let blob = await canvasToBlob(outputCanvas, type, quality);

        while (blob.size > maxBytes && quality > 0.45) {
            quality = Math.max(0.45, quality - 0.08);
            blob = await canvasToBlob(outputCanvas, type, quality);
        }

        while (blob.size > maxBytes && outputCanvas.width > 120 && outputCanvas.height > 120) {
            const nextCanvas = document.createElement('canvas');
            nextCanvas.width = Math.max(120, Math.floor(outputCanvas.width * 0.9));
            nextCanvas.height = Math.max(120, Math.floor(outputCanvas.height * 0.9));

            const nextCtx = nextCanvas.getContext('2d');
            if (!nextCtx) {
                break;
            }

            nextCtx.drawImage(outputCanvas, 0, 0, nextCanvas.width, nextCanvas.height);
            outputCanvas = nextCanvas;
            quality = Math.min(quality, 0.78);
            blob = await canvasToBlob(outputCanvas, type, quality);
        }

        if (blob.size > maxBytes) {
            throw new Error(i18n.imageTooLarge);
        }

        return blob;
    };

    const showFlash = (message, type = 'success') => {
        if (!flash) return;
        flash.classList.remove('hidden', 'border-emerald-200', 'bg-emerald-50', 'text-emerald-700', 'border-rose-200', 'bg-rose-50', 'text-rose-700');
        if (type === 'error') {
            flash.classList.add('border-rose-200', 'bg-rose-50', 'text-rose-700');
        } else {
            flash.classList.add('border-emerald-200', 'bg-emerald-50', 'text-emerald-700');
        }
        flash.textContent = message;
    };

    const openModal = () => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    const openCategoryModal = () => {
        categoryModal.classList.remove('hidden');
        categoryModal.classList.add('flex');
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modalError.classList.add('hidden');
        modalError.textContent = '';
        currentProductId = null;
    };

    const closeCategoryModal = () => {
        categoryModal.classList.add('hidden');
        categoryModal.classList.remove('flex');
        categoryModalError.classList.add('hidden');
        categoryModalError.textContent = '';
        currentCategoryId = null;
    };

    const setCategoryFormValues = (category = null) => {
        categoryModalForm.reset();
        categoryModalMethod.value = 'POST';

        if (!category) {
            categoryModalForm.elements['sort'].value = 1;
            return;
        }

        categoryModalForm.elements['name'].value = category.name ?? '';
        categoryModalForm.elements['sort'].value = category.sort ?? 1;
    };

    const openCreateCategoryModal = () => {
        categoryMode = 'create';
        currentCategoryId = null;
        categoryModalTitle.textContent = i18n.modalCategoryTitleCreate;
        categoryModalSubmit.textContent = i18n.modalCategorySubmitCreate;
        setCategoryFormValues(null);
        openCategoryModal();
    };

    const openEditCategoryModal = async (categoryId) => {
        categoryMode = 'edit';
        currentCategoryId = categoryId;
        categoryModalTitle.textContent = i18n.modalCategoryTitleEdit;
        categoryModalSubmit.textContent = i18n.modalCategorySubmitEdit;

        try {
            const url = editCategoryUrlTemplate.replace('__CATEGORY__', String(categoryId));
            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await res.json();
            if (!res.ok || !data.ok) {
                throw new Error(data.message || i18n.readCategoryFailed);
            }

            setCategoryFormValues(data.category);
            openCategoryModal();
        } catch (e) {
            showFlash(e.message || i18n.readCategoryFailed, 'error');
        }
    };

    const submitCategoryForm = async (event) => {
        event.preventDefault();
        categoryModalError.classList.add('hidden');
        categoryModalError.textContent = '';

        const formData = new FormData(categoryModalForm);
        let url = createCategoryUrl;

        if (categoryMode === 'edit' && currentCategoryId) {
            url = updateCategoryUrlTemplate.replace('__CATEGORY__', String(currentCategoryId));
            formData.set('_method', 'PUT');
        }

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData,
            });

            const data = await res.json();
            if (!res.ok || !data.ok) {
                const validationMessage = Object.values(data.errors || {}).flat().join('，');
                throw new Error(data.message || validationMessage || i18n.saveCategoryFailed);
            }

            closeCategoryModal();
            showFlash(data.message || i18n.categorySaved);
            window.location.reload();
        } catch (e) {
            categoryModalError.classList.remove('hidden');
            categoryModalError.textContent = e.message || i18n.saveCategoryFailed;
        }
    };

    const deleteCategory = async (categoryId, categoryName) => {
        if (!confirm(i18n.confirmDeleteCategory.replace('__name__', categoryName))) {
            return;
        }

        const url = deleteCategoryUrlTemplate.replace('__CATEGORY__', String(categoryId));
        const formData = new FormData();
        formData.set('_method', 'DELETE');

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData,
            });

            const data = await res.json();
            if (!res.ok || !data.ok) {
                const validationMessage = Object.values(data.errors || {}).flat().join('，');
                throw new Error(data.message || validationMessage || i18n.deleteCategoryFailed);
            }

            showFlash(data.message || i18n.categoryDeleted);
            window.location.reload();
        } catch (e) {
            showFlash(e.message || i18n.deleteCategoryFailed, 'error');
        }
    };

    const disableCategory = async (categoryId, categoryName) => {
        if (!confirm(i18n.confirmDisableCategory.replace('__name__', categoryName))) {
            return;
        }

        const url = disableCategoryUrlTemplate.replace('__CATEGORY__', String(categoryId));
        const formData = new FormData();
        formData.set('_method', 'PATCH');

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData,
            });

            const data = await res.json();
            if (!res.ok || !data.ok) {
                const validationMessage = Object.values(data.errors || {}).flat().join('，');
                throw new Error(data.message || validationMessage || i18n.disableCategoryFailed);
            }

            showFlash(data.message || i18n.categoryDisabled);
            window.location.reload();
        } catch (e) {
            showFlash(e.message || i18n.disableCategoryFailed, 'error');
        }
    };

    const enableCategory = async (categoryId, categoryName) => {
        if (!confirm(i18n.confirmEnableCategory.replace('__name__', categoryName))) {
            return;
        }

        const url = enableCategoryUrlTemplate.replace('__CATEGORY__', String(categoryId));
        const formData = new FormData();
        formData.set('_method', 'PATCH');

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData,
            });

            const data = await res.json();
            if (!res.ok || !data.ok) {
                const validationMessage = Object.values(data.errors || {}).flat().join('，');
                throw new Error(data.message || validationMessage || i18n.enableCategoryFailed);
            }

            showFlash(data.message || i18n.categoryEnabled);
            window.location.reload();
        } catch (e) {
            showFlash(e.message || i18n.enableCategoryFailed, 'error');
        }
    };

    const setFormValues = (product = null, categoryId = null) => {
        modalForm.reset();
        modalMethod.value = 'POST';
        optionGroups = [];
        optionGroupsInput.value = '[]';
        resetImageState();
        document.getElementById('modal-is-active').checked = true;
        document.getElementById('modal-is-sold-out').checked = false;
        document.getElementById('modal-allow-item-note').checked = false;

        if (categoryId) {
            modalCategory.value = String(categoryId);
        }

        if (!product) {
            renderOptionEditor();
            return;
        }

        modalForm.elements['name'].value = product.name ?? '';
        modalForm.elements['category_id'].value = String(product.category_id ?? '');
        modalForm.elements['price'].value = product.price ?? 0;
        modalForm.elements['cost'].value = product.cost ?? 0;
        modalForm.elements['description'].value = product.description ?? '';
        setImageFromUrl(product.image_url ?? null, '目前圖片（可拖曳調整裁切）');
        optionGroups = Array.isArray(product.option_groups) ? product.option_groups : parseOptionGroups(product.option_groups_json ?? '[]');
        document.getElementById('modal-is-active').checked = !!product.is_active;
        document.getElementById('modal-is-sold-out').checked = !!product.is_sold_out;
        document.getElementById('modal-allow-item-note').checked = !!product.allow_item_note;
        renderOptionEditor();
    };

    const openCreateModal = (categoryId = null) => {
        currentMode = 'create';
        modalTitle.textContent = i18n.modalProductTitleCreate;
        modalSubmit.textContent = i18n.modalProductSubmitCreate;
        setFormValues(null, categoryId);
        openModal();
    };

    const openEditModal = async (productId) => {
        currentMode = 'edit';
        currentProductId = productId;
        modalTitle.textContent = i18n.modalProductTitleEdit;
        modalSubmit.textContent = i18n.modalProductSubmitEdit;

        try {
            const url = editUrlTemplate.replace('__PRODUCT__', String(productId));
            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!res.ok) {
                throw new Error(i18n.readProductFailed);
            }

            const data = await res.json();
            setFormValues(data.product);
            openModal();
        } catch (e) {
            showFlash(e.message || i18n.readProductFailed, 'error');
        }
    };

    const collectFormData = async () => {
        syncOptionGroups();
        const formData = new FormData(modalForm);
        if (!formData.get('option_groups_json')) {
            formData.set('option_groups_json', '[]');
        }

        if (!formData.get('is_active')) {
            formData.set('is_active', '0');
        }

        if (!formData.get('is_sold_out')) {
            formData.set('is_sold_out', '0');
        }

        if (!formData.get('allow_item_note')) {
            formData.set('allow_item_note', '0');
        }

        formData.delete('image_upload');
        if (imageState.hasNewUpload && imageState.sourceImage && imagePreviewCanvas) {
            const blob = await ensureCanvasBlobWithinLimit(imagePreviewCanvas, 'image/jpeg', maxUploadImageBytes);
            const filename = (imageState.sourceFileName || 'product-image.jpg').replace(/\.[^.]+$/, '.jpg');
            formData.set('image_upload', new File([blob], filename, { type: 'image/jpeg' }));
            formData.set('remove_image', '0');
        } else if (imageState.removeRequested) {
            formData.set('remove_image', '1');
        } else {
            formData.set('remove_image', '0');
        }

        return formData;
    };

    const updateSortLabels = (container) => {
        const cards = [...container.querySelectorAll('[data-product-card]')];
        cards.forEach((card, index) => {
            const label = card.querySelector('[data-product-sort]');
            if (label) {
                label.textContent = `${i18n.sortLabel} ${index + 1}`;
            }
        });
    };

    const orderSignature = (container) => [...container.querySelectorAll('[data-product-card]')]
        .map((card) => card.getAttribute('data-product-id'))
        .join(',');

    const persistCategorySort = async (categoryId, container, notify = true) => {
        const productIds = [...container.querySelectorAll('[data-product-card]')]
            .map((card) => Number(card.getAttribute('data-product-id')))
            .filter((id) => Number.isInteger(id) && id > 0);

        if (productIds.length <= 1) {
            updateSortLabels(container);
            return;
        }

        try {
            const previousController = reorderAbortControllers.get(String(categoryId));
            if (previousController) {
                previousController.abort();
            }

            const controller = new AbortController();
            reorderAbortControllers.set(String(categoryId), controller);

            const res = await fetch(reorderUrl, {
                method: 'POST',
                signal: controller.signal,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    category_id: Number(categoryId),
                    product_ids: productIds,
                }),
            });

            const data = await res.json();
            if (!res.ok || !data.ok) {
                throw new Error(data.message || i18n.sortUpdateFailed);
            }

            updateSortLabels(container);
            if (notify) {
                showFlash(data.message || i18n.sortUpdated);
            }
        } catch (e) {
            if (e.name === 'AbortError') {
                return;
            }
            if (notify) {
                showFlash(e.message || i18n.sortUpdateFailed, 'error');
            }
        } finally {
            reorderAbortControllers.delete(String(categoryId));
        }
    };

    const persistCategoryMove = async (payload) => {
        const res = await fetch(moveUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
        });

        const data = await res.json();
        if (!res.ok || !data.ok) {
            throw new Error(data.message || i18n.moveAcrossCategoryFailed);
        }

        return data;
    };

    const clearDropTargetStyles = (container) => {
        container.querySelectorAll('[data-product-card].drop-target').forEach((card) => {
            card.classList.remove('drop-target', 'ring-2', 'ring-amber-300');
        });
    };

    const getDropReference = (container, draggingCard, clientX, clientY, hoverState) => {
        const pointed = document.elementFromPoint(clientX, clientY)?.closest('[data-product-card]');

        if (pointed && container.contains(pointed) && pointed !== draggingCard) {
            const allCards = [...container.querySelectorAll('[data-product-card]')];
            const targetIndex = allCards.indexOf(pointed);
            const dragIndex = allCards.indexOf(draggingCard);
            const pointedId = pointed.getAttribute('data-product-id') || '';

            // Decide direction once per hovered card to keep drag ordering stable.
            if (hoverState.targetId !== pointedId || !hoverState.decision) {
                hoverState.targetId = pointedId;
                hoverState.decision = dragIndex < targetIndex ? 'after' : 'before';
            }

            const insertBefore = hoverState.decision === 'before';

            return {
                reference: insertBefore ? pointed : pointed.nextElementSibling,
                target: pointed,
            };
        }

        hoverState.targetId = null;
        hoverState.decision = null;

        const elements = [...container.querySelectorAll('[data-product-card]:not(.is-dragging)')];
        const fallback = elements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = clientY - box.top - box.height / 2;

            if (offset < 0 && offset > closest.offset) {
                return { offset, element: child };
            }

            return closest;
        }, { offset: Number.NEGATIVE_INFINITY, element: null }).element;

        return {
            reference: fallback,
            target: fallback,
        };
    };

    const initProductSorting = () => {
        const containers = [...document.querySelectorAll('[data-category-products][data-category-id]')];
        const hoverStateByContainer = new WeakMap();

        const dragState = {
            card: null,
            sourceContainer: null,
            currentContainer: null,
            pendingReference: null,
            rafId: 0,
            clientX: 0,
            clientY: 0,
            startSourceSignature: '',
        };

        const touchState = {
            active: false,
            touchId: null,
        };

        const toggleEmptyPlaceholder = (container) => {
            if (!container) {
                return;
            }

            const placeholder = container.querySelector('[data-empty-placeholder]');
            if (!placeholder) {
                return;
            }

            const hasCards = container.querySelectorAll('[data-product-card]').length > 0;
            placeholder.classList.toggle('hidden', hasCards);
        };

        const applyPendingDrop = (container) => {
            if (!container || !dragState.card) {
                return;
            }

            const dragged = dragState.card;
            const reference = dragState.pendingReference;
            const placeholder = container.querySelector('[data-empty-placeholder]');

            if (!reference) {
                if (placeholder) {
                    container.insertBefore(dragged, placeholder);
                } else {
                    container.appendChild(dragged);
                }
            } else {
                container.insertBefore(dragged, reference);
            }

            toggleEmptyPlaceholder(container);
            toggleEmptyPlaceholder(dragState.sourceContainer);
        };

        const updateDropReference = (container, clientX, clientY) => {
            if (!container || !dragState.card) {
                return;
            }

            containers.forEach((c) => {
                clearDropTargetStyles(c);
                c.classList.remove('ring-2', 'ring-amber-300');
            });

            container.classList.add('ring-2', 'ring-amber-300');

            const hoverState = hoverStateByContainer.get(container) || { targetId: null, decision: null };
            const dropRef = getDropReference(container, dragState.card, clientX, clientY, hoverState);
            hoverStateByContainer.set(container, hoverState);
            dragState.pendingReference = dropRef.reference || null;

            if (dropRef.target && dropRef.target !== dragState.card) {
                dropRef.target.classList.add('drop-target', 'ring-2', 'ring-amber-300');
            }
        };

        const finishDrag = async () => {
            const dragged = dragState.card;
            if (!dragged) {
                touchState.active = false;
                touchState.touchId = null;
                return;
            }

            dragged.classList.remove('is-dragging', 'opacity-60', 'ring-2', 'ring-indigo-300');
            dragged.draggable = false;
            delete dragged.dataset.dragEnabled;

            containers.forEach((container) => {
                clearDropTargetStyles(container);
                container.classList.remove('ring-2', 'ring-amber-300');
            });

            if (dragState.rafId) {
                cancelAnimationFrame(dragState.rafId);
                dragState.rafId = 0;
            }

            const sourceContainer = dragState.sourceContainer;
            const targetContainer = dragState.currentContainer || sourceContainer;
            const sourceCategoryId = sourceContainer?.getAttribute('data-category-id');
            const targetCategoryId = targetContainer?.getAttribute('data-category-id');

            if (sourceContainer && targetContainer && sourceCategoryId && targetCategoryId) {
                const sourceIds = [...sourceContainer.querySelectorAll('[data-product-card]')]
                    .map((el) => Number(el.getAttribute('data-product-id')))
                    .filter((id) => Number.isInteger(id) && id > 0);

                const targetIds = [...targetContainer.querySelectorAll('[data-product-card]')]
                    .map((el) => Number(el.getAttribute('data-product-id')))
                    .filter((id) => Number.isInteger(id) && id > 0);

                const movedProductId = Number(dragged.getAttribute('data-product-id'));

                const movedAcrossCategory = sourceCategoryId !== targetCategoryId;
                const reorderedWithinCategory = sourceCategoryId === targetCategoryId
                    && sourceContainer
                    && dragState.startSourceSignature !== orderSignature(sourceContainer);

                const shouldPersist = movedAcrossCategory || reorderedWithinCategory;

                if (shouldPersist && Number.isInteger(movedProductId) && movedProductId > 0) {
                    try {
                        const data = await persistCategoryMove({
                            moved_product_id: movedProductId,
                            source_category_id: Number(sourceCategoryId),
                            target_category_id: Number(targetCategoryId),
                            source_product_ids: sourceIds,
                            target_product_ids: targetIds,
                        });
                        showFlash(data.message || i18n.moveUpdated);
                    } catch (e) {
                        showFlash(e.message || i18n.moveUpdateFailed, 'error');
                        window.location.reload();
                    }
                }

                updateSortLabels(sourceContainer);
                if (targetContainer !== sourceContainer) {
                    updateSortLabels(targetContainer);
                }
                toggleEmptyPlaceholder(sourceContainer);
                toggleEmptyPlaceholder(targetContainer);
            }

            dragState.card = null;
            dragState.sourceContainer = null;
            dragState.currentContainer = null;
            dragState.pendingReference = null;
            dragState.startSourceSignature = '';
            touchState.active = false;
            touchState.touchId = null;
        };

        const onTouchMove = (event) => {
            if (!touchState.active || !dragState.card) {
                return;
            }

            const touch = [...event.changedTouches].find((item) => item.identifier === touchState.touchId);
            if (!touch) {
                return;
            }

            event.preventDefault();
            dragState.clientX = touch.clientX;
            dragState.clientY = touch.clientY;

            const targetContainer = document
                .elementFromPoint(touch.clientX, touch.clientY)
                ?.closest('[data-category-products][data-category-id]');

            if (!targetContainer) {
                return;
            }

            dragState.currentContainer = targetContainer;
            updateDropReference(targetContainer, touch.clientX, touch.clientY);
        };

        const onTouchEnd = async (event) => {
            if (!touchState.active || !dragState.card) {
                return;
            }

            const touch = [...event.changedTouches].find((item) => item.identifier === touchState.touchId);
            if (!touch) {
                return;
            }

            event.preventDefault();

            const targetContainer = dragState.currentContainer
                || document
                    .elementFromPoint(touch.clientX, touch.clientY)
                    ?.closest('[data-category-products][data-category-id]');

            applyPendingDrop(targetContainer || dragState.sourceContainer);
            await finishDrag();
        };

        document.addEventListener('touchmove', onTouchMove, { passive: false });
        document.addEventListener('touchend', onTouchEnd, { passive: false });
        document.addEventListener('touchcancel', onTouchEnd, { passive: false });

        const attachCardEvents = (card) => {
            if (!card) {
                return;
            }

            const handle = card.querySelector('[data-drag-product-handle]');
            card.draggable = false;

            handle?.addEventListener('mousedown', () => {
                card.draggable = true;
                card.dataset.dragEnabled = '1';
            });

            handle?.addEventListener('touchstart', (event) => {
                if (event.touches.length === 0 || dragState.card) {
                    return;
                }

                event.preventDefault();
                const touch = event.changedTouches[0];
                card.dataset.dragEnabled = '1';
                touchState.active = true;
                touchState.touchId = touch.identifier;

                dragState.card = card;
                dragState.sourceContainer = card.closest('[data-category-products][data-category-id]');
                dragState.currentContainer = dragState.sourceContainer;
                dragState.pendingReference = null;
                dragState.clientX = touch.clientX;
                dragState.clientY = touch.clientY;
                dragState.startSourceSignature = dragState.sourceContainer ? orderSignature(dragState.sourceContainer) : '';

                card.classList.add('is-dragging', 'opacity-60', 'ring-2', 'ring-indigo-300');
            }, { passive: false });

            card.addEventListener('mouseup', () => {
                if (!card.classList.contains('is-dragging')) {
                    card.draggable = false;
                    delete card.dataset.dragEnabled;
                }
            });

            card.addEventListener('dragstart', (event) => {
                if (card.dataset.dragEnabled !== '1') {
                    event.preventDefault();
                    return;
                }

                dragState.card = card;
                dragState.sourceContainer = card.closest('[data-category-products][data-category-id]');
                dragState.currentContainer = dragState.sourceContainer;
                dragState.pendingReference = null;
                dragState.clientX = 0;
                dragState.clientY = 0;
                dragState.startSourceSignature = dragState.sourceContainer ? orderSignature(dragState.sourceContainer) : '';

                card.classList.add('is-dragging', 'opacity-60', 'ring-2', 'ring-indigo-300');
            });

            card.addEventListener('dragend', finishDrag);
        };

        containers.forEach((container) => {
            hoverStateByContainer.set(container, { targetId: null, decision: null });

            container.querySelectorAll('[data-product-card]').forEach(attachCardEvents);

            container.addEventListener('dragover', (event) => {
                event.preventDefault();
                if (!dragState.card) {
                    return;
                }

                dragState.clientX = event.clientX;
                dragState.clientY = event.clientY;
                dragState.currentContainer = container;

                if (dragState.rafId) {
                    return;
                }

                dragState.rafId = requestAnimationFrame(() => {
                    containers.forEach((c) => {
                        clearDropTargetStyles(c);
                        c.classList.remove('ring-2', 'ring-amber-300');
                    });

                    container.classList.add('ring-2', 'ring-amber-300');

                    const hoverState = hoverStateByContainer.get(container) || { targetId: null, decision: null };
                    const dropRef = getDropReference(container, dragState.card, dragState.clientX, dragState.clientY, hoverState);
                    hoverStateByContainer.set(container, hoverState);
                    dragState.pendingReference = dropRef.reference || null;

                    if (dropRef.target && dropRef.target !== dragState.card) {
                        dropRef.target.classList.add('drop-target', 'ring-2', 'ring-amber-300');
                    }

                    dragState.rafId = 0;
                });
            });

            container.addEventListener('drop', (event) => {
                event.preventDefault();
                if (!dragState.card) {
                    return;
                }

                const dragged = dragState.card;
                const reference = dragState.pendingReference;
                const placeholder = container.querySelector('[data-empty-placeholder]');

                if (!reference) {
                    if (placeholder) {
                        container.insertBefore(dragged, placeholder);
                    } else {
                        container.appendChild(dragged);
                    }
                } else {
                    container.insertBefore(dragged, reference);
                }

                toggleEmptyPlaceholder(container);
                toggleEmptyPlaceholder(dragState.sourceContainer);
            });
        });
    };

    const submitModalForm = async (event) => {
        event.preventDefault();
        modalError.classList.add('hidden');
        modalError.textContent = '';

        const formData = await collectFormData();
        let url = createUrl;

        if (currentMode === 'edit' && currentProductId) {
            url = updateUrlTemplate.replace('__PRODUCT__', String(currentProductId));
            formData.set('_method', 'PUT');
        }

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData,
            });

            const data = await res.json();
            if (!res.ok || !data.ok) {
                throw new Error(data.message || Object.values(data.errors || {}).flat().join('，') || i18n.saveFailed);
            }

            closeModal();
            showFlash(data.message || i18n.saved);
            window.location.reload();
        } catch (e) {
            modalError.classList.remove('hidden');
            modalError.textContent = e.message || i18n.saveFailed;
        }
    };

    const deleteProduct = async (productId, productName) => {
        if (!confirm(i18n.confirmDeleteProduct.replace('__name__', productName))) {
            return;
        }

        const url = deleteUrlTemplate.replace('__PRODUCT__', String(productId));
        const formData = new FormData();
        formData.set('_method', 'DELETE');

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData,
            });

            const data = await res.json();
            if (!res.ok || !data.ok) {
                throw new Error(data.message || i18n.deleteFailed);
            }

            showFlash(data.message || i18n.productDeleted);
            window.location.reload();
        } catch (e) {
            showFlash(e.message || i18n.deleteFailed, 'error');
        }
    };

    document.getElementById('create-product-btn')?.addEventListener('click', () => openCreateModal());
    createCategoryBtn?.addEventListener('click', () => openCreateCategoryModal());
    modalClose?.addEventListener('click', closeModal);
    modalCancel?.addEventListener('click', closeModal);
    modalForm?.addEventListener('submit', submitModalForm);
        imageUploadInput?.addEventListener('change', (event) => {
            const file = event.target.files?.[0];
            if (!file) {
                return;
            }

            setImageFromFile(file);
        });

        imageZoomInput?.addEventListener('input', (event) => {
            imageState.zoom = Math.max(1, Number(event.target.value || 1));
            renderImagePreview();
        });

        imageResetBtn?.addEventListener('click', () => {
            imageState.zoom = 1;
            imageState.offsetX = 0;
            imageState.offsetY = 0;
            if (imageZoomInput) {
                imageZoomInput.value = '1';
            }
            renderImagePreview();
        });

        imageRemoveBtn?.addEventListener('click', () => {
            resetImageState();
            imageState.removeRequested = true;
            if (removeImageInput) {
                removeImageInput.value = '1';
            }
            if (imageHelper) {
                imageHelper.textContent = '圖片將在儲存後移除';
            }
        });

        imagePreviewCanvas?.addEventListener('pointerdown', (event) => {
            if (!imageState.sourceImage) {
                return;
            }

            imageState.dragging = true;
            imageState.lastX = event.clientX;
            imageState.lastY = event.clientY;
            imagePreviewCanvas.setPointerCapture(event.pointerId);
        });

        imagePreviewCanvas?.addEventListener('pointermove', (event) => {
            if (!imageState.dragging) {
                return;
            }

            const deltaX = event.clientX - imageState.lastX;
            const deltaY = event.clientY - imageState.lastY;
            imageState.lastX = event.clientX;
            imageState.lastY = event.clientY;
            imageState.offsetX += deltaX;
            imageState.offsetY += deltaY;
            renderImagePreview();
        });

        const endImageDrag = (event) => {
            if (!imageState.dragging || !imagePreviewCanvas) {
                return;
            }

            imageState.dragging = false;
            try {
                imagePreviewCanvas.releasePointerCapture(event.pointerId);
            } catch (_e) {
                // Ignore if capture already released.
            }
        };

        imagePreviewCanvas?.addEventListener('pointerup', endImageDrag);
        imagePreviewCanvas?.addEventListener('pointercancel', endImageDrag);

    categoryModalClose?.addEventListener('click', closeCategoryModal);
    categoryModalCancel?.addEventListener('click', closeCategoryModal);
    categoryModalForm?.addEventListener('submit', submitCategoryForm);

    document.querySelectorAll('[data-create-in-category]').forEach((button) => {
        button.addEventListener('click', () => {
            openCreateModal(button.getAttribute('data-create-in-category'));
        });
    });

    document.querySelectorAll('[data-edit-product]').forEach((button) => {
        button.addEventListener('click', () => {
            openEditModal(button.getAttribute('data-edit-product'));
        });
    });

    document.querySelectorAll('[data-delete-product]').forEach((button) => {
        button.addEventListener('click', () => {
            deleteProduct(button.getAttribute('data-delete-product'), button.getAttribute('data-product-name') || i18n.fallbackProduct);
        });
    });

    document.querySelectorAll('[data-edit-category]').forEach((button) => {
        button.addEventListener('click', () => {
            openEditCategoryModal(button.getAttribute('data-edit-category'));
        });
    });

    document.querySelectorAll('[data-delete-category]').forEach((button) => {
        button.addEventListener('click', () => {
            deleteCategory(button.getAttribute('data-delete-category'), button.getAttribute('data-category-name') || i18n.fallbackCategory);
        });
    });

    document.querySelectorAll('[data-disable-category]').forEach((button) => {
        button.addEventListener('click', () => {
            disableCategory(button.getAttribute('data-disable-category'), button.getAttribute('data-category-name') || i18n.fallbackCategory);
        });
    });

    document.querySelectorAll('[data-enable-category]').forEach((button) => {
        button.addEventListener('click', () => {
            enableCategory(button.getAttribute('data-enable-category'), button.getAttribute('data-category-name') || i18n.fallbackCategory);
        });
    });

    addGroupBtn?.addEventListener('click', () => {
        optionGroups.push(createGroup());
        renderOptionEditor();
    });

    clearAllBtn?.addEventListener('click', () => {
        if (!confirm(i18n.confirmClearAllOptions)) {
            return;
        }

        optionGroups = [];
        renderOptionEditor();
    });

    templateButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const key = button.getAttribute('data-option-template');
            if (!key || !optionTemplates[key]) {
                return;
            }

            if (optionGroups.length > 0 && !confirm(i18n.confirmApplyTemplate)) {
                return;
            }

            optionGroups = JSON.parse(JSON.stringify(optionTemplates[key]));
            optionGroups = optionGroups.map((group) => ({ ...group, collapsed: false }));
            renderOptionEditor();
        });
    });

    expandAllBtn?.addEventListener('click', () => {
        optionGroups.forEach((group) => {
            group.collapsed = false;
        });
        renderOptionEditor();
    });

    collapseAllBtn?.addEventListener('click', () => {
        optionGroups.forEach((group) => {
            group.collapsed = true;
        });
        renderOptionEditor();
    });

    optionEditor?.addEventListener('click', (event) => {
        const groupCard = event.target.closest('[data-group-index]');
        if (!groupCard) {
            return;
        }

        const groupIndex = Number(groupCard.dataset.groupIndex);
        if (!Number.isInteger(groupIndex) || !optionGroups[groupIndex]) {
            return;
        }

        if (event.target.closest('[data-remove-group]')) {
            optionGroups.splice(groupIndex, 1);
            renderOptionEditor();
            return;
        }

        if (event.target.closest('[data-toggle-group]')) {
            optionGroups[groupIndex].collapsed = !optionGroups[groupIndex].collapsed;
            renderOptionEditor();
            return;
        }

        if (event.target.closest('[data-move-group-up]')) {
            if (groupIndex > 0) {
                [optionGroups[groupIndex - 1], optionGroups[groupIndex]] = [optionGroups[groupIndex], optionGroups[groupIndex - 1]];
                renderOptionEditor();
            }
            return;
        }

        if (event.target.closest('[data-move-group-down]')) {
            if (groupIndex < optionGroups.length - 1) {
                [optionGroups[groupIndex + 1], optionGroups[groupIndex]] = [optionGroups[groupIndex], optionGroups[groupIndex + 1]];
                renderOptionEditor();
            }
            return;
        }

        if (event.target.closest('[data-add-choice]')) {
            optionGroups[groupIndex].choices = Array.isArray(optionGroups[groupIndex].choices) ? optionGroups[groupIndex].choices : [];
            optionGroups[groupIndex].choices.push(createChoice());
            renderOptionEditor();
            return;
        }

        const choiceRow = event.target.closest('[data-choice-index]');
        if (choiceRow && event.target.closest('[data-remove-choice]')) {
            const choiceIndex = Number(choiceRow.dataset.choiceIndex);
            if (Number.isInteger(choiceIndex) && Array.isArray(optionGroups[groupIndex].choices)) {
                optionGroups[groupIndex].choices.splice(choiceIndex, 1);
                renderOptionEditor();
            }
        }
    });

    optionEditor?.addEventListener('input', (event) => {
        const groupCard = event.target.closest('[data-group-index]');
        if (!groupCard) {
            return;
        }

        const groupIndex = Number(groupCard.dataset.groupIndex);
        if (!Number.isInteger(groupIndex) || !optionGroups[groupIndex]) {
            return;
        }

        const groupField = event.target.getAttribute('data-group-field');
        if (groupField) {
            if (groupField === 'max_select') {
                optionGroups[groupIndex][groupField] = Math.max(Number(event.target.value || 1), 1);
            } else {
                optionGroups[groupIndex][groupField] = event.target.value;
            }

            if (groupField === 'type' && event.target.value === 'single') {
                optionGroups[groupIndex].max_select = 1;
            }

            if (groupField === 'type') {
                renderOptionEditor();
                return;
            }

            syncOptionGroups();
            return;
        }

        const choiceRow = event.target.closest('[data-choice-index]');
        if (!choiceRow) {
            return;
        }

        const choiceIndex = Number(choiceRow.dataset.choiceIndex);
        if (!Array.isArray(optionGroups[groupIndex].choices) || !optionGroups[groupIndex].choices[choiceIndex]) {
            return;
        }

        const choiceField = event.target.getAttribute('data-choice-field');
        if (!choiceField) {
            return;
        }

        if (choiceField === 'price') {
            optionGroups[groupIndex].choices[choiceIndex][choiceField] = Math.max(Number(event.target.value || 0), 0);
        } else {
            optionGroups[groupIndex].choices[choiceIndex][choiceField] = event.target.value;
        }

        syncOptionGroups();
    });

    optionEditor?.addEventListener('change', (event) => {
        const groupCard = event.target.closest('[data-group-index]');
        if (!groupCard) {
            return;
        }

        const groupIndex = Number(groupCard.dataset.groupIndex);
        if (!Number.isInteger(groupIndex) || !optionGroups[groupIndex]) {
            return;
        }

        if (event.target.getAttribute('data-group-field') === 'required') {
            optionGroups[groupIndex].required = !!event.target.checked;
            syncOptionGroups();
        }
    });

    renderOptionEditor();
    initProductSorting();
})();
</script>
@endsection
