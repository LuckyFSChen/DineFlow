@extends('layouts.app')

@section('content')
@php
    $storeCollection = $stores->getCollection();
    $activeStoreCount = $storeCollection->where('is_active', true)->count();
    $inactiveStoreCount = $storeCollection->count() - $activeStoreCount;
    $countryLabelMap = [
        'tw' => __('admin.country_tw'),
        'vn' => __('admin.country_vn'),
        'cn' => __('admin.country_cn'),
        'us' => __('admin.country_us'),
    ];
@endphp
<div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-7xl px-6 py-10 lg:px-8">
        <div class="admin-hero mb-6 rounded-3xl px-5 py-5 md:px-7">
            <div class="mb-5 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ __('admin.store_management') }}</h1>
                    <p class="mt-2 text-slate-600">{{ __('admin.store_management_desc') }}</p>
                </div>

                @if(isset($canCreateStore) && ! $canCreateStore)
                    <button
                        type="button"
                        id="open-store-modal-btn"
                        disabled
                        class="inline-flex cursor-not-allowed items-center justify-center rounded-2xl bg-slate-300 px-5 py-3 text-sm font-semibold text-slate-500">
                        {{ __('admin.add_store_limit') }}
                    </button>
                @else
                    <button
                        type="button"
                        id="open-store-modal-btn"
                        class="inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500">
                        {{ __('admin.add_store') }}
                    </button>
                @endif
            </div>

            <div class="grid gap-3 md:grid-cols-3">
                <div class="admin-kpi rounded-2xl p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('admin.store_kpi_total') }}</p>
                    <p class="value mt-2 text-slate-900">{{ $storeCollection->count() }}</p>
                </div>
                <div class="admin-kpi rounded-2xl p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('admin.store_kpi_active') }}</p>
                    <p class="value mt-2 text-emerald-700">{{ $activeStoreCount }}</p>
                </div>
                <div class="admin-kpi rounded-2xl p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('admin.store_kpi_inactive') }}</p>
                    <p class="value mt-2 text-amber-700">{{ $inactiveStoreCount }}</p>
                </div>
            </div>
        </div>

        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="admin-pill-nav inline-flex items-center gap-2 rounded-full px-3 py-2 text-xs font-semibold text-slate-700">
                    <span class="rounded-full bg-cyan-100 px-2 py-1 text-cyan-700">{{ __('admin.store_overview_badge') }}</span>
                    <span>{{ __('admin.store_overview_desc') }}</span>
                </div>
            </div>
        </div>

        @if(isset($usedStores))
            <div class="mb-6 rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-800">
                {{ __('admin.quota_label') }}
                {{ __('admin.used_stores', ['count' => $usedStores]) }}
                @if($maxStores === null)
                    / {{ __('admin.no_limit') }}
                @else
                    / {{ __('admin.limit_stores', ['max' => $maxStores]) }}（{{ __('admin.remaining_stores', ['count' => $remainingStores]) }}）
                @endif
                <span class="ml-2 text-indigo-700">{{ __('admin.quota_inactive_not_counted') }}</span>
            </div>
        @endif

        @if(session('success'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                {{ session('error') }}
            </div>
        @endif

        <div class="admin-search-shell mb-6 rounded-3xl p-4 shadow-sm">
            <form method="GET" action="{{ route('admin.stores.index') }}" class="flex flex-col gap-3 md:flex-row">
                <input
                    type="text"
                    name="keyword"
                    value="{{ $keyword ?? '' }}"
                    placeholder="{{ __('admin.search_placeholder') }}"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                >
                <select
                    name="country_code"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200 md:w-60"
                >
                    <option value="">{{ __('admin.filter_all_countries') }}</option>
                    @foreach(($countryOptions ?? []) as $code => $labelKey)
                        <option value="{{ $code }}" @selected(($countryCode ?? '') === $code)>{{ __($labelKey) }}</option>
                    @endforeach
                </select>
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                    {{ __('admin.search') }}
                </button>
            </form>
        </div>

        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1080px] divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-4 text-left font-semibold text-slate-700">{{ __('admin.store_name') }}</th>
                            <th class="px-6 py-4 text-left font-semibold text-slate-700">Slug</th>
                            <th class="px-6 py-4 text-left font-semibold text-slate-700">{{ __('admin.phone') }}</th>
                            <th class="px-6 py-4 text-left font-semibold text-slate-700">{{ __('admin.currency') }}</th>
                            <th class="px-6 py-4 text-left font-semibold text-slate-700">{{ __('admin.store_country') }}</th>
                            <th class="px-6 py-4 text-left font-semibold text-slate-700">{{ __('admin.address') }}</th>
                            <th class="w-28 whitespace-nowrap px-6 py-4 text-left font-semibold text-slate-700">{{ __('admin.status') }}</th>
                            <th class="w-56 whitespace-nowrap px-6 py-4 text-right font-semibold text-slate-700">{{ __('admin.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($stores as $store)
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-start gap-3">
                                        @if($store->banner_image)
                                            <img src="{{ asset('storage/' . ltrim($store->banner_image, '/')) }}" alt="{{ $store->name }} {{ __('admin.no_banner') }}" class="h-12 w-20 rounded-lg object-cover ring-1 ring-slate-200">
                                        @else
                                            <div class="flex h-12 w-20 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-100 text-[11px] text-slate-500">
                                                {{ __('admin.no_banner') }}
                                            </div>
                                        @endif

                                        <div>
                                            <div class="font-semibold text-slate-900">{{ $store->name }}</div>
                                            <div class="mt-1 text-xs text-slate-500">
                                                {{ \Illuminate\Support\Str::limit($store->description, 50) ?: __('admin.no_description') }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-600">{{ $store->slug }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $store->phone ?: '-' }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ strtoupper($store->currency ?? 'twd') }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $countryLabelMap[strtolower($store->country_code ?? 'tw')] ?? strtoupper($store->country_code ?? 'tw') }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $store->address ?: '-' }}</td>
                                <td class="w-28 px-6 py-4 whitespace-nowrap">
                                    @if($store->is_active)
                                        <span class="inline-flex whitespace-nowrap rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                            {{ __('admin.active') }}
                                        </span>
                                    @else
                                        <span class="inline-flex whitespace-nowrap rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">
                                            {{ __('admin.inactive') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="w-56 px-6 py-4 align-middle">
                                    <div class="flex flex-wrap justify-end gap-2 sm:flex-nowrap">
                                        <a href="{{ route('admin.stores.products.index', $store) }}"
                                           class="inline-flex items-center whitespace-nowrap rounded-xl border border-indigo-300 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100">
                                            {{ __('admin.products') }}
                                        </a>

                                        <button
                                            type="button"
                                            data-store-actions-toggle="{{ $store->id }}"
                                            aria-expanded="false"
                                            class="inline-flex items-center whitespace-nowrap rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                            {{ __('admin.store_actions_expand') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr class="hidden bg-slate-50/70" data-store-actions-row="{{ $store->id }}">
                                <td colspan="7" class="px-6 pb-5 pt-0">
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ __('admin.store_actionable_items') }}</p>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <a href="{{ route('admin.stores.products.index', $store) }}"
                                               class="inline-flex items-center rounded-xl border border-indigo-300 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100">
                                                {{ __('admin.products') }}
                                            </a>

                                            <a href="{{ route('admin.stores.tables.index', $store) }}"
                                               class="inline-flex items-center rounded-xl border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700 transition hover:bg-amber-100">
                                                {{ __('admin.tables_qr') }}
                                            </a>

                                            @if($store->is_active)
                                                <a href="{{ route('admin.stores.kitchen', $store) }}"
                                                   class="inline-flex items-center rounded-xl border border-orange-300 bg-orange-50 px-4 py-2 text-sm font-semibold text-orange-700 transition hover:bg-orange-100">
                                                    🍳 {{ __('admin.kitchen') }}
                                                </a>

                                                <a href="{{ route('admin.stores.chefs.index', $store) }}"
                                                   class="inline-flex items-center rounded-xl border border-cyan-300 bg-cyan-50 px-4 py-2 text-sm font-semibold text-cyan-700 transition hover:bg-cyan-100">
                                                    👨‍🍳 {{ __('admin.chef_accounts') }}
                                                </a>
                                            @endif

                                            <button
                                                type="button"
                                                data-edit-store="{{ $store->slug }}"
                                                class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                                {{ __('admin.edit') }}
                                            </button>

                                            <form method="POST" action="{{ route('admin.stores.destroy', $store) }}" onsubmit="return confirm('{{ __('admin.delete_confirm') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="inline-flex items-center rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-500">
                                                    {{ __('admin.delete') }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                    {{ __('admin.no_stores') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-6 py-4">
                {{ $stores->links() }}
            </div>
        </div>
    </div>
</div>

<div id="store-modal" class="fixed inset-0 z-[120] hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-4xl rounded-3xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <div>
                <h3 id="store-modal-title" class="text-lg font-bold text-slate-900">{{ __('admin.add_store_modal_title') }}</h3>
                <p class="text-xs text-slate-500">{{ __('admin.modal_subtitle') }}</p>
            </div>
            <button type="button" id="store-modal-close" class="rounded-full p-2 text-slate-500 hover:bg-slate-100">✕</button>
        </div>

        <form id="store-modal-form" class="max-h-[78vh] overflow-y-auto px-6 py-5" enctype="multipart/form-data">
            <input type="hidden" name="_method" id="store-modal-method" value="POST">

            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('admin.store_name') }}</label>
                    <input type="text" name="name" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" required>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Slug</label>
                    <input type="text" name="slug" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="{{ __('admin.slug_placeholder') }}">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('admin.phone') }}</label>
                    <input type="text" name="phone" id="store-modal-phone" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="0922-333-444" maxlength="12">
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('admin.address') }}</label>
                    <input type="text" name="address" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('admin.store_description') }}</label>
                    <textarea name="description" rows="4" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"></textarea>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('admin.opening_time') }}</label>
                    <input type="time" name="opening_time" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('admin.closing_time') }}</label>
                    <input type="time" name="closing_time" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('admin.checkout_timing') }}</label>
                    <select name="checkout_timing" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="postpay">{{ __('admin.checkout_postpay') }}</option>
                        <option value="prepay">{{ __('admin.checkout_prepay') }}</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('admin.store_country') }}</label>
                    <select name="country_code" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="tw">{{ __('admin.country_tw') }}</option>
                        <option value="vn">{{ __('admin.country_vn') }}</option>
                        <option value="cn">{{ __('admin.country_cn') }}</option>
                        <option value="us">{{ __('admin.country_us') }}</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('admin.currency') }}</label>
                    <select name="currency" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="twd">{{ __('admin.currency_twd') }}</option>
                        <option value="vnd">{{ __('admin.currency_vnd') }}</option>
                        <option value="cny">{{ __('admin.currency_cny') }}</option>
                        <option value="usd">{{ __('admin.currency_usd') }}</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Timezone</label>
                    <select name="timezone" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="Asia/Taipei">Asia/Taipei</option>
                        <option value="Asia/Ho_Chi_Minh">Asia/Ho_Chi_Minh</option>
                        <option value="Asia/Shanghai">Asia/Shanghai</option>
                        <option value="America/New_York">America/New_York</option>
                    </select>
                </div>

                <div class="md:col-span-2 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                    <label class="mb-2 block text-xs font-semibold text-slate-600">{{ __('admin.banner_image') }}</label>
                    <div id="store-banner-dropzone" class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 bg-white p-5 text-center transition hover:border-indigo-400 hover:bg-indigo-50">
                        <input type="file" id="store-banner-input" name="banner_image" accept="image/*" class="hidden">
                        <p class="text-sm text-slate-600">{{ __('admin.banner_dropzone') }} <span class="font-semibold text-indigo-600">{{ __('admin.click_to_upload') }}</span></p>
                        <p class="mt-1 text-xs text-slate-400">{{ __('admin.banner_file_hint') }}</p>
                    </div>

                    <div id="store-banner-preview-wrapper" class="mt-3 hidden">
                        <canvas id="store-banner-crop-preview" width="1200" height="400" class="w-full rounded-xl border border-slate-300 bg-white"></canvas>
                        <p id="store-banner-helper" class="mt-2 text-xs text-slate-500">尚未選擇橫幅</p>
                        <div class="mt-3">
                            <label for="store-banner-zoom" class="mb-1 block text-xs font-semibold text-slate-600">縮放</label>
                            <input id="store-banner-zoom" type="range" min="1" max="3" step="0.05" value="1" class="w-full">
                        </div>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button type="button" id="store-banner-reset" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">重設位置</button>
                            <button type="button" id="store-banner-remove" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100">{{ __('admin.remove') }}</button>
                        </div>
                        <p class="mt-2 text-[11px] text-slate-500">在預覽區拖曳可調整橫幅裁切範圍，儲存時會套用。</p>
                    </div>
                </div>

                <div>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="is_active" id="store-modal-is-active" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600">
                        {{ __('admin.enable_store') }}
                    </label>
                </div>
            </div>

            <div id="store-modal-error" class="mt-4 hidden rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700"></div>

            <div class="mt-6 flex gap-2">
                <button type="submit" id="store-modal-submit" class="inline-flex flex-1 items-center justify-center rounded-2xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500">{{ __('admin.save_store') }}</button>
                <button type="button" id="store-modal-cancel" class="inline-flex flex-1 items-center justify-center rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">{{ __('admin.cancel') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
(() => {
    const csrfToken = '{{ csrf_token() }}';
    const canCreateStore = {{ isset($canCreateStore) && ! $canCreateStore ? 'false' : 'true' }};

    const createUrl = '{{ route('admin.stores.store') }}';
    const editUrlTemplate = '{{ route('admin.stores.edit', '__STORE__') }}';
    const updateUrlTemplate = '{{ route('admin.stores.update', '__STORE__') }}';

    const flash = document.getElementById('product-flash') || (() => {
        const box = document.createElement('div');
        box.id = 'store-ajax-flash';
        box.className = 'mb-6 hidden rounded-2xl border px-4 py-3 text-sm';
        const host = document.querySelector('.mx-auto.max-w-7xl');
        host?.insertBefore(box, host.children[3] || null);
        return box;
    })();

    const modal = document.getElementById('store-modal');
    const modalTitle = document.getElementById('store-modal-title');
    const modalClose = document.getElementById('store-modal-close');
    const modalCancel = document.getElementById('store-modal-cancel');
    const modalForm = document.getElementById('store-modal-form');
    const modalMethod = document.getElementById('store-modal-method');
    const modalSubmit = document.getElementById('store-modal-submit');
    const modalError = document.getElementById('store-modal-error');
    const openModalBtn = document.getElementById('open-store-modal-btn');
    const phoneInput = document.getElementById('store-modal-phone');

    const bannerDropzone = document.getElementById('store-banner-dropzone');
    const bannerInput = document.getElementById('store-banner-input');
    const bannerPreviewWrapper = document.getElementById('store-banner-preview-wrapper');
    const bannerCropPreview = document.getElementById('store-banner-crop-preview');
    const bannerCropCtx = bannerCropPreview?.getContext('2d');
    const bannerHelper = document.getElementById('store-banner-helper');
    const bannerZoomInput = document.getElementById('store-banner-zoom');
    const bannerReset = document.getElementById('store-banner-reset');
    const bannerRemove = document.getElementById('store-banner-remove');

    let currentMode = 'create';
    let currentStoreId = null;
    const maxUploadImageBytes = 2 * 1024 * 1024;
    const bannerState = {
        sourceImage: null,
        sourceObjectUrl: null,
        sourceFileName: 'banner.jpg',
        hasNewUpload: false,
        dirty: false,
        zoom: 1,
        offsetX: 0,
        offsetY: 0,
        dragging: false,
        lastX: 0,
        lastY: 0,
    };
    const i18n = {
        imageOnly: @json(__('admin.error_image_only')),
        imageTooLarge: @json(__('admin.error_image_too_large')),
        createTitle: @json(__('admin.add_store_modal_title')),
        createSubmit: @json(__('admin.create_store')),
        editTitle: @json(__('admin.edit_store_modal_title')),
        editSubmit: @json(__('admin.update_store')),
        fetchStoreFailed: @json(__('admin.fetch_store_failed')),
        saveFailed: @json(__('admin.save_failed')),
        storeSaved: @json(__('admin.store_saved')),
        actionsExpand: @json(__('admin.store_actions_expand')),
        actionsCollapse: @json(__('admin.store_actions_collapse')),
    };

    const showFlash = (message, type = 'success') => {
        if (!flash) {
            return;
        }

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

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modalError.classList.add('hidden');
        modalError.textContent = '';
        currentStoreId = null;
    };

    const formatTaiwanPhone = (value) => {
        const digits = String(value || '').replace(/\D+/g, '').slice(0, 10);
        if (digits.length <= 4) {
            return digits;
        }
        if (digits.length <= 7) {
            return `${digits.slice(0, 4)}-${digits.slice(4)}`;
        }
        return `${digits.slice(0, 4)}-${digits.slice(4, 7)}-${digits.slice(7)}`;
    };

    const revokeBannerObjectUrl = () => {
        if (!bannerState.sourceObjectUrl) {
            return;
        }

        URL.revokeObjectURL(bannerState.sourceObjectUrl);
        bannerState.sourceObjectUrl = null;
    };

    const loadImageFromFile = (file) => new Promise((resolve, reject) => {
        const url = URL.createObjectURL(file);
        const image = new Image();

        image.onload = () => {
            URL.revokeObjectURL(url);
            resolve(image);
        };

        image.onerror = () => {
            URL.revokeObjectURL(url);
            reject(new Error('圖片讀取失敗'));
        };

        image.src = url;
    });

    const clampBannerOffsets = () => {
        if (!bannerCropPreview || !bannerState.sourceImage) {
            return;
        }

        const baseScale = Math.max(
            bannerCropPreview.width / bannerState.sourceImage.naturalWidth,
            bannerCropPreview.height / bannerState.sourceImage.naturalHeight,
        );
        const drawWidth = bannerState.sourceImage.naturalWidth * baseScale * bannerState.zoom;
        const drawHeight = bannerState.sourceImage.naturalHeight * baseScale * bannerState.zoom;
        const minX = bannerCropPreview.width - drawWidth;
        const minY = bannerCropPreview.height - drawHeight;

        bannerState.offsetX = Math.min(0, Math.max(minX, bannerState.offsetX));
        bannerState.offsetY = Math.min(0, Math.max(minY, bannerState.offsetY));
    };

    const centerBannerImage = () => {
        if (!bannerCropPreview || !bannerState.sourceImage) {
            return;
        }

        const baseScale = Math.max(
            bannerCropPreview.width / bannerState.sourceImage.naturalWidth,
            bannerCropPreview.height / bannerState.sourceImage.naturalHeight,
        );
        const drawWidth = bannerState.sourceImage.naturalWidth * baseScale * bannerState.zoom;
        const drawHeight = bannerState.sourceImage.naturalHeight * baseScale * bannerState.zoom;
        bannerState.offsetX = (bannerCropPreview.width - drawWidth) / 2;
        bannerState.offsetY = (bannerCropPreview.height - drawHeight) / 2;
        clampBannerOffsets();
    };

    const renderBannerPreview = () => {
        if (!bannerCropPreview || !bannerCropCtx) {
            return;
        }

        bannerCropCtx.clearRect(0, 0, bannerCropPreview.width, bannerCropPreview.height);
        bannerCropCtx.fillStyle = '#f8fafc';
        bannerCropCtx.fillRect(0, 0, bannerCropPreview.width, bannerCropPreview.height);

        if (!bannerState.sourceImage) {
            bannerCropCtx.strokeStyle = '#cbd5e1';
            bannerCropCtx.lineWidth = 2;
            bannerCropCtx.strokeRect(8, 8, bannerCropPreview.width - 16, bannerCropPreview.height - 16);
            bannerCropCtx.fillStyle = '#64748b';
            bannerCropCtx.font = '24px sans-serif';
            bannerCropCtx.textAlign = 'center';
            bannerCropCtx.fillText('尚未選擇橫幅', bannerCropPreview.width / 2, bannerCropPreview.height / 2 + 8);
            return;
        }

        const baseScale = Math.max(
            bannerCropPreview.width / bannerState.sourceImage.naturalWidth,
            bannerCropPreview.height / bannerState.sourceImage.naturalHeight,
        );
        const drawWidth = bannerState.sourceImage.naturalWidth * baseScale * bannerState.zoom;
        const drawHeight = bannerState.sourceImage.naturalHeight * baseScale * bannerState.zoom;
        clampBannerOffsets();

        bannerCropCtx.drawImage(
            bannerState.sourceImage,
            bannerState.offsetX,
            bannerState.offsetY,
            drawWidth,
            drawHeight,
        );
    };

    const canvasToBlob = (canvas, mimeType, quality) => new Promise((resolve, reject) => {
        canvas.toBlob((blob) => {
            if (blob) {
                resolve(blob);
                return;
            }

            reject(new Error('圖片轉換失敗'));
        }, mimeType, quality);
    });

    const ensureBannerBlobWithinLimit = async (canvas, maxBytes = maxUploadImageBytes) => {
        let quality = 0.92;
        let outputCanvas = canvas;
        let blob = await canvasToBlob(outputCanvas, 'image/jpeg', quality);

        while (blob.size > maxBytes && quality > 0.45) {
            quality = Math.max(0.45, quality - 0.08);
            blob = await canvasToBlob(outputCanvas, 'image/jpeg', quality);
        }

        while (blob.size > maxBytes && outputCanvas.width > 360 && outputCanvas.height > 120) {
            const nextCanvas = document.createElement('canvas');
            nextCanvas.width = Math.max(360, Math.floor(outputCanvas.width * 0.9));
            nextCanvas.height = Math.max(120, Math.floor(outputCanvas.height * 0.9));
            const nextCtx = nextCanvas.getContext('2d');
            if (!nextCtx) {
                break;
            }

            nextCtx.drawImage(outputCanvas, 0, 0, nextCanvas.width, nextCanvas.height);
            outputCanvas = nextCanvas;
            quality = Math.min(quality, 0.78);
            blob = await canvasToBlob(outputCanvas, 'image/jpeg', quality);
        }

        if (!blob || blob.size > maxBytes) {
            throw new Error(i18n.imageTooLarge);
        }

        return blob;
    };

    const clearBannerPreview = () => {
        revokeBannerObjectUrl();
        bannerInput.value = '';
        bannerState.sourceImage = null;
        bannerState.sourceFileName = 'banner.jpg';
        bannerState.hasNewUpload = false;
        bannerState.dirty = false;
        bannerState.zoom = 1;
        bannerState.offsetX = 0;
        bannerState.offsetY = 0;
        bannerState.dragging = false;
        if (bannerZoomInput) {
            bannerZoomInput.value = '1';
        }
        if (bannerHelper) {
            bannerHelper.textContent = '尚未選擇橫幅';
        }
        bannerPreviewWrapper.classList.add('hidden');
        renderBannerPreview();
    };

    const setBannerFromUrl = (url) => {
        if (!url) {
            clearBannerPreview();
            return;
        }

        revokeBannerObjectUrl();
        const image = new Image();
        image.onload = () => {
            bannerState.sourceImage = image;
            bannerState.sourceFileName = 'banner.jpg';
            bannerState.hasNewUpload = false;
            bannerState.dirty = false;
            bannerState.zoom = 1;
            if (bannerZoomInput) {
                bannerZoomInput.value = '1';
            }
            centerBannerImage();
            bannerPreviewWrapper.classList.remove('hidden');
            if (bannerHelper) {
                bannerHelper.textContent = '目前橫幅（可拖曳調整裁切）';
            }
            renderBannerPreview();
        };
        image.onerror = () => {
            clearBannerPreview();
        };
        image.src = url;
    };

    const setBannerPreviewFromFile = async (file) => {
        if (!file) {
            return;
        }

        if (!file.type.startsWith('image/')) {
            showFlash(i18n.imageOnly, 'error');
            return;
        }

        revokeBannerObjectUrl();
        const url = URL.createObjectURL(file);
        bannerState.sourceObjectUrl = url;
        const image = new Image();
        image.onload = () => {
            bannerState.sourceImage = image;
            bannerState.sourceFileName = file.name || 'banner.jpg';
            bannerState.hasNewUpload = true;
            bannerState.dirty = false;
            bannerState.zoom = 1;
            if (bannerZoomInput) {
                bannerZoomInput.value = '1';
            }
            centerBannerImage();
            bannerPreviewWrapper.classList.remove('hidden');
            if (bannerHelper) {
                bannerHelper.textContent = `已選擇：${file.name}`;
            }
            renderBannerPreview();
        };
        image.onerror = () => {
            showFlash('圖片讀取失敗，請重新選擇。', 'error');
        };
        image.src = url;
    };

    const setFormValues = (store = null) => {
        modalForm.reset();
        modalMethod.value = 'POST';
        clearBannerPreview();
        modalForm.elements['is_active'].checked = true;
        modalForm.elements['checkout_timing'].value = 'postpay';
        modalForm.elements['country_code'].value = 'tw';
        modalForm.elements['currency'].value = 'twd';
        modalForm.elements['timezone'].value = 'Asia/Taipei';

        if (!store) {
            return;
        }

        modalForm.elements['name'].value = store.name || '';
        modalForm.elements['slug'].value = store.slug || '';
        modalForm.elements['phone'].value = store.phone || '';
        modalForm.elements['address'].value = store.address || '';
        modalForm.elements['description'].value = store.description || '';
        modalForm.elements['opening_time'].value = store.opening_time || '';
        modalForm.elements['closing_time'].value = store.closing_time || '';
        modalForm.elements['checkout_timing'].value = store.checkout_timing || 'postpay';
        modalForm.elements['country_code'].value = (store.country_code || 'tw').toLowerCase();
        modalForm.elements['currency'].value = (store.currency || 'twd').toLowerCase();
        modalForm.elements['timezone'].value = store.timezone || 'Asia/Taipei';
        modalForm.elements['is_active'].checked = !!store.is_active;

        if (store.banner_image_url) {
            setBannerFromUrl(store.banner_image_url);
        }
    };

    const openCreateModal = () => {
        if (!canCreateStore) {
            return;
        }
        currentMode = 'create';
        currentStoreId = null;
        modalTitle.textContent = i18n.createTitle;
        modalSubmit.textContent = i18n.createSubmit;
        setFormValues(null);
        openModal();
    };

    const openEditModal = async (storeId) => {
        currentMode = 'edit';
        currentStoreId = storeId;
        modalTitle.textContent = i18n.editTitle;
        modalSubmit.textContent = i18n.editSubmit;

        try {
            const url = editUrlTemplate.replace('__STORE__', String(storeId));
            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await res.json();
            if (!res.ok || !data.ok) {
                throw new Error(data.message || i18n.fetchStoreFailed);
            }

            setFormValues(data.store);
            openModal();
        } catch (e) {
            showFlash(e.message || i18n.fetchStoreFailed, 'error');
        }
    };

    const submitModalForm = async (event) => {
        event.preventDefault();
        modalError.classList.add('hidden');
        modalError.textContent = '';

        const formData = new FormData(modalForm);
        formData.delete('banner_image');
        if (!formData.get('is_active')) {
            formData.set('is_active', '0');
        }

        try {
            if (bannerState.sourceImage && (bannerState.hasNewUpload || bannerState.dirty) && bannerCropPreview) {
                const blob = await ensureBannerBlobWithinLimit(bannerCropPreview, maxUploadImageBytes);
                const filename = (bannerState.sourceFileName || 'banner.jpg').replace(/\.[^.]+$/, '.jpg');
                formData.set('banner_image', new File([blob], filename, { type: 'image/jpeg' }));
            }

            let url = createUrl;
            if (currentMode === 'edit' && currentStoreId) {
                url = updateUrlTemplate.replace('__STORE__', String(currentStoreId));
                formData.set('_method', 'PUT');
            }

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
                throw new Error(data.message || validationMessage || i18n.saveFailed);
            }

            closeModal();
            showFlash(data.message || i18n.storeSaved);
            window.location.reload();
        } catch (e) {
            modalError.classList.remove('hidden');
            modalError.textContent = e.message || i18n.saveFailed;
        }
    };

    openModalBtn?.addEventListener('click', openCreateModal);
    modalClose?.addEventListener('click', closeModal);
    modalCancel?.addEventListener('click', closeModal);
    modalForm?.addEventListener('submit', submitModalForm);

    document.querySelectorAll('[data-edit-store]').forEach((button) => {
        button.addEventListener('click', () => {
            openEditModal(button.getAttribute('data-edit-store'));
        });
    });

    document.querySelectorAll('[data-store-actions-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const storeId = button.getAttribute('data-store-actions-toggle');
            const targetRow = document.querySelector(`[data-store-actions-row="${storeId}"]`);
            if (!targetRow) {
                return;
            }

            const isHidden = targetRow.classList.contains('hidden');

            document.querySelectorAll('[data-store-actions-row]').forEach((row) => {
                row.classList.add('hidden');
            });

            document.querySelectorAll('[data-store-actions-toggle]').forEach((toggleBtn) => {
                toggleBtn.setAttribute('aria-expanded', 'false');
                toggleBtn.textContent = i18n.actionsExpand;
            });

            if (isHidden) {
                targetRow.classList.remove('hidden');
                button.setAttribute('aria-expanded', 'true');
                button.textContent = i18n.actionsCollapse;
            }
        });
    });

    phoneInput?.addEventListener('input', (event) => {
        event.target.value = formatTaiwanPhone(event.target.value);
    });

    bannerDropzone?.addEventListener('click', () => bannerInput?.click());
    bannerDropzone?.addEventListener('dragover', (event) => {
        event.preventDefault();
        bannerDropzone.classList.add('border-indigo-500', 'bg-indigo-50');
    });
    bannerDropzone?.addEventListener('dragleave', () => {
        bannerDropzone.classList.remove('border-indigo-500', 'bg-indigo-50');
    });
    bannerDropzone?.addEventListener('drop', (event) => {
        event.preventDefault();
        bannerDropzone.classList.remove('border-indigo-500', 'bg-indigo-50');
        const file = event.dataTransfer?.files?.[0];
        void setBannerPreviewFromFile(file);
    });
    bannerInput?.addEventListener('change', (event) => {
        const file = event.target.files?.[0];
        void setBannerPreviewFromFile(file);
    });
    bannerZoomInput?.addEventListener('input', () => {
        if (!bannerState.sourceImage) {
            return;
        }

        bannerState.zoom = Number(bannerZoomInput.value || '1');
        bannerState.dirty = true;
        centerBannerImage();
        renderBannerPreview();
    });
    bannerCropPreview?.addEventListener('mousedown', (event) => {
        if (!bannerState.sourceImage) {
            return;
        }

        bannerState.dragging = true;
        bannerState.lastX = event.clientX;
        bannerState.lastY = event.clientY;
    });
    bannerCropPreview?.addEventListener('mousemove', (event) => {
        if (!bannerState.dragging || !bannerState.sourceImage) {
            return;
        }

        bannerState.offsetX += event.clientX - bannerState.lastX;
        bannerState.offsetY += event.clientY - bannerState.lastY;
        bannerState.lastX = event.clientX;
        bannerState.lastY = event.clientY;
        bannerState.dirty = true;
        clampBannerOffsets();
        renderBannerPreview();
    });
    bannerCropPreview?.addEventListener('mouseup', () => {
        bannerState.dragging = false;
    });
    bannerCropPreview?.addEventListener('mouseleave', () => {
        bannerState.dragging = false;
    });
    bannerCropPreview?.addEventListener('touchstart', (event) => {
        if (!bannerState.sourceImage || event.touches.length === 0) {
            return;
        }

        const touch = event.touches[0];
        bannerState.dragging = true;
        bannerState.lastX = touch.clientX;
        bannerState.lastY = touch.clientY;
    }, { passive: true });
    bannerCropPreview?.addEventListener('touchmove', (event) => {
        if (!bannerState.dragging || !bannerState.sourceImage || event.touches.length === 0) {
            return;
        }

        const touch = event.touches[0];
        bannerState.offsetX += touch.clientX - bannerState.lastX;
        bannerState.offsetY += touch.clientY - bannerState.lastY;
        bannerState.lastX = touch.clientX;
        bannerState.lastY = touch.clientY;
        bannerState.dirty = true;
        clampBannerOffsets();
        renderBannerPreview();
    }, { passive: true });
    bannerCropPreview?.addEventListener('touchend', () => {
        bannerState.dragging = false;
    }, { passive: true });
    bannerCropPreview?.addEventListener('touchcancel', () => {
        bannerState.dragging = false;
    }, { passive: true });
    bannerReset?.addEventListener('click', () => {
        if (!bannerState.sourceImage) {
            return;
        }

        bannerState.zoom = 1;
        bannerState.dirty = true;
        if (bannerZoomInput) {
            bannerZoomInput.value = '1';
        }
        centerBannerImage();
        renderBannerPreview();
    });
    bannerRemove?.addEventListener('click', clearBannerPreview);
    renderBannerPreview();
})();
</script>
@endsection