import './bootstrap';

import Alpine from 'alpinejs';

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

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootPhoneFormatter, { once: true });
} else {
    bootPhoneFormatter();
}
