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

const setInputValue = (input, value) => {
    if (!(input instanceof HTMLInputElement)) {
        return;
    }

    const nextValue = String(value ?? '');
    if (input.value === nextValue) {
        return;
    }

    input.value = nextValue;
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
};

const syncRangeHiddenFields = (selectedDates, instance, startInput, endInput, outputFormat) => {
    if (!(startInput instanceof HTMLInputElement) || !(endInput instanceof HTMLInputElement)) {
        return;
    }

    const format = outputFormat || 'Y-m-d';
    if (!Array.isArray(selectedDates) || selectedDates.length === 0) {
        setInputValue(startInput, '');
        setInputValue(endInput, '');
        return;
    }

    const startDate = selectedDates[0];
    const endDate = selectedDates.length > 1 ? selectedDates[1] : selectedDates[0];
    setInputValue(startInput, instance.formatDate(startDate, format));
    setInputValue(endInput, instance.formatDate(endDate, format));
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
            syncRangeHiddenFields(selectedDates, instance, startInput, endInput, 'Y-m-d');
        },
        onChange: (selectedDates, _dateStr, instance) => {
            syncRangeHiddenFields(selectedDates, instance, startInput, endInput, 'Y-m-d');
        },
        onClose: (selectedDates, _dateStr, instance) => {
            syncRangeHiddenFields(selectedDates, instance, startInput, endInput, 'Y-m-d');
        },
    });
};

const initRangeDateTimeInput = (input) => {
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

    const normalizeDefaultDate = (value) => String(value || '').replace('T', ' ');
    const defaultDates = [];
    if (startInput.value) {
        defaultDates.push(normalizeDefaultDate(startInput.value));
    }
    if (endInput.value && endInput.value !== startInput.value) {
        defaultDates.push(normalizeDefaultDate(endInput.value));
    }

    flatpickr(input, {
        mode: 'range',
        enableTime: true,
        time_24hr: true,
        dateFormat: 'Y-m-d H:i',
        locale: flatpickrLocale,
        allowInput: true,
        defaultDate: defaultDates.length > 0 ? defaultDates : null,
        onReady: (selectedDates, _dateStr, instance) => {
            syncRangeHiddenFields(selectedDates, instance, startInput, endInput, 'Y-m-d\\TH:i');
        },
        onChange: (selectedDates, _dateStr, instance) => {
            syncRangeHiddenFields(selectedDates, instance, startInput, endInput, 'Y-m-d\\TH:i');
        },
        onClose: (selectedDates, _dateStr, instance) => {
            syncRangeHiddenFields(selectedDates, instance, startInput, endInput, 'Y-m-d\\TH:i');
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

    bindBySelector('input[data-flatpickr-datetime-range]', initRangeDateTimeInput);
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

const getFullscreenElement = () => document.fullscreenElement
    || document.webkitFullscreenElement
    || document.msFullscreenElement
    || null;

const isVideoFullscreen = (video) => {
    if (!(video instanceof HTMLVideoElement)) {
        return false;
    }

    return getFullscreenElement() === video || video.webkitDisplayingFullscreen === true;
};

const syncMarketingVideoState = (root, video, playToggle, muteToggle, fullscreenToggle) => {
    const isPaused = video.paused;
    const isMuted = video.muted;
    const isFullscreen = isVideoFullscreen(video);
    const playLabel = isPaused ? (root.dataset.labelPlay || 'Play video') : (root.dataset.labelPause || 'Pause video');
    const muteLabel = isMuted ? (root.dataset.labelUnmute || 'Unmute video') : (root.dataset.labelMute || 'Mute video');
    const fullscreenLabel = isFullscreen
        ? (root.dataset.labelFullscreenExit || 'Exit fullscreen')
        : (root.dataset.labelFullscreenEnter || 'Enter fullscreen');

    root.classList.toggle('is-paused', isPaused);
    root.classList.toggle('is-playing', !isPaused);
    root.classList.toggle('is-muted', isMuted);
    root.classList.toggle('is-fullscreen', isFullscreen);
    video.controls = isFullscreen;
    video.style.objectFit = isFullscreen ? 'contain' : '';
    video.style.background = isFullscreen ? '#000' : '';
    video.style.transform = isFullscreen ? 'none' : '';
    video.style.filter = isFullscreen ? 'none' : '';

    if (playToggle instanceof HTMLButtonElement) {
        playToggle.setAttribute('aria-label', playLabel);
        playToggle.setAttribute('title', playLabel);
        const labelNode = playToggle.querySelector('[data-video-toggle-play-label]');
        if (labelNode) {
            labelNode.textContent = playLabel;
        }
    }

    if (muteToggle instanceof HTMLButtonElement) {
        muteToggle.setAttribute('aria-label', muteLabel);
        muteToggle.setAttribute('title', muteLabel);
        const labelNode = muteToggle.querySelector('[data-video-toggle-mute-label]');
        if (labelNode) {
            labelNode.textContent = muteLabel;
        }
    }

    if (fullscreenToggle instanceof HTMLButtonElement) {
        fullscreenToggle.setAttribute('aria-label', fullscreenLabel);
        fullscreenToggle.setAttribute('title', fullscreenLabel);
        const labelNode = fullscreenToggle.querySelector('[data-video-toggle-fullscreen-label]');
        if (labelNode) {
            labelNode.textContent = fullscreenLabel;
        }
    }
};

const initMarketingVideo = (root) => {
    if (!(root instanceof Element) || root.dataset.marketingVideoReady === '1') {
        return;
    }

    const video = root.querySelector('[data-marketing-video]');
    if (!(video instanceof HTMLVideoElement)) {
        return;
    }

    root.dataset.marketingVideoReady = '1';

    const playToggle = root.querySelector('[data-video-toggle-play]');
    const muteToggle = root.querySelector('[data-video-toggle-mute]');
    const fullscreenToggle = root.querySelector('[data-video-toggle-fullscreen]');
    const stage = root.querySelector('[data-marketing-video-stage]');
    const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const autoplayEnabled = root.dataset.videoAutoplay !== 'false' && !prefersReducedMotion;

    const playVideo = () => {
        root.dataset.userPaused = '0';
        const result = video.play();
        if (result && typeof result.catch === 'function') {
            result.catch(() => {
                syncMarketingVideoState(root, video, playToggle, muteToggle, fullscreenToggle);
            });
        }
    };

    const pauseVideo = (userInitiated = false) => {
        if (userInitiated) {
            root.dataset.userPaused = '1';
        }
        video.pause();
    };

    const handleStageToggle = () => {
        if (video.paused) {
            playVideo();
            return;
        }

        pauseVideo(true);
    };

    if (video.readyState >= 2) {
        root.classList.add('is-ready');
    }

    video.addEventListener('loadeddata', () => {
        root.classList.add('is-ready');
        syncMarketingVideoState(root, video, playToggle, muteToggle, fullscreenToggle);
    });

    video.addEventListener('play', () => {
        syncMarketingVideoState(root, video, playToggle, muteToggle, fullscreenToggle);
    });

    video.addEventListener('pause', () => {
        syncMarketingVideoState(root, video, playToggle, muteToggle, fullscreenToggle);
    });

    video.addEventListener('volumechange', () => {
        syncMarketingVideoState(root, video, playToggle, muteToggle, fullscreenToggle);
    });

    if (playToggle instanceof HTMLButtonElement) {
        playToggle.addEventListener('click', (event) => {
            event.preventDefault();
            handleStageToggle();
        });
    }

    if (muteToggle instanceof HTMLButtonElement) {
        muteToggle.addEventListener('click', (event) => {
            event.preventDefault();
            video.muted = !video.muted;
            syncMarketingVideoState(root, video, playToggle, muteToggle, fullscreenToggle);
        });
    }

    const requestFullscreen = () => {
        if (typeof video.requestFullscreen === 'function') {
            return video.requestFullscreen();
        }
        if (typeof video.webkitEnterFullscreen === 'function') {
            video.webkitEnterFullscreen();
            return null;
        }
        if (typeof video.webkitRequestFullscreen === 'function') {
            return video.webkitRequestFullscreen();
        }
        if (typeof video.msRequestFullscreen === 'function') {
            return video.msRequestFullscreen();
        }
        return null;
    };

    const exitFullscreen = () => {
        if (typeof video.webkitExitFullscreen === 'function' && video.webkitDisplayingFullscreen) {
            video.webkitExitFullscreen();
            return null;
        }
        if (typeof document.exitFullscreen === 'function') {
            return document.exitFullscreen();
        }
        if (typeof document.webkitExitFullscreen === 'function') {
            return document.webkitExitFullscreen();
        }
        if (typeof document.msExitFullscreen === 'function') {
            return document.msExitFullscreen();
        }
        return null;
    };

    if (fullscreenToggle instanceof HTMLButtonElement) {
        fullscreenToggle.addEventListener('click', async (event) => {
            event.preventDefault();

            try {
                if (isVideoFullscreen(video)) {
                    await exitFullscreen();
                } else {
                    await requestFullscreen();
                }
            } catch (_error) {
                // Ignore rejected fullscreen requests and keep the current state.
            } finally {
                syncMarketingVideoState(root, video, playToggle, muteToggle, fullscreenToggle);
            }
        });
    }

    ['fullscreenchange', 'webkitfullscreenchange', 'msfullscreenchange'].forEach((eventName) => {
        document.addEventListener(eventName, () => {
            syncMarketingVideoState(root, video, playToggle, muteToggle, fullscreenToggle);
        });
    });

    if (stage instanceof Element) {
        stage.addEventListener('click', (event) => {
            if (event.target instanceof Element && event.target.closest('button')) {
                return;
            }

            handleStageToggle();
        });
    }

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    if (autoplayEnabled && root.dataset.userPaused !== '1') {
                        playVideo();
                    }
                    return;
                }

                if (!video.paused) {
                    video.pause();
                }
            });
        }, {
            threshold: 0.45,
        });

        observer.observe(root);
    } else if (autoplayEnabled) {
        playVideo();
    }

    if (autoplayEnabled) {
        playVideo();
    }

    syncMarketingVideoState(root, video, playToggle, muteToggle, fullscreenToggle);
};

const bindMarketingVideos = (root = document) => {
    if (!(root instanceof Document || root instanceof Element)) {
        return;
    }

    if (root instanceof Element && root.matches('[data-marketing-video-root]')) {
        initMarketingVideo(root);
    }

    root.querySelectorAll('[data-marketing-video-root]').forEach(initMarketingVideo);
};

const bootMarketingVideos = () => {
    bindMarketingVideos();

    if (!document.body || typeof MutationObserver === 'undefined') {
        return;
    }

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node instanceof Element) {
                    bindMarketingVideos(node);
                }
            });
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true,
    });
};

const getGlobalRequestAlert = () => {
    const modal = document.getElementById('global-request-alert');
    if (!(modal instanceof HTMLElement)) {
        return null;
    }

    return {
        modal,
        title: modal.querySelector('[data-global-request-alert-title]'),
        subtitle: modal.querySelector('[data-global-request-alert-subtitle]'),
        message: modal.querySelector('[data-global-request-alert-message]'),
        closeButtons: modal.querySelectorAll('[data-global-request-alert-close], [data-global-request-alert-ok]'),
    };
};

const getGlobalRequestAlertText = () => getGlobalRequestAlert()?.modal?.dataset || {};

const interpolateTemplate = (template, replacements) => Object.entries(replacements).reduce(
    (value, [key, replacement]) => value.replaceAll(key, String(replacement)),
    template,
);

const showGlobalRequestAlert = (variant, message, subtitle = null) => {
    const alert = getGlobalRequestAlert();
    if (!alert) {
        window.alert(message);
        return;
    }

    const isSuccess = variant === 'success';
    const defaultSubtitle = alert.modal.dataset.subtitle || subtitle;
    if (alert.title) {
        alert.title.textContent = isSuccess
            ? (alert.modal.dataset.titleSuccess || 'Success')
            : (alert.modal.dataset.titleError || 'Request failed');
        alert.title.className = `text-base font-bold ${isSuccess ? 'text-emerald-800' : 'text-rose-800'}`;
    }
    if (alert.subtitle) {
        alert.subtitle.textContent = subtitle || defaultSubtitle;
    }
    if (alert.message) {
        alert.message.textContent = message;
    }

    alert.modal.classList.remove('hidden');
    alert.modal.classList.add('flex');
    document.body.classList.add('overflow-y-hidden');
};

const hideGlobalRequestAlert = () => {
    const alert = getGlobalRequestAlert();
    if (!alert) {
        return;
    }

    alert.modal.classList.add('hidden');
    alert.modal.classList.remove('flex');
    document.body.classList.remove('overflow-y-hidden');
};

const extractRequestMessage = async (response) => {
    const contentType = response.headers.get('content-type') || '';

    if (contentType.includes('application/json')) {
        const payload = await response.json().catch(() => null);
        if (payload?.message) {
            return String(payload.message);
        }

        const errors = payload?.errors;
        if (errors && typeof errors === 'object') {
            const flattened = Object.values(errors).flat().filter(Boolean);
            if (flattened.length > 0) {
                return flattened.join('\n');
            }
        }

        if (payload?.error) {
            return String(payload.error);
        }
    }

    const text = await response.text().catch(() => '');
    const trimmed = text.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
    if (trimmed) {
        return trimmed.slice(0, 700);
    }

    const template = getGlobalRequestAlertText().httpErrorTemplate || 'HTTP __STATUS__ __STATUS_TEXT__';

    return interpolateTemplate(template, {
        __STATUS__: response.status,
        __STATUS_TEXT__: response.statusText || 'Error',
    });
};

const shouldInterceptForm = (form) => {
    if (!(form instanceof HTMLFormElement)) {
        return false;
    }

    if (form.dataset.noGlobalErrorModal === 'true' || form.dataset.noGlobalAjax === 'true') {
        return false;
    }

    const method = String(form.getAttribute('method') || 'GET').toUpperCase();
    if (method === 'GET') {
        return false;
    }

    const target = String(form.getAttribute('target') || '').trim();
    if (target && target !== '_self') {
        return false;
    }

    const action = form.getAttribute('action') || window.location.href;
    try {
        const url = new URL(action, window.location.href);
        return url.origin === window.location.origin;
    } catch (_error) {
        return false;
    }
};

const bootGlobalRequestHandling = () => {
    const alert = getGlobalRequestAlert();
    alert?.closeButtons.forEach((button) => button.addEventListener('click', hideGlobalRequestAlert));
    alert?.modal.addEventListener('click', (event) => {
        if (event.target === alert.modal) {
            hideGlobalRequestAlert();
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            hideGlobalRequestAlert();
        }
    });

    document.addEventListener('submit', async (event) => {
        if (event.defaultPrevented || !shouldInterceptForm(event.target)) {
            return;
        }

        const form = event.target;
        event.preventDefault();

        const submitter = event.submitter instanceof HTMLElement
            ? event.submitter
            : form.querySelector('button[type="submit"], input[type="submit"]');
        const originalText = submitter instanceof HTMLButtonElement ? submitter.textContent : null;
        const alertText = getGlobalRequestAlertText();

        let formData;
        try {
            formData = new FormData(form, submitter instanceof HTMLElement ? submitter : undefined);
        } catch (_error) {
            formData = new FormData(form);
        }

        if (
            submitter instanceof HTMLElement
            && submitter.getAttribute('name')
            && !formData.has(submitter.getAttribute('name'))
        ) {
            formData.append(
                submitter.getAttribute('name'),
                submitter.getAttribute('value') || '',
            );
        }

        if (submitter instanceof HTMLButtonElement) {
            submitter.disabled = true;
            submitter.textContent = submitter.dataset.loadingText || form.dataset.loadingText || originalText || alertText.working || 'Working...';
            submitter.classList.add('opacity-70', 'cursor-wait');
        }

        try {
            const response = await fetch(form.action || window.location.href, {
                method: String(form.method || 'POST').toUpperCase(),
                headers: {
                    Accept: 'application/json, text/html;q=0.9',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
                credentials: 'same-origin',
                redirect: 'follow',
            });

            if (!response.ok) {
                const message = await extractRequestMessage(response);
                showGlobalRequestAlert('error', message);
                return;
            }

            if (response.redirected) {
                window.location.assign(response.url);
                return;
            }

            const contentType = response.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
                const payload = await response.json().catch(() => null);
                if (payload?.ok === false) {
                    showGlobalRequestAlert('error', payload?.message || alertText.actionFailed || 'Action failed.');
                    return;
                }

                if (payload?.message) {
                    showGlobalRequestAlert('success', String(payload.message));
                }

                if (payload?.redirect) {
                    window.location.assign(String(payload.redirect));
                    return;
                }

                if (payload?.reload) {
                    setTimeout(() => window.location.reload(), 700);
                }

                return;
            }

            window.location.reload();
        } catch (error) {
            showGlobalRequestAlert(
                'error',
                error?.message || alertText.networkError || 'Network error. Please try again.',
                alertText.titleError || 'Network error',
            );
        } finally {
            if (submitter instanceof HTMLButtonElement) {
                submitter.disabled = false;
                submitter.textContent = originalText;
                submitter.classList.remove('opacity-70', 'cursor-wait');
            }
        }
    });
};

const bootFrontendEnhancements = () => {
    bootPhoneFormatter();
    bootDataTables();
    bootFlatpickr();
    bootMarketingVideos();
    bootGlobalRequestHandling();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootFrontendEnhancements, { once: true });
} else {
    bootFrontendEnhancements();
}
