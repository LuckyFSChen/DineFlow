<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Validation\ValidationException;

trait BuildsMerchantOrderPageData
{
    protected function merchantOrderPageViewData(Store $store): array
    {
        $categories = Category::query()
            ->where('store_id', $store->id)
            ->where('is_active', true)
            ->with(['products' => function ($query) use ($store) {
                $query->where('store_id', $store->id)
                    ->where('is_active', true)
                    ->where('is_sold_out', false)
                    ->orderBy('sort')
                    ->orderBy('id');
            }])
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        $categoriesPayload = $categories->map(function (Category $category) {
            return [
                'id' => (int) $category->id,
                'name' => (string) $category->name,
                'prep_time_minutes' => $category->prep_time_minutes ? (int) $category->prep_time_minutes : null,
                'product_count' => $category->products->count(),
                'products' => $category->products->map(function (Product $product) use ($category) {
                    $optionGroups = is_array($product->option_groups) ? $product->option_groups : [];
                    $optionCount = collect($optionGroups)
                        ->sum(fn ($group) => is_array($group['choices'] ?? null) ? count($group['choices']) : 0);

                    $requiredGroupCount = collect($optionGroups)
                        ->filter(fn ($group) => is_array($group) && (bool) ($group['required'] ?? false))
                        ->count();

                    $imageUrl = null;
                    if (filled($product->image)) {
                        $imageUrl = str_starts_with((string) $product->image, 'http')
                            ? (string) $product->image
                            : asset('storage/'.ltrim((string) $product->image, '/'));
                    }

                    return [
                        'id' => (int) $product->id,
                        'category_id' => (int) $category->id,
                        'name' => (string) $product->name,
                        'description' => (string) ($product->description ?? ''),
                        'price' => (int) $product->price,
                        'image_url' => $imageUrl,
                        'option_groups' => $optionGroups,
                        'option_group_count' => count($optionGroups),
                        'option_count' => (int) $optionCount,
                        'required_group_count' => (int) $requiredGroupCount,
                        'allow_item_note' => (bool) $product->allow_item_note,
                        'category_name' => (string) $category->name,
                    ];
                })->values()->all(),
            ];
        })->values();

        return [
            'store' => $store,
            'tables' => DiningTable::query()
                ->where('store_id', $store->id)
                ->orderBy('table_no')
                ->get(['id', 'store_id', 'table_no', 'status']),
            'categories' => $categories,
            'tablesPayload' => $this->merchantOrderTablesPayload($store),
            'categoriesPayload' => $categoriesPayload,
            'initialCartItems' => $this->buildInitialCartItemsFromOldInput($store),
            'currencySymbol' => $this->currencySymbol($store),
            'checkoutTiming' => $store->checkout_timing ?? 'postpay',
            'defaultOrderType' => old('order_type', 'dine_in') === 'takeout' ? 'takeout' : 'dine_in',
            'defaultTableId' => (int) old('dining_table_id', 0),
            'defaultCustomerName' => (string) old('customer_name', ''),
            'defaultCustomerPhone' => (string) old('customer_phone', ''),
            'defaultNote' => (string) old('note', ''),
        ];
    }

    protected function merchantOrderTablesPayload(Store $store): array
    {
        $tables = DiningTable::query()
            ->where('store_id', $store->id)
            ->orderBy('table_no')
            ->get(['id', 'store_id', 'table_no', 'status']);

        $openOrders = Order::query()
            ->where('store_id', $store->id)
            ->where('order_type', 'dine_in')
            ->whereNotIn('status', $this->nonAppendableOrderStatuses())
            ->where(function ($query) {
                $query->where('payment_status', 'unpaid')
                    ->orWhereNull('payment_status');
            })
            ->withCount('items')
            ->orderByDesc('id')
            ->get(['id', 'dining_table_id', 'order_no', 'status', 'payment_status', 'total'])
            ->unique('dining_table_id')
            ->keyBy('dining_table_id');

        return $tables->map(function (DiningTable $table) use ($openOrders) {
            $openOrder = $openOrders->get($table->id);

            return [
                'id' => (int) $table->id,
                'table_no' => (string) $table->table_no,
                'status' => (string) $table->status,
                'status_label' => $table->status === 'inactive'
                    ? __('merchant_order.table_status_inactive')
                    : __('merchant_order.table_status_available'),
                'open_order' => $openOrder ? [
                    'order_no' => (string) $openOrder->order_no,
                    'status' => (string) $openOrder->status,
                    'payment_status' => (string) $openOrder->payment_status,
                    'items_count' => (int) $openOrder->items_count,
                    'total' => (int) $openOrder->total,
                ] : null,
            ];
        })->values()->all();
    }

    protected function resolveOrderItems(Store $store, array $items): array
    {
        $productIds = collect($items)
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $products = Product::query()
            ->where('store_id', $store->id)
            ->whereIn('id', $productIds)
            ->where('is_active', true)
            ->where('is_sold_out', false)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $resolved = [];
        $unavailableNames = [];

        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $product = $products->get($productId);

            if (! $product instanceof Product) {
                $unavailableNames[] = __('merchant_order.unavailable_product_fallback');
                continue;
            }

            $qty = max((int) ($item['qty'] ?? 1), 1);
            $optionResult = $this->resolveSelectedOptions($product, $item['option_payload'] ?? null);
            $itemNote = $this->sanitizeItemNote($item['item_note'] ?? null, (bool) $product->allow_item_note);
            $price = (int) $product->price + (int) $optionResult['extra_price'];

            $resolved[] = [
                'product_id' => (int) $product->id,
                'product_name' => (string) $product->name,
                'price' => $price,
                'qty' => $qty,
                'subtotal' => $price * $qty,
                'note' => $this->composeOrderItemNote($optionResult['label'] ?? null, $itemNote),
            ];
        }

        if ($unavailableNames !== []) {
            throw ValidationException::withMessages([
                'items' => __('merchant_order.error_unavailable_items'),
            ]);
        }

        if ($resolved === []) {
            throw ValidationException::withMessages([
                'items' => __('merchant_order.validation_items_required'),
            ]);
        }

        return $resolved;
    }

    protected function buildInitialCartItemsFromOldInput(Store $store): array
    {
        $oldItems = old('items', []);
        if (! is_array($oldItems) || $oldItems === []) {
            return [];
        }

        $productIds = collect($oldItems)
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return [];
        }

        $products = Product::query()
            ->where('store_id', $store->id)
            ->whereIn('id', $productIds->all())
            ->get()
            ->keyBy('id');

        $items = [];

        foreach ($oldItems as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $product = $products->get($productId);

            if (! $product instanceof Product) {
                continue;
            }

            $optionPayload = is_string($item['option_payload'] ?? null)
                ? (string) $item['option_payload']
                : null;

            try {
                $optionResult = $this->resolveSelectedOptions($product, $optionPayload);
            } catch (ValidationException) {
                $optionResult = [
                    'selected' => [],
                    'extra_price' => 0,
                    'label' => null,
                ];
                $optionPayload = null;
            }

            $itemNote = $this->sanitizeItemNote($item['item_note'] ?? null, (bool) $product->allow_item_note);
            $price = (int) $product->price + (int) $optionResult['extra_price'];
            $qty = max((int) ($item['qty'] ?? 1), 1);

            $items[] = [
                'productId' => (int) $product->id,
                'productName' => (string) $product->name,
                'categoryName' => (string) optional($product->category)->name,
                'basePrice' => (int) $product->price,
                'price' => $price,
                'qty' => $qty,
                'subtotal' => $price * $qty,
                'optionLabel' => $optionResult['label'],
                'optionPayload' => $optionPayload,
                'itemNote' => $itemNote,
            ];
        }

        return $items;
    }

    protected function resolveSelectedOptions(Product $product, ?string $optionPayload): array
    {
        $groups = is_array($product->option_groups) ? $product->option_groups : [];
        if ($groups === []) {
            return ['selected' => [], 'extra_price' => 0, 'label' => null];
        }

        $payload = [];
        if ($optionPayload !== null && trim($optionPayload) !== '') {
            $decoded = json_decode($optionPayload, true);

            if (! is_array($decoded)) {
                throw ValidationException::withMessages([
                    'items' => __('merchant_order.error_invalid_option_payload'),
                ]);
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
            $maxSelect = max((int) ($group['max_select'] ?? 99), 1);

            if ($groupId === '') {
                continue;
            }

            $rawSelection = $payload[$groupId] ?? [];
            if (! is_array($rawSelection)) {
                $rawSelection = [$rawSelection];
            }

            $rawSelection = array_values(array_filter(array_map('strval', $rawSelection), fn ($value) => $value !== ''));

            if ($type === 'single') {
                $rawSelection = array_slice($rawSelection, 0, 1);
            }

            if ($required && $rawSelection === []) {
                throw ValidationException::withMessages([
                    'items' => __('merchant_order.error_required_group', ['group' => $groupName]),
                ]);
            }

            if ($type === 'multiple' && count($rawSelection) > $maxSelect) {
                throw ValidationException::withMessages([
                    'items' => __('merchant_order.error_group_max_select', ['group' => $groupName, 'count' => $maxSelect]),
                ]);
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

                $groupLabel[] = $name.($price > 0 ? ' (+'.$price.')' : '');
                $extraPrice += $price;
            }

            if ($required && $groupSelected === []) {
                throw ValidationException::withMessages([
                    'items' => __('merchant_order.error_required_group', ['group' => $groupName]),
                ]);
            }

            if ($groupSelected !== []) {
                $selected[$groupId] = [
                    'name' => $groupName,
                    'type' => $type,
                    'items' => $groupSelected,
                ];

                $labelParts[] = $groupName.': '.implode(', ', $groupLabel);
            }
        }

        return [
            'selected' => $selected,
            'extra_price' => $extraPrice,
            'label' => $labelParts === [] ? null : implode(' / ', $labelParts),
        ];
    }

    protected function sanitizeItemNote(?string $note, bool $allowItemNote): ?string
    {
        if (! $allowItemNote || $note === null) {
            return null;
        }

        $trimmed = trim($note);

        return $trimmed === '' ? null : $trimmed;
    }

    protected function composeOrderItemNote(?string $optionLabel, ?string $itemNote): ?string
    {
        $parts = [];

        if ($optionLabel !== null && trim($optionLabel) !== '') {
            $parts[] = trim($optionLabel);
        }

        if ($itemNote !== null && trim($itemNote) !== '') {
            $parts[] = __('merchant_order.item_note_prefix').trim($itemNote);
        }

        return $parts === [] ? null : implode(' | ', $parts);
    }

    protected function customerPhoneValidationRules(Store $store): array
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
                    $fail(__('merchant_order.validation_customer_phone_length', ['digits' => $expectedLength]));
                }
            },
        ];
    }

    protected function expectedCustomerPhoneLength(Store $store): int
    {
        return match (strtolower((string) ($store->country_code ?? 'tw'))) {
            'cn' => 11,
            'tw', 'vn', 'us' => 10,
            default => 10,
        };
    }

    protected function normalizeCustomerPhone(?string $phone, Store $store): ?string
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

    protected function normalizeOptionalText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }

    protected function findAppendableDineInOrder(Store $store, DiningTable $table): ?Order
    {
        return Order::query()
            ->where('store_id', $store->id)
            ->where('dining_table_id', $table->id)
            ->where('order_type', 'dine_in')
            ->whereNotIn('status', $this->nonAppendableOrderStatuses())
            ->where(function ($query) {
                $query->where('payment_status', 'unpaid')
                    ->orWhereNull('payment_status');
            })
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();
    }

    protected function currencySymbol(Store $store): string
    {
        return match (strtolower((string) ($store->currency ?? 'twd'))) {
            'vnd' => 'VND',
            'cny' => 'CNY',
            'usd' => 'USD',
            default => 'NT$',
        };
    }

    protected function nonAppendableOrderStatuses(): array
    {
        return ['cancel', 'cancelled', 'canceled'];
    }

    protected function completedOrderStatuses(): array
    {
        return ['complete', 'completed', 'ready', 'ready_for_pickup'];
    }
}
