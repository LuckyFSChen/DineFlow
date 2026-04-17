<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Services\CustomerAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
        $sessionKey = 'takeout_cart_token_' . $store->id;

        if (! session()->has($sessionKey)) {
            session([$sessionKey => (string) Str::uuid()]);
        }

        return session($sessionKey);
    }

    protected function getTakeoutCartSessionKey(Store $store): string
    {
        return 'takeout_cart.' . $store->id . '.' . $this->getTakeoutCartToken($store);
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
        $rememberedCustomerInfo = session()->get(self::CUSTOMER_PROFILE_SESSION_KEY, []);
        $orderHistory = $this->getTakeoutOrderHistory($store);

        return view('customer.cart', compact('store', 'cart', 'total', 'orderingAvailable', 'rememberedCustomerInfo', 'orderHistory'));
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

        return redirect()->route('customer.takeout.cart.show', ['store' => $store]);
    }

    public function removeCartItem(Store $store, string $lineKey)
    {
        $this->ensureTakeoutEnabled($store);

        $cartKey = $this->getTakeoutCartSessionKey($store);
        $cart = session()->get($cartKey, []);

        if (isset($cart[$lineKey])) {
            unset($cart[$lineKey]);
            session()->put($cartKey, $cart);
        }

        return redirect()->route('customer.takeout.cart.show', ['store' => $store]);
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

        $order = DB::transaction(function () use ($store, $validated, $cart, $total, $cartToken, $shouldCreateAccount) {
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

        session()->forget($cartKey);
        session()->forget('takeout_cart_token_' . $store->id);
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
