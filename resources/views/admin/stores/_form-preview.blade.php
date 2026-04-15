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
        <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('admin.store_country') }}</label>
        <select name="country_code"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
            <option value="tw" @selected(old('country_code', $store->country_code ?? 'tw') === 'tw')>{{ __('admin.country_tw') }}</option>
            <option value="vn" @selected(old('country_code', $store->country_code ?? 'tw') === 'vn')>{{ __('admin.country_vn') }}</option>
            <option value="cn" @selected(old('country_code', $store->country_code ?? 'tw') === 'cn')>{{ __('admin.country_cn') }}</option>
            <option value="us" @selected(old('country_code', $store->country_code ?? 'tw') === 'us')>{{ __('admin.country_us') }}</option>
        </select>
        @error('country_code')
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
    const wrapper = document.getElementById('banner-preview-wrapper');
    const cropCanvas = document.getElementById('banner-crop-preview');
    const cropCtx = cropCanvas?.getContext('2d');
    const helper = document.getElementById('banner-helper');
    const zoomInput = document.getElementById('banner-zoom');
    const resetButton = document.getElementById('banner-reset');
    const removeButton = document.getElementById('banner-remove');
    const form = input?.closest('form');
    const maxUploadImageBytes = 2 * 1024 * 1024;
    const i18n = {
        imageOnly: @json(__('admin.error_select_image')),
        imageTooLarge: @json(__('admin.error_image_too_large_2')),
    };

    if (!dropzone || !input || !wrapper || !cropCanvas || !cropCtx || !zoomInput || !removeButton || !form) {
        return;
    }

    const state = {
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
        isSubmittingPrepared: false,
    };

    const resetDropzoneState = () => {
        dropzone.classList.remove('border-brand-primary', 'bg-brand-soft/30');
    };

    const revokeObjectUrl = () => {
        if (!state.sourceObjectUrl) {
            return;
        }

        URL.revokeObjectURL(state.sourceObjectUrl);
        state.sourceObjectUrl = null;
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

    const clampOffsets = () => {
        if (!state.sourceImage) {
            return;
        }

        const baseScale = Math.max(
            cropCanvas.width / state.sourceImage.naturalWidth,
            cropCanvas.height / state.sourceImage.naturalHeight,
        );
        const drawWidth = state.sourceImage.naturalWidth * baseScale * state.zoom;
        const drawHeight = state.sourceImage.naturalHeight * baseScale * state.zoom;
        const minX = cropCanvas.width - drawWidth;
        const minY = cropCanvas.height - drawHeight;

        state.offsetX = Math.min(0, Math.max(minX, state.offsetX));
        state.offsetY = Math.min(0, Math.max(minY, state.offsetY));
    };

    const centerImage = () => {
        if (!state.sourceImage) {
            return;
        }

        const baseScale = Math.max(
            cropCanvas.width / state.sourceImage.naturalWidth,
            cropCanvas.height / state.sourceImage.naturalHeight,
        );
        const drawWidth = state.sourceImage.naturalWidth * baseScale * state.zoom;
        const drawHeight = state.sourceImage.naturalHeight * baseScale * state.zoom;
        state.offsetX = (cropCanvas.width - drawWidth) / 2;
        state.offsetY = (cropCanvas.height - drawHeight) / 2;
        clampOffsets();
    };

    const renderPreview = () => {
        cropCtx.clearRect(0, 0, cropCanvas.width, cropCanvas.height);
        cropCtx.fillStyle = '#f8fafc';
        cropCtx.fillRect(0, 0, cropCanvas.width, cropCanvas.height);

        if (!state.sourceImage) {
            cropCtx.strokeStyle = '#cbd5e1';
            cropCtx.lineWidth = 2;
            cropCtx.strokeRect(8, 8, cropCanvas.width - 16, cropCanvas.height - 16);
            cropCtx.fillStyle = '#64748b';
            cropCtx.font = '24px sans-serif';
            cropCtx.textAlign = 'center';
            cropCtx.fillText('尚未選擇橫幅', cropCanvas.width / 2, cropCanvas.height / 2 + 8);
            return;
        }

        const baseScale = Math.max(
            cropCanvas.width / state.sourceImage.naturalWidth,
            cropCanvas.height / state.sourceImage.naturalHeight,
        );
        const drawWidth = state.sourceImage.naturalWidth * baseScale * state.zoom;
        const drawHeight = state.sourceImage.naturalHeight * baseScale * state.zoom;
        clampOffsets();
        cropCtx.drawImage(state.sourceImage, state.offsetX, state.offsetY, drawWidth, drawHeight);
    };

    const ensureBlobWithinLimit = async (canvas, maxBytes = maxUploadImageBytes) => {
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

        if (blob.size > maxBytes) {
            throw new Error(i18n.imageTooLarge);
        }

        return blob;
    };

    const clearPreview = () => {
        revokeObjectUrl();
        input.value = '';
        state.sourceImage = null;
        state.sourceFileName = 'banner.jpg';
        state.hasNewUpload = false;
        state.dirty = false;
        state.zoom = 1;
        state.offsetX = 0;
        state.offsetY = 0;
        state.dragging = false;
        zoomInput.value = '1';
        wrapper.classList.add('hidden');
        if (helper) {
            helper.textContent = '尚未選擇橫幅';
        }
        renderPreview();
    };

    const setImageFromUrl = (url) => {
        if (!url) {
            clearPreview();
            return;
        }

        revokeObjectUrl();
        const image = new Image();
        image.onload = () => {
            state.sourceImage = image;
            state.sourceFileName = 'banner.jpg';
            state.hasNewUpload = false;
            state.dirty = false;
            state.zoom = 1;
            zoomInput.value = '1';
            centerImage();
            wrapper.classList.remove('hidden');
            if (helper) {
                helper.textContent = '目前橫幅（可拖曳調整裁切）';
            }
            renderPreview();
        };
        image.onerror = clearPreview;
        image.src = url;
    };

    const setImageFromFile = (file) => {
        if (!file) {
            return;
        }

        if (!file.type.startsWith('image/')) {
            alert(i18n.imageOnly);
            return;
        }

        revokeObjectUrl();
        const url = URL.createObjectURL(file);
        state.sourceObjectUrl = url;

        const image = new Image();
        image.onload = () => {
            state.sourceImage = image;
            state.sourceFileName = file.name || 'banner.jpg';
            state.hasNewUpload = true;
            state.dirty = false;
            state.zoom = 1;
            zoomInput.value = '1';
            centerImage();
            wrapper.classList.remove('hidden');
            if (helper) {
                helper.textContent = `已選擇：${file.name}`;
            }
            renderPreview();
        };
        image.onerror = () => {
            alert('圖片讀取失敗，請重新選擇。');
        };
        image.src = url;
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
        setImageFromFile(event.dataTransfer.files[0]);
    });

    input.addEventListener('change', (event) => {
        setImageFromFile(event.target.files[0]);
    });

    zoomInput.addEventListener('input', () => {
        if (!state.sourceImage) {
            return;
        }

        state.zoom = Number(zoomInput.value || '1');
        state.dirty = true;
        centerImage();
        renderPreview();
    });

    cropCanvas.addEventListener('mousedown', (event) => {
        if (!state.sourceImage) {
            return;
        }

        state.dragging = true;
        state.lastX = event.clientX;
        state.lastY = event.clientY;
    });
    cropCanvas.addEventListener('mousemove', (event) => {
        if (!state.dragging || !state.sourceImage) {
            return;
        }

        state.offsetX += event.clientX - state.lastX;
        state.offsetY += event.clientY - state.lastY;
        state.lastX = event.clientX;
        state.lastY = event.clientY;
        state.dirty = true;
        clampOffsets();
        renderPreview();
    });
    cropCanvas.addEventListener('mouseup', () => {
        state.dragging = false;
    });
    cropCanvas.addEventListener('mouseleave', () => {
        state.dragging = false;
    });
    cropCanvas.addEventListener('touchstart', (event) => {
        if (!state.sourceImage || event.touches.length === 0) {
            return;
        }

        const touch = event.touches[0];
        state.dragging = true;
        state.lastX = touch.clientX;
        state.lastY = touch.clientY;
    }, { passive: true });
    cropCanvas.addEventListener('touchmove', (event) => {
        if (!state.dragging || !state.sourceImage || event.touches.length === 0) {
            return;
        }

        const touch = event.touches[0];
        state.offsetX += touch.clientX - state.lastX;
        state.offsetY += touch.clientY - state.lastY;
        state.lastX = touch.clientX;
        state.lastY = touch.clientY;
        state.dirty = true;
        clampOffsets();
        renderPreview();
    }, { passive: true });
    cropCanvas.addEventListener('touchend', () => {
        state.dragging = false;
    }, { passive: true });
    cropCanvas.addEventListener('touchcancel', () => {
        state.dragging = false;
    }, { passive: true });

    resetButton?.addEventListener('click', () => {
        if (!state.sourceImage) {
            return;
        }

        state.zoom = 1;
        state.dirty = true;
        zoomInput.value = '1';
        centerImage();
        renderPreview();
    });

    removeButton.addEventListener('click', () => {
        clearPreview();
    });

    form.addEventListener('submit', async (event) => {
        if (state.isSubmittingPrepared) {
            return;
        }

        if (!state.sourceImage || (!state.hasNewUpload && !state.dirty)) {
            return;
        }

        event.preventDefault();
        try {
            const blob = await ensureBlobWithinLimit(cropCanvas, maxUploadImageBytes);
            const filename = (state.sourceFileName || 'banner.jpg').replace(/\.[^.]+$/, '.jpg');
            const transfer = new DataTransfer();
            transfer.items.add(new File([blob], filename, { type: 'image/jpeg' }));
            input.files = transfer.files;
            state.isSubmittingPrepared = true;
            form.submit();
        } catch (error) {
            alert(error?.message || i18n.imageTooLarge);
        }
    });

    const existingUrl = wrapper.getAttribute('data-existing-banner-url');
    if (existingUrl) {
        setImageFromUrl(existingUrl);
    } else {
        renderPreview();
    }
})();
</script>
