@csrf

<div class="grid gap-6 lg:grid-cols-2">
    <div class="lg:col-span-2">
        <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('admin.store_name') }}</label>
        <input type="text" name="name" value="{{ old('name', $store->name) }}"
               class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
        @error('name')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">Slug</label>
        <input type="text" name="slug" value="{{ old('slug', $store->slug) }}"
               placeholder="{{ __('admin.slug_preview_placeholder') }}"
               class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
        @error('slug')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('admin.phone') }}</label>
        <input type="text" name="phone" value="{{ old('phone', $store->phone) }}"
               placeholder="0922-333-444"
               pattern="09[0-9]{2}-[0-9]{3}-[0-9]{3}"
               class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
        <p class="mt-2 text-xs text-slate-500">{{ __('admin.phone_format_hint') }}</p>
        @error('phone')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="lg:col-span-2">
        <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('admin.address') }}</label>
        <input type="text" name="address" value="{{ old('address', $store->address) }}"
               class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
        @error('address')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('admin.currency') }}</label>
        <select name="currency"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
            <option value="twd" @selected(old('currency', $store->currency ?? 'twd') === 'twd')>{{ __('admin.currency_twd') }}</option>
            <option value="vnd" @selected(old('currency', $store->currency ?? 'twd') === 'vnd')>{{ __('admin.currency_vnd') }}</option>
            <option value="cny" @selected(old('currency', $store->currency ?? 'twd') === 'cny')>{{ __('admin.currency_cny') }}</option>
            <option value="usd" @selected(old('currency', $store->currency ?? 'twd') === 'usd')>{{ __('admin.currency_usd') }}</option>
        </select>
        @error('currency')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('admin.opening_time') }}</label>
        <input type="time" name="opening_time" value="{{ old('opening_time', $store->opening_time ? substr($store->opening_time, 0, 5) : '') }}"
               class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
        <p class="mt-2 text-xs text-slate-500">{{ __('admin.opening_time_hint') }}</p>
        @error('opening_time')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('admin.closing_time') }}</label>
        <input type="time" name="closing_time" value="{{ old('closing_time', $store->closing_time ? substr($store->closing_time, 0, 5) : '') }}"
               class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
        <p class="mt-2 text-xs text-slate-500">{{ __('admin.closing_time_hint') }}</p>
        @error('closing_time')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="lg:col-span-2">
        <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('admin.store_description') }}</label>
        <textarea name="description" rows="5"
                  class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">{{ old('description', $store->description) }}</textarea>
        @error('description')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="lg:col-span-2">
        <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('admin.store_cover') }}</label>

        <div id="banner-dropzone"
             class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 p-6 text-center transition hover:border-brand-primary hover:bg-brand-soft/30">
            <input type="file"
                   id="banner-input"
                   name="banner_image"
                   accept="image/*"
                   class="hidden">

                 <p class="text-sm text-slate-600">{{ __('admin.cover_dropzone') }}</p>
                 <p class="mt-1 text-xs text-slate-400">{{ __('admin.banner_file_hint') }}</p>
        </div>

        <div id="banner-preview-wrapper" class="mt-4 {{ $store->banner_image ? '' : 'hidden' }}" data-existing-banner-url="{{ $store->banner_image ? asset('storage/' . $store->banner_image) : '' }}">
            <canvas id="banner-crop-preview" width="1200" height="400" class="w-full rounded-2xl border border-slate-300 bg-white"></canvas>
            <p id="banner-helper" class="mt-2 text-xs text-slate-500">尚未選擇橫幅</p>
            <div class="mt-3">
                <label for="banner-zoom" class="mb-1 block text-xs font-semibold text-slate-600">縮放</label>
                <input id="banner-zoom" type="range" min="1" max="3" step="0.05" value="1" class="w-full">
            </div>
            <div class="mt-2 flex flex-wrap gap-2">
                <button type="button" id="banner-reset" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">重設位置</button>
                <button type="button" id="banner-remove" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100">{{ __('admin.remove') }}</button>
            </div>
            <p class="mt-2 text-[11px] text-slate-500">在預覽區拖曳可調整橫幅裁切範圍，儲存時會套用。</p>
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
            <span class="text-sm font-semibold text-slate-700">{{ __('admin.enable_store') }}</span>
        </label>
    </div>
</div>

<div class="mt-8 flex gap-3">
    <button type="submit"
            class="inline-flex items-center justify-center rounded-2xl bg-brand-primary px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-accent hover:text-brand-dark">
        {{ __('admin.save') }}
    </button>

    <a href="{{ route('admin.stores.index') }}"
       class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
        {{ __('admin.back_to_list') }}
    </a>
</div>

<script>
(() => {
    const dropzone = document.getElementById('banner-dropzone');
    const input = document.getElementById('banner-input');
    const preview = document.getElementById('banner-preview');
    const wrapper = document.getElementById('banner-preview-wrapper');
    const removeButton = document.getElementById('banner-remove');
    const maxUploadImageBytes = 2 * 1024 * 1024;
    const i18n = {
        imageOnly: @json(__('admin.error_select_image')),
        imageTooLarge: @json(__('admin.error_image_too_large_2')),
    };

    if (!dropzone || !input || !preview || !wrapper || !removeButton) {
        return;
    }

    const resetDropzoneState = () => {
        dropzone.classList.remove('border-brand-primary', 'bg-brand-soft/30');
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

    const canvasToBlob = (canvas, mimeType, quality) => new Promise((resolve, reject) => {
        canvas.toBlob((blob) => {
            if (blob) {
                resolve(blob);
                return;
            }

            reject(new Error('圖片轉換失敗'));
        }, mimeType, quality);
    });

    const compressImageFileToLimit = async (file, maxBytes = maxUploadImageBytes) => {
        if (file.size <= maxBytes) {
            return file;
        }

        const image = await loadImageFromFile(file);
        let targetWidth = image.naturalWidth;
        let targetHeight = image.naturalHeight;
        let quality = 0.9;
        let blob = null;

        while ((targetWidth >= 360 && targetHeight >= 360) && (!blob || blob.size > maxBytes)) {
            const canvas = document.createElement('canvas');
            canvas.width = targetWidth;
            canvas.height = targetHeight;
            const ctx = canvas.getContext('2d');

            if (!ctx) {
                break;
            }

            ctx.drawImage(image, 0, 0, targetWidth, targetHeight);
            blob = await canvasToBlob(canvas, 'image/jpeg', quality);

            if (blob.size <= maxBytes) {
                break;
            }

            if (quality > 0.45) {
                quality = Math.max(0.45, quality - 0.08);
            } else {
                targetWidth = Math.max(360, Math.floor(targetWidth * 0.85));
                targetHeight = Math.max(360, Math.floor(targetHeight * 0.85));
            }
        }

        if (!blob || blob.size > maxBytes) {
            throw new Error(i18n.imageTooLarge);
        }

        const filename = (file.name || 'banner.jpg').replace(/\.[^.]+$/, '.jpg');
        return new File([blob], filename, { type: 'image/jpeg' });
    };

    const updatePreview = async (file) => {
        if (!file) {
            return;
        }

        if (!file.type.startsWith('image/')) {
            alert(i18n.imageOnly);
            return;
        }

        let uploadFile = file;
        try {
            uploadFile = await compressImageFileToLimit(file, maxUploadImageBytes);
        } catch (error) {
            alert(error?.message || i18n.imageTooLarge);
            return;
        }

        const url = URL.createObjectURL(uploadFile);
        preview.src = url;
        wrapper.classList.remove('hidden');
        preview.onload = () => URL.revokeObjectURL(url);

        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(uploadFile);
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
        void updatePreview(event.dataTransfer.files[0]);
    });

    input.addEventListener('change', (event) => {
        void updatePreview(event.target.files[0]);
    });

    removeButton.addEventListener('click', () => {
        input.value = '';
        preview.src = '';
        wrapper.classList.add('hidden');
    });
})();
</script>
