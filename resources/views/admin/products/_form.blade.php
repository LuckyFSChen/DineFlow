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
        {{ __('admin.products_form_suggested_workflow_title') }}<br>
        1. {{ __('admin.products_form_step_1') }}<br>
        2. {{ __('admin.products_form_step_2') }}<br>
        3. {{ __('admin.products_form_step_3') }}
    </div>

    <div class="lg:col-span-2 mt-2">
        <h2 class="text-base font-bold text-slate-900">{{ __('admin.products_form_basic_info_title') }}</h2>
        <p class="mt-1 text-xs text-slate-500">{{ __('admin.products_form_basic_info_description') }}</p>
    </div>

    <div class="lg:col-span-2">
        <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('admin.products_form_product_name') }}</label>
        <input type="text" name="name" value="{{ old('name', $product->name) }}"
               class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
        @error('name')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('admin.products_form_category') }}</label>
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
        <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('admin.products_form_price') }}</label>
        <input type="number" name="price" min="0" value="{{ old('price', $product->price) }}"
               class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
        @error('price')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('admin.products_form_cost') }}</label>
        <input type="number" name="cost" min="0" value="{{ old('cost', $product->cost ?? 0) }}"
               class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
        @error('cost')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('admin.products_form_image_url_optional') }}</label>
        <input type="text" name="image" value="{{ old('image', $product->image) }}"
               class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
        @error('image')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="lg:col-span-2">
        <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('admin.products_form_description') }}</label>
        <textarea name="description" rows="3"
                  class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">{{ old('description', $product->description) }}</textarea>
        @error('description')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="lg:col-span-2">
        <h2 class="text-base font-bold text-slate-900">{{ __('admin.products_options_settings_title') }}</h2>
        <p class="mt-1 text-xs text-slate-500">{{ __('admin.products_options_settings_description') }}</p>

        <div class="mb-2 flex items-center justify-between gap-3">
            <label class="block text-sm font-semibold text-slate-700">{{ __('admin.products_options_group_list') }}</label>
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    data-option-template="steak"
                    class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100">
                    {{ __('admin.products_options_template_steak') }}
                </button>
                <button
                    type="button"
                    data-option-template="combo"
                    class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100">
                    {{ __('admin.products_options_template_combo') }}
                </button>
                <button
                    type="button"
                    data-option-clear-all
                    class="inline-flex items-center rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-100">
                    {{ __('admin.products_options_clear_all') }}
                </button>
                <button
                    type="button"
                    data-option-add-group
                    class="inline-flex items-center rounded-xl bg-indigo-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-indigo-500">
                    {{ __('admin.products_options_add_group') }}
                </button>
            </div>
        </div>

        <input type="hidden" name="option_groups_json" id="option-groups-json-input" value="{{ $initialOptionGroupsJson }}">

        <div id="option-groups-editor" class="space-y-4"></div>

        <div class="mt-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600">
            {{ __('admin.products_options_hint_text') }}
        </div>

        @error('option_groups_json')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="lg:col-span-2 mt-2">
        <h2 class="text-base font-bold text-slate-900">{{ __('admin.products_form_sales_status_title') }}</h2>
        <p class="mt-1 text-xs text-slate-500">{{ __('admin.products_form_sales_status_description') }}</p>
    </div>

    <div>
        <label class="inline-flex items-center gap-3">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active ?? true) ? 'checked' : '' }}
                   class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            <span class="text-sm font-semibold text-slate-700">{{ __('admin.products_form_published') }}</span>
        </label>
    </div>

    <div>
        <label class="inline-flex items-center gap-3">
            <input type="checkbox" name="is_sold_out" value="1" {{ old('is_sold_out', $product->is_sold_out ?? false) ? 'checked' : '' }}
                   class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            <span class="text-sm font-semibold text-slate-700">{{ __('admin.products_form_sold_out') }}</span>
        </label>
    </div>

    <div class="lg:col-span-2">
        <label class="inline-flex items-center gap-3">
            <input type="checkbox" name="allow_item_note" value="1" {{ old('allow_item_note', $product->allow_item_note ?? false) ? 'checked' : '' }}
                   class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            <span class="text-sm font-semibold text-slate-700">{{ __('admin.products_form_allow_item_note') }}</span>
        </label>
    </div>
</div>

<div class="mt-8 flex gap-3">
    <button type="submit"
            class="inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500">
        {{ __('admin.products_btn_save') }}
    </button>

    <a href="{{ route('admin.stores.products.index', $store) }}"
       class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
        {{ __('admin.products_btn_back_to_list') }}
    </a>
</div>

<script>
(() => {
    const i18n = {
        dragShort: @json(__('admin.products_drag_short')),
        dragGroupTitle: @json(__('admin.products_drag_group_title')),
        dragChoiceTitle: @json(__('admin.products_drag_choice_title')),
        groupLabel: @json(__('admin.products_group_label')),
        removeGroup: @json(__('admin.products_group_delete')),
        groupName: @json(__('admin.products_group_name')),
        groupType: @json(__('admin.products_group_type')),
        groupTypeSingle: @json(__('admin.products_group_type_single')),
        groupTypeMultiple: @json(__('admin.products_group_type_multiple')),
        groupMaxSelect: @json(__('admin.products_group_max_select')),
        groupRequired: @json(__('admin.products_group_required')),
        choicesList: @json(__('admin.products_group_choices_list')),
        addChoice: @json(__('admin.products_group_add_choice')),
        choiceName: @json(__('admin.products_choice_name')),
        choicePrice: @json(__('admin.products_choice_price')),
        delete: @json(__('admin.products_btn_delete')),
        groupExampleName: @json(__('admin.products_group_example_name')),
        confirmClearAllOptions: @json(__('admin.products_confirm_clear_all_options')),
        confirmApplyTemplate: @json(__('admin.products_confirm_apply_template')),
        templateSteakDoneness: @json(__('admin.products_template_steak_doneness')),
        templateSteakRare: @json(__('admin.products_template_steak_rare')),
        templateSteakMedium: @json(__('admin.products_template_steak_medium')),
        templateSteakWell: @json(__('admin.products_template_steak_well')),
        templateSteakExtras: @json(__('admin.products_template_steak_extras')),
        templateSteakEgg: @json(__('admin.products_template_steak_egg')),
        templateSteakCheese: @json(__('admin.products_template_steak_cheese')),
        templateSteakSauce: @json(__('admin.products_template_steak_sauce')),
        templateComboMain: @json(__('admin.products_template_combo_main_choice')),
        templateComboChicken: @json(__('admin.products_template_combo_chicken')),
        templateComboPork: @json(__('admin.products_template_combo_pork')),
        templateComboFish: @json(__('admin.products_template_combo_fish')),
        templateComboSide: @json(__('admin.products_template_combo_side_choice')),
        templateComboFries: @json(__('admin.products_template_combo_fries')),
        templateComboSalad: @json(__('admin.products_template_combo_salad')),
        templateComboSoup: @json(__('admin.products_template_combo_soup')),
        templateComboDrink: @json(__('admin.products_template_combo_drink_choice')),
        templateComboBlackTea: @json(__('admin.products_template_combo_black_tea')),
        templateComboGreenTea: @json(__('admin.products_template_combo_green_tea')),
        templateComboMilkTea: @json(__('admin.products_template_combo_milk_tea')),
    };

    const hiddenInput = document.getElementById('option-groups-json-input');
    const editor = document.getElementById('option-groups-editor');
    const addGroupBtn = document.querySelector('[data-option-add-group]');
    const clearAllBtn = document.querySelector('[data-option-clear-all]');
    const templateButtons = document.querySelectorAll('[data-option-template]');

    if (!hiddenInput || !editor || !addGroupBtn) {
        return;
    }

    const esc = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

    const createGroup = () => ({
        name: '',
        type: 'single',
        required: false,
        max_select: 1,
        choices: [],
    });

    const createChoice = () => ({
        name: '',
        price: 0,
    });

    const optionTemplates = {
        steak: [
            {
                name: i18n.templateSteakDoneness,
                type: 'single',
                required: true,
                choices: [
                    { name: i18n.templateSteakRare, price: 0 },
                    { name: i18n.templateSteakMedium, price: 0 },
                    { name: i18n.templateSteakWell, price: 0 },
                ],
            },
            {
                name: i18n.templateSteakExtras,
                type: 'multiple',
                required: false,
                max_select: 3,
                choices: [
                    { name: i18n.templateSteakEgg, price: 20 },
                    { name: i18n.templateSteakCheese, price: 25 },
                    { name: i18n.templateSteakSauce, price: 15 },
                ],
            },
        ],
        combo: [
            {
                name: i18n.templateComboMain,
                type: 'single',
                required: true,
                choices: [
                    { name: i18n.templateComboChicken, price: 0 },
                    { name: i18n.templateComboPork, price: 0 },
                    { name: i18n.templateComboFish, price: 20 },
                ],
            },
            {
                name: i18n.templateComboSide,
                type: 'single',
                required: true,
                choices: [
                    { name: i18n.templateComboFries, price: 0 },
                    { name: i18n.templateComboSalad, price: 0 },
                    { name: i18n.templateComboSoup, price: 0 },
                ],
            },
            {
                name: i18n.templateComboDrink,
                type: 'single',
                required: true,
                choices: [
                    { name: i18n.templateComboBlackTea, price: 0 },
                    { name: i18n.templateComboGreenTea, price: 0 },
                    { name: i18n.templateComboMilkTea, price: 10 },
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
                    name: String(group.name || '').trim(),
                    type,
                    required: !!group.required,
                    max_select: type === 'multiple' ? Math.max(Number(group.max_select || 1), 1) : 1,
                    choices: choices
                        .filter((choice) => choice && typeof choice === 'object')
                        .map((choice) => ({
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
                <button type="button" draggable="true" data-drag-group-handle class="inline-flex cursor-grab items-center rounded-lg border border-slate-300 bg-slate-50 px-2 py-1 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 active:cursor-grabbing" title="${esc(i18n.dragGroupTitle)}">${i18n.dragShort}</button>
                <p class="text-sm font-semibold text-slate-800">${i18n.groupLabel} #${groupIndex + 1}</p>
            </div>
            <button type="button" data-remove-group class="inline-flex items-center rounded-xl bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100">${i18n.removeGroup}</button>
        `;
        wrapper.appendChild(header);

        const grid = document.createElement('div');
        grid.className = 'grid gap-3 md:grid-cols-2';

        grid.innerHTML = `
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">${i18n.groupName}</label>
                <input type="text" value="${esc(group.name || '')}" data-group-field="name" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" placeholder="${esc(i18n.groupExampleName)}">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">${i18n.groupType}</label>
                <select data-group-field="type" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                    <option value="single" ${group.type === 'single' ? 'selected' : ''}>${i18n.groupTypeSingle}</option>
                    <option value="multiple" ${group.type === 'multiple' ? 'selected' : ''}>${i18n.groupTypeMultiple}</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">${i18n.groupMaxSelect}</label>
                <input type="number" min="1" value="${group.max_select || 1}" data-group-field="max_select" ${group.type === 'single' ? 'disabled' : ''} class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200 disabled:cursor-not-allowed disabled:bg-slate-100">
            </div>
        `;

        wrapper.appendChild(grid);

        const requiredRow = document.createElement('div');
        requiredRow.className = 'mt-3';
        requiredRow.innerHTML = `
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" data-group-field="required" ${group.required ? 'checked' : ''} class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                ${i18n.groupRequired}
            </label>
        `;
        wrapper.appendChild(requiredRow);

        const choicesWrap = document.createElement('div');
        choicesWrap.className = 'mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-3';

        const choicesHeader = document.createElement('div');
        choicesHeader.className = 'mb-3 flex items-center justify-between';
        choicesHeader.innerHTML = `
            <p class="text-xs font-semibold text-slate-700">${i18n.choicesList}</p>
            <button type="button" data-add-choice class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800">${i18n.addChoice}</button>
        `;
        choicesWrap.appendChild(choicesHeader);

        const choicesList = document.createElement('div');
        choicesList.className = 'space-y-2';
        choicesList.dataset.choicesList = '1';

        (Array.isArray(group.choices) ? group.choices : []).forEach((choice, choiceIndex) => {
            const choiceRow = document.createElement('div');
            choiceRow.className = 'grid gap-2 rounded-xl border border-slate-200 bg-white p-2 md:grid-cols-[auto,1fr,140px,auto]';
            choiceRow.dataset.choiceIndex = String(choiceIndex);
            choiceRow.innerHTML = `
                <button type="button" draggable="true" data-drag-choice-handle class="rounded-lg border border-slate-300 bg-slate-50 px-2 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100" title="${esc(i18n.dragChoiceTitle)}">${i18n.dragShort}</button>
                <input type="text" value="${esc(choice.name || '')}" data-choice-field="name" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" placeholder="${esc(i18n.choiceName)}">
                <input type="number" min="0" value="${Number(choice.price || 0)}" data-choice-field="price" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" placeholder="${esc(i18n.choicePrice)}">
                <button type="button" data-remove-choice class="rounded-lg bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">${i18n.delete}</button>
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
        if (!confirm(i18n.confirmClearAllOptions)) {
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

            if (groups.length > 0 && !confirm(i18n.confirmApplyTemplate)) {
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
