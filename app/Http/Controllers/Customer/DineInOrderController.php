<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DineInOrderController extends Controller
{
    private const CUSTOMER_PROFILE_SESSION_KEY = 'customer_order_profile';
    private const ORDER_HISTORY_SESSION_PREFIX = 'dinein_order_history_';
    private const ORDER_HISTORY_LIMIT = 8;

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
        ]);

        $product = Product::where('id', $validated['product_id'])
            ->where('store_id', $store->id)
            ->where('is_active', true)
            ->where('is_sold_out', false)
            ->firstOrFail();

        $cartKey = $this->getDineInCartSessionKey($store, $table);
        $cart = session()->get($cartKey, []);

        $optionResult = $this->resolveSelectedOptions($product, $validated['option_payload'] ?? null);
        $unitPrice = (int) $product->price + (int) $optionResult['extra_price'];
        $lineKey = $this->cartLineKey($product->id, $optionResult['selected']);

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
            ->with('success', '商品已加入購物車。');
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
            'customer_phone' => ['nullable', 'regex:/^09\d{2}-?\d{3}-?\d{3}$/'],
            'note' => ['nullable', 'string'],
            'remember_customer_info' => ['nullable', 'boolean'],
        ]);

        $validated['customer_phone'] = $this->normalizeTaiwanMobilePhone($validated['customer_phone'] ?? null);

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
                ->with('error', '購物車是空的。');
        }

        $total = collect($cart)->sum('subtotal');

        $order = DB::transaction(function () use ($store, $table, $validated, $cart, $total) {
            $order = Order::create([
                'store_id' => $store->id,
                'dining_table_id' => $table->id,
                'order_type' => 'dine_in',
                'cart_token' => null,
                'order_no' => $this->generateOrderNo($store),
                'status' => 'pending',
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_email' => $validated['customer_email'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
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
                    'note' => $item['option_label'] ?? null,
                ]);
            }

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
        $date = now()->format('md');
        $count = Order::where('store_id', $store->id)
            ->whereDate('created_at', today())
            ->lockForUpdate()
            ->count() + 1;

        return $date . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    private function cartLineKey(int $productId, array $selectedOptions): string
    {
        $json = json_encode($selectedOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $productId . '_' . md5($json ?: '[]');
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
                throw ValidationException::withMessages(['option_payload' => '選配資料格式錯誤。']);
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
                throw ValidationException::withMessages(['option_payload' => "{$groupName} 為必選。"]);
            }

            if ($type === 'multiple' && count($rawSelection) > $maxSelect) {
                throw ValidationException::withMessages(['option_payload' => "{$groupName} 最多可選 {$maxSelect} 項。"]);
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
                throw ValidationException::withMessages(['option_payload' => "{$groupName} 為必選。"]);
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

    private function normalizeTaiwanMobilePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone ?? '');
        if (! is_string($digits) || strlen($digits) !== 10 || ! str_starts_with($digits, '09')) {
            return null;
        }

        return substr($digits, 0, 4) . '-' . substr($digits, 4, 3) . '-' . substr($digits, 7, 3);
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
