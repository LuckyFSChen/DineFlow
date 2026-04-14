@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50" x-data="{}">
    <div class="mx-auto max-w-7xl px-6 py-10 lg:px-8">
        <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-slate-900">商品管理中心</h1>
                <p class="mt-2 text-slate-600">店家：{{ $store->name }}，依分類管理商品，使用彈窗快速編輯。</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.stores.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">回店家列表</a>
                <button type="button" id="create-product-btn" class="inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500">新增商品</button>
            </div>
        </div>

        <div class="mb-8 grid gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">總商品數</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $totalProducts }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">可販售</p>
                <p class="mt-2 text-2xl font-bold text-emerald-700">{{ $activeProducts }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">有選配</p>
                <p class="mt-2 text-2xl font-bold text-indigo-700">{{ $optionEnabledProducts }}</p>
            </div>
        </div>

        <div id="product-flash" class="mb-6 hidden rounded-2xl border px-4 py-3 text-sm"></div>

        <div class="space-y-6">
            @forelse($categories as $category)
                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm" data-category-section data-category-id="{{ $category->id }}">
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-slate-900">{{ $category->name }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ $category->products->count() }} 項商品</p>
                        </div>
                        <button type="button" class="inline-flex items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100" data-create-in-category="{{ $category->id }}">在此分類新增</button>
                    </div>

                    @if($category->products->isNotEmpty())
                        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3" data-category-products data-category-id="{{ $category->id }}">
                            @foreach($category->products as $product)
                                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4" data-product-card data-product-id="{{ $product->id }}">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <h3 class="text-base font-semibold text-slate-900">{{ $product->name }}</h3>
                                            <p class="mt-1 text-sm text-slate-500">NT$ {{ number_format($product->price) }} ・ <span data-product-sort>排序 {{ $product->sort }}</span></p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex cursor-grab rounded-lg border border-slate-300 bg-white px-2 py-1 text-[11px] font-semibold text-slate-600 active:cursor-grabbing" data-drag-product-handle>拖曳排序</span>
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $product->is_active && ! $product->is_sold_out ? 'bg-emerald-50 text-emerald-700' : ($product->is_sold_out ? 'bg-amber-50 text-amber-700' : 'bg-slate-200 text-slate-600') }}">
                                                {{ $product->is_active && ! $product->is_sold_out ? '可販售' : ($product->is_sold_out ? '售完' : '下架') }}
                                            </span>
                                        </div>
                                    </div>

                                    <p class="mt-3 line-clamp-2 text-sm text-slate-600">{{ $product->description ?: '尚未填寫描述' }}</p>
                                    <p class="mt-2 text-xs font-medium text-indigo-700">{{ !empty($product->option_groups) ? '已設定選配' : '無選配' }}</p>

                                    <div class="mt-4 flex gap-2">
                                        <button type="button" class="inline-flex flex-1 items-center justify-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100" data-edit-product="{{ $product->id }}">編輯</button>
                                        <button type="button" class="inline-flex flex-1 items-center justify-center rounded-xl bg-rose-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-rose-500" data-delete-product="{{ $product->id }}" data-product-name="{{ $product->name }}">刪除</button>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500" data-category-products>
                            這個分類還沒有商品，點右上角「在此分類新增」快速建立。
                        </div>
                    @endif
                </section>
            @empty
                <div class="rounded-3xl border border-slate-200 bg-white px-6 py-12 text-center shadow-sm">
                    <p class="text-slate-600">目前沒有可用分類，請先建立分類再新增商品。</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

<div id="product-modal" class="fixed inset-0 z-[120] hidden items-end justify-center bg-black/50 p-4 sm:items-center">
    <div class="w-full max-w-3xl rounded-3xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <div>
                <h3 id="product-modal-title" class="text-lg font-bold text-slate-900">新增商品</h3>
                <p class="text-xs text-slate-500">使用彈窗快速維護商品資料</p>
            </div>
            <button type="button" id="product-modal-close" class="rounded-full p-2 text-slate-500 hover:bg-slate-100">✕</button>
        </div>

        <form id="product-modal-form" class="max-h-[75vh] overflow-y-auto px-6 py-5">
            <input type="hidden" name="_method" id="product-modal-method" value="POST">

            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-600">商品名稱</label>
                    <input type="text" name="name" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100" required>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">分類</label>
                    <select name="category_id" id="modal-category" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100" required>
                        @foreach($categoryOptions as $option)
                            <option value="{{ $option['id'] }}">{{ $option['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">價格 (NT$)</label>
                    <input type="number" name="price" min="0" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100" required>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">排序</label>
                    <input type="number" name="sort" min="1" value="1" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">圖片 URL</label>
                    <input type="text" name="image" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-600">描述</label>
                    <textarea name="description" rows="3" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"></textarea>
                </div>

                <div class="md:col-span-2 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                    <input type="hidden" name="option_groups_json" id="option-groups-json-input" value="[]">

                    <div class="mb-2 flex flex-wrap gap-2">
                        <button type="button" data-option-template="steak" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">套用牛排範本</button>
                        <button type="button" data-option-template="combo" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">套用套餐範本</button>
                        <button type="button" data-option-clear-all class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100">清空</button>
                        <button type="button" data-option-add-group class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500">新增群組</button>
                    </div>

                    <p class="mb-2 text-xs text-slate-600">選配樹狀編輯：先建立群組，再新增群組內選項。</p>
                    <div id="option-groups-editor" class="space-y-3"></div>
                </div>

                <div>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="is_active" id="modal-is-active" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        上架
                    </label>
                </div>

                <div>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="is_sold_out" id="modal-is-sold-out" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        售完
                    </label>
                </div>
            </div>

            <div id="product-modal-error" class="mt-4 hidden rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700"></div>

            <div class="mt-6 flex gap-2">
                <button type="submit" id="product-modal-submit" class="inline-flex flex-1 items-center justify-center rounded-2xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500">儲存商品</button>
                <button type="button" id="product-modal-cancel" class="inline-flex flex-1 items-center justify-center rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">取消</button>
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
    const addGroupBtn = document.querySelector('[data-option-add-group]');
    const clearAllBtn = document.querySelector('[data-option-clear-all]');
    const templateButtons = document.querySelectorAll('[data-option-template]');

    let currentMode = 'create';
    let currentProductId = null;
    let optionGroups = [];
    const reorderAbortControllers = new Map();

    const optionTemplates = {
        steak: [
            { id: 'doneness', name: '熟度', type: 'single', required: true, choices: [
                { id: 'rare', name: '三分熟', price: 0 },
                { id: 'medium', name: '五分熟', price: 0 },
                { id: 'well', name: '全熟', price: 0 },
            ] },
            { id: 'extras', name: '加購配料', type: 'multiple', required: false, max_select: 3, choices: [
                { id: 'egg', name: '加蛋', price: 20 },
                { id: 'cheese', name: '加起司', price: 25 },
                { id: 'sauce', name: '蘑菇醬', price: 15 },
            ] },
        ],
        combo: [
            { id: 'main_choice', name: '主餐', type: 'single', required: true, choices: [
                { id: 'chicken', name: '雞腿排', price: 0 },
                { id: 'pork', name: '豬排', price: 0 },
                { id: 'fish', name: '烤魚', price: 20 },
            ] },
            { id: 'side_choice', name: '附餐', type: 'single', required: true, choices: [
                { id: 'fries', name: '薯條', price: 0 },
                { id: 'salad', name: '沙拉', price: 0 },
                { id: 'soup', name: '濃湯', price: 0 },
            ] },
            { id: 'drink_choice', name: '飲料', type: 'single', required: true, choices: [
                { id: 'black_tea', name: '紅茶', price: 0 },
                { id: 'green_tea', name: '綠茶', price: 0 },
                { id: 'milk_tea', name: '奶茶', price: 10 },
            ] },
        ],
    };

    const uid = () => Math.random().toString(36).slice(2, 10);
    const toId = (value) => String(value || '')
        .trim()
        .toLowerCase()
        .replace(/\s+/g, '_')
        .replace(/[^a-z0-9_\-]/g, '');
    const esc = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

    const createGroup = () => ({
        id: '',
        name: '',
        type: 'single',
        required: false,
        max_select: 1,
        choices: [],
    });

    const createChoice = () => ({
        id: '',
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
                    id: String(group.id || '').trim(),
                    name: String(group.name || '').trim(),
                    type,
                    required: !!group.required,
                    max_select: type === 'multiple' ? Math.max(Number(group.max_select || 1), 1) : 1,
                    choices: choices
                        .filter((choice) => choice && typeof choice === 'object')
                        .map((choice) => ({
                            id: String(choice.id || '').trim(),
                            name: String(choice.name || '').trim(),
                            price: Math.max(Number(choice.price || 0), 0),
                        })),
                };
            });
    };

    const syncOptionGroups = () => {
        normalizeOptionGroups();
        optionGroupsInput.value = JSON.stringify(optionGroups);
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

            wrapper.innerHTML = `
                <div class="mb-3 flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-700">群組 #${groupIndex + 1}</p>
                    <button type="button" data-remove-group class="rounded-lg bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-100">刪除群組</button>
                </div>
                <div class="grid gap-2 md:grid-cols-2">
                    <input type="text" value="${esc(group.name || '')}" data-group-field="name" placeholder="群組名稱，例如：熟度" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <input type="text" value="${esc(group.id || '')}" data-group-field="id" placeholder="群組 ID，例如：doneness" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <select data-group-field="type" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="single" ${group.type === 'single' ? 'selected' : ''}>單選</option>
                        <option value="multiple" ${group.type === 'multiple' ? 'selected' : ''}>多選</option>
                    </select>
                    <input type="number" min="1" value="${group.max_select || 1}" data-group-field="max_select" ${group.type === 'single' ? 'disabled' : ''} class="rounded-lg border border-slate-300 px-3 py-2 text-sm disabled:bg-slate-100">
                </div>
                <label class="mt-2 inline-flex items-center gap-2 text-xs text-slate-700">
                    <input type="checkbox" data-group-field="required" ${group.required ? 'checked' : ''} class="h-4 w-4 rounded border-slate-300 text-indigo-600">
                    必選群組
                </label>
                <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-2">
                    <div class="mb-2 flex items-center justify-between">
                        <p class="text-xs font-semibold text-slate-600">選項</p>
                        <button type="button" data-add-choice class="rounded-md bg-slate-900 px-2.5 py-1 text-xs font-semibold text-white hover:bg-slate-800">新增選項</button>
                    </div>
                    <div class="space-y-2" data-choices-list="1"></div>
                </div>
            `;

            const choicesList = wrapper.querySelector('[data-choices-list]');
            (Array.isArray(group.choices) ? group.choices : []).forEach((choice, choiceIndex) => {
                const row = document.createElement('div');
                row.className = 'grid gap-2 rounded-lg border border-slate-200 bg-white p-2 md:grid-cols-[1fr,1fr,130px,auto]';
                row.dataset.choiceIndex = String(choiceIndex);
                row.innerHTML = `
                    <input type="text" value="${esc(choice.name || '')}" data-choice-field="name" placeholder="選項名稱" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <input type="text" value="${esc(choice.id || '')}" data-choice-field="id" placeholder="選項 ID" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <input type="number" min="0" value="${Number(choice.price || 0)}" data-choice-field="price" placeholder="加價" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <button type="button" data-remove-choice class="rounded-lg bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">刪除</button>
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

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modalError.classList.add('hidden');
        modalError.textContent = '';
        currentProductId = null;
    };

    const setFormValues = (product = null, categoryId = null) => {
        modalForm.reset();
        modalMethod.value = 'POST';
        optionGroups = [];
        optionGroupsInput.value = '[]';
        document.getElementById('modal-is-active').checked = true;
        document.getElementById('modal-is-sold-out').checked = false;

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
        modalForm.elements['sort'].value = product.sort ?? 1;
        modalForm.elements['image'].value = product.image ?? '';
        modalForm.elements['description'].value = product.description ?? '';
        optionGroups = Array.isArray(product.option_groups) ? product.option_groups : parseOptionGroups(product.option_groups_json ?? '[]');
        document.getElementById('modal-is-active').checked = !!product.is_active;
        document.getElementById('modal-is-sold-out').checked = !!product.is_sold_out;
        renderOptionEditor();
    };

    const openCreateModal = (categoryId = null) => {
        currentMode = 'create';
        modalTitle.textContent = '新增商品';
        modalSubmit.textContent = '建立商品';
        setFormValues(null, categoryId);
        openModal();
    };

    const openEditModal = async (productId) => {
        currentMode = 'edit';
        currentProductId = productId;
        modalTitle.textContent = '編輯商品';
        modalSubmit.textContent = '更新商品';

        try {
            const url = editUrlTemplate.replace('__PRODUCT__', String(productId));
            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!res.ok) {
                throw new Error('讀取商品資料失敗。');
            }

            const data = await res.json();
            setFormValues(data.product);
            openModal();
        } catch (e) {
            showFlash(e.message || '讀取商品資料失敗。', 'error');
        }
    };

    const collectFormData = () => {
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

        return formData;
    };

    const updateSortLabels = (container) => {
        const cards = [...container.querySelectorAll('[data-product-card]')];
        cards.forEach((card, index) => {
            const label = card.querySelector('[data-product-sort]');
            if (label) {
                label.textContent = `排序 ${index + 1}`;
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
                throw new Error(data.message || '排序更新失敗');
            }

            updateSortLabels(container);
            if (notify) {
                showFlash(data.message || '排序已更新。');
            }
        } catch (e) {
            if (e.name === 'AbortError') {
                return;
            }
            if (notify) {
                showFlash(e.message || '排序更新失敗', 'error');
            }
        } finally {
            reorderAbortControllers.delete(String(categoryId));
        }
    };

    const clearDropTargetStyles = (container) => {
        container.querySelectorAll('[data-product-card].drop-target').forEach((card) => {
            card.classList.remove('drop-target', 'ring-2', 'ring-amber-300');
        });
    };

    const getDropReference = (container, draggingCard, clientX, clientY, dragDirection) => {
        const pointed = document.elementFromPoint(clientX, clientY)?.closest('[data-product-card]');

        if (pointed && container.contains(pointed) && pointed !== draggingCard) {
            // 依拖曳方向決定插入點，避免在相鄰卡片間來回翻轉造成抖動。
            const insertBefore = dragDirection < 0;

            return {
                reference: insertBefore ? pointed : pointed.nextElementSibling,
                target: pointed,
            };
        }

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
        const containers = document.querySelectorAll('[data-category-products][data-category-id]');
        containers.forEach((container) => {
            let draggingCard = null;
            let startSignature = '';
            let lastPersistedSignature = orderSignature(container);
            let rafId = 0;
            let lastClientY = 0;
            let lastClientX = 0;
            let dragDirection = 1;
            let persistTimer = 0;

            const schedulePersist = () => {
                const categoryId = container.getAttribute('data-category-id');
                if (!categoryId) {
                    return;
                }

                if (persistTimer) {
                    clearTimeout(persistTimer);
                }

                persistTimer = window.setTimeout(async () => {
                    const currentSignature = orderSignature(container);
                    if (currentSignature === lastPersistedSignature) {
                        return;
                    }

                    await persistCategorySort(categoryId, container, false);
                    lastPersistedSignature = orderSignature(container);
                }, 220);
            };

            container.querySelectorAll('[data-product-card]').forEach((card) => {
                const handle = card.querySelector('[data-drag-product-handle]');
                card.draggable = false;

                handle?.addEventListener('mousedown', () => {
                    card.draggable = true;
                    card.dataset.dragEnabled = '1';
                });

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

                    draggingCard = card;
                    startSignature = orderSignature(container);
                    dragDirection = 1;
                    lastClientX = 0;
                    lastClientY = 0;
                    card.classList.add('is-dragging', 'opacity-60', 'ring-2', 'ring-indigo-300');
                });

                card.addEventListener('dragend', async () => {
                    card.classList.remove('is-dragging', 'opacity-60', 'ring-2', 'ring-indigo-300');
                    card.draggable = false;
                    delete card.dataset.dragEnabled;
                    clearDropTargetStyles(container);

                    if (rafId) {
                        cancelAnimationFrame(rafId);
                        rafId = 0;
                    }

                    if (persistTimer) {
                        clearTimeout(persistTimer);
                        persistTimer = 0;
                    }

                    const categoryId = container.getAttribute('data-category-id');
                    const changed = startSignature !== orderSignature(container);
                    if (categoryId && changed) {
                        await persistCategorySort(categoryId, container, true);
                        lastPersistedSignature = orderSignature(container);
                    } else {
                        updateSortLabels(container);
                    }
                    draggingCard = null;
                });
            });

            container.addEventListener('dragover', (event) => {
                event.preventDefault();
                if (!draggingCard) {
                    return;
                }

                if (lastClientX !== 0 || lastClientY !== 0) {
                    const dx = event.clientX - lastClientX;
                    const dy = event.clientY - lastClientY;
                    const majorDelta = Math.abs(dx) > Math.abs(dy) ? dx : dy;

                    if (Math.abs(majorDelta) > 1) {
                        dragDirection = majorDelta > 0 ? 1 : -1;
                    }
                }

                lastClientY = event.clientY;
                lastClientX = event.clientX;
                if (rafId) {
                    return;
                }

                rafId = requestAnimationFrame(() => {
                    const dropRef = getDropReference(container, draggingCard, lastClientX, lastClientY, dragDirection);
                    const afterElement = dropRef.reference;
                    const expectedNext = afterElement || null;

                    clearDropTargetStyles(container);
                    if (dropRef.target && dropRef.target !== draggingCard) {
                        dropRef.target.classList.add('drop-target', 'ring-2', 'ring-amber-300');
                    }

                    if (draggingCard.nextElementSibling === expectedNext) {
                        rafId = 0;
                        return;
                    }

                    if (!afterElement) {
                        container.appendChild(draggingCard);
                    } else {
                        container.insertBefore(draggingCard, afterElement);
                    }

                    updateSortLabels(container);
                    schedulePersist();

                    rafId = 0;
                });
            });
        });
    };

    const submitModalForm = async (event) => {
        event.preventDefault();
        modalError.classList.add('hidden');
        modalError.textContent = '';

        const formData = collectFormData();
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
                throw new Error(data.message || Object.values(data.errors || {}).flat().join('，') || '儲存失敗');
            }

            closeModal();
            showFlash(data.message || '已儲存');
            window.location.reload();
        } catch (e) {
            modalError.classList.remove('hidden');
            modalError.textContent = e.message || '儲存失敗';
        }
    };

    const deleteProduct = async (productId, productName) => {
        if (!confirm(`確定要刪除「${productName}」嗎？`)) {
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
                throw new Error(data.message || '刪除失敗');
            }

            showFlash(data.message || '商品已刪除');
            window.location.reload();
        } catch (e) {
            showFlash(e.message || '刪除失敗', 'error');
        }
    };

    document.getElementById('create-product-btn')?.addEventListener('click', () => openCreateModal());
    modalClose?.addEventListener('click', closeModal);
    modalCancel?.addEventListener('click', closeModal);
    modalForm?.addEventListener('submit', submitModalForm);

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
            deleteProduct(button.getAttribute('data-delete-product'), button.getAttribute('data-product-name') || '商品');
        });
    });

    addGroupBtn?.addEventListener('click', () => {
        optionGroups.push(createGroup());
        renderOptionEditor();
    });

    clearAllBtn?.addEventListener('click', () => {
        if (!confirm('確定要清空所有選配群組嗎？')) {
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

            if (optionGroups.length > 0 && !confirm('套用範本會覆蓋目前選配設定，是否繼續？')) {
                return;
            }

            optionGroups = JSON.parse(JSON.stringify(optionTemplates[key]));
            renderOptionEditor();
        });
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

            if (groupField === 'name' && !optionGroups[groupIndex].id) {
                optionGroups[groupIndex].id = toId(optionGroups[groupIndex].name || uid());
            }

            if (groupField === 'type' && event.target.value === 'single') {
                optionGroups[groupIndex].max_select = 1;
            }

            renderOptionEditor();
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
            if (choiceField === 'name' && !optionGroups[groupIndex].choices[choiceIndex].id) {
                optionGroups[groupIndex].choices[choiceIndex].id = toId(optionGroups[groupIndex].choices[choiceIndex].name || uid());
            }
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
