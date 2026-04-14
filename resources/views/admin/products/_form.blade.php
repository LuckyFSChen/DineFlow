@csrf

@php
    $initialOptionGroupsJson = old('option_groups_json');
    if ($initialOptionGroupsJson === null) {
        $initialOptionGroupsJson = $product->option_groups
            ? json_encode($product->option_groups, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : '[]';
    }
@endphp

<div class="grid gap-6 lg:grid-cols-2">
    <div class="lg:col-span-2 rounded-2xl border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-800">
        建議流程：
        1. 先填商品基本資訊。
        2. 設定銷售狀態。
        3. 最後用下方「選配設定」加上必選與加購項目。
    </div>

    <div class="lg:col-span-2 mt-2">
        <h2 class="text-base font-bold text-slate-900">基本資訊</h2>
        <p class="mt-1 text-xs text-slate-500">顧客第一眼會看到的名稱、分類、價格與描述。</p>
    </div>

    <div class="lg:col-span-2">
        <label class="mb-2 block text-sm font-semibold text-slate-700">商品名稱</label>
        <input type="text" name="name" value="{{ old('name', $product->name) }}"
               class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
        @error('name')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">分類</label>
        <select name="category_id"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected((int) old('category_id', $product->category_id) === (int) $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
        @error('category_id')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">價格 (NT$)</label>
        <input type="number" name="price" min="0" value="{{ old('price', $product->price) }}"
               class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
        @error('price')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">排序</label>
        <input type="number" name="sort" min="1" value="{{ old('sort', $product->sort ?? 1) }}"
               class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
        @error('sort')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">圖片 URL (可留空)</label>
        <input type="text" name="image" value="{{ old('image', $product->image) }}"
               class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
        @error('image')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="lg:col-span-2">
        <label class="mb-2 block text-sm font-semibold text-slate-700">商品描述</label>
        <textarea name="description" rows="3"
                  class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">{{ old('description', $product->description) }}</textarea>
        @error('description')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="lg:col-span-2">
        <h2 class="text-base font-bold text-slate-900">選配設定</h2>
        <p class="mt-1 text-xs text-slate-500">可建立必選、單選、多選與加價項目。</p>

        <div class="mb-2 flex items-center justify-between gap-3">
            <label class="block text-sm font-semibold text-slate-700">群組清單</label>
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    data-option-template="steak"
                    class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100">
                    套用牛排範本
                </button>
                <button
                    type="button"
                    data-option-template="combo"
                    class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100">
                    套用套餐範本
                </button>
                <button
                    type="button"
                    data-option-clear-all
                    class="inline-flex items-center rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-100">
                    清空
                </button>
                <button
                    type="button"
                    data-option-add-group
                    class="inline-flex items-center rounded-xl bg-indigo-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-indigo-500">
                    新增群組
                </button>
            </div>
        </div>

        <input type="hidden" name="option_groups_json" id="option-groups-json-input" value="{{ $initialOptionGroupsJson }}">

        <div id="option-groups-editor" class="space-y-4"></div>

        <div class="mt-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600">
            提示：
            群組可設為單選或多選，必選群組會在前台要求顧客一定要選。
            多選群組可設定最多可選幾項。
        </div>

        @error('option_groups_json')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="lg:col-span-2 mt-2">
        <h2 class="text-base font-bold text-slate-900">銷售狀態</h2>
        <p class="mt-1 text-xs text-slate-500">控制是否上架、或暫時標記售完。</p>
    </div>

    <div>
        <label class="inline-flex items-center gap-3">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active ?? true) ? 'checked' : '' }}
                   class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            <span class="text-sm font-semibold text-slate-700">上架</span>
        </label>
    </div>

    <div>
        <label class="inline-flex items-center gap-3">
            <input type="checkbox" name="is_sold_out" value="1" {{ old('is_sold_out', $product->is_sold_out ?? false) ? 'checked' : '' }}
                   class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            <span class="text-sm font-semibold text-slate-700">售完</span>
        </label>
    </div>
</div>

<div class="mt-8 flex gap-3">
    <button type="submit"
            class="inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500">
        儲存
    </button>

    <a href="{{ route('admin.stores.products.index', $store) }}"
       class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
        返回商品列表
    </a>
</div>

<script>
(() => {
    const hiddenInput = document.getElementById('option-groups-json-input');
    const editor = document.getElementById('option-groups-editor');
    const addGroupBtn = document.querySelector('[data-option-add-group]');
    const clearAllBtn = document.querySelector('[data-option-clear-all]');
    const templateButtons = document.querySelectorAll('[data-option-template]');

    if (!hiddenInput || !editor || !addGroupBtn) {
        return;
    }

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

    const optionTemplates = {
        steak: [
            {
                id: 'doneness',
                name: '熟度',
                type: 'single',
                required: true,
                choices: [
                    { id: 'rare', name: '三分熟', price: 0 },
                    { id: 'medium', name: '五分熟', price: 0 },
                    { id: 'well', name: '全熟', price: 0 },
                ],
            },
            {
                id: 'extras',
                name: '加購配料',
                type: 'multiple',
                required: false,
                max_select: 3,
                choices: [
                    { id: 'egg', name: '加蛋', price: 20 },
                    { id: 'cheese', name: '加起司', price: 25 },
                    { id: 'sauce', name: '蘑菇醬', price: 15 },
                ],
            },
        ],
        combo: [
            {
                id: 'main_choice',
                name: '主餐',
                type: 'single',
                required: true,
                choices: [
                    { id: 'chicken', name: '雞腿排', price: 0 },
                    { id: 'pork', name: '豬排', price: 0 },
                    { id: 'fish', name: '烤魚', price: 20 },
                ],
            },
            {
                id: 'side_choice',
                name: '附餐',
                type: 'single',
                required: true,
                choices: [
                    { id: 'fries', name: '薯條', price: 0 },
                    { id: 'salad', name: '沙拉', price: 0 },
                    { id: 'soup', name: '濃湯', price: 0 },
                ],
            },
            {
                id: 'drink_choice',
                name: '飲料',
                type: 'single',
                required: true,
                choices: [
                    { id: 'black_tea', name: '紅茶', price: 0 },
                    { id: 'green_tea', name: '綠茶', price: 0 },
                    { id: 'milk_tea', name: '奶茶', price: 10 },
                ],
            },
        ],
    };

    const moveItem = (list, fromIndex, toIndex) => {
        if (!Array.isArray(list)) {
            return;
        }

        if (fromIndex === toIndex || fromIndex < 0 || toIndex < 0 || fromIndex >= list.length || toIndex >= list.length) {
            return;
        }

        const [item] = list.splice(fromIndex, 1);
        list.splice(toIndex, 0, item);
    };

    let groups = [];
    let dragState = null;
    try {
        const parsed = JSON.parse(hiddenInput.value || '[]');
        groups = Array.isArray(parsed) ? parsed : [];
    } catch (_e) {
        groups = [];
    }

    const normalize = () => {
        groups = groups
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

    const sync = () => {
        normalize();
        hiddenInput.value = JSON.stringify(groups);
    };

    const groupCard = (group, groupIndex) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'rounded-3xl border border-slate-200 bg-white p-4 shadow-sm';
        wrapper.dataset.groupIndex = String(groupIndex);

        const header = document.createElement('div');
        header.className = 'mb-4 flex items-center justify-between gap-3';
        header.innerHTML = `
            <div class="flex items-center gap-2">
                <button type="button" draggable="true" data-drag-group-handle class="inline-flex cursor-grab items-center rounded-lg border border-slate-300 bg-slate-50 px-2 py-1 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 active:cursor-grabbing" title="拖曳排序群組">拖曳</button>
                <p class="text-sm font-semibold text-slate-800">群組 #${groupIndex + 1}</p>
            </div>
            <button type="button" data-remove-group class="inline-flex items-center rounded-xl bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100">刪除群組</button>
        `;
        wrapper.appendChild(header);

        const grid = document.createElement('div');
        grid.className = 'grid gap-3 md:grid-cols-2';

        grid.innerHTML = `
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">群組名稱</label>
                <input type="text" value="${esc(group.name || '')}" data-group-field="name" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" placeholder="例如：熟度">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">群組 ID</label>
                <input type="text" value="${esc(group.id || '')}" data-group-field="id" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" placeholder="例如：doneness">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">類型</label>
                <select data-group-field="type" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                    <option value="single" ${group.type === 'single' ? 'selected' : ''}>單選</option>
                    <option value="multiple" ${group.type === 'multiple' ? 'selected' : ''}>多選</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">最多可選（多選用）</label>
                <input type="number" min="1" value="${group.max_select || 1}" data-group-field="max_select" ${group.type === 'single' ? 'disabled' : ''} class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200 disabled:cursor-not-allowed disabled:bg-slate-100">
            </div>
        `;

        wrapper.appendChild(grid);

        const requiredRow = document.createElement('div');
        requiredRow.className = 'mt-3';
        requiredRow.innerHTML = `
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" data-group-field="required" ${group.required ? 'checked' : ''} class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                必選群組
            </label>
        `;
        wrapper.appendChild(requiredRow);

        const choicesWrap = document.createElement('div');
        choicesWrap.className = 'mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-3';

        const choicesHeader = document.createElement('div');
        choicesHeader.className = 'mb-3 flex items-center justify-between';
        choicesHeader.innerHTML = `
            <p class="text-xs font-semibold text-slate-700">選項列表</p>
            <button type="button" data-add-choice class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800">新增選項</button>
        `;
        choicesWrap.appendChild(choicesHeader);

        const choicesList = document.createElement('div');
        choicesList.className = 'space-y-2';
        choicesList.dataset.choicesList = '1';

        (Array.isArray(group.choices) ? group.choices : []).forEach((choice, choiceIndex) => {
            const choiceRow = document.createElement('div');
            choiceRow.className = 'grid gap-2 rounded-xl border border-slate-200 bg-white p-2 md:grid-cols-[auto,1fr,1fr,140px,auto]';
            choiceRow.dataset.choiceIndex = String(choiceIndex);
            choiceRow.innerHTML = `
                <button type="button" draggable="true" data-drag-choice-handle class="rounded-lg border border-slate-300 bg-slate-50 px-2 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100" title="拖曳排序選項">拖曳</button>
                <input type="text" value="${esc(choice.name || '')}" data-choice-field="name" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" placeholder="選項名稱">
                <input type="text" value="${esc(choice.id || '')}" data-choice-field="id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" placeholder="選項 ID">
                <input type="number" min="0" value="${Number(choice.price || 0)}" data-choice-field="price" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" placeholder="加價">
                <button type="button" data-remove-choice class="rounded-lg bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">刪除</button>
            `;
            choicesList.appendChild(choiceRow);
        });

        choicesWrap.appendChild(choicesList);
        wrapper.appendChild(choicesWrap);

        return wrapper;
    };

    const render = () => {
        editor.innerHTML = '';
        groups.forEach((group, groupIndex) => {
            editor.appendChild(groupCard(group, groupIndex));
        });
        sync();
    };

    addGroupBtn.addEventListener('click', () => {
        groups.push(createGroup());
        render();
    });

    clearAllBtn?.addEventListener('click', () => {
        if (!confirm('確定要清空所有選配群組嗎？')) {
            return;
        }
        groups = [];
        render();
    });

    templateButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const key = button.getAttribute('data-option-template');
            const template = key ? optionTemplates[key] : null;
            if (!template) {
                return;
            }

            if (groups.length > 0 && !confirm('套用範本會覆蓋目前選配設定，是否繼續？')) {
                return;
            }

            groups = JSON.parse(JSON.stringify(template));
            render();
        });
    });

    editor.addEventListener('dragstart', (event) => {
        const groupHandle = event.target.closest('[data-drag-group-handle]');
        if (groupHandle) {
            const card = groupHandle.closest('[data-group-index]');
            if (!card) {
                return;
            }

            const fromGroupIndex = Number(card.dataset.groupIndex);
            if (!Number.isInteger(fromGroupIndex)) {
                return;
            }

            dragState = { type: 'group', fromGroupIndex };
            card.classList.add('ring-2', 'ring-indigo-300');
            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
            }
            return;
        }

        const choiceHandle = event.target.closest('[data-drag-choice-handle]');
        if (choiceHandle) {
            const card = choiceHandle.closest('[data-group-index]');
            const row = choiceHandle.closest('[data-choice-index]');
            if (!card || !row) {
                return;
            }

            const fromGroupIndex = Number(card.dataset.groupIndex);
            const fromChoiceIndex = Number(row.dataset.choiceIndex);
            if (!Number.isInteger(fromGroupIndex) || !Number.isInteger(fromChoiceIndex)) {
                return;
            }

            dragState = { type: 'choice', fromGroupIndex, fromChoiceIndex };
            row.classList.add('ring-2', 'ring-indigo-300');
            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
            }
        }
    });

    editor.addEventListener('dragover', (event) => {
        if (dragState) {
            event.preventDefault();
        }
    });

    editor.addEventListener('drop', (event) => {
        if (!dragState) {
            return;
        }

        event.preventDefault();

        if (dragState.type === 'group') {
            const targetCard = event.target.closest('[data-group-index]');
            if (!targetCard) {
                dragState = null;
                render();
                return;
            }

            const toGroupIndex = Number(targetCard.dataset.groupIndex);
            if (Number.isInteger(toGroupIndex)) {
                moveItem(groups, dragState.fromGroupIndex, toGroupIndex);
            }

            dragState = null;
            render();
            return;
        }

        if (dragState.type === 'choice') {
            const targetCard = event.target.closest('[data-group-index]');
            const targetRow = event.target.closest('[data-choice-index]');
            if (!targetCard || !targetRow) {
                dragState = null;
                render();
                return;
            }

            const toGroupIndex = Number(targetCard.dataset.groupIndex);
            const toChoiceIndex = Number(targetRow.dataset.choiceIndex);

            if (
                Number.isInteger(toGroupIndex) &&
                Number.isInteger(toChoiceIndex) &&
                toGroupIndex === dragState.fromGroupIndex &&
                Array.isArray(groups[toGroupIndex]?.choices)
            ) {
                moveItem(groups[toGroupIndex].choices, dragState.fromChoiceIndex, toChoiceIndex);
            }

            dragState = null;
            render();
        }
    });

    editor.addEventListener('dragend', () => {
        dragState = null;
        render();
    });

    editor.addEventListener('click', (event) => {
        const groupCardElement = event.target.closest('[data-group-index]');
        if (!groupCardElement) {
            return;
        }

        const groupIndex = Number(groupCardElement.dataset.groupIndex);
        if (!Number.isInteger(groupIndex) || !groups[groupIndex]) {
            return;
        }

        if (event.target.closest('[data-remove-group]')) {
            groups.splice(groupIndex, 1);
            render();
            return;
        }

        if (event.target.closest('[data-add-choice]')) {
            groups[groupIndex].choices = Array.isArray(groups[groupIndex].choices) ? groups[groupIndex].choices : [];
            groups[groupIndex].choices.push(createChoice());
            render();
            return;
        }

        const choiceRow = event.target.closest('[data-choice-index]');
        if (choiceRow && event.target.closest('[data-remove-choice]')) {
            const choiceIndex = Number(choiceRow.dataset.choiceIndex);
            if (Number.isInteger(choiceIndex) && Array.isArray(groups[groupIndex].choices)) {
                groups[groupIndex].choices.splice(choiceIndex, 1);
                render();
            }
        }
    });

    editor.addEventListener('input', (event) => {
        const groupCardElement = event.target.closest('[data-group-index]');
        if (!groupCardElement) {
            return;
        }

        const groupIndex = Number(groupCardElement.dataset.groupIndex);
        if (!Number.isInteger(groupIndex) || !groups[groupIndex]) {
            return;
        }

        const groupField = event.target.getAttribute('data-group-field');
        if (groupField) {
            if (groupField === 'max_select') {
                groups[groupIndex][groupField] = Math.max(Number(event.target.value || 1), 1);
            } else {
                groups[groupIndex][groupField] = event.target.value;
            }

            if (groupField === 'name' && !groups[groupIndex].id) {
                groups[groupIndex].id = toId(groups[groupIndex].name || uid());
            }

            if (groupField === 'type' && event.target.value === 'single') {
                groups[groupIndex].max_select = 1;
            }

            render();
            return;
        }

        const choiceRow = event.target.closest('[data-choice-index]');
        if (!choiceRow) {
            return;
        }

        const choiceIndex = Number(choiceRow.dataset.choiceIndex);
        if (!Array.isArray(groups[groupIndex].choices) || !groups[groupIndex].choices[choiceIndex]) {
            return;
        }

        const choiceField = event.target.getAttribute('data-choice-field');
        if (!choiceField) {
            return;
        }

        if (choiceField === 'price') {
            groups[groupIndex].choices[choiceIndex][choiceField] = Math.max(Number(event.target.value || 0), 0);
        } else {
            groups[groupIndex].choices[choiceIndex][choiceField] = event.target.value;
            if (choiceField === 'name' && !groups[groupIndex].choices[choiceIndex].id) {
                groups[groupIndex].choices[choiceIndex].id = toId(groups[groupIndex].choices[choiceIndex].name || uid());
            }
        }

        sync();
    });

    editor.addEventListener('change', (event) => {
        const groupCardElement = event.target.closest('[data-group-index]');
        if (!groupCardElement) {
            return;
        }

        const groupIndex = Number(groupCardElement.dataset.groupIndex);
        if (!Number.isInteger(groupIndex) || !groups[groupIndex]) {
            return;
        }

        if (event.target.getAttribute('data-group-field') === 'required') {
            groups[groupIndex].required = !!event.target.checked;
            sync();
        }
    });

    render();
})();
</script>
