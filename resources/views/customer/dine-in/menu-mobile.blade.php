<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $store->name }} 內用點餐</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .cart-fly-clone {
            position: fixed;
            z-index: 80;
            pointer-events: none;
            border-radius: 9999px;
            background: linear-gradient(135deg, #ec9057, #F6AE2D);
            box-shadow: 0 16px 34px rgba(90, 30, 14, 0.24);
            transition:
                transform 620ms cubic-bezier(0.22, 1, 0.36, 1),
                opacity 620ms ease,
                width 620ms cubic-bezier(0.22, 1, 0.36, 1),
                height 620ms cubic-bezier(0.22, 1, 0.36, 1),
                left 620ms cubic-bezier(0.22, 1, 0.36, 1),
                top 620ms cubic-bezier(0.22, 1, 0.36, 1);
        }
    </style>
</head>
<body class="bg-brand-soft/20 text-brand-dark">
    <div class="min-h-screen pb-32">
        <header class="sticky top-0 z-30 border-b border-brand-soft/60 bg-white/95 backdrop-blur">
            <div class="mx-auto max-w-5xl px-4">
                <div class="flex min-h-[160px] flex-col justify-center py-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium uppercase tracking-[0.24em] text-brand-primary">DineFlow</p>
                            <h1 class="mt-2 text-2xl font-bold tracking-tight text-brand-dark">{{ $store->name }}</h1>
                            <p class="mt-1 text-sm text-brand-primary/75">桌號 {{ $table->table_no }}</p>
                            <p class="mt-1 text-sm text-brand-primary/75">營業時間 {{ $store->businessHoursLabel() }}</p>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <a href="{{ route('customer.dinein.cart.show', ['store' => $store, 'table' => $table]) }}" class="inline-flex items-center rounded-2xl border border-brand-soft bg-brand-soft/20 px-4 py-2 text-sm font-semibold text-brand-primary transition hover:bg-brand-highlight/50">查看購物車</a>
                            @if(isset($orderHistory) && $orderHistory->isNotEmpty())
                                <div class="flex flex-wrap justify-end gap-2">
                                    @foreach($orderHistory->take(3) as $historyOrder)
                                        <a href="{{ route('customer.order.success', ['store' => $store, 'order' => $historyOrder]) }}" class="inline-flex items-center rounded-xl border border-brand-soft bg-white px-3 py-1.5 text-xs font-semibold text-brand-primary transition hover:bg-brand-soft/30">狀態 {{ $historyOrder->order_no }}</a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    @if(! $orderingAvailable)
                        <div class="mt-4 rounded-2xl border border-brand-soft bg-brand-soft/35 px-4 py-3 text-sm text-brand-dark">{{ $store->orderingClosedMessage() }}</div>
                    @else
                        <div class="mt-4 rounded-2xl border border-brand-soft/70 bg-brand-soft/20 px-4 py-3 text-sm text-brand-primary/80">選好餐點後加入購物車，再一起送出本桌訂單。</div>
                    @endif

                    @if(session('success'))
                        <div class="mt-4 rounded-2xl border border-brand-accent/30 bg-brand-accent/10 px-4 py-3 text-sm font-medium text-brand-primary">{{ session('success') }}</div>
                    @endif

                    @if(session('error'))
                        <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
                    @endif

                    @if($errors->any())
                        <div class="mt-4 rounded-2xl border border-brand-soft bg-brand-soft/30 px-4 py-3 text-sm text-brand-dark">
                            @foreach($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </header>

        <nav class="sticky top-[160px] z-20 hidden h-16 border-b border-brand-soft/60 bg-white/95 backdrop-blur md:block">
            <div class="mx-auto flex h-full max-w-5xl items-center overflow-x-auto px-4">
                <div class="flex min-w-max gap-2">
                    @foreach($categories as $category)
                        <a href="#category-{{ $category->id }}" class="inline-flex h-10 items-center whitespace-nowrap rounded-full border border-brand-soft/70 bg-brand-soft/20 px-4 text-sm font-medium text-brand-primary transition hover:-translate-y-0.5 hover:border-brand-accent hover:bg-brand-highlight/60">{{ $category->name }}</a>
                    @endforeach
                </div>
            </div>
        </nav>

        <main class="mx-auto max-w-5xl px-4 py-6">
            <div class="relative grid grid-cols-[5.5rem,minmax(0,1fr)] items-start gap-4 md:block">
                <aside class="self-stretch md:hidden">
                    <div class="sticky top-[10.5rem]">
                        <div class="h-[calc(100vh-12rem)] overflow-hidden rounded-[1.75rem] border border-brand-soft/60 bg-white shadow-[0_18px_40px_rgba(90,30,14,0.08)]">
                            <div class="h-full overflow-y-auto p-2">
                                <div class="flex flex-col gap-2">
                                    @foreach($categories as $category)
                                        <a href="#category-{{ $category->id }}" class="rounded-2xl border border-brand-soft/70 bg-brand-soft/15 px-2 py-3 text-center text-xs font-semibold leading-4 text-brand-primary transition hover:border-brand-accent hover:bg-brand-highlight/50">
                                            {{ $category->name }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>

                <div class="min-w-0">
                    @foreach($categories as $category)
                        <section id="category-{{ $category->id }}" class="mb-8 scroll-mt-24 md:scroll-mt-[230px]">
                            <div class="mb-4 flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-accent">Table Menu</p>
                                    <h2 class="mt-2 text-2xl font-bold text-brand-dark">{{ $category->name }}</h2>
                                </div>
                                <span class="text-sm text-brand-primary/70">{{ count($products[$category->id] ?? []) }} 項餐點</span>
                            </div>

                            <div class="grid gap-5 md:grid-cols-2">
                                @forelse(($products[$category->id] ?? collect()) as $product)
                                    @php
                                        $productImage = filled($product->image)
                                            ? (\Illuminate\Support\Str::startsWith($product->image, ['http://', 'https://']) ? $product->image : asset('storage/' . ltrim($product->image, '/')))
                                            : 'https://images.unsplash.com/photo-1515003197210-e0cd71810b5f?auto=format&fit=crop&w=900&q=80';
                                    @endphp
                                    <div class="group overflow-hidden rounded-[1.75rem] border border-brand-soft/60 bg-white shadow-[0_18px_44px_rgba(90,30,14,0.1)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_24px_60px_rgba(90,30,14,0.16)]">
                                        <div class="relative overflow-hidden">
                                            <img src="{{ $productImage }}" alt="{{ $product->name }}" class="h-44 w-full object-cover transition duration-500 group-hover:scale-105">
                                            <div class="absolute inset-0 bg-gradient-to-t from-brand-dark/85 via-brand-dark/20 to-transparent"></div>
                                            <div class="absolute left-4 top-4 inline-flex rounded-full border border-white/20 bg-white/15 px-3 py-1 text-xs font-semibold text-white backdrop-blur">{{ $category->name }}</div>
                                            <div class="absolute bottom-4 right-4 rounded-full bg-brand-highlight px-3 py-1.5 text-sm font-bold text-brand-dark shadow-lg">NT$ {{ number_format($product->price) }}</div>
                                        </div>

                                        <div class="p-5">
                                            <div class="flex items-start justify-between gap-4">
                                                <div class="min-w-0 flex-1">
                                                    <h3 class="text-lg font-semibold text-brand-dark">{{ $product->name }}</h3>
                                                    <p class="mt-2 line-clamp-2 text-sm leading-6 text-brand-primary/75">{{ $product->description ?: '現點現做，適合直接加入本桌訂單。' }}</p>
                                                </div>
                                                @if($product->is_sold_out)
                                                    <span class="shrink-0 rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-600">已售完</span>
                                                @endif
                                            </div>

                                            <div class="mt-5 border-t border-brand-soft/50 pt-4">
                                                @if(! $orderingAvailable)
                                                    <div class="rounded-2xl bg-brand-soft/25 px-3 py-3 text-center text-sm font-medium text-brand-dark">目前不在營業時間，暫停點餐</div>
                                                @elseif($product->is_sold_out)
                                                    <div class="rounded-2xl bg-slate-100 px-3 py-3 text-center text-sm font-medium text-slate-500">本品項目前無法點餐</div>
                                                @else
                                                    <form method="POST" action="{{ route('customer.dinein.cart.items.store', ['store' => $store, 'table' => $table]) }}" class="flex items-center justify-between gap-3" data-add-to-cart-form>
                                                        @csrf
                                                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                                                        <input type="hidden" name="option_payload" value="" data-option-payload>
                                                        <div class="inline-flex h-12 items-center rounded-2xl border border-brand-soft bg-brand-soft/20 p-1 shadow-sm">
                                                            <button type="button" class="flex h-10 w-10 items-center justify-center rounded-xl text-lg font-bold text-brand-primary transition hover:bg-white" data-qty-decrement>-</button>
                                                            <input type="hidden" name="qty" value="1" data-qty-input>
                                                            <span class="flex min-w-[2.8rem] items-center justify-center text-sm font-semibold text-brand-dark" data-qty-display>1</span>
                                                            <button type="button" class="flex h-10 w-10 items-center justify-center rounded-xl text-lg font-bold text-brand-primary transition hover:bg-white" data-qty-increment>+</button>
                                                        </div>
                                                        <button type="submit" class="inline-flex h-12 items-center justify-center rounded-2xl bg-brand-primary px-5 text-sm font-semibold text-white shadow-lg shadow-brand-primary/20 transition hover:-translate-y-0.5 hover:bg-brand-accent hover:text-brand-dark" data-add-to-cart-button data-option-groups='@json($product->option_groups ?? [])' data-product-name="{{ $product->name }}">加入購物車</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-[1.75rem] border border-brand-soft/60 bg-white px-5 py-8 text-center text-sm text-brand-primary/70 shadow-[0_18px_40px_rgba(90,30,14,0.08)] md:col-span-2">這個分類目前還沒有可點的商品。</div>
                                @endforelse
                            </div>
                        </section>
                    @endforeach
                </div>
            </div>
        </main>

        <div class="fixed inset-x-0 bottom-0 z-40 border-t border-brand-soft/60 bg-white/95 px-4 py-4 backdrop-blur">
            <div class="mx-auto flex max-w-5xl items-center justify-between gap-3 rounded-[1.75rem] bg-brand-dark px-4 py-3 text-white shadow-[0_18px_44px_rgba(90,30,14,0.24)] transition-transform duration-200" data-cart-bar>
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-brand-highlight/80">Table {{ $table->table_no }}</p>
                    <p class="text-sm font-semibold">{{ $orderingAvailable ? '可前往購物車送出本桌訂單' : '目前不在營業時間' }}</p>
                    <p class="mt-1 text-xs text-white/70">{{ $cartCount > 0 ? $cartCount . ' 項 | NT$ ' . number_format($cartTotal) : '購物車目前是空的' }}</p>
                    @if(isset($orderHistory) && $orderHistory->isNotEmpty())
                        <div class="mt-1 flex flex-wrap gap-2">
                            @foreach($orderHistory->take(2) as $historyOrder)
                                <a href="{{ route('customer.order.success', ['store' => $store, 'order' => $historyOrder]) }}" class="inline-flex text-xs font-semibold text-brand-highlight underline-offset-2 hover:underline">狀態 {{ $historyOrder->order_no }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
                <a href="{{ route('customer.dinein.cart.show', ['store' => $store, 'table' => $table]) }}" class="inline-flex h-11 items-center justify-center rounded-2xl bg-brand-highlight px-4 text-sm font-semibold text-brand-dark transition hover:bg-brand-soft" data-cart-target>查看購物車{{ $cartCount > 0 ? ' (' . $cartCount . ')' : '' }}</a>
            </div>
        </div>

        <div id="option-modal" class="fixed inset-0 z-[90] hidden items-center justify-center bg-black/45 p-4">
            <div class="w-full max-w-lg rounded-3xl bg-white p-5 shadow-2xl">
                <div class="flex items-center justify-between">
                    <h3 id="option-modal-title" class="text-lg font-bold text-brand-dark">選擇搭配</h3>
                    <button type="button" id="option-modal-close" class="rounded-full p-2 text-slate-500 hover:bg-slate-100">✕</button>
                </div>
                <div id="option-modal-body" class="mt-4 max-h-[60vh] space-y-4 overflow-y-auto"></div>
                <div class="mt-5 flex gap-3">
                    <button type="button" id="option-modal-cancel" class="inline-flex flex-1 items-center justify-center rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">取消</button>
                    <button type="button" id="option-modal-confirm" class="inline-flex flex-1 items-center justify-center rounded-2xl bg-brand-primary px-4 py-3 text-sm font-semibold text-white hover:bg-brand-accent hover:text-brand-dark">確認加入</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    (() => {
        const forms = document.querySelectorAll('[data-add-to-cart-form]');
        const cartTarget = document.querySelector('[data-cart-target]');
        const cartBar = document.querySelector('[data-cart-bar]');
        const modal = document.getElementById('option-modal');
        const modalTitle = document.getElementById('option-modal-title');
        const modalBody = document.getElementById('option-modal-body');
        const modalClose = document.getElementById('option-modal-close');
        const modalCancel = document.getElementById('option-modal-cancel');
        const modalConfirm = document.getElementById('option-modal-confirm');

        let activeForm = null;
        let activeGroups = [];

        const closeModal = () => {
            modal?.classList.add('hidden');
            modal?.classList.remove('flex');
            modalBody.innerHTML = '';
            activeForm = null;
            activeGroups = [];
        };

        const openModal = (form, productName, groups) => {
            activeForm = form;
            activeGroups = groups;
            modalTitle.textContent = `選擇搭配：${productName}`;
            modalBody.innerHTML = '';

            groups.forEach((group) => {
                const groupId = String(group.id || '');
                if (!groupId) {
                    return;
                }

                const type = group.type === 'multiple' ? 'multiple' : 'single';
                const required = !!group.required;
                const maxSelect = Number(group.max_select || 99);

                const wrapper = document.createElement('div');
                wrapper.className = 'rounded-2xl border border-slate-200 p-4';
                wrapper.dataset.groupId = groupId;
                wrapper.dataset.groupType = type;
                wrapper.dataset.groupRequired = required ? '1' : '0';
                wrapper.dataset.groupMax = String(maxSelect);

                const title = document.createElement('div');
                title.className = 'mb-2 text-sm font-semibold text-slate-800';
                title.textContent = `${group.name || groupId}${required ? '（必選）' : ''}`;
                wrapper.appendChild(title);

                const choices = Array.isArray(group.choices) ? group.choices : [];
                choices.forEach((choice, index) => {
                    const choiceId = String(choice.id || '');
                    if (!choiceId) {
                        return;
                    }

                    const row = document.createElement('label');
                    row.className = 'mb-2 flex cursor-pointer items-center justify-between rounded-xl border border-slate-200 px-3 py-2 text-sm last:mb-0 hover:bg-slate-50';

                    const left = document.createElement('div');
                    left.className = 'flex items-center gap-2';

                    const input = document.createElement('input');
                    input.type = type === 'single' ? 'radio' : 'checkbox';
                    input.name = `opt_${groupId}` + (type === 'multiple' ? '[]' : '');
                    input.value = choiceId;
                    input.dataset.choiceName = String(choice.name || choiceId);
                    input.dataset.choicePrice = String(Number(choice.price || 0));
                    if (required && type === 'single' && index === 0) {
                        input.checked = true;
                    }

                    left.appendChild(input);
                    const text = document.createElement('span');
                    text.textContent = String(choice.name || choiceId);
                    left.appendChild(text);

                    const price = document.createElement('span');
                    const p = Number(choice.price || 0);
                    price.className = 'text-xs font-semibold ' + (p > 0 ? 'text-brand-primary' : 'text-slate-500');
                    price.textContent = p > 0 ? `+NT$ ${p}` : '免費';

                    row.appendChild(left);
                    row.appendChild(price);
                    wrapper.appendChild(row);
                });

                modalBody.appendChild(wrapper);
            });

            modal?.classList.remove('hidden');
            modal?.classList.add('flex');
        };

        modalClose?.addEventListener('click', closeModal);
        modalCancel?.addEventListener('click', closeModal);

        modalConfirm?.addEventListener('click', () => {
            if (!activeForm) {
                closeModal();
                return;
            }

            const payload = {};

            for (const group of activeGroups) {
                const groupId = String(group.id || '');
                if (!groupId) {
                    continue;
                }

                const wrapper = modalBody.querySelector(`[data-group-id="${groupId}"]`);
                if (!wrapper) {
                    continue;
                }

                const type = wrapper.dataset.groupType;
                const required = wrapper.dataset.groupRequired === '1';
                const maxSelect = Number(wrapper.dataset.groupMax || 99);

                const checked = Array.from(wrapper.querySelectorAll('input:checked')).map((input) => input.value);

                if (required && checked.length === 0) {
                    alert(`${group.name || groupId} 為必選`);
                    return;
                }

                if (type === 'multiple' && checked.length > maxSelect) {
                    alert(`${group.name || groupId} 最多可選 ${maxSelect} 項`);
                    return;
                }

                if (checked.length > 0) {
                    payload[groupId] = type === 'single' ? [checked[0]] : checked;
                }
            }

            const payloadInput = activeForm.querySelector('[data-option-payload]');
            if (payloadInput) {
                payloadInput.value = JSON.stringify(payload);
            }

            const confirmedForm = activeForm;
            confirmedForm.dataset.confirmed = '1';
            closeModal();
            confirmedForm.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
        });

        forms.forEach((form) => {
            const input = form.querySelector('[data-qty-input]');
            const display = form.querySelector('[data-qty-display]');
            const decrement = form.querySelector('[data-qty-decrement]');
            const increment = form.querySelector('[data-qty-increment]');
            const submitButton = form.querySelector('[data-add-to-cart-button]');

            const syncQty = (nextValue) => {
                const safeValue = Math.max(1, Number(nextValue) || 1);
                input.value = safeValue;
                display.textContent = safeValue;
                decrement.disabled = safeValue <= 1;
                decrement.classList.toggle('opacity-40', safeValue <= 1);
            };

            decrement?.addEventListener('click', () => syncQty(Number(input.value) - 1));
            increment?.addEventListener('click', () => syncQty(Number(input.value) + 1));
            syncQty(input.value);

            form.addEventListener('submit', (event) => {
                if (!cartTarget || !submitButton || form.dataset.animating === 'true') {
                    return;
                }

                const groupsRaw = submitButton.dataset.optionGroups || '[]';
                let groups = [];
                try {
                    groups = JSON.parse(groupsRaw);
                } catch (_e) {
                    groups = [];
                }

                if (Array.isArray(groups) && groups.length > 0 && form.dataset.confirmed !== '1') {
                    event.preventDefault();
                    openModal(form, submitButton.dataset.productName || '商品', groups);
                    return;
                }

                form.dataset.confirmed = '';

                event.preventDefault();
                form.dataset.animating = 'true';

                const sourceRect = submitButton.getBoundingClientRect();
                const targetRect = cartTarget.getBoundingClientRect();
                const clone = document.createElement('div');
                clone.className = 'cart-fly-clone';
                clone.style.left = `${sourceRect.left + sourceRect.width / 2 - 12}px`;
                clone.style.top = `${sourceRect.top + sourceRect.height / 2 - 12}px`;
                clone.style.width = '24px';
                clone.style.height = '24px';
                clone.style.opacity = '1';
                document.body.appendChild(clone);

                cartBar?.classList.add('scale-[1.02]');

                requestAnimationFrame(() => {
                    clone.style.left = `${targetRect.left + targetRect.width / 2 - 10}px`;
                    clone.style.top = `${targetRect.top + targetRect.height / 2 - 10}px`;
                    clone.style.width = '20px';
                    clone.style.height = '20px';
                    clone.style.opacity = '0.2';
                    clone.style.transform = 'scale(0.8)';
                });

                window.setTimeout(() => {
                    clone.remove();
                    cartBar?.classList.remove('scale-[1.02]');
                    form.submit();
                }, 620);
            });
        });
    })();
    </script>
</body>
</html>
