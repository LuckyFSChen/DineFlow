@csrf

<div class="grid gap-6 lg:grid-cols-2">
    <div class="lg:col-span-2">
        <label class="mb-2 block text-sm font-semibold text-slate-700">店家名稱</label>
        <input type="text" name="name" value="{{ old('name', $store->name) }}"
               class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
        @error('name')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">Slug</label>
        <input type="text" name="slug" value="{{ old('slug', $store->slug) }}"
               placeholder="例如 lucky-cafe"
               class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
        @error('slug')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">電話</label>
        <input type="text" name="phone" value="{{ old('phone', $store->phone) }}"
               placeholder="0922-333-444"
               pattern="09[0-9]{2}-[0-9]{3}-[0-9]{3}"
               class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
        <p class="mt-2 text-xs text-slate-500">格式：0922-333-444</p>
        @error('phone')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="lg:col-span-2">
        <label class="mb-2 block text-sm font-semibold text-slate-700">地址</label>
        <input type="text" name="address" value="{{ old('address', $store->address) }}"
               class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
        @error('address')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">開始營業</label>
        <input type="time" name="opening_time" value="{{ old('opening_time', $store->opening_time ? substr($store->opening_time, 0, 5) : '') }}"
               class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
        <p class="mt-2 text-xs text-slate-500">未設定則不限制時間，若有設定請同時填寫結束營業時間。</p>
        @error('opening_time')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">結束營業</label>
        <input type="time" name="closing_time" value="{{ old('closing_time', $store->closing_time ? substr($store->closing_time, 0, 5) : '') }}"
               class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
        <p class="mt-2 text-xs text-slate-500">若結束時間早於開始時間，會視為跨夜營業。</p>
        @error('closing_time')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="lg:col-span-2">
        <label class="mb-2 block text-sm font-semibold text-slate-700">店家描述</label>
        <textarea name="description" rows="5"
                  class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">{{ old('description', $store->description) }}</textarea>
        @error('description')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="lg:col-span-2">
        <label class="mb-2 block text-sm font-semibold text-slate-700">店家封面</label>

        <div id="banner-dropzone"
             class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 p-6 text-center transition hover:border-brand-primary hover:bg-brand-soft/30">
            <input type="file"
                   id="banner-input"
                   name="banner_image"
                   accept="image/*"
                   class="hidden">

            <p class="text-sm text-slate-600">選擇封面圖片，或拖曳圖片到這裡</p>
            <p class="mt-1 text-xs text-slate-400">支援 JPG / PNG / WEBP，大小上限 2MB</p>
        </div>

        <div id="banner-preview-wrapper" class="mt-4 {{ $store->banner_image ? '' : 'hidden' }}">
            <div class="relative">
                <img id="banner-preview"
                     src="{{ $store->banner_image ? asset('storage/' . $store->banner_image) : '' }}"
                     alt="{{ $store->name }}"
                     class="h-40 w-full rounded-2xl object-cover transition">

                <button type="button"
                        id="banner-remove"
                        class="absolute right-2 top-2 rounded-full bg-black/60 px-3 py-1 text-xs text-white hover:bg-black">
                    移除
                </button>
            </div>
        </div>

        @error('banner_image')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="lg:col-span-2">
        <label class="inline-flex items-center gap-3">
            <input type="checkbox" name="is_active" value="1"
                   {{ old('is_active', $store->is_active ?? true) ? 'checked' : '' }}
                   class="h-5 w-5 rounded border-slate-300 text-brand-primary focus:ring-brand-highlight">
            <span class="text-sm font-semibold text-slate-700">啟用店家</span>
        </label>
    </div>
</div>

<div class="mt-8 flex gap-3">
    <button type="submit"
            class="inline-flex items-center justify-center rounded-2xl bg-brand-primary px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-accent hover:text-brand-dark">
        儲存
    </button>

    <a href="{{ route('admin.stores.index') }}"
       class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
        返回列表
    </a>
</div>

<script>
(() => {
    const dropzone = document.getElementById('banner-dropzone');
    const input = document.getElementById('banner-input');
    const preview = document.getElementById('banner-preview');
    const wrapper = document.getElementById('banner-preview-wrapper');
    const removeButton = document.getElementById('banner-remove');

    if (!dropzone || !input || !preview || !wrapper || !removeButton) {
        return;
    }

    const resetDropzoneState = () => {
        dropzone.classList.remove('border-brand-primary', 'bg-brand-soft/30');
    };

    const updatePreview = (file) => {
        if (!file) {
            return;
        }

        if (!file.type.startsWith('image/')) {
            alert('請選擇圖片檔案。');
            return;
        }

        if (file.size > 2 * 1024 * 1024) {
            alert('圖片大小不可超過 2MB。');
            return;
        }

        const url = URL.createObjectURL(file);
        preview.src = url;
        wrapper.classList.remove('hidden');
        preview.onload = () => URL.revokeObjectURL(url);

        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        input.files = dataTransfer.files;
    };

    dropzone.addEventListener('click', () => input.click());

    dropzone.addEventListener('dragover', (event) => {
        event.preventDefault();
        dropzone.classList.add('border-brand-primary', 'bg-brand-soft/30');
    });

    dropzone.addEventListener('dragleave', resetDropzoneState);

    dropzone.addEventListener('drop', (event) => {
        event.preventDefault();
        resetDropzoneState();
        updatePreview(event.dataTransfer.files[0]);
    });

    input.addEventListener('change', (event) => {
        updatePreview(event.target.files[0]);
    });

    removeButton.addEventListener('click', () => {
        input.value = '';
        preview.src = '';
        wrapper.classList.add('hidden');
    });
})();
</script>
