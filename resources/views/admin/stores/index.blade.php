@extends('layouts.app')

@section('content')
@php
    $storeCollection = $stores->getCollection();
    $activeStoreCount = $storeCollection->where('is_active', true)->count();
    $inactiveStoreCount = $storeCollection->count() - $activeStoreCount;
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
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">店家總數</p>
                    <p class="value mt-2 text-slate-900">{{ $storeCollection->count() }}</p>
                </div>
                <div class="admin-kpi rounded-2xl p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">啟用中</p>
                    <p class="value mt-2 text-emerald-700">{{ $activeStoreCount }}</p>
                </div>
                <div class="admin-kpi rounded-2xl p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">已關閉</p>
                    <p class="value mt-2 text-amber-700">{{ $inactiveStoreCount }}</p>
                </div>
            </div>
        </div>

        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="admin-pill-nav inline-flex items-center gap-2 rounded-full px-3 py-2 text-xs font-semibold text-slate-700">
                    <span class="rounded-full bg-cyan-100 px-2 py-1 text-cyan-700">總覽</span>
                    <span>可管理所有門市與營運入口</span>
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
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                    {{ __('admin.search') }}
                </button>
            </form>
        </div>

        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-4 text-left font-semibold text-slate-700">{{ __('admin.store_name') }}</th>
                            <th class="px-6 py-4 text-left font-semibold text-slate-700">Slug</th>
                            <th class="px-6 py-4 text-left font-semibold text-slate-700">{{ __('admin.phone') }}</th>
                            <th class="px-6 py-4 text-left font-semibold text-slate-700">{{ __('admin.currency') }}</th>
                            <th class="px-6 py-4 text-left font-semibold text-slate-700">{{ __('admin.address') }}</th>
                            <th class="px-6 py-4 text-left font-semibold text-slate-700">{{ __('admin.status') }}</th>
                            <th class="px-6 py-4 text-right font-semibold text-slate-700">{{ __('admin.actions') }}</th>
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
                                <td class="px-6 py-4 text-slate-600">{{ $store->address ?: '-' }}</td>
                                <td class="px-6 py-4">
                                    @if($store->is_active)
                                        <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                            {{ __('admin.active') }}
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">
                                            {{ __('admin.inactive') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.stores.products.index', $store) }}"
                                           class="inline-flex items-center rounded-xl border border-indigo-300 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100">
                                            {{ __('admin.products') }}
                                        </a>

                                        <button
                                            type="button"
                                            data-store-actions-toggle="{{ $store->id }}"
                                            aria-expanded="false"
                                            class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                            展開操作
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr class="hidden bg-slate-50/70" data-store-actions-row="{{ $store->id }}">
                                <td colspan="6" class="px-6 pb-5 pt-0">
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">可操作項目</p>
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
                                                    👨‍🍳 廚師帳號
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
                                <td colspan="6" class="px-6 py-12 text-center text-slate-500">
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

<div id="store-modal" class="fixed inset-0 z-[120] hidden items-end justify-center bg-black/50 p-4 sm:items-center">
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

                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('admin.currency') }}</label>
                    <select name="currency" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="twd">{{ __('admin.currency_twd') }}</option>
                        <option value="vnd">{{ __('admin.currency_vnd') }}</option>
                        <option value="cny">{{ __('admin.currency_cny') }}</option>
                        <option value="usd">{{ __('admin.currency_usd') }}</option>
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
                        <div class="relative">
                            <img id="store-banner-preview" src="" class="h-40 w-full rounded-xl object-cover">
                            <button type="button" id="store-banner-remove" class="absolute right-2 top-2 rounded-full bg-black/60 px-3 py-1 text-xs text-white hover:bg-black">{{ __('admin.remove') }}</button>
                        </div>
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
    const bannerPreview = document.getElementById('store-banner-preview');
    const bannerRemove = document.getElementById('store-banner-remove');

    let currentMode = 'create';
    let currentStoreId = null;
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

    const clearBannerPreview = () => {
        bannerInput.value = '';
        bannerPreview.src = '';
        bannerPreviewWrapper.classList.add('hidden');
    };

    const setBannerPreviewFromFile = (file) => {
        if (!file) {
            return;
        }

        if (!file.type.startsWith('image/')) {
            showFlash(i18n.imageOnly, 'error');
            return;
        }

        if (file.size > 2 * 1024 * 1024) {
            showFlash(i18n.imageTooLarge, 'error');
            return;
        }

        const url = URL.createObjectURL(file);
        bannerPreview.src = url;
        bannerPreviewWrapper.classList.remove('hidden');
        bannerPreview.onload = () => URL.revokeObjectURL(url);

        const transfer = new DataTransfer();
        transfer.items.add(file);
        bannerInput.files = transfer.files;
    };

    const setFormValues = (store = null) => {
        modalForm.reset();
        modalMethod.value = 'POST';
        clearBannerPreview();
        modalForm.elements['is_active'].checked = true;
        modalForm.elements['checkout_timing'].value = 'postpay';
        modalForm.elements['currency'].value = 'twd';

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
        modalForm.elements['currency'].value = (store.currency || 'twd').toLowerCase();
        modalForm.elements['is_active'].checked = !!store.is_active;

        if (store.banner_image_url) {
            bannerPreview.src = store.banner_image_url;
            bannerPreviewWrapper.classList.remove('hidden');
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
        if (!formData.get('is_active')) {
            formData.set('is_active', '0');
        }

        let url = createUrl;
        if (currentMode === 'edit' && currentStoreId) {
            url = updateUrlTemplate.replace('__STORE__', String(currentStoreId));
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
                toggleBtn.textContent = '展開操作';
            });

            if (isHidden) {
                targetRow.classList.remove('hidden');
                button.setAttribute('aria-expanded', 'true');
                button.textContent = '收合操作';
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
        setBannerPreviewFromFile(file);
    });
    bannerInput?.addEventListener('change', (event) => {
        const file = event.target.files?.[0];
        setBannerPreviewFromFile(file);
    });
    bannerRemove?.addEventListener('click', clearBannerPreview);
})();
</script>
@endsection