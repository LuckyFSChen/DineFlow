<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('customer.success_page_title') }}</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-orange-50 text-gray-900">
    @php
        $currencyCode = strtolower((string) ($order->store->currency ?? 'twd'));
        $currencySymbol = match ($currencyCode) {
            'vnd' => 'VND',
            'cny' => 'CNY',
            'usd' => 'USD',
            default => 'NT$',
        };
        $configuredPrepTimeMinutes = $order->store->prep_time_minutes ?? $store->prep_time_minutes ?? null;
        $defaultPrepTimeMinutes = max(1, (int) config('dineflow.default_prep_time_minutes', 30));
        $prepTimeMinutes = (is_numeric($configuredPrepTimeMinutes) && (int) $configuredPrepTimeMinutes > 0)
            ? (int) $configuredPrepTimeMinutes
            : $defaultPrepTimeMinutes;
        $estimatedReadyAt = ($prepTimeMinutes !== null && $order->created_at !== null)
            ? $order->created_at->copy()->setTimezone($store->businessTimezone())->addMinutes($prepTimeMinutes)
            : null;
        $estimatedReadyAtUnixMs = $estimatedReadyAt?->valueOf();
    @endphp
    <div class="min-h-screen">
        <main class="mx-auto max-w-3xl px-4 py-8 sm:py-12">
            {{-- Success Banner --}}
            <section class="rounded-3xl border border-green-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="mb-3 inline-flex h-12 w-12 items-center justify-center rounded-full bg-green-100 text-2xl">
                            &#10003;
                        </div>
                        <h1 class="text-2xl font-bold tracking-tight text-gray-900">
                            {{ __('customer.order_success_title') }}
                        </h1>
                        <p class="mt-2 text-sm leading-6 text-gray-500">
                            {{ __('customer.order_success_hint') }}
                        </p>
                    </div>

                    <div class="rounded-2xl border border-orange-100 bg-orange-50 px-4 py-3 text-left sm:min-w-[220px]">
                        <p class="text-xs font-medium uppercase tracking-wide text-orange-500">{{ __('customer.order_no') }}</p>
                        <p class="mt-1 text-lg font-bold text-gray-900">{{ $order->order_no }}</p>
                    </div>
                </div>
            </section>

            {{-- Order Summary --}}
            <section class="mt-6 rounded-3xl border border-orange-100 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold">{{ __('customer.order_info_section') }}</h2>

                @php
                    $isTakeout = ($order->order_type ?? null) === 'takeout';
                    $cancelReasons = $order->resolvedCancelReasons();
                @endphp

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl bg-orange-50 px-4 py-4">
                        <p class="text-sm text-gray-500">{{ __('customer.store') }}</p>
                        <p class="mt-1 font-semibold text-gray-900">{{ $order->store->name }}</p>
                    </div>

                    <div class="rounded-2xl bg-orange-50 px-4 py-4">
                        <p class="text-sm text-gray-500">{{ __('customer.table_no') }}</p>
                        <p class="mt-1 font-semibold text-gray-900">{{ $isTakeout ? __('customer.takeout_short') : ($order->table->table_no ?? '-') }}</p>
                    </div>

                    <div class="rounded-2xl bg-orange-50 px-4 py-4">
                        <p class="text-sm text-gray-500">{{ __('customer.order_status') }}</p>
                        <p id="customer-order-status-label" class="mt-1 font-semibold text-orange-600">{{ $order->customer_status_label }}</p>
                    </div>

                    <div class="rounded-2xl bg-orange-50 px-4 py-4">
                        <p class="text-sm text-gray-500">{{ __('customer.payment_status_unpaid') }}/{{ __('customer.payment_status_paid') }}</p>
                        <p id="customer-order-payment-label" class="mt-1 font-semibold text-gray-900">{{ $order->payment_status === 'paid' ? __('customer.payment_status_paid') : __('customer.payment_status_unpaid') }}</p>
                    </div>

                    <div class="rounded-2xl bg-orange-50 px-4 py-4">
                        <p class="text-sm text-gray-500">{{ __('customer.order_amount') }}</p>
                        <p class="mt-1 font-semibold text-gray-900">{{ $currencySymbol }} {{ number_format($order->total) }}</p>
                    </div>

                    <div class="rounded-2xl bg-orange-50 px-4 py-4">
                        <p class="text-sm text-gray-500">{{ __('customer.coupon_discount') }}</p>
                        <p class="mt-1 font-semibold text-gray-900">
                            @if((int) $order->coupon_discount > 0)
                                -{{ $currencySymbol }} {{ number_format((int) $order->coupon_discount) }}
                                @if($order->coupon_code)
                                    <span class="text-xs text-gray-500">({{ $order->coupon_code }})</span>
                                @endif
                            @else
                                --
                            @endif
                        </p>
                    </div>

                    <div class="rounded-2xl bg-orange-50 px-4 py-4">
                        <p class="text-sm text-gray-500">{{ __('customer.points_change') }}</p>
                        <p class="mt-1 font-semibold text-gray-900">
                            -{{ number_format((int) $order->points_used) }} / +{{ number_format((int) $order->points_earned) }} {{ __('customer.points_unit') }}
                        </p>
                        @if($order->member)
                            <p class="mt-1 text-xs text-gray-500">
                                {{ __('customer.current_balance', ['balance' => number_format((int) $order->member->points_balance), 'unit' => __('customer.points_unit')]) }}
                            </p>
                        @endif
                    </div>

                    <div class="rounded-2xl bg-orange-50 px-4 py-4">
                        <p class="text-sm text-gray-500">{{ __('customer.estimated_ready_time') }}</p>
                        <p id="customer-estimated-ready-label" class="mt-1 font-semibold text-gray-900">
                            @if($prepTimeMinutes !== null)
                                {{ __('customer.estimated_prep_time_only', ['minutes' => $prepTimeMinutes]) }}
                            @else
                                {{ __('customer.estimated_ready_time_unknown') }}
                            @endif
                        </p>
                    </div>
                </div>

                <div id="customer-cancel-reason-box" class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-4 {{ in_array(strtolower((string) $order->status), ['cancel', 'cancelled', 'canceled'], true) ? '' : 'hidden' }}">
                    <p class="text-sm text-rose-700">{{ __('customer.cancel_reason_title') }}</p>
                    <ul id="customer-cancel-reason-list" class="mt-2 list-disc space-y-1 pl-5 text-sm text-rose-800 {{ empty($cancelReasons) ? 'hidden' : '' }}">
                        @foreach ($cancelReasons as $cancelReason)
                            <li>{{ $cancelReason }}</li>
                        @endforeach
                    </ul>
                    <p id="customer-cancel-reason-empty" class="mt-2 text-sm text-rose-700 {{ empty($cancelReasons) ? '' : 'hidden' }}">{{ __('customer.cancel_reason_empty') }}</p>
                </div>
            </section>

            {{-- Items --}}
            <section class="mt-6 rounded-3xl border border-orange-100 bg-white p-5 shadow-sm">
                <div class="mb-5 flex items-center justify-between">
                    <h2 class="text-lg font-bold">{{ __('customer.order_items_title') }}</h2>
                    <span class="text-sm text-gray-400">{{ $order->items->count() }} {{ __('customer.items') }}</span>
                </div>

                <div class="space-y-4">
                    @foreach ($order->items as $item)
                        <div class="flex items-center justify-between gap-4 rounded-2xl border border-orange-50 bg-orange-50/50 px-4 py-4">
                            <div class="min-w-0 flex-1">
                                <h3 class="text-base font-semibold text-gray-900">
                                    {{ $item->product_name }}
                                </h3>
                                @if(!empty($item->note))
                                    <p class="mt-1 text-xs text-orange-600">{{ $item->note }}</p>
                                @endif
                                <p class="mt-1 text-sm text-gray-500">
                                    {{ __('customer.unit_price') }} {{ $currencySymbol }} {{ number_format($item->price) }}
                                </p>
                            </div>

                            <div class="text-right">
                                <p class="text-sm text-gray-500">x {{ $item->qty }}</p>
                                <p class="mt-1 text-base font-bold text-orange-600">
                                    {{ $currencySymbol }} {{ number_format($item->subtotal) }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 border-t border-orange-100 pt-4">
                    <div class="flex items-center justify-between">
                        <span class="text-base font-medium text-gray-600">{{ __('customer.total_label') }}</span>
                        <span class="text-2xl font-bold text-orange-600">
                            {{ $currencySymbol }} {{ number_format($order->total) }}
                        </span>
                    </div>
                </div>
            </section>

            {{-- Action --}}
            <section class="mt-6">
                <div class="rounded-3xl border border-orange-100 bg-white p-5 text-center shadow-sm">
                    <p class="text-sm text-gray-500">
                        {{ __('customer.continue_ordering') }}
                    </p>

                    <a href="{{ $isTakeout ? route('customer.takeout.menu', ['store' => $store]) : route('customer.dinein.menu', ['store' => $store, 'table' => $order->table]) }}"
                       class="mt-4 inline-flex h-11 items-center justify-center rounded-2xl bg-orange-500 px-5 text-sm font-semibold text-white hover:bg-orange-600">
                        {{ __('customer.back_to_menu') }}
                    </a>

                    <a href="{{ route('customer.order.history', ['store' => $store, 'customer_email' => $order->customer_email, 'customer_phone' => $order->customer_phone]) }}"
                       class="mt-3 inline-flex h-11 items-center justify-center rounded-2xl border border-orange-200 bg-orange-50 px-5 text-sm font-semibold text-orange-700 hover:bg-orange-100">
                        {{ __('customer.view_my_order_history') }}
                    </a>
                </div>
            </section>
        </main>
    </div>

    <div id="order-status-toast" class="fixed bottom-6 right-6 z-50 hidden rounded-2xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 shadow-lg">
        {{ __('customer.order_status_changed_title') }}
    </div>

    <script>
    (() => {
        const statusLabel = document.getElementById('customer-order-status-label');
        const paymentLabel = document.getElementById('customer-order-payment-label');
        const cancelReasonBox = document.getElementById('customer-cancel-reason-box');
        const cancelReasonList = document.getElementById('customer-cancel-reason-list');
        const cancelReasonEmpty = document.getElementById('customer-cancel-reason-empty');
        const estimatedReadyLabel = document.getElementById('customer-estimated-ready-label');
        const toast = document.getElementById('order-status-toast');
        const endpoint = @json(route('customer.order.status', ['store' => $store, 'order' => $order]));
        const paidLabel = @json(__('customer.payment_status_paid'));
        const unpaidLabel = @json(__('customer.payment_status_unpaid'));
        const estimatedReadyAtUnixMs = @json($estimatedReadyAtUnixMs);
        const prepTimeMinutes = @json($prepTimeMinutes);
        const estimatedPrepOnlyTemplate = @json(__('customer.estimated_prep_time_only', ['minutes' => '__minutes__']));
        const estimatedReadyUnknown = @json(__('customer.estimated_ready_time_unknown'));
        const estimatedReadyNow = @json('已可取餐');

        if (!statusLabel || !paymentLabel || !endpoint) {
            return;
        }

        let lastStatus = @json($order->status);
        let lastPaymentStatus = @json($order->payment_status);
        let audioContext = null;

        const isCancelledStatus = (status) => ['cancel', 'cancelled', 'canceled'].includes(String(status || '').toLowerCase());
        const isFinalStatus = (status) => ['complete', 'completed', 'ready', 'ready_for_pickup', 'picked_up', 'collected', 'served', 'cancel', 'cancelled', 'canceled'].includes(String(status || '').toLowerCase());
        const isCompletedStatus = (status) => ['complete', 'completed', 'ready', 'ready_for_pickup', 'picked_up', 'collected', 'served'].includes(String(status || '').toLowerCase());

        const displayMinutesByRule = (remainingMinutes) => {
            if (remainingMinutes > 5) {
                return Math.ceil(remainingMinutes / 5) * 5;
            }

            return remainingMinutes;
        };

        const renderEstimatedReady = (status) => {
            if (!estimatedReadyLabel) {
                return;
            }

            if (isCancelledStatus(status)) {
                estimatedReadyLabel.textContent = estimatedReadyUnknown;
                return;
            }

            if (isCompletedStatus(status)) {
                estimatedReadyLabel.textContent = estimatedReadyNow;
                return;
            }

            if (!estimatedReadyAtUnixMs || !prepTimeMinutes) {
                estimatedReadyLabel.textContent = estimatedReadyUnknown;
                return;
            }

            const nowMs = Date.now();
            const remainingMinutes = Math.max(0, Math.ceil((Number(estimatedReadyAtUnixMs) - nowMs) / 60000));
            if (remainingMinutes <= 0) {
                estimatedReadyLabel.textContent = estimatedReadyNow;
                return;
            }

            const displayMinutes = displayMinutesByRule(remainingMinutes);
            estimatedReadyLabel.textContent = estimatedPrepOnlyTemplate.replace('__minutes__', String(displayMinutes));
        };

        const renderCancelReasons = (status, reasons) => {
            if (!cancelReasonBox || !cancelReasonList || !cancelReasonEmpty) {
                return;
            }

            if (!isCancelledStatus(status)) {
                cancelReasonBox.classList.add('hidden');
                return;
            }

            cancelReasonBox.classList.remove('hidden');

            const list = Array.isArray(reasons)
                ? reasons.filter((reason) => String(reason || '').trim() !== '')
                : [];

            cancelReasonList.innerHTML = '';

            if (list.length === 0) {
                cancelReasonList.classList.add('hidden');
                cancelReasonEmpty.classList.remove('hidden');
                return;
            }

            cancelReasonList.classList.remove('hidden');
            cancelReasonEmpty.classList.add('hidden');

            list.forEach((reason) => {
                const li = document.createElement('li');
                li.textContent = reason;
                cancelReasonList.appendChild(li);
            });
        };

        renderCancelReasons(lastStatus, @json($cancelReasons));
        renderEstimatedReady(lastStatus);

        const initAudio = () => {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) {
                return;
            }

            audioContext = new AudioCtx();
            const unlock = () => {
                if (audioContext?.state === 'suspended') {
                    audioContext.resume().catch(() => {});
                }
                window.removeEventListener('click', unlock);
                window.removeEventListener('keydown', unlock);
            };

            window.addEventListener('click', unlock, { once: true });
            window.addEventListener('keydown', unlock, { once: true });
        };

        const playSound = () => {
            if (!audioContext) {
                return;
            }

            const now = audioContext.currentTime;
            const beep = (start, frequency, duration) => {
                const osc = audioContext.createOscillator();
                const gain = audioContext.createGain();
                osc.type = 'triangle';
                osc.frequency.value = frequency;
                gain.gain.setValueAtTime(0.0001, start);
                gain.gain.exponentialRampToValueAtTime(0.1, start + 0.01);
                gain.gain.exponentialRampToValueAtTime(0.0001, start + duration);
                osc.connect(gain);
                gain.connect(audioContext.destination);
                osc.start(start);
                osc.stop(start + duration);
            };

            if (audioContext.state === 'suspended') {
                audioContext.resume().catch(() => {});
            }

            beep(now, 740, 0.16);
            beep(now + 0.2, 990, 0.2);
        };

        const showToast = () => {
            if (!toast) {
                return;
            }

            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 3000);
        };

        const poll = async () => {
            try {
                const res = await fetch(endpoint, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!res.ok) {
                    return;
                }

                const data = await res.json();
                if (!data.ok) {
                    return;
                }

                const nextStatus = data.status || lastStatus;
                const nextPaymentStatus = data.payment_status || lastPaymentStatus;
                const changed = nextStatus !== lastStatus || nextPaymentStatus !== lastPaymentStatus;

                statusLabel.textContent = data.customer_status_label || statusLabel.textContent;
                paymentLabel.textContent = nextPaymentStatus === 'paid' ? paidLabel : unpaidLabel;
                renderCancelReasons(nextStatus, data.cancel_reasons || []);
                renderEstimatedReady(nextStatus);

                if (changed) {
                    playSound();
                    showToast();
                    lastStatus = nextStatus;
                    lastPaymentStatus = nextPaymentStatus;
                }
            } catch (_) {
            }
        };

        initAudio();
        const estimatedTimer = setInterval(() => {
            renderEstimatedReady(lastStatus);
            if (isFinalStatus(lastStatus)) {
                clearInterval(estimatedTimer);
            }
        }, 30000);
        setInterval(poll, 10000);
    })();
    </script>
</body>
</html>
