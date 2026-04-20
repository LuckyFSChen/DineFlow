import './bootstrap';

import Alpine from 'alpinejs';
import DataTable from 'datatables.net-dt';
import 'datatables.net-responsive-dt';
import 'datatables.net-dt/css/dataTables.dataTables.css';
import 'datatables.net-responsive-dt/css/responsive.dataTables.css';
import flatpickr from 'flatpickr';
import { Mandarin } from 'flatpickr/dist/l10n/zh.js';
import { MandarinTraditional } from 'flatpickr/dist/l10n/zh-tw.js';
import { Vietnamese } from 'flatpickr/dist/l10n/vn.js';
import 'flatpickr/dist/flatpickr.min.css';

window.Alpine = Alpine;

Alpine.start();

const phoneSelectors = [
    'input[type="tel"]',
    'input[name*="phone"]',
    'input[id*="phone"]',
];

const phoneSelector = phoneSelectors.join(',');

const detectDigitsLimit = (input) => {
    const explicit = Number(input.dataset.phoneDigits || 0);
    if (explicit > 0) {
        return explicit;
    }

    const rawMaxLength = Number(input.getAttribute('maxlength') || 0);
    if (rawMaxLength > 0) {
        return rawMaxLength > 11 ? rawMaxLength - 2 : rawMaxLength;
    }

    return 11;
};

const formatPhone = (raw, digitsLimit) => {
    const digits = String(raw || '').replace(/\D/g, '').slice(0, digitsLimit);

    if (digits.length <= 4) {
        return digits;
    }

    if (digits.length === 11) {
        return `${digits.slice(0, 3)}-${digits.slice(3, 7)}-${digits.slice(7)}`;
    }

    if (digits.length === 10) {
        if (digits.startsWith('09')) {
            return `${digits.slice(0, 4)}-${digits.slice(4, 7)}-${digits.slice(7)}`;
        }

        return `${digits.slice(0, 3)}-${digits.slice(3, 6)}-${digits.slice(6)}`;
    }

    if (digits.length <= 7) {
        return `${digits.slice(0, 4)}-${digits.slice(4)}`;
    }

    return `${digits.slice(0, 3)}-${digits.slice(3, 6)}-${digits.slice(6)}`;
};

const bindPhoneInput = (input) => {
    if (!(input instanceof HTMLInputElement) || input.dataset.phoneAutoHyphenBound === '1') {
        return;
    }

    input.dataset.phoneAutoHyphenBound = '1';
    const digitsLimit = detectDigitsLimit(input);
    input.setAttribute('inputmode', input.getAttribute('inputmode') || 'numeric');
    input.setAttribute('pattern', '[0-9-]*');
    input.setAttribute('maxlength', String(digitsLimit + 2));

    const applyFormatting = () => {
        input.value = formatPhone(input.value, digitsLimit);
    };

    input.addEventListener('input', applyFormatting);
    input.addEventListener('blur', applyFormatting);
    applyFormatting();
};

const bindPhoneInputs = (root = document) => {
    if (!(root instanceof Document || root instanceof Element)) {
        return;
    }

    if (root instanceof Element && root.matches(phoneSelector)) {
        bindPhoneInput(root);
    }

    root.querySelectorAll(phoneSelector).forEach(bindPhoneInput);
};

const bootPhoneFormatter = () => {
    bindPhoneInputs();

    if (!document.body || typeof MutationObserver === 'undefined') {
        return;
    }

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node instanceof Element) {
                    bindPhoneInputs(node);
                }
            });
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true,
    });
};

const dataTableLanguage = {
    emptyTable: '目前沒有資料',
    zeroRecords: '找不到符合條件的資料',
    info: '顯示第 _START_ 到 _END_ 筆，共 _TOTAL_ 筆',
    infoEmpty: '顯示第 0 到 0 筆，共 0 筆',
    infoFiltered: '(從 _MAX_ 筆資料中篩選)',
    lengthMenu: '每頁顯示 _MENU_ 筆',
    search: '搜尋：',
    paginate: {
        first: '第一頁',
        previous: '上一頁',
        next: '下一頁',
        last: '最後一頁',
    },
};

const toBooleanOption = (value, fallback) => {
    if (value === undefined || value === null || value === '') {
        return fallback;
    }

    const normalized = String(value).trim().toLowerCase();
    if (['1', 'true', 'yes', 'on'].includes(normalized)) {
        return true;
    }

    if (['0', 'false', 'no', 'off'].includes(normalized)) {
        return false;
    }

    return fallback;
};

const getTableBooleanOption = (table, key, fallback) => toBooleanOption(table.dataset[key], fallback);

const getTableNumberOption = (table, key, fallback) => {
    const raw = Number(table.dataset[key]);
    if (!Number.isFinite(raw) || raw <= 0) {
        return fallback;
    }

    return Math.floor(raw);
};

const getTableOrderableTargets = (table) => {
    const raw = (table.dataset.dtDisableOrderCols || '').trim();
    if (!raw) {
        return [];
    }

    return raw
        .split(',')
        .map((v) => Number(v.trim()))
        .filter((v) => Number.isInteger(v) && v >= 0);
};

const initDataTable = (table) => {
    if (!(table instanceof HTMLTableElement) || table.dataset.datatable === 'off') {
        return;
    }

    if (table.dataset.datatableReady === '1') {
        return;
    }

    if (typeof DataTable.isDataTable === 'function' && DataTable.isDataTable(table)) {
        table.dataset.datatableReady = '1';
        return;
    }

    const allBodyRows = Array.from(table.querySelectorAll('tbody tr'));
    const ignoreRows = allBodyRows.filter((row) => row.dataset.datatableRow === 'ignore');
    const dataRows = allBodyRows.filter((row) => row.dataset.datatableRow !== 'ignore');
    if (dataRows.length === 0) {
        return;
    }

    ignoreRows.forEach((row) => row.remove());

    const headerColumns = table.tHead?.rows?.[0]?.cells?.length ?? 0;
    if (headerColumns <= 0) {
        return;
    }

    const hasMalformedBodyRow = Array.from(table.querySelectorAll('tbody tr')).some((row) => {
        const cellCount = row.cells?.length ?? 0;
        return cellCount > 0 && cellCount !== headerColumns;
    });

    if (hasMalformedBodyRow) {
        return;
    }

    const paging = getTableBooleanOption(table, 'dtPaging', false);
    const searching = getTableBooleanOption(table, 'dtSearching', true);
    const ordering = getTableBooleanOption(table, 'dtOrdering', true);
    const info = getTableBooleanOption(table, 'dtInfo', false);
    const responsive = getTableBooleanOption(table, 'dtResponsive', true);
    const lengthChange = getTableBooleanOption(table, 'dtLengthChange', false);
    const pageLength = getTableNumberOption(table, 'dtPageLength', 10);
    const disabledOrderCols = getTableOrderableTargets(table);

    const options = {
        responsive,
        paging,
        searching,
        ordering,
        info,
        lengthChange,
        pageLength,
        autoWidth: false,
        language: dataTableLanguage,
    };

    if (disabledOrderCols.length > 0) {
        options.columnDefs = [
            {
                targets: disabledOrderCols,
                orderable: false,
            },
        ];
    }

    const customDom = (table.dataset.dtDom || '').trim();
    if (customDom) {
        options.dom = customDom;
    } else if (!paging && !info && searching) {
        options.dom = 'ft';
    } else if (!paging && !info && !searching) {
        options.dom = 't';
    }

    new DataTable(table, options);
    table.dataset.datatableReady = '1';
};

const bindDataTables = (root = document) => {
    if (!(root instanceof Document || root instanceof Element)) {
        return;
    }

    if (root instanceof Element && root.matches('table[data-datatable]')) {
        initDataTable(root);
    }

    root.querySelectorAll('table[data-datatable]').forEach(initDataTable);
};

const bootDataTables = () => {
    bindDataTables();

    if (!document.body || typeof MutationObserver === 'undefined') {
        return;
    }

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node instanceof Element) {
                    bindDataTables(node);
                }
            });
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true,
    });
};

const findNamedInput = (input, name) => {
    if (!(input instanceof HTMLInputElement) || !name) {
        return null;
    }

    const scopes = [];
    if (input.form instanceof HTMLFormElement) {
        scopes.push(input.form);
    }
    if (input.parentElement instanceof Element) {
        scopes.push(input.parentElement);
    }
    scopes.push(document);

    for (const scope of scopes) {
        const matched = scope.querySelector(`input[name="${name}"]`);
        if (matched instanceof HTMLInputElement) {
            return matched;
        }
    }

    return null;
};

const getCurrentDocumentLang = () => String(document.documentElement?.lang || 'en')
    .trim()
    .toLowerCase()
    .replace('_', '-');

const getFlatpickrLocaleKey = () => {
    const lang = getCurrentDocumentLang();

    if (lang.startsWith('zh-tw') || lang.startsWith('zh-hant')) {
        return 'zh_tw';
    }

    if (lang.startsWith('zh')) {
        return 'zh';
    }

    if (lang.startsWith('vi')) {
        return 'vn';
    }

    return 'en';
};

const getFlatpickrLocale = () => {
    const localeKey = getFlatpickrLocaleKey();
    const localeMap = {
        zh: Mandarin,
        zh_tw: MandarinTraditional,
        vn: Vietnamese,
    };

    return localeMap[localeKey] || flatpickr.l10ns.default || {};
};

const flatpickrLocale = getFlatpickrLocale();

const normalizeFlatpickrInputType = (input) => {
    if (!(input instanceof HTMLInputElement)) {
        return;
    }

    if (input.type === 'date' || input.type === 'datetime-local') {
        input.type = 'text';
    }
};

const syncRangeHiddenFields = (selectedDates, instance, startInput, endInput) => {
    if (!(startInput instanceof HTMLInputElement) || !(endInput instanceof HTMLInputElement)) {
        return;
    }

    if (!Array.isArray(selectedDates) || selectedDates.length === 0) {
        startInput.value = '';
        endInput.value = '';
        return;
    }

    const startDate = selectedDates[0];
    const endDate = selectedDates.length > 1 ? selectedDates[1] : selectedDates[0];
    startInput.value = instance.formatDate(startDate, 'Y-m-d');
    endInput.value = instance.formatDate(endDate, 'Y-m-d');
};

const initRangeDateInput = (input) => {
    if (!(input instanceof HTMLInputElement) || input.dataset.flatpickrReady === '1') {
        return;
    }

    const startName = (input.dataset.rangeStartName || '').trim();
    const endName = (input.dataset.rangeEndName || '').trim();
    const startInput = findNamedInput(input, startName);
    const endInput = findNamedInput(input, endName);
    if (!startInput || !endInput) {
        return;
    }

    input.dataset.flatpickrReady = '1';
    normalizeFlatpickrInputType(input);

    const defaultDates = [];
    if (startInput.value) {
        defaultDates.push(startInput.value);
    }
    if (endInput.value && endInput.value !== startInput.value) {
        defaultDates.push(endInput.value);
    }

    flatpickr(input, {
        mode: 'range',
        dateFormat: 'Y-m-d',
        locale: flatpickrLocale,
        allowInput: true,
        defaultDate: defaultDates.length > 0 ? defaultDates : null,
        onReady: (selectedDates, _dateStr, instance) => {
            syncRangeHiddenFields(selectedDates, instance, startInput, endInput);
        },
        onChange: (selectedDates, _dateStr, instance) => {
            syncRangeHiddenFields(selectedDates, instance, startInput, endInput);
        },
        onClose: (selectedDates, _dateStr, instance) => {
            syncRangeHiddenFields(selectedDates, instance, startInput, endInput);
        },
    });
};

const initDateInput = (input) => {
    if (!(input instanceof HTMLInputElement) || input.dataset.flatpickrReady === '1') {
        return;
    }

    input.dataset.flatpickrReady = '1';
    normalizeFlatpickrInputType(input);

    flatpickr(input, {
        dateFormat: 'Y-m-d',
        locale: flatpickrLocale,
        allowInput: true,
    });
};

const initDateTimeInput = (input) => {
    if (!(input instanceof HTMLInputElement) || input.dataset.flatpickrReady === '1') {
        return;
    }

    input.dataset.flatpickrReady = '1';
    normalizeFlatpickrInputType(input);

    flatpickr(input, {
        enableTime: true,
        time_24hr: true,
        dateFormat: 'Y-m-d\\TH:i',
        locale: flatpickrLocale,
        allowInput: true,
    });
};

const bindFlatpickrInputs = (root = document) => {
    if (!(root instanceof Document || root instanceof Element)) {
        return;
    }

    const bindBySelector = (selector, initializer) => {
        if (root instanceof Element && root.matches(selector)) {
            initializer(root);
        }
        root.querySelectorAll(selector).forEach(initializer);
    };

    bindBySelector('input[data-flatpickr-range]', initRangeDateInput);
    bindBySelector('input[data-flatpickr-datetime], input[type="datetime-local"]', initDateTimeInput);
    bindBySelector('input[data-flatpickr-date], input[type="date"]', initDateInput);
};

const bootFlatpickr = () => {
    bindFlatpickrInputs();

    if (!document.body || typeof MutationObserver === 'undefined') {
        return;
    }

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node instanceof Element) {
                    bindFlatpickrInputs(node);
                }
            });
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true,
    });
};

const bootFrontendEnhancements = () => {
    bootPhoneFormatter();
    bootDataTables();
    bootFlatpickr();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootFrontendEnhancements, { once: true });
} else {
    bootFrontendEnhancements();
}
