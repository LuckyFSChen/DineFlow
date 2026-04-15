@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-7xl px-6 py-10 lg:px-8">
        <x-backend-header
            :title="__('admin.tables_qr_title')"
            :subtitle="__('admin.store_label') . $store->name . '（' . $store->slug . '）'"
        >
            <x-slot name="actions">
                <a href="{{ route('admin.stores.index') }}"
                   class="inline-flex items-center justify-center rounded-2xl border border-slate-300/70 bg-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/20">
                    {{ __('admin.back_to_stores') }}
                </a>
                @if($store->is_active)
                    <a href="{{ route('admin.stores.kitchen', $store) }}"
                       class="inline-flex items-center justify-center rounded-2xl border border-orange-300/70 bg-orange-500/20 px-5 py-3 text-sm font-semibold text-orange-100 transition hover:bg-orange-500/30">
                        🍳 {{ __('admin.kitchen') }}
                    </a>
                @endif
            </x-slot>
        </x-backend-header>

        @if(session('success'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="mb-6 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">{{ __('admin.takeout_qr_title') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ __('admin.takeout_qr_desc') }}</p>

                    <form method="POST" action="{{ route('admin.stores.takeout-qr.update', $store) }}" class="mt-3 inline-flex items-center gap-2">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="takeout_qr_enabled" value="{{ $store->takeout_qr_enabled ? '0' : '1' }}">
                        <button type="submit" class="inline-flex items-center rounded-xl {{ $store->takeout_qr_enabled ? 'border border-rose-300 bg-rose-50 text-rose-700 hover:bg-rose-100' : 'border border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }} px-4 py-2 text-sm font-semibold transition">
                            {{ $store->takeout_qr_enabled ? __('admin.takeout_qr_disable') : __('admin.takeout_qr_enable') }}
                        </button>
                        <span class="text-xs font-semibold {{ $store->takeout_qr_enabled ? 'text-emerald-700' : 'text-slate-500' }}">
                            {{ $store->takeout_qr_enabled ? __('admin.takeout_qr_open') : __('admin.takeout_qr_closed') }}
                        </span>
                    </form>
                </div>

                @if($store->takeout_qr_enabled)
                    <div class="w-full max-w-[320px] rounded-2xl border border-slate-200 bg-slate-50 p-3">
                        <label class="mb-2 inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600">
                            <input type="checkbox" id="takeout-select-checkbox" class="h-3.5 w-3.5 rounded border-slate-300 text-indigo-600">
                            {{ __('admin.select_for_print') }}
                        </label>
                        <div class="mx-auto flex h-[220px] w-[220px] items-center justify-center overflow-hidden rounded-xl bg-white ring-1 ring-slate-200 [&_svg]:h-full [&_svg]:w-full">
                            {!! $takeoutQrSvg !!}
                        </div>
                        <p class="mt-2 text-center text-sm font-semibold text-slate-700">{{ __('admin.takeout_exclusive') }}</p>
                        <label class="mt-3 mb-1 block text-xs font-semibold text-slate-500">{{ __('admin.takeout_qr_link') }}</label>
                        <input type="text" value="{{ $takeoutMenuUrl }}" readonly class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-700" onclick="this.select()">
                    </div>
                @else
                    <div class="w-full max-w-[320px] rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                        {{ __('admin.takeout_qr_not_ready') }}
                    </div>
                @endif
            </div>
        </div>

        <div class="mb-6 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">{{ __('admin.add_table') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('admin.add_table_desc') }}</p>

            <form method="POST" action="{{ route('admin.stores.tables.store', $store) }}" class="mt-4 flex flex-col gap-3 sm:flex-row">
                @csrf
                <input type="text"
                       name="table_no"
                       value="{{ old('table_no') }}"
                       placeholder="{{ __('admin.table_no_placeholder') }}"
                       class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                       required>
                <button type="submit"
                        class="inline-flex shrink-0 items-center justify-center whitespace-nowrap rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500">
                    {{ __('admin.add_table_btn') }}
                </button>
            </form>
        </div>

        <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" id="select-all-tables" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">{{ __('admin.select_all') }}</button>
                    <button type="button" id="clear-all-tables" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">{{ __('admin.clear_all') }}</button>
                    <span id="selected-tables-count" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ __('admin.selected_count', ['count' => 0]) }}</span>
                </div>

                <form id="print-selected-form" method="GET" action="{{ route('admin.stores.tables.print', $store) }}" target="_blank" class="inline-flex">
                    <div id="print-selected-inputs"></div>
                    <button id="print-selected-submit" type="submit" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:bg-indigo-300" disabled>
                        {{ __('admin.print_selected') }}
                    </button>
                </form>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse($tables as $table)
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div>
                            <label class="mb-2 inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600">
                                <input type="checkbox" class="table-select-checkbox h-3.5 w-3.5 rounded border-slate-300 text-indigo-600" value="{{ $table->id }}">
                                {{ __('admin.select_for_print') }}
                            </label>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.table_no_label') }}</p>
                            <h3 class="text-2xl font-bold text-slate-900">{{ $table->table_no }}</h3>
                        </div>
                        @if($table->status === 'available')
                            <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">{{ __('admin.table_enabled') }}</span>
                        @else
                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">{{ __('admin.table_disabled') }}</span>
                        @endif
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                        <div class="mx-auto flex h-[180px] w-[180px] items-center justify-center overflow-hidden rounded-xl bg-white ring-1 ring-slate-200 [&_svg]:h-full [&_svg]:w-full">
                            {!! $table->qr_svg !!}
                        </div>
                        <p class="mt-2 text-center text-sm font-semibold text-slate-700">{{ $table->table_no }}</p>
                    </div>

                    <div class="mt-3">
                        <label class="mb-1 block text-xs font-semibold text-slate-500">{{ __('admin.table_link_label') }}</label>
                        <input type="text" value="{{ $table->menu_url }}" readonly class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-xs text-slate-700" onclick="this.select()">
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <form method="POST" action="{{ route('admin.stores.tables.status', [$store, $table]) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="{{ $table->status === 'available' ? 'inactive' : 'available' }}">
                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                {{ $table->status === 'available' ? __('admin.disable_table') : __('admin.enable_table') }}
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.stores.tables.regenerate-qr', [$store, $table]) }}" onsubmit="return confirm('{{ __('admin.regenerate_qr_confirm') }}')">
                            @csrf
                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500">
                                {{ __('admin.regenerate_qr') }}
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white/70 p-10 text-center text-sm text-slate-500 md:col-span-2 xl:col-span-3">
                    {{ __('admin.no_tables_yet') }}
                </div>
            @endforelse
        </div>
    </div>
</div>

<script>
(() => {
    const checkboxes = Array.from(document.querySelectorAll('.table-select-checkbox'));
    const takeoutCheckbox = document.getElementById('takeout-select-checkbox');
    const selectAllBtn = document.getElementById('select-all-tables');
    const clearAllBtn = document.getElementById('clear-all-tables');
    const selectedCountEl = document.getElementById('selected-tables-count');
    const printSubmitBtn = document.getElementById('print-selected-submit');
    const printInputs = document.getElementById('print-selected-inputs');
    const selectedCountTemplate = @json(__('admin.selected_count', ['count' => '__count__']));

    if (!selectedCountEl || !printSubmitBtn || !printInputs) {
        return;
    }

    const syncSelected = () => {
        const selectedTableIds = checkboxes.filter((cb) => cb.checked).map((cb) => cb.value);
        const includeTakeout = !!takeoutCheckbox?.checked;
        const selectedCount = selectedTableIds.length + (includeTakeout ? 1 : 0);

        selectedCountEl.textContent = selectedCountTemplate.replace('__count__', selectedCount);
        printSubmitBtn.disabled = selectedCount === 0;

        const tableInputs = selectedTableIds
            .map((id) => `<input type="hidden" name="table_ids[]" value="${id}">`)
            .join('');

        const takeoutInput = includeTakeout
            ? '<input type="hidden" name="include_takeout" value="1">'
            : '';

        printInputs.innerHTML = tableInputs + takeoutInput;
    };

    checkboxes.forEach((cb) => {
        cb.addEventListener('change', syncSelected);
    });

    takeoutCheckbox?.addEventListener('change', syncSelected);

    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', () => {
            checkboxes.forEach((cb) => {
                cb.checked = true;
            });
            if (takeoutCheckbox) {
                takeoutCheckbox.checked = true;
            }
            syncSelected();
        });
    }

    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', () => {
            checkboxes.forEach((cb) => {
                cb.checked = false;
            });
            if (takeoutCheckbox) {
                takeoutCheckbox.checked = false;
            }
            syncSelected();
        });
    }

    syncSelected();
})();
</script>
@endsection
