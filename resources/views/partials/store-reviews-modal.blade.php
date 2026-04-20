<div
    id="store-reviews-modal"
    class="fixed inset-0 z-[70] hidden"
    aria-hidden="true"
>
    <div class="absolute inset-0 bg-brand-dark/55 backdrop-blur-sm" data-store-reviews-close></div>
    <div class="relative mx-auto flex min-h-screen w-full max-w-3xl items-center justify-center px-4 py-8">
        <section
            role="dialog"
            aria-modal="true"
            aria-labelledby="store-reviews-modal-title"
            class="max-h-[88vh] w-full overflow-hidden rounded-[1.75rem] border border-brand-soft/70 bg-white shadow-[0_28px_80px_rgba(40,20,12,0.24)]"
        >
            <header class="flex items-center justify-between border-b border-brand-soft/60 px-6 py-4">
                <h2 id="store-reviews-modal-title" class="text-xl font-bold text-brand-dark"></h2>
                <button
                    type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-brand-soft/70 text-brand-primary transition hover:bg-brand-soft/20"
                    aria-label="Close reviews"
                    data-store-reviews-close
                >
                    <span aria-hidden="true" class="text-2xl leading-none">&times;</span>
                </button>
            </header>

            <div id="store-reviews-modal-body" class="max-h-[72vh] overflow-y-auto p-6"></div>
        </section>
    </div>
</div>

<script>
(() => {
    if (window.__dineflowStoreReviewsModalBound) {
        return;
    }

    window.__dineflowStoreReviewsModalBound = true;

    const modal = document.getElementById('store-reviews-modal');
    const modalTitle = document.getElementById('store-reviews-modal-title');
    const modalBody = document.getElementById('store-reviews-modal-body');
    const closeButtons = document.querySelectorAll('[data-store-reviews-close]');
    const reviewButtons = document.querySelectorAll('[data-store-review-trigger]');
    const reviewUnit = @json(__('home.store_reviews_unit'));
    const emptyMessage = @json(__('home.store_rating_empty'));
    const customerLabel = @json(__('home.role_customer'));
    const loadingMessage = 'Loading...';
    const fallbackErrorMessage = 'Unable to load reviews right now.';

    if (!modal || !modalTitle || !modalBody || reviewButtons.length === 0) {
        return;
    }

    let activeTrigger = null;

    const escapeHtml = (value) => {
        if (value === null || value === undefined) {
            return '';
        }

        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    };

    const ratingStars = (rating) => {
        const normalized = Math.max(0, Math.min(5, Number(rating) || 0));
        const starPath = 'M12 3.75l2.67 5.41 5.97.87-4.32 4.21 1.02 5.95L12 17.41l-5.34 2.8 1.02-5.95-4.32-4.21 5.97-.87L12 3.75z';

        return Array.from({ length: 5 }, (_, index) => {
            const fill = Math.max(0, Math.min(1, normalized - index));

            return `
                <span class="relative inline-flex h-4 w-4 shrink-0">
                    <svg class="h-4 w-4 text-amber-100" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="${starPath}" />
                    </svg>
                    ${fill > 0 ? `
                        <span class="absolute inset-y-0 left-0 overflow-hidden" style="width: ${fill * 100}%">
                            <svg class="h-4 w-4 text-amber-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="${starPath}" />
                            </svg>
                        </span>
                    ` : ''}
                    <svg class="pointer-events-none absolute inset-0 h-4 w-4 text-amber-500/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.35" aria-hidden="true">
                        <path d="${starPath}" />
                    </svg>
                </span>
            `;
        }).join('');
    };

    const formatDate = (value) => {
        if (!value) {
            return '';
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return '';
        }

        return date.toLocaleDateString();
    };

    const renderState = (text, withSpinner = false) => {
        modalBody.innerHTML = `
            <div class="flex min-h-44 flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-brand-soft/80 bg-brand-soft/10 p-6 text-center text-base text-brand-primary/75">
                ${withSpinner ? '<span class="inline-flex h-8 w-8 animate-spin rounded-full border-2 border-brand-primary/20 border-t-brand-primary"></span>' : ''}
                <p>${escapeHtml(text)}</p>
            </div>
        `;
    };

    const renderReviews = (reviews) => {
        if (!Array.isArray(reviews) || reviews.length === 0) {
            renderState(emptyMessage);
            return;
        }

        modalBody.innerHTML = `
            <div class="space-y-3">
                ${reviews.map((review) => {
                    const authorName = review.customer_name ? String(review.customer_name) : customerLabel;
                    const dateText = formatDate(review.created_at);
                    const metaText = [authorName, dateText].filter((item) => item && item.trim() !== '').join(' - ');

                    return `
                        <article class="rounded-2xl border border-brand-soft/70 bg-brand-soft/10 p-4">
                            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                <div class="inline-flex items-center gap-2">
                                    <div class="inline-flex items-center gap-0.5">${ratingStars(review.rating)}</div>
                                    <span class="text-sm font-semibold text-amber-600">${escapeHtml(Number(review.rating || 0).toFixed(1))}</span>
                                </div>
                                <p class="text-sm text-brand-primary/70">${escapeHtml(metaText)}</p>
                            </div>
                            <p class="text-base leading-7 text-brand-dark">${escapeHtml(review.comment || '')}</p>
                        </article>
                    `;
                }).join('')}
            </div>
        `;
    };

    const openModal = () => {
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');

        if (activeTrigger) {
            activeTrigger.focus();
            activeTrigger = null;
        }
    };

    reviewButtons.forEach((button) => {
        button.addEventListener('click', async () => {
            const reviewsUrl = button.dataset.storeReviewUrl || '';
            const storeName = button.dataset.storeName || '';
            const reviewCount = button.dataset.reviewCount || '0';
            activeTrigger = button;

            modalTitle.textContent = `${storeName} (${reviewCount} ${reviewUnit})`;
            openModal();
            renderState(loadingMessage, true);

            if (!reviewsUrl) {
                renderState(fallbackErrorMessage);
                return;
            }

            try {
                const response = await fetch(reviewsUrl, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error(`Request failed: ${response.status}`);
                }

                const payload = await response.json();
                renderReviews(payload?.reviews || []);
            } catch (error) {
                console.error(error);
                renderState(fallbackErrorMessage);
            }
        });
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
})();
</script>
