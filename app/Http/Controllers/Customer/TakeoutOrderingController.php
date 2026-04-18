<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\CustomerAccountService;
use App\Support\TakeoutCartSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TakeoutOrderingController extends Controller
{
    private const CUSTOMER_PROFILE_SESSION_KEY = 'customer_order_profile';
    private const ORDER_HISTORY_SESSION_PREFIX = 'takeout_order_history_';
    private const ORDER_HISTORY_LIMIT = 8;

    public function __construct(private readonly CustomerAccountService $customerAccountService)
    {
    }

    protected function getTakeoutCartToken(Store $store): string
    {
        return TakeoutCartSession::currentToken(request(), $store->id);
    }

    protected function getTakeoutCartSessionKey(Store $store): string
    {
        return TakeoutCartSession::currentCartSessionKey(request(), $store->id);
    }

    public function menu(Store $store)
    {
        $this->ensureTakeoutEnabled($store);

        $categories = $store->categories()
            ->select(['id', 'store_id', 'name', 'sort'])
            ->where('is_active', true)
            ->with(['products' => function ($query) use ($store) {
                $query->select([
                    'id',
                    'store_id',
                    'category_id',
                    'name',
                    'description',
                    'price',
                    'image',
                    'option_groups',
                    'allow_item_note',
                    'sort',
                ])
                    ->where('store_id', $store->id)
                    ->where('is_active', true)
                    ->where('is_sold_out', false)
                    ->orderBy('sort');
            }])
            ->orderBy('sort')
            ->get();

        $orderingAvailable = $store->isOrderingAvailable();
        $cart = session()->get($this->getTakeoutCartSessionKey($store), []);
        $cartCount = collect($cart)->sum('qty');
        $cartTotal = collect($cart)->sum('subtotal');
        $cartPreviewItems = collect($cart)->values();
        $orderHistory = $this->getTakeoutOrderHistory($store);

        return view('customer.takeout.menu-mobile', compact(
            'store',
            'categories',
            'orderingAvailable',
            'cartCount',
            'cartTotal',
            'cartPreviewItems',
            'orderHistory'
        ));
    }

    public function addToCart(Request $request, Store $store)
    {
        $this->ensureTakeoutEnabled($store);

        if (! $store->isOrderingAvailable()) {
            return redirect()
                ->route('customer.takeout.menu', ['store' => $store])
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

        $cartKey = $this->getTakeoutCartSessionKey($store);
        $cart = session()->get($cartKey, []);

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

        session()->put($cartKey, $cart);
        TakeoutCartSession::persistCurrentSessionCart($request, $store->id, $cart);

        if ($this->shouldReturnJson($request)) {
            return response()->json($this->buildCartResponse(
                $store,
                $cart,
                __('customer.item_added_to_cart')
            ));
        }

        return redirect()
            ->route('customer.takeout.menu', ['store' => $store])
            ->with('success', __('customer.item_added_to_cart'));
    }

    public function cart(Store $store)
    {
        $this->ensureTakeoutEnabled($store);

        $cartKey = $this->getTakeoutCartSessionKey($store);
        $cart = session()->get($cartKey, []);
        $total = collect($cart)->sum('subtotal');
        $orderingAvailable = $store->isOrderingAvailable();
        $rememberedCustomerInfo = $this->resolvePrefilledCustomerInfo();
        $orderHistory = $this->getTakeoutOrderHistory($store);

        return view('customer.cart', compact('store', 'cart', 'total', 'orderingAvailable', 'rememberedCustomerInfo', 'orderHistory'));
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

    public function updateCartItem(Request $request, Store $store, string $lineKey)
    {
        $this->ensureTakeoutEnabled($store);

        $validated = $request->validate([
            'action' => ['required', 'in:increase,decrease'],
        ]);

        $cartKey = $this->getTakeoutCartSessionKey($store);
        $cart = session()->get($cartKey, []);

        if (! isset($cart[$lineKey])) {
            return redirect()
                ->route('customer.takeout.cart.show', ['store' => $store])
                ->with('error', __('customer.error_cart_empty'));
        }

        if ($validated['action'] === 'increase') {
            $cart[$lineKey]['qty'] = (int) $cart[$lineKey]['qty'] + 1;
        } else {
            $cart[$lineKey]['qty'] = (int) $cart[$lineKey]['qty'] - 1;

            if ($cart[$lineKey]['qty'] <= 0) {
                unset($cart[$lineKey]);
            }
        }

        foreach ($cart as &$item) {
            $item['subtotal'] = (int) $item['price'] * (int) $item['qty'];
        }
        unset($item);

        session()->put($cartKey, $cart);
        TakeoutCartSession::persistCurrentSessionCart($request, $store->id, $cart);

        if ($this->shouldReturnJson($request)) {
            return response()->json($this->buildCartResponse($store, $cart));
        }

        return redirect()->back();
    }

    public function removeCartItem(Request $request, Store $store, string $lineKey)
    {
        $this->ensureTakeoutEnabled($store);

        $cartKey = $this->getTakeoutCartSessionKey($store);
        $cart = session()->get($cartKey, []);

        if (isset($cart[$lineKey])) {
            unset($cart[$lineKey]);
            session()->put($cartKey, $cart);
            TakeoutCartSession::persistCurrentSessionCart($request, $store->id, $cart);
        }

        if ($this->shouldReturnJson($request)) {
            return response()->json($this->buildCartResponse($store, $cart));
        }

        return redirect()->back();
    }

    public function checkout(Request $request, Store $store)
    {
        $this->ensureTakeoutEnabled($store);

        if (! $store->isOrderingAvailable()) {
            return redirect()
                ->route('customer.takeout.cart.show', ['store' => $store])
                ->with('error', $store->orderingClosedMessage());
        }

        $validated = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => $this->customerPhoneValidationRules($store),
            'coupon_code' => ['nullable', 'string', 'max:64'],
            'note' => ['nullable', 'string'],
            'remember_customer_info' => ['nullable', 'boolean'],
            'create_account_with_phone' => ['nullable', 'boolean'],
        ]);

        $validated['customer_phone'] = $this->normalizeCustomerPhone($validated['customer_phone'] ?? null, $store);

        if (! $request->user() && $request->boolean('remember_customer_info')) {
            session()->put(self::CUSTOMER_PROFILE_SESSION_KEY, [
                'customer_name' => $validated['customer_name'] ?? '',
                'customer_email' => $validated['customer_email'] ?? '',
                'customer_phone' => $validated['customer_phone'] ?? '',
                'note' => $this->normalizeOptionalText($validated['note'] ?? null) ?? '',
            ]);
        } else {
            session()->forget(self::CUSTOMER_PROFILE_SESSION_KEY);
        }

        $cartKey = $this->getTakeoutCartSessionKey($store);
        $cart = session()->get($cartKey, []);

        if (empty($cart)) {
            return redirect()
                ->route('customer.takeout.cart.show', ['store' => $store])
                ->with('error', __('customer.error_cart_empty'));
        }

        $total = collect($cart)->sum('subtotal');
        $cartToken = $this->getTakeoutCartToken($store);
        $customerPhone = $this->normalizeOptionalText($validated['customer_phone'] ?? null);
        $phoneAlreadyRegistered = $this->customerAccountService->isPhoneRegistered($customerPhone);
        $shouldCreateAccount = ! $request->user()
            && $request->boolean('create_account_with_phone')
            && ! $phoneAlreadyRegistered;

        try {
            $order = DB::transaction(function () use ($store, $validated, $cart, $total, $cartToken, $shouldCreateAccount) {
                $this->assertCartItemsStillAvailable($store, $cart);

                $customerName = $this->normalizeOptionalText($validated['customer_name'] ?? null);
                $customerEmail = $this->normalizeOptionalText($validated['customer_email'] ?? null);
                $customerPhone = $this->normalizeOptionalText($validated['customer_phone'] ?? null);

                if ($shouldCreateAccount) {
                    $this->customerAccountService->registerOrUpdateFromOrder(
                        $customerPhone,
                        $customerName,
                        $customerEmail
                    );
                }

                $order = Order::create([
                    'store_id' => $store->id,
                    'dining_table_id' => null,
                    'order_type' => 'takeout',
                    'cart_token' => $cartToken,
                    'order_no' => $this->generateOrderNo($store),
                    'status' => 'pending',
                    'payment_status' => 'unpaid',
                    'customer_name' => $customerName,
                    'customer_email' => $customerEmail,
                    'customer_phone' => $customerPhone,
                    'note' => $validated['note'] ?? null,
                    'subtotal' => $total,
                    'total' => $total,
                ]);

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

                return $order;
            });
        } catch (ValidationException $exception) {
            return redirect()
                ->route('customer.takeout.cart.show', ['store' => $store])
                ->with('error', $exception->validator->errors()->first() ?: __('customer.item_not_available'));
        }

        session()->forget($cartKey);
        session()->forget(TakeoutCartSession::tokenSessionKey($store->id));
        TakeoutCartSession::clearPersistedCartForCurrentUser($request, $store->id);
        $this->pushTakeoutOrderToHistory($store, $order);

        return redirect()->route('customer.order.success', [
            'store' => $store->slug,
            'order' => $order->uuid,
        ]);
    }

    public function checkPhoneRegistered(Request $request, Store $store)
    {
        $this->ensureTakeoutEnabled($store);

        $validated = $request->validate([
            'customer_phone' => ['nullable', 'string', 'max:32'],
        ]);

        $normalizedPhone = $this->normalizeCustomerPhone($validated['customer_phone'] ?? null, $store);

        return response()->json([
            'ok' => true,
            'registered' => $this->customerAccountService->isPhoneRegistered($normalizedPhone),
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
                'cart' => __('customer.error_items_unavailable', ['items' => implode('、', $unavailableNames)]),
            ]);
        }
    }

    public function clearRememberedCustomerInfo(Store $store)
    {
        $this->ensureTakeoutEnabled($store);

        session()->forget(self::CUSTOMER_PROFILE_SESSION_KEY);

        return redirect()
            ->route('customer.takeout.cart.show', ['store' => $store])
            ->with('success', '已清除記住的訂單資訊。');
    }

    private function generateOrderNo(Store $store): string
    {
        $storeToken = str_pad((string) $store->id, 2, '0', STR_PAD_LEFT);

        for ($attempt = 0; $attempt < 8; $attempt++) {
            $candidate = now()->format('mdHisv') . '-' . $storeToken . random_int(10, 99);

            if (! Order::where('order_no', $candidate)->exists()) {
                return $candidate;
            }

            usleep(10000);
        }

        return now()->format('mdHisv') . '-' . $storeToken . random_int(100, 999);
    }

    private function ensureTakeoutEnabled(Store $store): void
    {
        abort_unless($store->takeout_qr_enabled, 404);
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
            $parts[] = '備註：' . trim($itemNote);
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

                $labelParts[] = $groupName . '：' . implode('、', $groupLabel);
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

    private function normalizeOptionalText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }

    private function shouldReturnJson(Request $request): bool
    {
        return $request->expectsJson()
            || $request->wantsJson()
            || $request->ajax();
    }

    private function buildCartResponse(Store $store, array $cart, ?string $message = null): array
    {
        $currencySymbol = $this->currencySymbol($store);
        $items = collect($cart)
            ->values()
            ->map(function (array $item) use ($store, $currencySymbol) {
                $lineKey = (string) ($item['line_key'] ?? '');

                return [
                    'line_key' => $lineKey,
                    'product_name' => (string) ($item['product_name'] ?? __('customer.product_default_name')),
                    'option_label' => $item['option_label'] ?? null,
                    'item_note' => $item['item_note'] ?? null,
                    'item_note_display' => ! empty($item['item_note'])
                        ? __('customer.item_note_prefix') . ' ' . $item['item_note']
                        : null,
                    'qty' => (int) ($item['qty'] ?? 0),
                    'price' => (int) ($item['price'] ?? 0),
                    'price_display' => $currencySymbol . ' ' . number_format((int) ($item['price'] ?? 0)),
                    'subtotal' => (int) ($item['subtotal'] ?? 0),
                    'subtotal_display' => $currencySymbol . ' ' . number_format((int) ($item['subtotal'] ?? 0)),
                    'update_urls' => [
                        'increase' => route('customer.takeout.cart.items.update', ['store' => $store, 'lineKey' => $lineKey]),
                        'decrease' => route('customer.takeout.cart.items.update', ['store' => $store, 'lineKey' => $lineKey]),
                    ],
                    'remove_url' => route('customer.takeout.cart.items.destroy', ['store' => $store, 'lineKey' => $lineKey]),
                ];
            })
            ->values();

        $count = (int) $items->sum('qty');
        $lineCount = $items->count();
        $total = (int) $items->sum('subtotal');

        return [
            'ok' => true,
            'message' => $message,
            'cart' => [
                'count' => $count,
                'line_count' => $lineCount,
                'total' => $total,
                'total_display' => $currencySymbol . ' ' . number_format($total),
                'bar_text' => $count > 0
                    ? __('customer.cart_bar_total', [
                        'count' => $count,
                        'currency' => $currencySymbol,
                        'total' => number_format($total),
                    ])
                    : __('customer.cart_bar_empty'),
                'view_cart_label' => __('customer.view_cart') . ($count > 0 ? ' (' . $count . ')' : ''),
                'preview_items' => $items->take(6)->all(),
                'remaining_preview_count' => max(0, $lineCount - 6),
                'remaining_preview_text' => $lineCount > 6
                    ? __('customer.more_items_in_cart', ['count' => $lineCount - 6])
                    : null,
                'empty_preview_text' => __('customer.no_products_available'),
                'cart_url' => route('customer.takeout.cart.show', ['store' => $store]),
                'items' => $items->all(),
            ],
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

    private function getTakeoutOrderHistorySessionKey(Store $store): string
    {
        return self::ORDER_HISTORY_SESSION_PREFIX . $store->id;
    }

    private function pushTakeoutOrderToHistory(Store $store, Order $order): void
    {
        $history = session()->get($this->getTakeoutOrderHistorySessionKey($store), []);
        if (! is_array($history)) {
            $history = [];
        }

        $history = array_values(array_filter(array_map('strval', $history), fn ($v) => $v !== '' && $v !== $order->uuid));
        array_unshift($history, $order->uuid);
        $history = array_slice($history, 0, self::ORDER_HISTORY_LIMIT);

        session()->put($this->getTakeoutOrderHistorySessionKey($store), $history);
    }

    private function getTakeoutOrderHistory(Store $store)
    {
        $history = session()->get($this->getTakeoutOrderHistorySessionKey($store), []);
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
                'order_type',
                'status',
                'payment_status',
                'created_at',
            ])
            ->where('store_id', $store->id)
            ->where('order_type', 'takeout')
            ->whereIn('uuid', $uuids)
            ->orderByDesc('created_at')
            ->get();

        $orderMap = $orders->keyBy('uuid');

        return collect($uuids)
            ->map(fn ($uuid) => $orderMap->get($uuid))
            ->filter()
            ->values();
    }
}
