@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-7xl px-6 py-10 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-slate-900">店家管理</h1>
                <p class="mt-2 text-slate-600">管理店家基本資料、上架狀態與入口資訊。</p>
            </div>

            @if(isset($canCreateStore) && ! $canCreateStore)
                <button
                    type="button"
                    id="open-store-modal-btn"
                    disabled
                    class="inline-flex cursor-not-allowed items-center justify-center rounded-2xl bg-slate-300 px-5 py-3 text-sm font-semibold text-slate-500">
                    新增店家（已達上限）
                </button>
            @else
                <button
                    type="button"
                    id="open-store-modal-btn"
                    class="inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500">
                    新增店家
                </button>
            @endif
        </div>

        @if(isset($usedStores))
            <div class="mb-6 rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-800">
                方案店家額度：
                已使用 {{ $usedStores }} 間
                @if($maxStores === null)
                    / 不限
                @else
                    / 上限 {{ $maxStores }} 間（剩餘 {{ $remainingStores }} 間）
                @endif
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

        <div class="mb-6 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('admin.stores.index') }}" class="flex flex-col gap-3 md:flex-row">
                <input
                    type="text"
                    name="keyword"
                    value="{{ $keyword ?? '' }}"
                    placeholder="搜尋店家名稱 / slug / 地址 / 電話"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                >
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                    搜尋
                </button>
            </form>
        </div>

        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-4 text-left font-semibold text-slate-700">店家名稱</th>
                            <th class="px-6 py-4 text-left font-semibold text-slate-700">Slug</th>
                            <th class="px-6 py-4 text-left font-semibold text-slate-700">電話</th>
                            <th class="px-6 py-4 text-left font-semibold text-slate-700">地址</th>
                            <th class="px-6 py-4 text-left font-semibold text-slate-700">狀態</th>
                            <th class="px-6 py-4 text-right font-semibold text-slate-700">操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($stores as $store)
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-start gap-3">
                                        @if($store->banner_image)
                                            <img src="{{ asset('storage/' . ltrim($store->banner_image, '/')) }}" alt="{{ $store->name }} 橫幅" class="h-12 w-20 rounded-lg object-cover ring-1 ring-slate-200">
                                        @else
                                            <div class="flex h-12 w-20 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-100 text-[11px] text-slate-500">
                                                無橫幅
                                            </div>
                                        @endif

                                        <div>
                                            <div class="font-semibold text-slate-900">{{ $store->name }}</div>
                                            <div class="mt-1 text-xs text-slate-500">
                                                {{ \Illuminate\Support\Str::limit($store->description, 50) ?: '尚未填寫描述' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-600">{{ $store->slug }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $store->phone ?: '-' }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $store->address ?: '-' }}</td>
                                <td class="px-6 py-4">
                                    @if($store->is_active)
                                        <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                            營業中
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">
                                            未開放
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.stores.products.index', $store) }}"
                                           class="inline-flex items-center rounded-xl border border-indigo-300 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100">
                                            商品
                                        </a>

                                                     <button
                                                         type="button"
                                                         data-edit-store="{{ $store->slug }}"
                                                         class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                            編輯
                                                     </button>

                                        <form method="POST" action="{{ route('admin.stores.destroy', $store) }}" onsubmit="return confirm('確定要刪除此店家嗎？')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="inline-flex items-center rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-500">
                                                刪除
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                    目前沒有店家資料
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
                <h3 id="store-modal-title" class="text-lg font-bold text-slate-900">新增店家</h3>
                <p class="text-xs text-slate-500">彈窗即時編輯，儲存後自動更新</p>
            </div>
            <button type="button" id="store-modal-close" class="rounded-full p-2 text-slate-500 hover:bg-slate-100">✕</button>
        </div>

        <form id="store-modal-form" class="max-h-[78vh] overflow-y-auto px-6 py-5" enctype="multipart/form-data">
            <input type="hidden" name="_method" id="store-modal-method" value="POST">

            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-600">店家名稱</label>
                    <input type="text" name="name" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" required>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Slug</label>
                    <input type="text" name="slug" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="可留空，自動產生">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">電話</label>
                    <input type="text" name="phone" id="store-modal-phone" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="0922-333-444" maxlength="12">
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-600">地址</label>
                    <input type="text" name="address" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-600">店家描述</label>
                    <textarea name="description" rows="4" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"></textarea>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">開始營業</label>
                    <input type="time" name="opening_time" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">結束營業</label>
                    <input type="time" name="closing_time" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </div>

                <div class="md:col-span-2 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                    <label class="mb-2 block text-xs font-semibold text-slate-600">橫幅圖片</label>
                    <div id="store-banner-dropzone" class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 bg-white p-5 text-center transition hover:border-indigo-400 hover:bg-indigo-50">
                        <input type="file" id="store-banner-input" name="banner_image" accept="image/*" class="hidden">
                        <p class="text-sm text-slate-600">拖曳圖片到這裡，或 <span class="font-semibold text-indigo-600">點擊上傳</span></p>
                        <p class="mt-1 text-xs text-slate-400">JPG / PNG / WEBP，最大 2MB</p>
                    </div>

                    <div id="store-banner-preview-wrapper" class="mt-3 hidden">
                        <div class="relative">
                            <img id="store-banner-preview" src="" class="h-40 w-full rounded-xl object-cover">
                            <button type="button" id="store-banner-remove" class="absolute right-2 top-2 rounded-full bg-black/60 px-3 py-1 text-xs text-white hover:bg-black">移除</button>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="is_active" id="store-modal-is-active" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600">
                        啟用店家
                    </label>
                </div>
            </div>

            <div id="store-modal-error" class="mt-4 hidden rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700"></div>

            <div class="mt-6 flex gap-2">
                <button type="submit" id="store-modal-submit" class="inline-flex flex-1 items-center justify-center rounded-2xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500">儲存店家</button>
                <button type="button" id="store-modal-cancel" class="inline-flex flex-1 items-center justify-center rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">取消</button>
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
            showFlash('請上傳圖片檔。', 'error');
            return;
        }

        if (file.size > 2 * 1024 * 1024) {
            showFlash('圖片不能超過 2MB。', 'error');
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
        modalTitle.textContent = '新增店家';
        modalSubmit.textContent = '建立店家';
        setFormValues(null);
        openModal();
    };

    const openEditModal = async (storeId) => {
        currentMode = 'edit';
        currentStoreId = storeId;
        modalTitle.textContent = '編輯店家';
        modalSubmit.textContent = '更新店家';

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
                throw new Error(data.message || '讀取店家資料失敗');
            }

            setFormValues(data.store);
            openModal();
        } catch (e) {
            showFlash(e.message || '讀取店家資料失敗', 'error');
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
                throw new Error(data.message || validationMessage || '儲存失敗');
            }

            closeModal();
            showFlash(data.message || '店家已儲存');
            window.location.reload();
        } catch (e) {
            modalError.classList.remove('hidden');
            modalError.textContent = e.message || '儲存失敗';
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