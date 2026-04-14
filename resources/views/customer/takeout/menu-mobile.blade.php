@extends('layouts.app')

@section('content')
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

<div class="min-h-screen bg-brand-soft/20">
    <div class="mx-auto max-w-7xl px-4 py-8 pb-32 sm:px-6 lg:px-8">
        <div class="mb-8 overflow-hidden rounded-[2rem] border border-brand-soft/60 bg-white shadow-[0_24px_60px_rgba(90,30,14,0.12)]">
            <div class="relative isolate overflow-hidden bg-brand-dark px-6 py-8 text-white sm:px-8">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(255,214,112,0.28),_transparent_34%),linear-gradient(135deg,_rgba(90,30,14,0.96),_rgba(236,144,87,0.88))]"></div>
                <div class="absolute -right-12 -top-10 h-36 w-36 rounded-full bg-brand-highlight/20 blur-3xl"></div>
                <div class="absolute -bottom-14 left-10 h-32 w-32 rounded-full bg-brand-accent/20 blur-3xl"></div>
                <div class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-3xl">
                        <a href="{{ route('home') }}" class="inline-flex items-center text-sm font-medium text-white/70 transition hover:text-white">返回首頁</a>
                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold tracking-[0.2em] text-brand-highlight">外帶</span>
                            <span class="inline-flex rounded-full border border-brand-soft/30 bg-white/10 px-3 py-1 text-xs font-semibold text-white/80">營業時間 {{ $store->businessHoursLabel() }}</span>
                        </div>
                        <h1 class="mt-5 text-3xl font-bold tracking-tight sm:text-4xl">{{ $store->name }}</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-white/75 sm:text-base">{{ $store->description ?: '選好餐點後即可加入購物車，快速完成外帶點餐。' }}</p>
                    </div>

                    <div class="hidden md:flex">
                        <div class="flex gap-3">
                            @if(isset($orderHistory) && $orderHistory->isNotEmpty())
                                <div class="flex items-center gap-2">
                                    @foreach($orderHistory->take(3) as $historyOrder)
                                        <a href="{{ route('customer.order.success', ['store' => $store, 'order' => $historyOrder]) }}" class="inline-flex items-center justify-center rounded-2xl border border-white/25 bg-white/10 px-4 py-3 text-xs font-semibold text-white transition hover:-translate-y-0.5 hover:bg-white/20">狀態 {{ $historyOrder->order_no }}</a>
                                    @endforeach
                                </div>
                            @endif
                            <a href="{{ route('customer.takeout.cart.show', ['store' => $store]) }}" class="inline-flex items-center justify-center rounded-2xl bg-brand-highlight px-5 py-3 text-sm font-semibold text-brand-dark shadow-lg shadow-brand-highlight/30 transition hover:-translate-y-0.5 hover:bg-brand-soft">查看購物車</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(! $orderingAvailable)
            <div class="mb-6 rounded-2xl border border-brand-soft bg-brand-soft/35 px-4 py-3 text-sm text-brand-dark shadow-sm">{{ $store->orderingClosedMessage() }}</div>
        @endif

        @if(session('success'))
            <div class="mb-6 rounded-2xl border border-brand-accent/30 bg-brand-accent/10 px-4 py-3 text-sm text-brand-primary shadow-sm">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 shadow-sm">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="mb-6 rounded-2xl border border-brand-soft bg-brand-soft/30 px-4 py-4 text-sm text-brand-dark shadow-sm">
                <div class="mb-2 font-semibold">請先確認以下資訊</div>
                <ul class="space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($categories->isEmpty())
            <div class="rounded-[2rem] border border-brand-soft/60 bg-white px-6 py-16 text-center shadow-[0_20px_40px_rgba(90,30,14,0.08)]">
                <h2 class="text-2xl font-bold text-brand-dark">目前還沒有上架商品</h2>
                <p class="mt-3 text-brand-primary/80">請稍後再回來看看，或聯繫店家確認菜單。</p>
            </div>
        @else
            <div class="mb-8 hidden overflow-x-auto rounded-[1.75rem] border border-brand-soft/60 bg-white px-4 py-4 shadow-[0_12px_32px_rgba(90,30,14,0.08)] md:block">
                <div class="flex min-w-max items-center gap-3">
                    @foreach($categories as $category)
                        <a href="#category-{{ $category->id }}" class="inline-flex rounded-full border border-brand-soft/70 bg-brand-soft/20 px-4 py-2 text-sm font-medium text-brand-primary transition hover:-translate-y-0.5 hover:border-brand-accent hover:bg-brand-highlight/60">{{ $category->name }}</a>
                    @endforeach
                </div>
            </div>

            <div class="relative grid grid-cols-[5.5rem,minmax(0,1fr)] items-start gap-4 md:block">
                <aside class="self-stretch md:hidden">
                    <div class="sticky top-5">
                        <div class="h-[calc(100vh-2.5rem)] overflow-hidden rounded-[1.75rem] border border-brand-soft/60 bg-white shadow-[0_18px_40px_rgba(90,30,14,0.08)]">
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

                <div class="min-w-0 space-y-10">
                    @foreach($categories as $category)
                        <section id="category-{{ $category->id }}" class="scroll-mt-24 md:scroll-mt-24">
                            <div class="mb-5 flex items-end justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-accent">Menu Section</p>
                                    <h2 class="mt-2 text-2xl font-bold tracking-tight text-brand-dark">{{ $category->name }}</h2>
                                    <p class="mt-1 text-sm text-brand-primary/70">{{ $category->products->count() }} 項餐點</p>
                                </div>
                            </div>

                            @if($category->products->count())
                                <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                                    @foreach($category->products as $product)
                                        @php
                                            $productImage = filled($product->image)
                                                ? (\Illuminate\Support\Str::startsWith($product->image, ['http://', 'https://']) ? $product->image : asset('storage/' . ltrim($product->image, '/')))
                                                : 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=900&q=80';
                                        @endphp
                                        <div class="group overflow-hidden rounded-[1.75rem] border border-brand-soft/60 bg-white shadow-[0_18px_44px_rgba(90,30,14,0.1)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_24px_60px_rgba(90,30,14,0.16)]">
                                            <div class="relative overflow-hidden">
                                                <img src="{{ $productImage }}" alt="{{ $product->name }}" class="h-48 w-full object-cover transition duration-500 group-hover:scale-105">
                                                <div class="absolute inset-0 bg-gradient-to-t from-brand-dark/85 via-brand-dark/20 to-transparent"></div>
                                                <div class="absolute left-4 top-4 inline-flex rounded-full border border-white/20 bg-white/15 px-3 py-1 text-xs font-semibold text-white backdrop-blur">{{ $category->name }}</div>
                                                <div class="absolute bottom-4 right-4 rounded-full bg-brand-highlight px-3 py-1.5 text-sm font-bold text-brand-dark shadow-lg">NT$ {{ number_format($product->price) }}</div>
                                            </div>

                                            <div class="p-5">
                                                <div class="flex items-start justify-between gap-4">
                                                    <div class="min-w-0">
                                                        <h3 class="text-lg font-bold text-brand-dark">{{ $product->name }}</h3>
                                                        <p class="mt-2 line-clamp-2 text-sm leading-6 text-brand-primary/75">{{ $product->description ?: '精選現做餐點，快加入購物車！' }}</p>
                                                    </div>
                                                    @if($product->is_sold_out)
                                                        <div class="shrink-0 rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-600">已售完</div>
                                                    @endif
                                                </div>

                                                <div class="mt-5">
                                                    @if(! $orderingAvailable)
                                                        <div class="rounded-2xl bg-brand-soft/25 px-3 py-3 text-center text-sm font-medium text-brand-dark">目前不在營業時間，暫停點餐</div>
                                                    @elseif($product->is_sold_out)
                                                        <div class="rounded-2xl bg-slate-100 px-3 py-3 text-center text-sm font-medium text-slate-500">本品項目前無法點餐</div>
                                                    @else
                                                        <form method="POST" action="{{ route('customer.takeout.cart.items.store', ['store' => $store]) }}" class="flex items-center gap-2" data-add-to-cart-form>
                                                            @csrf
                                                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                                                            <input type="hidden" name="option_payload" value="" data-option-payload>
                                                            <div class="inline-flex h-12 items-center rounded-2xl border border-brand-soft bg-brand-soft/20 p-1 shadow-sm">
                                                                <button type="button" class="flex h-10 w-10 items-center justify-center rounded-xl text-lg font-bold text-brand-primary transition hover:bg-white" data-qty-decrement>-</button>
                                                                <input type="hidden" name="qty" value="1" data-qty-input>
                                                                <span class="flex min-w-[2.8rem] items-center justify-center text-sm font-semibold text-brand-dark" data-qty-display>1</span>
                                                                <button type="button" class="flex h-10 w-10 items-center justify-center rounded-xl text-lg font-bold text-brand-primary transition hover:bg-white" data-qty-increment>+</button>
                                                            </div>
                                                            <button type="submit" class="flex-1 rounded-2xl bg-brand-primary px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-primary/20 transition hover:-translate-y-0.5 hover:bg-brand-accent hover:text-brand-dark" data-add-to-cart-button data-option-groups='@json($product->option_groups ?? [])' data-product-name="{{ $product->name }}">加入購物車</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="rounded-[1.75rem] border border-brand-soft/60 bg-white px-5 py-8 text-sm text-brand-primary/70 shadow-[0_18px_40px_rgba(90,30,14,0.08)]">這個分類目前還沒有可點的商品。</div>
                            @endif
                        </section>
                    @endforeach
                </div>

            </div>
        @endif
    </div>
</div>

@if($categories->isNotEmpty())
<aside class="fixed right-6 top-24 z-30 hidden w-80 xl:block">
    <div class="overflow-hidden rounded-[1.5rem] border border-brand-soft/70 bg-white shadow-[0_18px_40px_rgba(90,30,14,0.1)]">
        <div class="border-b border-brand-soft/60 bg-brand-dark px-4 py-4 text-white">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-highlight/80">購物車預覽</p>
            <p class="mt-1 text-sm font-semibold">{{ $cartCount > 0 ? $cartCount . ' 項 | NT$ ' . number_format($cartTotal) : '購物車目前是空的' }}</p>
        </div>

        <div class="max-h-[52vh] space-y-3 overflow-y-auto px-4 py-4">
            @forelse($cartPreviewItems->take(6) as $item)
                <article class="rounded-2xl border border-brand-soft/70 bg-brand-soft/15 px-3 py-3">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-sm font-semibold text-brand-dark">{{ $item['product_name'] ?? '商品' }}</p>
                        <p class="shrink-0 text-xs font-semibold text-brand-primary">x{{ $item['qty'] ?? 1 }}</p>
                    </div>
                    @if(!empty($item['option_label']))
                        <p class="mt-1 text-xs text-brand-primary/75">{{ $item['option_label'] }}</p>
                    @endif
                    <p class="mt-2 text-xs font-semibold text-brand-accent">小計 NT$ {{ number_format((int) ($item['subtotal'] ?? 0)) }}</p>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-brand-soft/80 bg-brand-soft/10 px-3 py-8 text-center text-sm text-brand-primary/75">
                    尚未加入商品
                </div>
            @endforelse

            @if($cartPreviewItems->count() > 6)
                <p class="text-center text-xs font-semibold text-brand-primary/70">還有 {{ $cartPreviewItems->count() - 6 }} 項，請前往購物車查看</p>
            @endif
        </div>

        <div class="border-t border-brand-soft/60 p-4">
            <a href="{{ route('customer.takeout.cart.show', ['store' => $store]) }}" class="inline-flex h-11 w-full items-center justify-center rounded-2xl bg-brand-highlight px-4 text-sm font-semibold text-brand-dark transition hover:bg-brand-soft">查看購物車{{ $cartCount > 0 ? ' (' . $cartCount . ')' : '' }}</a>
        </div>
    </div>
</aside>
@endif

<div class="fixed inset-x-0 bottom-0 z-40 border-t border-brand-soft/60 bg-white/95 px-4 py-4 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 rounded-[1.75rem] bg-brand-dark px-4 py-3 text-white shadow-[0_18px_44px_rgba(90,30,14,0.24)] transition-transform duration-200" data-cart-bar>
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-brand-highlight/80">{{ $orderingAvailable ? '營業中' : '暫停點餐' }}</p>
            <p class="mt-1 text-sm font-semibold">{{ $cartCount > 0 ? $cartCount . ' 項 | NT$ ' . number_format($cartTotal) : '購物車目前是空的' }}</p>
            @if(isset($orderHistory) && $orderHistory->isNotEmpty())
                <div class="mt-1 flex flex-wrap gap-2">
                    @foreach($orderHistory->take(2) as $historyOrder)
                        <a href="{{ route('customer.order.success', ['store' => $store, 'order' => $historyOrder]) }}" class="inline-flex text-xs font-semibold text-brand-highlight underline-offset-2 hover:underline">狀態 {{ $historyOrder->order_no }}</a>
                    @endforeach
                </div>
            @endif
        </div>
        <a href="{{ route('customer.takeout.cart.show', ['store' => $store]) }}" class="inline-flex h-11 items-center justify-center rounded-2xl bg-brand-highlight px-4 text-sm font-semibold text-brand-dark transition hover:bg-brand-soft" data-cart-target>查看購物車{{ $cartCount > 0 ? ' (' . $cartCount . ')' : '' }}</a>
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
@endsection
