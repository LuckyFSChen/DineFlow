<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Services\CustomerAccountService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DineInOrderController extends Controller
{
    private const CUSTOMER_PROFILE_SESSION_KEY = 'customer_order_profile';
    private const ORDER_HISTORY_SESSION_PREFIX = 'dinein_order_history_';
    private const ORDER_HISTORY_LIMIT = 8;
    private const APPENDABLE_ORDER_STATUSES = [
        'pending',
        'accepted',
        'confirmed',
        'received',
        'preparing',
        'processing',
        'cooking',
        'in_progress',
    ];

    public function __construct(private readonly CustomerAccountService $customerAccountService)
    {
    }

    protected function getDineInCartSessionKey(Store $store, DiningTable $table): string
    {
        return 'dinein_cart.' . $store->id . '.' . $table->id;
    }

    public function addToCart(Request $request, Store $store, DiningTable $table)
    {
        abort_unless($table->store_id === $store->id, 404);

        if (! $store->isOrderingAvailable()) {
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

        $cartKey = $this->getDineInCartSessionKey($store, $table);
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

        return redirect()
            ->route('customer.dinein.menu', ['store' => $store, 'table' => $table])
            ->with('success', __('customer.item_added_to_cart'));
    }

    public function cart(Store $store, DiningTable $table)
    {
        abort_unless($table->store_id === $store->id, 404);

        $cartKey = $this->getDineInCartSessionKey($store, $table);
        $cart = session()->get($cartKey, []);
        $total = collect($cart)->sum('subtotal');
        $orderingAvailable = $store->isOrderingAvailable();
        $rememberedCustomerInfo = session()->get(self::CUSTOMER_PROFILE_SESSION_KEY, []);
        $orderHistory = $this->getDineInOrderHistory($store, $table);

        return view('customer.dine-in.cart', compact('store', 'table', 'cart', 'total', 'orderingAvailable', 'rememberedCustomerInfo', 'orderHistory'));
    }

    public function updateCartItem(Request $request, Store $store, DiningTable $table, string $lineKey)
    {
        abort_unless($table->store_id === $store->id, 404);

        $validated = $request->validate([
            'action' => ['required', 'in:increase,decrease'],
        ]);

        $cartKey = $this->getDineInCartSessionKey($store, $table);
        $cart = session()->get($cartKey, []);

        if (! isset($cart[$lineKey])) {
            return redirect()
                ->route('customer.dinein.cart.show', ['store' => $store, 'table' => $table])
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

        return redirect()->route('customer.dinein.cart.show', ['store' => $store, 'table' => $table]);
    }

    public function removeCartItem(Store $store, DiningTable $table, string $lineKey)
    {
        abort_unless($table->store_id === $store->id, 404);

        $cartKey = $this->getDineInCartSessionKey($store, $table);
        $cart = session()->get($cartKey, []);

        if (isset($cart[$lineKey])) {
            unset($cart[$lineKey]);
            session()->put($cartKey, $cart);
        }

        return redirect()->route('customer.dinein.cart.show', ['store' => $store, 'table' => $table]);
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
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => $this->customerPhoneValidationRules($store),
            'note' => ['nullable', 'string'],
            'remember_customer_info' => ['nullable', 'boolean'],
            'create_account_with_phone' => ['nullable', 'boolean'],
        ]);

        $validated['customer_phone'] = $this->normalizeCustomerPhone($validated['customer_phone'] ?? null, $store);

        if ($request->boolean('remember_customer_info')) {
            session()->put(self::CUSTOMER_PROFILE_SESSION_KEY, [
                'customer_name' => $validated['customer_name'] ?? '',
                'customer_email' => $validated['customer_email'] ?? '',
                'customer_phone' => $validated['customer_phone'] ?? '',
            ]);
        } else {
            session()->forget(self::CUSTOMER_PROFILE_SESSION_KEY);
        }

        $cartKey = $this->getDineInCartSessionKey($store, $table);
        $cart = session()->get($cartKey, []);

        if (empty($cart)) {
            return redirect()
                ->route('customer.dinein.cart.show', ['store' => $store, 'table' => $table])
                ->with('error', __('customer.error_cart_empty'));
        }

        $total = collect($cart)->sum('subtotal');
        $shouldCreateAccount = ! $request->user() && $request->boolean('create_account_with_phone');

        $order = DB::transaction(function () use ($store, $table, $validated, $cart, $total, $shouldCreateAccount) {
            $customerName = $this->normalizeCustomerName($validated['customer_name'] ?? null);
            $customerEmail = $this->normalizeOptionalText($validated['customer_email'] ?? null);
            $customerPhone = $this->normalizeOptionalText($validated['customer_phone'] ?? null);
            $customerNote = $this->normalizeOptionalText($validated['note'] ?? null);

            if ($shouldCreateAccount) {
                $this->customerAccountService->registerOrUpdateFromOrder(
                    $customerPhone,
                    $customerName,
                    $customerEmail
                );
            }

            $order = $this->findAppendableDineInOrder(
                $store,
                $table,
                $customerName
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
                    'customer_name' => $customerName,
                    'customer_email' => $customerEmail,
                    'customer_phone' => $customerPhone,
                    'note' => $customerNote,
                    'subtotal' => 0,
                    'total' => 0,
                ]);
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
            $order->total = (int) $order->total + (int) $total;
            if ($order->customer_name === null && $customerName !== null) {
                $order->customer_name = $customerName;
            }
            if ($order->customer_email === null && $customerEmail !== null) {
                $order->customer_email = $customerEmail;
            }
            if ($order->customer_phone === null && $customerPhone !== null) {
                $order->customer_phone = $customerPhone;
            }
            if ($order->note === null && $customerNote !== null) {
                $order->note = $customerNote;
            }
            $order->save();

            return $order;
        });

        session()->forget($cartKey);
        $this->pushDineInOrderToHistory($store, $table, $order);

        return redirect()->route('customer.order.success', [
            'store' => $store->slug,
            'order' => $order->uuid,
        ]);
    }

    public function success(Store $store, Order $order)
    {
        abort_unless($order->store_id === $store->id, 404);

        $order->load('items', 'store', 'table');

        return view('customer.success', compact('order', 'store'));
    }

    public function orderStatus(Store $store, Order $order): JsonResponse
    {
        abort_unless($order->store_id === $store->id, 404);

        return response()->json([
            'ok' => true,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'customer_status_label' => $order->customer_status_label,
            'cancel_reasons' => $order->resolvedCancelReasons(),
        ]);
    }

    public function history(Request $request, Store $store)
    {
        $remembered = session()->get(self::CUSTOMER_PROFILE_SESSION_KEY, []);

        $email = trim((string) $request->query('customer_email', $remembered['customer_email'] ?? ''));
        $phoneRaw = trim((string) $request->query('customer_phone', $remembered['customer_phone'] ?? ''));

        $validator = Validator::make([
            'customer_email' => $email,
            'customer_phone' => $phoneRaw,
        ], [
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => $this->customerPhoneValidationRules($store),
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $phone = $this->normalizeCustomerPhone($phoneRaw, $store);
        $hasFilters = $email !== '' || $phone !== null;
        $hasStrongLookup = $email !== '' && $phone !== null;

        $orders = collect();
        if ($hasStrongLookup) {
            $orders = Order::query()
                ->where('store_id', $store->id)
                ->where('customer_email', $email)
                ->where('customer_phone', $phone)
                ->with('table')
                ->orderByDesc('created_at')
                ->limit(50)
                ->get();

            session()->put(self::CUSTOMER_PROFILE_SESSION_KEY, [
                'customer_name' => $remembered['customer_name'] ?? '',
                'customer_email' => $email,
                'customer_phone' => $phone ?? '',
            ]);
        }

        return view('customer.history', [
            'store' => $store,
            'orders' => $orders,
            'customerEmail' => $email,
            'customerPhone' => $phoneRaw,
            'hasFilters' => $hasFilters,
            'requiresBothIdentifiers' => $hasFilters && ! $hasStrongLookup,
        ]);
    }

    public function clearRememberedCustomerInfo(Store $store, DiningTable $table)
    {
        abort_unless($table->store_id === $store->id, 404);

        session()->forget(self::CUSTOMER_PROFILE_SESSION_KEY);

        return redirect()
            ->route('customer.dinein.cart.show', ['store' => $store, 'table' => $table])
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

    private function findAppendableDineInOrder(Store $store, DiningTable $table, ?string $customerName): ?Order
    {
        if ($customerName === null) {
            return null;
        }

        return Order::query()
            ->where('store_id', $store->id)
            ->where('dining_table_id', $table->id)
            ->where('order_type', 'dine_in')
            ->where('customer_name', $customerName)
            ->whereIn('status', self::APPENDABLE_ORDER_STATUSES)
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
                    'store_id',
                    'dining_table_id',
                    'status',
                    'payment_status',
                    'created_at',
                ])
                ->where('store_id', $store->id)
                ->where('dining_table_id', $table->id)
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
