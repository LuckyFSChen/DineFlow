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

        <div id="banner-preview-wrapper" class="mt-4 {{ $store->banner_image ? '' : 'hidden' }}">
            <div class="relative">
                <img id="banner-preview"
                     src="{{ $store->banner_image ? asset('storage/' . $store->banner_image) : '' }}"
                     alt="{{ $store->name }}"
                     class="h-40 w-full rounded-2xl object-cover transition">

                <button type="button"
                        id="banner-remove"
                        class="absolute right-2 top-2 rounded-full bg-black/60 px-3 py-1 text-xs text-white hover:bg-black">
                    {{ __('admin.remove') }}
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

    const updatePreview = (file) => {
        if (!file) {
            return;
        }

        if (!file.type.startsWith('image/')) {
            alert(i18n.imageOnly);
            return;
        }

        if (file.size > 2 * 1024 * 1024) {
            alert(i18n.imageTooLarge);
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
