@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-7xl px-6 py-10 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-slate-900">桌位與 QR Code 管理</h1>
                <p class="mt-2 text-slate-600">店家：{{ $store->name }}（{{ $store->slug }}）</p>
            </div>
            <a href="{{ route('admin.stores.index') }}"
               class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                返回店家管理
            </a>
        </div>

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
                    <h2 class="text-lg font-semibold text-slate-900">外帶 QR Code（獨立）</h2>
                    <p class="mt-1 text-sm text-slate-500">外帶不與內用桌次混在一起，可由店家自行決定是否開放。</p>

                    <form method="POST" action="{{ route('admin.stores.takeout-qr.update', $store) }}" class="mt-3 inline-flex items-center gap-2">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="takeout_qr_enabled" value="{{ $store->takeout_qr_enabled ? '0' : '1' }}">
                        <button type="submit" class="inline-flex items-center rounded-xl {{ $store->takeout_qr_enabled ? 'border border-rose-300 bg-rose-50 text-rose-700 hover:bg-rose-100' : 'border border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }} px-4 py-2 text-sm font-semibold transition">
                            {{ $store->takeout_qr_enabled ? '關閉外帶 QR' : '開放外帶 QR' }}
                        </button>
                        <span class="text-xs font-semibold {{ $store->takeout_qr_enabled ? 'text-emerald-700' : 'text-slate-500' }}">
                            {{ $store->takeout_qr_enabled ? '目前已開放' : '目前未開放' }}
                        </span>
                    </form>
                </div>

                @if($store->takeout_qr_enabled)
                    <div class="w-full max-w-[320px] rounded-2xl border border-slate-200 bg-slate-50 p-3">
                        <div class="mx-auto flex h-[220px] w-[220px] items-center justify-center overflow-hidden rounded-xl bg-white ring-1 ring-slate-200 [&_svg]:h-full [&_svg]:w-full">
                            {!! $takeoutQrSvg !!}
                        </div>
                        <p class="mt-2 text-center text-sm font-semibold text-slate-700">外帶專用</p>
                        <label class="mt-3 mb-1 block text-xs font-semibold text-slate-500">外帶點餐連結</label>
                        <input type="text" value="{{ $takeoutMenuUrl }}" readonly class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-700" onclick="this.select()">
                    </div>
                @else
                    <div class="w-full max-w-[320px] rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                        外帶 QR Code 尚未開放
                    </div>
                @endif
            </div>
        </div>

        <div class="mb-6 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">新增桌位</h2>
            <p class="mt-1 text-sm text-slate-500">新增後會立即生成對應的桌次 Menu QR Code。</p>

            <form method="POST" action="{{ route('admin.stores.tables.store', $store) }}" class="mt-4 flex flex-col gap-3 sm:flex-row">
                @csrf
                <input type="text"
                       name="table_no"
                       value="{{ old('table_no') }}"
                       placeholder="例如：A1、1號桌、VIP-1"
                       class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                       required>
                <button type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500">
                    新增桌位
                </button>
            </form>
        </div>

        <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" id="select-all-tables" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">全選</button>
                    <button type="button" id="clear-all-tables" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">清空</button>
                    <span id="selected-tables-count" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">已選 0 桌</span>
                </div>

                <form id="print-selected-form" method="GET" action="{{ route('admin.stores.tables.print', $store) }}" target="_blank" class="inline-flex">
                    <div id="print-selected-inputs"></div>
                    <button id="print-selected-submit" type="submit" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:bg-indigo-300" disabled>
                        列印選取桌位
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
                                選取列印
                            </label>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">桌號</p>
                            <h3 class="text-2xl font-bold text-slate-900">{{ $table->table_no }}</h3>
                        </div>
                        @if($table->status === 'available')
                            <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">啟用中</span>
                        @else
                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">停用</span>
                        @endif
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                        <div class="mx-auto flex h-[180px] w-[180px] items-center justify-center overflow-hidden rounded-xl bg-white ring-1 ring-slate-200 [&_svg]:h-full [&_svg]:w-full">
                            {!! $table->qr_svg !!}
                        </div>
                        <p class="mt-2 text-center text-sm font-semibold text-slate-700">{{ $table->table_no }}</p>
                    </div>

                    <div class="mt-3">
                        <label class="mb-1 block text-xs font-semibold text-slate-500">桌次點餐連結</label>
                        <input type="text" value="{{ $table->menu_url }}" readonly class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-xs text-slate-700" onclick="this.select()">
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <form method="POST" action="{{ route('admin.stores.tables.status', [$store, $table]) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="{{ $table->status === 'available' ? 'inactive' : 'available' }}">
                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                {{ $table->status === 'available' ? '停用桌位' : '啟用桌位' }}
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.stores.tables.regenerate-qr', [$store, $table]) }}" onsubmit="return confirm('確定要重新產生 QR Code 嗎？舊 QR 將失效。')">
                            @csrf
                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500">
                                重生 QR
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white/70 p-10 text-center text-sm text-slate-500 md:col-span-2 xl:col-span-3">
                    目前還沒有桌位，先新增一個桌位吧。
                </div>
            @endforelse
        </div>
    </div>
</div>

<script>
(() => {
    const checkboxes = Array.from(document.querySelectorAll('.table-select-checkbox'));
    const selectAllBtn = document.getElementById('select-all-tables');
    const clearAllBtn = document.getElementById('clear-all-tables');
    const selectedCountEl = document.getElementById('selected-tables-count');
    const printSubmitBtn = document.getElementById('print-selected-submit');
    const printInputs = document.getElementById('print-selected-inputs');

    if (!selectedCountEl || !printSubmitBtn || !printInputs) {
        return;
    }

    const syncSelected = () => {
        const selected = checkboxes.filter((cb) => cb.checked).map((cb) => cb.value);
        selectedCountEl.textContent = `已選 ${selected.length} 桌`;
        printSubmitBtn.disabled = selected.length === 0;

        printInputs.innerHTML = selected
            .map((id) => `<input type="hidden" name="table_ids[]" value="${id}">`)
            .join('');
    };

    checkboxes.forEach((cb) => {
        cb.addEventListener('change', syncSelected);
    });

    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', () => {
            checkboxes.forEach((cb) => {
                cb.checked = true;
            });
            syncSelected();
        });
    }

    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', () => {
            checkboxes.forEach((cb) => {
                cb.checked = false;
            });
            syncSelected();
        });
    }

    syncSelected();
})();
</script>
@endsection
