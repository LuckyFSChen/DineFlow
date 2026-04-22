<?php

namespace App\Http\Controllers\Customer;

use App\Events\DineInCartUpdated;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\DiningTable;
use App\Models\Member;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\CustomerAccountService;
use App\Services\LoyaltyService;
use App\Support\DineInCartStore;
use App\Support\InvoiceFlow;
use App\Support\PhoneFormatter;
use App\Support\TakeoutCartSession;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class DineInOrderController extends Controller
{
    private const CUSTOMER_PROFILE_SESSION_KEY = 'customer_order_profile';
    private const ORDER_HISTORY_SESSION_PREFIX = 'dinein_order_history_';
    private const TAKEOUT_ORDER_HISTORY_SESSION_PREFIX = 'takeout_order_history_';
    private const GLOBAL_ORDER_HISTORY_SESSION_KEY = 'customer_order_history';
    private const GLOBAL_ORDER_HISTORY_LIMIT = 60;
    private const ORDER_HISTORY_LIMIT = 8;
    private const NON_APPENDABLE_ORDER_STATUSES = [
        'cancel',
        'cancelled',
        'canceled',
    ];

    private const COMPLETED_ORDER_STATUSES = [
        'complete',
        'completed',
        'ready',
        'ready_for_pickup',
    ];

    public function __construct(
        private readonly CustomerAccountService $customerAccountService,
        private readonly LoyaltyService $loyaltyService
    )
    {
    }

    public function addToCart(Request $request, Store $store, DiningTable $table)
    {
        abort_unless($table->store_id === $store->id, 404);

        if (! $store->isOrderingAvailable()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $store->orderingClosedMessage(),
                ], 422);
            }

            return redirect()
                ->route('customer.dinein.menu', ['store' => $store, 'table' => $table])
                ->with('error', $store->orderingClosedMessage());
        }

        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'qty' => ['required', 'integer', 'min:1'],
            'option_payload' => ['nullable', 'string'],
            'item_note' => ['nullable', 'string', 'max:255'],
        ]);

        $product = Product::where('id', $validated['product_id'])
            ->where('store_id', $store->id)
            ->where('is_active', true)
            ->where('is_sold_out', false)
            ->firstOrFail();

        $cart = DineInCartStore::getCart($request, $store->id, $table->id);

        $optionResult = $this->resolveSelectedOptions($product, $validated['option_payload'] ?? null);
        $itemNote = $this->sanitizeItemNote($validated['item_note'] ?? null, (bool) $product->allow_item_note);
        $unitPrice = (int) $product->price + (int) $optionResult['extra_price'];
        $lineKey = $this->cartLineKey($product->id, $optionResult['selected'], $itemNote);

        if (isset($cart[$lineKey])) {
            $cart[$lineKey]['qty'] += $validated['qty'];
        } else {
            $cart[$lineKey] = [
                'line_key' => $lineKey,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'base_price' => (int) $product->price,
                'extra_price' => (int) $optionResult['extra_price'],
                'price' => $unitPrice,
                'option_items' => $optionResult['selected'],
                'option_label' => $optionResult['label'],
                'item_note' => $itemNote,
                'qty' => $validated['qty'],
                'subtotal' => 0,
            ];
        }

        foreach ($cart as &$item) {
            $item['subtotal'] = $item['price'] * $item['qty'];
        }
        unset($item);

        $cart = DineInCartStore::putCart($request, $store->id, $table->id, $cart);
        $this->broadcastDineInCartUpdated($request, $store, $table);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('customer.item_added_to_cart'),
                'cart' => $this->buildDineInCartPayload($store, $table, $cart),
            ]);
        }

        return redirect()
            ->route('customer.dinein.menu', ['store' => $store, 'table' => $table])
            ->with('success', __('customer.item_added_to_cart'));
    }

    public function cart(Store $store, DiningTable $table)
    {
        abort_unless($table->store_id === $store->id, 404);

        $cart = DineInCartStore::getCart(request(), $store->id, $table->id);
        $cart = $this->hydrateEditableProductMeta($store, $cart);
        $total = collect($cart)->sum('subtotal');
        $orderingAvailable = $store->isOrderingAvailable();
        $rememberedCustomerInfo = $this->resolvePrefilledCustomerInfo();
        $orderHistory = $this->getDineInOrderHistory($store, $table);
        $customerName = $this->normalizeCustomerName((string) old('customer_name', $rememberedCustomerInfo['customer_name'] ?? ''));
        $customerEmail = $this->normalizeOptionalText((string) old('customer_email', $rememberedCustomerInfo['customer_email'] ?? ''));
        $customerPhone = $this->normalizeCustomerPhone((string) old('customer_phone', $rememberedCustomerInfo['customer_phone'] ?? ''), $store);
        $member = $this->loyaltyService->resolveMember($store, $customerName, $customerEmail, $customerPhone);
        $estimatedReadyTime = $store->estimateCustomerReadyTimeForOrderItems($cart);

        return view('customer.cart', compact('store', 'table', 'cart', 'total', 'orderingAvailable', 'rememberedCustomerInfo', 'orderHistory', 'member', 'estimatedReadyTime'));
    }

    private function resolvePrefilledCustomerInfo(): array
    {
        $rememberedCustomerInfo = session()->get(self::CUSTOMER_PROFILE_SESSION_KEY, []);
        $user = request()->user();

        if (! $user instanceof User || ! $user->isCustomer()) {
            return $rememberedCustomerInfo;
        }

        return [
            'customer_name' => $rememberedCustomerInfo['customer_name'] ?? (string) ($user->name ?? ''),
            'customer_email' => $rememberedCustomerInfo['customer_email'] ?? (string) ($user->email ?? ''),
            'customer_phone' => $rememberedCustomerInfo['customer_phone'] ?? (string) ($user->phone ?? ''),
            'note' => $rememberedCustomerInfo['note'] ?? '',
        ];
    }

    private function buildDineInCartPayload(Store $store, DiningTable $table, array $cart): array
    {
        $cartCollection = collect($cart)->values();
        $cartCount = (int) $cartCollection->sum('qty');
        $cartTotal = (int) $cartCollection->sum('subtotal');
        $currencySymbol = $this->currencySymbol($store);
        $estimatedReadyTime = $store->estimateCustomerReadyTimeForOrderItems($cartCollection->all());

        return [
            'count' => $cartCount,
            'line_count' => $cartCollection->count(),
            'total' => $cartTotal,
            'summary_label' => $cartCount > 0
                ? __('customer.cart_bar_total', ['count' => $cartCount, 'currency' => $currencySymbol, 'total' => number_format($cartTotal)])
                : __('customer.cart_bar_empty'),
            'link_label' => __('customer.view_cart') . ($cartCount > 0 ? ' (' . $cartCount . ')' : ''),
            'preview_html' => view('customer.dine-in.partials.cart-preview-items', [
                'cartPreviewItems' => $cartCollection,
                'store' => $store,
                'table' => $table,
                'currencySymbol' => $currencySymbol,
            ])->render(),
            'estimated_ready' => [
                'minutes' => (int) ($estimatedReadyTime['minutes'] ?? 0),
                'label' => $store->customerReadyTimeLabel($estimatedReadyTime['minutes'] ?? null),
            ],
            'items' => $cartCollection->map(fn (array $item) => [
                'line_key' => (string) ($item['line_key'] ?? ''),
                'qty' => (int) ($item['qty'] ?? 0),
                'subtotal' => (int) ($item['subtotal'] ?? 0),
                'subtotal_display' => $currencySymbol . ' ' . number_format((int) ($item['subtotal'] ?? 0)),
            ])->values()->all(),
        ];
    }

    private function currencySymbol(Store $store): string
    {
        return match (strtolower((string) ($store->currency ?? 'twd'))) {
            'vnd' => 'VND',
            'cny' => 'CNY',
            'usd' => 'USD',
            default => 'NT$',
        };
    }

    private function broadcastDineInCartUpdated(Request $request, Store $store, DiningTable $table): void
    {
        try {
            event(new DineInCartUpdated(
                storeId: (int) $store->id,
                tableToken: (string) $table->qr_token,
                sourceClientId: $request->header('X-DineIn-Client-Id'),
            ));
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function syncCart(Request $request, Store $store, DiningTable $table): JsonResponse
    {
        abort_unless($table->store_id === $store->id, 404);

        $cart = DineInCartStore::getCart($request, $store->id, $table->id);

        return response()->json([
            'ok' => true,
            'cart' => $this->buildDineInCartPayload($store, $table, $cart),
        ]);
    }

    public function updateCartItem(Request $request, Store $store, DiningTable $table, string $lineKey)
    {
        abort_unless($table->store_id === $store->id, 404);

        $validated = $request->validate([
            'action' => ['required', 'in:increase,decrease,update_options'],
            'option_payload' => ['nullable', 'string'],
            'item_note' => ['nullable', 'string', 'max:255'],
        ]);

        $cart = DineInCartStore::getCart($request, $store->id, $table->id);

        if (! isset($cart[$lineKey])) {
            return redirect()
                ->route('customer.dinein.cart.show', ['store' => $store, 'table' => $table])
                ->with('error', __('customer.error_cart_empty'));
        }

        if ($validated['action'] === 'increase') {
            $cart[$lineKey]['qty'] = (int) $cart[$lineKey]['qty'] + 1;
        } elseif ($validated['action'] === 'decrease') {
            $cart[$lineKey]['qty'] = (int) $cart[$lineKey]['qty'] - 1;

            if ($cart[$lineKey]['qty'] <= 0) {
                unset($cart[$lineKey]);
            }
        } else {
            $existingItem = $cart[$lineKey];
            $product = Product::query()
                ->where('id', (int) ($existingItem['product_id'] ?? 0))
                ->where('store_id', $store->id)
                ->where('is_active', true)
                ->where('is_sold_out', false)
                ->firstOrFail();

            $optionResult = $this->resolveSelectedOptions($product, $validated['option_payload'] ?? null);
            $itemNote = $this->sanitizeItemNote($validated['item_note'] ?? null, (bool) $product->allow_item_note);
            $updatedLineKey = $this->cartLineKey((int) $product->id, $optionResult['selected'], $itemNote);
            $qty = max(1, (int) ($existingItem['qty'] ?? 1));
            $unitPrice = (int) $product->price + (int) $optionResult['extra_price'];

            unset($cart[$lineKey]);

            if (isset($cart[$updatedLineKey])) {
                $cart[$updatedLineKey]['qty'] = (int) $cart[$updatedLineKey]['qty'] + $qty;
            } else {
                $cart[$updatedLineKey] = [
                    'line_key' => $updatedLineKey,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'base_price' => (int) $product->price,
                    'extra_price' => (int) $optionResult['extra_price'],
                    'price' => $unitPrice,
                    'option_items' => $optionResult['selected'],
                    'option_label' => $optionResult['label'],
                    'item_note' => $itemNote,
                    'qty' => $qty,
                    'subtotal' => 0,
                ];
            }
        }

        foreach ($cart as &$item) {
            $item['subtotal'] = (int) $item['price'] * (int) $item['qty'];
        }
        unset($item);

        DineInCartStore::putCart($request, $store->id, $table->id, $cart);
        $this->broadcastDineInCartUpdated($request, $store, $table);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'cart' => $this->buildDineInCartPayload($store, $table, $cart),
            ]);
        }

        return redirect()->route('customer.dinein.cart.show', ['store' => $store, 'table' => $table]);
    }

    public function removeCartItem(Request $request, Store $store, DiningTable $table, string $lineKey)
    {
        abort_unless($table->store_id === $store->id, 404);

        $cart = DineInCartStore::getCart($request, $store->id, $table->id);

        if (isset($cart[$lineKey])) {
            unset($cart[$lineKey]);
            DineInCartStore::putCart($request, $store->id, $table->id, $cart);
            $this->broadcastDineInCartUpdated($request, $store, $table);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'cart' => $this->buildDineInCartPayload($store, $table, $cart),
            ]);
        }

        return redirect()->route('customer.dinein.cart.show', ['store' => $store, 'table' => $table]);
    }

    public function clearCart(Request $request, Store $store, DiningTable $table)
    {
        abort_unless($table->store_id === $store->id, 404);

        $cart = DineInCartStore::getCart($request, $store->id, $table->id);

        if (! empty($cart)) {
            DineInCartStore::clearCart($request, $store->id, $table->id);
            $this->broadcastDineInCartUpdated($request, $store, $table);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'cart' => $this->buildDineInCartPayload($store, $table, []),
            ]);
        }

        return redirect()
            ->route('customer.dinein.menu', ['store' => $store, 'table' => $table])
            ->with('success', __('customer.cart_cleared'));
    }

    public function submit(Request $request, Store $store, DiningTable $table)
    {
        abort_unless($table->store_id === $store->id, 404);

        if (! $store->isOrderingAvailable()) {
            return redirect()
                ->route('customer.dinein.cart.show', ['store' => $store, 'table' => $table])
                ->with('error', $store->orderingClosedMessage());
        }

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => $this->customerPhoneValidationRules($store),
            'coupon_code' => ['nullable', 'string', 'max:64'],
            'remember_customer_info' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string'],
        ] + InvoiceFlow::validationRules());

        $validated['customer_name'] = $this->normalizeCustomerName($validated['customer_name'] ?? null);
        $validated['customer_email'] = $this->normalizeOptionalText($validated['customer_email'] ?? null);
        $validated['customer_phone'] = $this->normalizeCustomerPhone($validated['customer_phone'] ?? null, $store);

        if (! $request->user() && $request->boolean('remember_customer_info')) {
            session()->put(self::CUSTOMER_PROFILE_SESSION_KEY, [
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'],
                'note' => $validated['note'] ?? '',
            ]);
        }

        $invoicePayload = InvoiceFlow::normalize($validated);
        $invoiceValidationErrors = InvoiceFlow::validateFlowPayload($invoicePayload);
        if ($invoiceValidationErrors !== []) {
            return redirect()
                ->route('customer.dinein.cart.show', ['store' => $store, 'table' => $table])
                ->withErrors($invoiceValidationErrors)
                ->withInput();
        }

        $cart = DineInCartStore::getCart($request, $store->id, $table->id);

        if (empty($cart)) {
            return redirect()
                ->route('customer.dinein.cart.show', ['store' => $store, 'table' => $table])
                ->with('error', __('customer.error_cart_empty'));
        }

        $total = collect($cart)->sum('subtotal');
        $customerPhone = $this->normalizeOptionalText($validated['customer_phone'] ?? null);
        $phoneAlreadyRegistered = $this->customerAccountService->isPhoneRegistered($customerPhone);
        $shouldCreateAccount = ! $request->user()
            && ! $phoneAlreadyRegistered;

        try {
            $checkoutResult = DB::transaction(function () use ($store, $table, $validated, $cart, $total, $invoicePayload, $shouldCreateAccount) {
                $this->assertCartItemsStillAvailable($store, $cart);

                $customerName = $this->normalizeCustomerName($validated['customer_name'] ?? null);
                $customerEmail = $this->normalizeOptionalText($validated['customer_email'] ?? null);
                $customerPhone = $this->normalizeOptionalText($validated['customer_phone'] ?? null);
                $customerNote = $this->normalizeOptionalText($validated['note'] ?? null);
                $couponCode = $this->normalizeCouponCode($validated['coupon_code'] ?? null);
                $registeredCustomer = null;

                if ($shouldCreateAccount) {
                    $registeredCustomer = $this->customerAccountService->registerOrUpdateFromOrder(
                        $customerPhone,
                        $customerName,
                        $customerEmail
                    );
                }

                $member = $this->loyaltyService->resolveMember($store, $customerName, $customerEmail, $customerPhone);
                if ($member !== null) {
                    $member = Member::query()->lockForUpdate()->find($member->id);
                }
                $couponResult = $this->loyaltyService->resolveCoupon($store, $couponCode, $total, $member, 'dine_in');
                $couponError = $couponResult['error'] ?? null;

                if ($couponError !== null) {
                    $message = $couponError === __('customer.coupon_not_found')
                        ? __('customer.coupon_invalid_code')
                        : $couponError;

                    throw ValidationException::withMessages([
                        'coupon_code' => $message,
                    ]);
                }

                /** @var Coupon|null $coupon */
                $coupon = $couponResult['coupon'] ?? null;
                $couponDiscount = max((int) ($couponResult['discount'] ?? 0), 0);
                $finalTotal = max($total - $couponDiscount, 0);
                $pointsUsed = max((int) ($couponResult['points_cost'] ?? 0), 0);
                $baseEarnedPoints = $store->calculateEarnedPoints($finalTotal);
                $bonusEarnedPoints = max((int) ($couponResult['bonus_points'] ?? 0), 0);
                $pointsEarned = max($baseEarnedPoints + $bonusEarnedPoints, 0);

                $order = $this->findAppendableDineInOrder(
                    $store,
                    $table,
                );

                if (! $order) {
                    $order = Order::create([
                        'store_id' => $store->id,
                        'dining_table_id' => $table->id,
                        'order_type' => 'dine_in',
                        'cart_token' => null,
                        'order_no' => $this->generateOrderNo($store),
                        'status' => 'pending',
                        'payment_status' => 'unpaid',
                        'invoice_flow' => $invoicePayload['invoice_flow'],
                        'invoice_mobile_barcode' => $invoicePayload['invoice_mobile_barcode'],
                        'invoice_member_carrier_code' => $invoicePayload['invoice_member_carrier_code'],
                        'invoice_donation_code' => $invoicePayload['invoice_donation_code'],
                        'invoice_company_tax_id' => $invoicePayload['invoice_company_tax_id'],
                        'invoice_company_name' => $invoicePayload['invoice_company_name'],
                        'invoice_requested_at' => $invoicePayload['invoice_flow'] !== InvoiceFlow::NONE ? now() : null,
                        'customer_name' => $customerName,
                        'customer_email' => $customerEmail,
                        'customer_phone' => $customerPhone,
                        'note' => $customerNote,
                        'member_id' => $member?->id,
                        'coupon_id' => $coupon?->id,
                        'coupon_code' => $coupon?->code,
                        'coupon_discount' => $couponDiscount,
                        'points_used' => $pointsUsed,
                        'points_earned' => $pointsEarned,
                        'subtotal' => 0,
                        'total' => 0,
                    ]);
                } elseif (in_array(strtolower((string) $order->status), self::COMPLETED_ORDER_STATUSES, true)) {
                    // Postpay orders can be reopened for appended items before settlement.
                    $order->status = 'preparing';
                } elseif (
                    (string) $order->invoice_flow === InvoiceFlow::NONE
                    && (string) $invoicePayload['invoice_flow'] !== InvoiceFlow::NONE
                ) {
                    $order->fill([
                        'invoice_flow' => $invoicePayload['invoice_flow'],
                        'invoice_mobile_barcode' => $invoicePayload['invoice_mobile_barcode'],
                        'invoice_member_carrier_code' => $invoicePayload['invoice_member_carrier_code'],
                        'invoice_donation_code' => $invoicePayload['invoice_donation_code'],
                        'invoice_company_tax_id' => $invoicePayload['invoice_company_tax_id'],
                        'invoice_company_name' => $invoicePayload['invoice_company_name'],
                        'invoice_requested_at' => now(),
                    ]);
                }

                if ($order->member_id === null && $member !== null) {
                    $order->member_id = $member->id;
                }
                if ($order->customer_name === null && $customerName !== null) {
                    $order->customer_name = $customerName;
                }
                if ($order->customer_email === null && $customerEmail !== null) {
                    $order->customer_email = $customerEmail;
                }
                if ($order->getRawOriginal('customer_phone') === null && $customerPhone !== null) {
                    $order->customer_phone = $customerPhone;
                }
                if ($coupon !== null) {
                    $order->coupon_id = $coupon->id;
                    $order->coupon_code = $coupon->code;
                }

                foreach ($cart as $item) {
                    $order->items()->create([
                        'product_id' => $item['product_id'],
                        'product_name' => $item['product_name'],
                        'price' => $item['price'],
                        'qty' => $item['qty'],
                        'subtotal' => $item['subtotal'],
                        'note' => $this->composeOrderItemNote($item['option_label'] ?? null, $item['item_note'] ?? null),
                    ]);
                }

                $order->subtotal = (int) $order->subtotal + (int) $total;
                $order->coupon_discount = (int) $order->coupon_discount + $couponDiscount;
                $order->points_used = (int) $order->points_used + $pointsUsed;
                $order->points_earned = (int) $order->points_earned + $pointsEarned;
                $order->total = (int) $order->total + (int) $finalTotal;
                if ($order->note === null && $customerNote !== null) {
                    $order->note = $customerNote;
                }
                $order->save();

                if ($coupon !== null) {
                    $coupon->increment('used_count');
                }

                $this->loyaltyService->finalizeOrderLoyalty(
                    $order,
                    $member,
                    $coupon,
                    $pointsUsed,
                    $pointsEarned
                );

                $this->bindMemberCarrierCode(
                    $store,
                    $customerName,
                    $customerEmail,
                    $customerPhone,
                    $invoicePayload
                );

                return [
                    'order' => $order,
                    'registered_customer' => $registeredCustomer,
                ];
            });
        } catch (ValidationException $exception) {
            return redirect()
                ->route('customer.dinein.cart.show', ['store' => $store, 'table' => $table])
                ->with('error', $exception->validator->errors()->first() ?: __('customer.item_not_available'));
        }

        /** @var Order $order */
        $order = $checkoutResult['order'];
        $registeredCustomer = $checkoutResult['registered_customer'] ?? null;

        if (
            $shouldCreateAccount
            && $registeredCustomer instanceof User
            && $registeredCustomer->isCustomer()
        ) {
            Auth::login($registeredCustomer);
            $request->session()->regenerate();
        }

        DineInCartStore::clearCart($request, $store->id, $table->id);
        $this->broadcastDineInCartUpdated($request, $store, $table);
        $this->pushDineInOrderToHistory($store, $table, $order);
        $this->pushOrderToGlobalHistory($order);

        return redirect()->route('customer.order.success', [
            'store' => $store->slug,
            'order' => $order->uuid,
        ]);
    }

    protected function assertCartItemsStillAvailable(Store $store, array $cart): void
    {
        $productIds = collect($cart)
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => __('customer.error_items_unavailable', ['items' => __('customer.product_default_name')]),
            ]);
        }

        $products = Product::query()
            ->where('store_id', $store->id)
            ->whereIn('id', $productIds->all())
            ->lockForUpdate()
            ->get(['id', 'name', 'is_active', 'is_sold_out'])
            ->keyBy('id');

        $unavailableNames = [];

        foreach ($cart as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $product = $products->get($productId);

            if (! $product || ! $product->is_active || $product->is_sold_out) {
                $unavailableNames[] = (string) ($item['product_name'] ?? __('customer.product_default_name'));
            }
        }

        $unavailableNames = array_values(array_unique(array_filter($unavailableNames)));

        if (! empty($unavailableNames)) {
            throw ValidationException::withMessages([
                'cart' => __('customer.error_items_unavailable', ['items' => implode(', ', $unavailableNames)]),
            ]);
        }
    }

    public function checkPhoneRegistered(Request $request, Store $store, DiningTable $table): JsonResponse
    {
        abort_unless($table->store_id === $store->id, 404);

        $validated = $request->validate([
            'customer_phone' => ['nullable', 'string', 'max:32'],
        ]);

        $normalizedPhone = $this->normalizeCustomerPhone($validated['customer_phone'] ?? null, $store);

        return response()->json([
            'ok' => true,
            'registered' => $this->customerAccountService->isPhoneRegistered($normalizedPhone),
        ]);
    }

    public function checkCoupon(Request $request, Store $store, DiningTable $table): JsonResponse
    {
        abort_unless($table->store_id === $store->id, 404);

        $validated = $request->validate([
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:32'],
            'coupon_code' => ['nullable', 'string', 'max:64'],
        ]);

        $cart = DineInCartStore::getCart($request, $store->id, $table->id);
        $subtotal = (int) collect($cart)->sum('subtotal');
        $couponCode = $this->normalizeCouponCode($validated['coupon_code'] ?? null);

        if ($couponCode === null) {
            return response()->json([
                'ok' => false,
                'error' => __('customer.coupon_enter_code_first'),
            ], 422);
        }

        $customerPhone = $this->normalizeCustomerPhone($validated['customer_phone'] ?? null, $store);
        $customerEmail = $this->normalizeOptionalText($validated['customer_email'] ?? null);
        $member = $this->findExistingMemberForCoupon($store, $customerEmail, $customerPhone);
        $result = $this->loyaltyService->resolveCoupon($store, $couponCode, $subtotal, $member, 'dine_in');

        $error = $result['error'] ?? null;
        if ($error !== null) {
            return response()->json([
                'ok' => false,
                'error' => $error === __('customer.coupon_not_found') ? __('customer.coupon_invalid_code') : $error,
            ], 422);
        }

        /** @var Coupon|null $coupon */
        $coupon = $result['coupon'] ?? null;
        if (! $coupon) {
            return response()->json([
                'ok' => false,
                'error' => __('customer.coupon_invalid_code'),
            ], 422);
        }

        $discount = max((int) ($result['discount'] ?? 0), 0);
        $currencySymbol = $this->currencySymbol($store);

        return response()->json([
            'ok' => true,
            'coupon' => [
                'code' => $coupon->code,
                'name' => (string) ($coupon->name ?? ''),
                'discount' => $discount,
                'discount_display' => $currencySymbol . ' ' . number_format($discount),
                'summary' => $this->formatCouponSummary($coupon, $discount, $currencySymbol),
            ],
        ]);
    }

    public function success(Store $store, Order $order)
    {
        abort_unless($order->store_id === $store->id, 404);

        $order->load('items', 'store', 'table', 'member');

        return view('customer.success', compact('order', 'store'));
    }

    public function orderStatus(Store $store, Order $order): JsonResponse
    {
        abort_unless($order->store_id === $store->id, 404);

        $order->loadMissing(['items:id,order_id,product_id,qty,item_status,completed_at']);
        $estimatedReadyTime = $store->estimateCustomerReadyTimeForOrder($order);
        $normalizedStatus = strtolower((string) $order->status);
        $estimatedReadyLabel = match (true) {
            in_array($normalizedStatus, ['cancel', 'cancelled', 'canceled'], true) => __('customer.estimated_ready_time_unknown'),
            in_array($normalizedStatus, ['complete', 'completed', 'ready', 'ready_for_pickup', 'picked_up', 'collected', 'served'], true) => __('mail_orders.status.completed'),
            default => $store->customerReadyTimeLabel($estimatedReadyTime['minutes'] ?? null),
        };

        return response()->json([
            'ok' => true,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'customer_status_label' => $order->customer_status_label,
            'cancel_reasons' => $order->resolvedCancelReasons(),
            'estimated_ready_minutes' => (int) ($estimatedReadyTime['minutes'] ?? 0),
            'estimated_ready_label' => $estimatedReadyLabel,
        ]);
    }

    public function history(Request $request)
    {
        $user = $request->user();
        if ($user instanceof User) {
            $email = strtolower(trim((string) ($user->email ?? '')));
            $phone = PhoneFormatter::digitsOnly((string) ($user->phone ?? ''), 32);
            $memberPointSummaries = collect();

            $orders = collect();
            if ($email !== '' || $phone !== null) {
                $orders = Order::query()
                    ->where(function ($query) use ($email, $phone): void {
                        if ($email !== '') {
                            $query->orWhereRaw('LOWER(customer_email) = ?', [$email]);
                        }

                        if ($phone !== null) {
                            $query->orWhere('customer_phone', $phone);
                        }
                    })
                    ->with(['store:id,slug,name,currency', 'table', 'items', 'review'])
                    ->orderByDesc('created_at')
                    ->limit(self::GLOBAL_ORDER_HISTORY_LIMIT)
                    ->get();

                $memberPointSummaries = Member::query()
                    ->where(function ($query) use ($email, $phone): void {
                        if ($email !== '') {
                            $query->orWhereRaw('LOWER(email) = ?', [$email]);
                        }

                        if ($phone !== null) {
                            $query->orWhere('phone', $phone);
                        }
                    })
                    ->with('store:id,slug,name,currency')
                    ->orderByDesc('points_balance')
                    ->orderByDesc('last_order_at')
                    ->get();
            }

            return view('customer.history', [
                'orders' => $orders,
                'memberPointSummaries' => $memberPointSummaries,
            ]);
        }

        $storeParam = (string) $request->query('store', '');
        $tableParam = (int) $request->query('table', 0);
        $orders = collect();

        if ($storeParam !== '' && $tableParam > 0) {
            $store = Store::query()
                ->where('slug', $storeParam)
                ->orWhere('id', $storeParam)
                ->first();

            if ($store instanceof Store) {
                $table = DiningTable::query()
                    ->where('id', $tableParam)
                    ->where('store_id', $store->id)
                    ->first();

                if ($table instanceof DiningTable) {
                    $orders = Order::query()
                        ->where('store_id', $store->id)
                        ->where('dining_table_id', $table->id)
                        ->where('order_type', 'dine_in')
                        ->with(['store:id,slug,name,currency', 'table', 'items', 'review'])
                        ->orderByDesc('created_at')
                        ->limit(self::ORDER_HISTORY_LIMIT)
                        ->get();
                }
            }
        }

        return view('customer.history', [
            'orders' => $orders,
            'memberPointSummaries' => collect(),
        ]);
    }

    public function reorderToCart(Request $request, Order $order)
    {
        $user = $request->user();
        if (! $user instanceof User || ! $user->isCustomer()) {
            abort(403);
        }

        $order->loadMissing(['store', 'table:id,store_id,qr_token', 'items:id,order_id,product_id,product_name,qty']);

        if (! $this->canReorderOrder($user, $order)) {
            abort(403);
        }

        $store = $order->store;
        if (! $store instanceof Store) {
            return redirect()
                ->route('customer.order.history')
                ->with('error', __('customer.reorder_unavailable'));
        }

        if (! $store->isOrderingAvailable()) {
            return redirect()
                ->route('customer.order.history')
                ->with('error', $store->orderingClosedMessage());
        }

        $orderItems = $order->items;
        if ($orderItems->isEmpty()) {
            return redirect()
                ->route('customer.order.history')
                ->with('error', __('customer.reorder_items_unavailable'));
        }

        $productIds = $orderItems
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $availableProducts = Product::query()
            ->where('store_id', $store->id)
            ->whereIn('id', $productIds->all())
            ->where('is_active', true)
            ->where('is_sold_out', false)
            ->get(['id', 'name', 'price'])
            ->keyBy('id');

        $addedQty = 0;
        $skippedQty = 0;

        if ($order->order_type === 'takeout') {
            $cartKey = TakeoutCartSession::currentCartSessionKey($request, $store->id);
            $cart = session()->get($cartKey, []);

            foreach ($orderItems as $orderItem) {
                $qty = max(1, (int) $orderItem->qty);
                $product = $availableProducts->get((int) $orderItem->product_id);

                if (! $product instanceof Product) {
                    $skippedQty += $qty;
                    continue;
                }

                $lineKey = $this->cartLineKey((int) $product->id, [], null);

                if (isset($cart[$lineKey])) {
                    $cart[$lineKey]['qty'] = (int) $cart[$lineKey]['qty'] + $qty;
                } else {
                    $cart[$lineKey] = [
                        'line_key' => $lineKey,
                        'product_id' => (int) $product->id,
                        'product_name' => (string) $product->name,
                        'base_price' => (int) $product->price,
                        'extra_price' => 0,
                        'price' => (int) $product->price,
                        'option_items' => [],
                        'option_label' => null,
                        'item_note' => null,
                        'qty' => $qty,
                        'subtotal' => 0,
                    ];
                }

                $addedQty += $qty;
            }

            foreach ($cart as &$item) {
                $item['subtotal'] = (int) $item['price'] * (int) $item['qty'];
            }
            unset($item);

            session()->put($cartKey, $cart);
            TakeoutCartSession::persistCurrentSessionCart($request, $store->id, $cart);

            if ($addedQty === 0) {
                return redirect()
                    ->route('customer.takeout.cart.show', ['store' => $store])
                    ->with('error', __('customer.reorder_items_unavailable'));
            }

            return redirect()
                ->route('customer.takeout.cart.show', ['store' => $store])
                ->with('success', $this->buildReorderSuccessMessage($addedQty, $skippedQty));
        }

        $table = $order->table;
        if (! $table instanceof DiningTable || (int) $table->store_id !== (int) $store->id) {
            return redirect()
                ->route('customer.order.history')
                ->with('error', __('customer.reorder_unavailable'));
        }

        $cart = DineInCartStore::getCart($request, $store->id, $table->id);

        foreach ($orderItems as $orderItem) {
            $qty = max(1, (int) $orderItem->qty);
            $product = $availableProducts->get((int) $orderItem->product_id);

            if (! $product instanceof Product) {
                $skippedQty += $qty;
                continue;
            }

            $lineKey = $this->cartLineKey((int) $product->id, [], null);

            if (isset($cart[$lineKey])) {
                $cart[$lineKey]['qty'] = (int) $cart[$lineKey]['qty'] + $qty;
            } else {
                $cart[$lineKey] = [
                    'line_key' => $lineKey,
                    'product_id' => (int) $product->id,
                    'product_name' => (string) $product->name,
                    'base_price' => (int) $product->price,
                    'extra_price' => 0,
                    'price' => (int) $product->price,
                    'option_items' => [],
                    'option_label' => null,
                    'item_note' => null,
                    'qty' => $qty,
                    'subtotal' => 0,
                ];
            }

            $addedQty += $qty;
        }

        foreach ($cart as &$item) {
            $item['subtotal'] = (int) $item['price'] * (int) $item['qty'];
        }
        unset($item);

        DineInCartStore::putCart($request, $store->id, $table->id, $cart);
        $this->broadcastDineInCartUpdated($request, $store, $table);

        if ($addedQty === 0) {
            return redirect()
                ->route('customer.dinein.cart.show', ['store' => $store, 'table' => $table])
                ->with('error', __('customer.reorder_items_unavailable'));
        }

        return redirect()
            ->route('customer.dinein.cart.show', ['store' => $store, 'table' => $table])
            ->with('success', $this->buildReorderSuccessMessage($addedQty, $skippedQty));
    }

    public function clearRememberedCustomerInfo(Store $store, DiningTable $table)
    {
        abort_unless($table->store_id === $store->id, 404);

        session()->forget(self::CUSTOMER_PROFILE_SESSION_KEY);

        return redirect()
            ->route('customer.dinein.cart.show', ['store' => $store, 'table' => $table])
            ->with('success', __('customer.clear_remembered_info'));
    }

    private function generateOrderNo(Store $store): string
    {
        return Order::generateOrderNoForStore((int) $store->id);
    }

    private function cartLineKey(int $productId, array $selectedOptions, ?string $itemNote): string
    {
        $json = json_encode([
            'options' => $selectedOptions,
            'item_note' => $itemNote,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $productId . '_' . md5($json ?: '[]');
    }

    private function sanitizeItemNote(?string $note, bool $allowItemNote): ?string
    {
        if (! $allowItemNote) {
            return null;
        }

        if ($note === null) {
            return null;
        }

        $trimmed = trim($note);

        return $trimmed === '' ? null : $trimmed;
    }

    private function composeOrderItemNote(?string $optionLabel, ?string $itemNote): ?string
    {
        $parts = [];

        if ($optionLabel !== null && trim($optionLabel) !== '') {
            $parts[] = trim($optionLabel);
        }

        if ($itemNote !== null && trim($itemNote) !== '') {
            $parts[] = __('customer.item_note_prefix') . ' ' . trim($itemNote);
        }

        if (count($parts) === 0) {
            return null;
        }

        return implode(' | ', $parts);
    }

    private function resolveSelectedOptions(Product $product, ?string $optionPayload): array
    {
        $groups = is_array($product->option_groups) ? $product->option_groups : [];
        if (empty($groups)) {
            return ['selected' => [], 'extra_price' => 0, 'label' => null];
        }

        $payload = [];
        if ($optionPayload !== null && trim($optionPayload) !== '') {
            $decoded = json_decode($optionPayload, true);
            if (! is_array($decoded)) {
                throw ValidationException::withMessages(['option_payload' => __('customer.error_option_payload_invalid')]);
            }
            $payload = $decoded;
        }

        $selected = [];
        $extraPrice = 0;
        $labelParts = [];

        foreach ($groups as $group) {
            if (! is_array($group)) {
                continue;
            }

            $groupId = (string) ($group['id'] ?? '');
            $groupName = (string) ($group['name'] ?? $groupId);
            $type = (string) ($group['type'] ?? 'single');
            $required = (bool) ($group['required'] ?? false);
            $choices = is_array($group['choices'] ?? null) ? $group['choices'] : [];
            $maxSelect = (int) ($group['max_select'] ?? 99);

            if ($groupId === '') {
                continue;
            }

            $rawSelection = $payload[$groupId] ?? [];
            if (! is_array($rawSelection)) {
                $rawSelection = [$rawSelection];
            }

            $rawSelection = array_values(array_filter(array_map('strval', $rawSelection), fn ($v) => $v !== ''));
            if ($type === 'single') {
                $rawSelection = array_slice($rawSelection, 0, 1);
            }

            if ($required && empty($rawSelection)) {
                throw ValidationException::withMessages(['option_payload' => __('customer.option_required_error', ['group' => $groupName])]);
            }

            if ($type === 'multiple' && count($rawSelection) > $maxSelect) {
                throw ValidationException::withMessages(['option_payload' => __('customer.option_max_select_error', ['group' => $groupName, 'max' => $maxSelect])]);
            }

            $choiceMap = [];
            foreach ($choices as $choice) {
                if (! is_array($choice)) {
                    continue;
                }

                $choiceId = (string) ($choice['id'] ?? '');
                if ($choiceId === '') {
                    continue;
                }

                $choiceMap[$choiceId] = $choice;
            }

            $groupSelected = [];
            $groupLabel = [];

            foreach ($rawSelection as $choiceId) {
                if (! isset($choiceMap[$choiceId])) {
                    continue;
                }

                $choice = $choiceMap[$choiceId];
                $name = (string) ($choice['name'] ?? $choiceId);
                $price = (int) ($choice['price'] ?? 0);

                $groupSelected[] = [
                    'id' => $choiceId,
                    'name' => $name,
                    'price' => $price,
                ];

                $groupLabel[] = $name . ($price > 0 ? '(+' . $price . ')' : '');
                $extraPrice += $price;
            }

            if ($required && empty($groupSelected)) {
                throw ValidationException::withMessages(['option_payload' => __('customer.option_required_error', ['group' => $groupName])]);
            }

            if (! empty($groupSelected)) {
                $selected[$groupId] = [
                    'name' => $groupName,
                    'type' => $type,
                    'items' => $groupSelected,
                ];

                $labelParts[] = $groupName . ': ' . implode(', ', $groupLabel);
            }
        }

        return [
            'selected' => $selected,
            'extra_price' => $extraPrice,
            'label' => empty($labelParts) ? null : implode(' / ', $labelParts),
        ];
    }

    private function customerPhoneValidationRules(Store $store): array
    {
        $expectedLength = $this->expectedCustomerPhoneLength($store);

        return [
            'nullable',
            'string',
            'max:32',
            function (string $attribute, mixed $value, \Closure $fail) use ($expectedLength): void {
                $raw = trim((string) ($value ?? ''));
                if ($raw === '') {
                    return;
                }

                $digits = preg_replace('/\D+/', '', $raw);
                if (! is_string($digits) || strlen($digits) !== $expectedLength) {
                    $fail(__('customer.phone_length_error', ['digits' => $expectedLength]));
                }
            },
        ];
    }

    private function expectedCustomerPhoneLength(Store $store): int
    {
        return match (strtolower((string) ($store->country_code ?? 'tw'))) {
            'cn' => 11,
            'tw', 'vn', 'us' => 10,
            default => 10,
        };
    }

    private function normalizeCustomerPhone(?string $phone, Store $store): ?string
    {
        if ($phone === null) {
            return null;
        }

        $raw = trim($phone);
        if ($raw === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw);
        if (! is_string($digits) || strlen($digits) !== $this->expectedCustomerPhoneLength($store)) {
            return null;
        }

        return $digits;
    }

    private function normalizeCustomerName(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        $normalized = trim($name);

        return $normalized === '' ? null : $normalized;
    }

    private function normalizeOptionalText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }

    private function normalizeCouponCode(?string $couponCode): ?string
    {
        if ($couponCode === null) {
            return null;
        }

        $normalized = strtoupper(trim($couponCode));

        return $normalized === '' ? null : $normalized;
    }

    private function findExistingMemberForCoupon(Store $store, ?string $email, ?string $phone, bool $lockForUpdate = false): ?Member
    {
        if ($email === null && $phone === null) {
            return null;
        }

        $query = Member::query()
            ->where('store_id', $store->id)
            ->where(function ($nested) use ($email, $phone) {
                if ($email !== null) {
                    $nested->orWhere('email', $email);
                }

                if ($phone !== null) {
                    $nested->orWhere('phone', $phone);
                }
            });

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    private function bindMemberCarrierCode(
        Store $store,
        ?string $customerName,
        ?string $customerEmail,
        ?string $customerPhone,
        array $invoicePayload
    ): void {
        if (($invoicePayload['invoice_flow'] ?? null) !== InvoiceFlow::MEMBER_CARRIER) {
            return;
        }

        $carrierCode = trim((string) ($invoicePayload['invoice_member_carrier_code'] ?? ''));
        if ($carrierCode === '') {
            return;
        }

        $member = $this->loyaltyService->resolveMember($store, $customerName, $customerEmail, $customerPhone);
        if (! $member) {
            return;
        }

        $member->invoice_carrier_code = strtoupper($carrierCode);
        $member->invoice_carrier_bound_at = now();
        $member->save();
    }

    private function formatCouponSummary(Coupon $coupon, int $discount, string $currencySymbol): string
    {
        $parts = [];

        if ($coupon->normalizedDiscountType() === 'percent') {
            $parts[] = __('customer.coupon_summary_percent_with_amount', [
                'value' => (int) $coupon->discount_value,
                'amount' => $currencySymbol . ' ' . number_format($discount),
            ]);
        } elseif ($coupon->hasDiscount()) {
            $parts[] = __('customer.coupon_summary_fixed', [
                'amount' => $currencySymbol . ' ' . number_format($discount),
            ]);
        }

        if ($coupon->hasBonusPointsReward()) {
            $parts[] = __('customer.coupon_summary_points_reward', [
                'amount' => $currencySymbol . ' ' . number_format(max((int) $coupon->reward_per_amount, 0)),
                'points' => (int) $coupon->reward_points,
            ]);
        }

        $summary = implode(' | ', array_filter($parts));
        if ($summary === '') {
            $summary = __('customer.coupon_summary_fixed', [
                'amount' => $currencySymbol . ' ' . number_format($discount),
            ]);
        }

        $minimum = max((int) $coupon->min_order_amount, 0);
        if ($minimum > 0) {
            $summary .= ' | ' . __('customer.coupon_summary_minimum', [
                'amount' => $currencySymbol . ' ' . number_format($minimum),
            ]);
        }

        return $summary;
    }
    private function canReorderOrder(User $user, Order $order): bool
    {
        $orderEmail = strtolower(trim((string) ($order->getRawOriginal('customer_email') ?? '')));
        $orderPhone = PhoneFormatter::digitsOnly((string) ($order->getRawOriginal('customer_phone') ?? ''), 32);

        $userEmail = strtolower(trim((string) ($user->email ?? '')));
        $userPhone = PhoneFormatter::digitsOnly((string) ($user->phone ?? ''), 32);

        $matchesEmail = $userEmail !== '' && $orderEmail !== '' && hash_equals($orderEmail, $userEmail);
        $matchesPhone = $userPhone !== null && $orderPhone !== null && hash_equals($orderPhone, $userPhone);

        return $matchesEmail || $matchesPhone;
    }

    private function buildReorderSuccessMessage(int $addedQty, int $skippedQty): string
    {
        if ($skippedQty > 0) {
            return __('customer.reorder_partial_added_to_cart', [
                'added' => $addedQty,
                'skipped' => $skippedQty,
            ]);
        }

        return __('customer.reorder_added_to_cart', [
            'count' => $addedQty,
        ]);
    }

    private function hydrateEditableProductMeta(Store $store, array $cart): array
    {
        if (empty($cart)) {
            return $cart;
        }

        $productIds = collect($cart)
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return $cart;
        }

        $products = Product::query()
            ->where('store_id', $store->id)
            ->whereIn('id', $productIds->all())
            ->get(['id', 'option_groups', 'allow_item_note'])
            ->keyBy('id');

        foreach ($cart as &$item) {
            $product = $products->get((int) ($item['product_id'] ?? 0));
            $item['editable_option_groups'] = is_array($product?->option_groups) ? $product->option_groups : [];
            $item['allow_item_note'] = (bool) ($product?->allow_item_note ?? false);
        }
        unset($item);

        return $cart;
    }

    private function findAppendableDineInOrder(Store $store, DiningTable $table): ?Order
    {
        return Order::query()
            ->where('store_id', $store->id)
            ->where('dining_table_id', $table->id)
            ->where('order_type', 'dine_in')
            ->whereNotIn('status', self::NON_APPENDABLE_ORDER_STATUSES)
            ->where(function ($query) {
                $query->where('payment_status', 'unpaid')
                    ->orWhereNull('payment_status');
            })
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();
    }

    private function getDineInOrderHistorySessionKey(Store $store, DiningTable $table): string
    {
        return self::ORDER_HISTORY_SESSION_PREFIX . $store->id . '_' . $table->id;
    }

    private function getGlobalOrderHistoryUuids(): array
    {
        $history = session()->get(self::GLOBAL_ORDER_HISTORY_SESSION_KEY, []);
        $uuids = $this->normalizeOrderHistoryUuids(is_array($history) ? $history : []);
        if (! empty($uuids)) {
            return $uuids;
        }

        $legacyUuids = $this->getLegacyOrderHistoryUuids();
        if (empty($legacyUuids)) {
            return [];
        }

        $uuids = Order::query()
            ->whereIn('uuid', $legacyUuids)
            ->orderByDesc('created_at')
            ->limit(self::GLOBAL_ORDER_HISTORY_LIMIT)
            ->pluck('uuid')
            ->map(fn ($uuid) => (string) $uuid)
            ->all();

        $uuids = $this->normalizeOrderHistoryUuids($uuids);
        if (! empty($uuids)) {
            session()->put(self::GLOBAL_ORDER_HISTORY_SESSION_KEY, $uuids);
        }

        return $uuids;
    }

    private function getLegacyOrderHistoryUuids(): array
    {
        $legacy = [];
        $sessionData = session()->all();

        foreach ($sessionData as $key => $value) {
            if (! is_string($key) || ! is_array($value)) {
                continue;
            }

            if (
                ! str_starts_with($key, self::ORDER_HISTORY_SESSION_PREFIX)
                && ! str_starts_with($key, self::TAKEOUT_ORDER_HISTORY_SESSION_PREFIX)
            ) {
                continue;
            }

            foreach ($value as $uuid) {
                if (! is_string($uuid)) {
                    continue;
                }

                $normalized = trim($uuid);
                if ($normalized === '') {
                    continue;
                }

                $legacy[] = $normalized;
            }
        }

        return $this->normalizeOrderHistoryUuids($legacy);
    }

    private function pushOrderToGlobalHistory(Order $order): void
    {
        $this->pushManyOrdersToGlobalHistory([$order->uuid]);
    }

    private function pushManyOrdersToGlobalHistory(array $uuids): void
    {
        $incoming = $this->normalizeOrderHistoryUuids($uuids);
        if (empty($incoming)) {
            return;
        }

        $history = session()->get(self::GLOBAL_ORDER_HISTORY_SESSION_KEY, []);
        $history = $this->normalizeOrderHistoryUuids(is_array($history) ? $history : []);

        foreach (array_reverse($incoming) as $uuid) {
            $history = array_values(array_filter($history, fn (string $value) => $value !== $uuid));
            array_unshift($history, $uuid);
        }

        session()->put(
            self::GLOBAL_ORDER_HISTORY_SESSION_KEY,
            array_slice($history, 0, self::GLOBAL_ORDER_HISTORY_LIMIT)
        );
    }

    private function getOrdersByUuids(array $uuids)
    {
        $normalizedUuids = $this->normalizeOrderHistoryUuids($uuids);
        if (empty($normalizedUuids)) {
            return collect();
        }

        $orders = Order::query()
            ->whereIn('uuid', $normalizedUuids)
            ->with(['store:id,slug,name,currency', 'table', 'items', 'review'])
            ->orderByDesc('created_at')
            ->get();

        $orderMap = $orders->keyBy('uuid');

        return collect($normalizedUuids)
            ->map(fn (string $uuid) => $orderMap->get($uuid))
            ->filter()
            ->values();
    }

    private function normalizeOrderHistoryUuids(array $uuids): array
    {
        $normalized = [];

        foreach ($uuids as $uuid) {
            if (! is_string($uuid)) {
                continue;
            }

            $value = trim($uuid);
            if ($value === '' || isset($normalized[$value])) {
                continue;
            }

            $normalized[$value] = true;
        }

        return array_keys($normalized);
    }

    private function pushDineInOrderToHistory(Store $store, DiningTable $table, Order $order): void
    {
        $history = session()->get($this->getDineInOrderHistorySessionKey($store, $table), []);
        if (! is_array($history)) {
            $history = [];
        }

        $history = array_values(array_filter(array_map('strval', $history), fn ($v) => $v !== '' && $v !== $order->uuid));
        array_unshift($history, $order->uuid);
        $history = array_slice($history, 0, self::ORDER_HISTORY_LIMIT);

        session()->put($this->getDineInOrderHistorySessionKey($store, $table), $history);
    }

    private function getDineInOrderHistory(Store $store, DiningTable $table)
    {
        $history = session()->get($this->getDineInOrderHistorySessionKey($store, $table), []);
        if (! is_array($history) || empty($history)) {
            return collect();
        }

        $uuids = array_values(array_filter(array_map('strval', $history), fn ($v) => $v !== ''));
        if (empty($uuids)) {
            return collect();
        }

        $orders = Order::query()
            ->select([
                'id',
                'uuid',
                'order_no',
                'store_id',
                'dining_table_id',
                'status',
                'payment_status',
                'created_at',
            ])
            ->where('store_id', $store->id)
            ->where('dining_table_id', $table->id)
            ->whereIn('uuid', $uuids)
            ->whereNotIn('status', self::NON_APPENDABLE_ORDER_STATUSES)
            ->where(function ($query) use ($store) {
                if ($store->isPrepayCheckout()) {
                    $query->whereNotIn('status', self::COMPLETED_ORDER_STATUSES);

                    return;
                }

                $query->where('payment_status', 'unpaid')
                    ->orWhereNull('payment_status');
            })
            ->orderByDesc('created_at')
            ->get();

        $orderMap = $orders->keyBy('uuid');

        return collect($uuids)
            ->map(fn ($uuid) => $orderMap->get($uuid))
            ->filter()
            ->values();
    }
}


