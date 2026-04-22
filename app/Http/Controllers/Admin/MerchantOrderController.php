<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Support\InvoiceFlow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MerchantOrderController extends Controller
{
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

    public function create(Request $request, Store $store): View
    {
        $this->authorize('update', $store);

        $tables = DiningTable::query()
            ->where('store_id', $store->id)
            ->orderBy('table_no')
            ->get(['id', 'store_id', 'table_no', 'status']);

        $openOrders = Order::query()
            ->where('store_id', $store->id)
            ->where('order_type', 'dine_in')
            ->whereNotIn('status', self::NON_APPENDABLE_ORDER_STATUSES)
            ->where(function ($query) {
                $query->where('payment_status', 'unpaid')
                    ->orWhereNull('payment_status');
            })
            ->withCount('items')
            ->orderByDesc('id')
            ->get(['id', 'dining_table_id', 'order_no', 'status', 'payment_status', 'total'])
            ->unique('dining_table_id')
            ->keyBy('dining_table_id');

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

        $tablesPayload = $tables->map(function (DiningTable $table) use ($openOrders) {
            $openOrder = $openOrders->get($table->id);

            return [
                'id' => (int) $table->id,
                'table_no' => (string) $table->table_no,
                'status' => (string) $table->status,
                'status_label' => $table->status === 'inactive' ? '停用中' : '可點餐',
                'open_order' => $openOrder ? [
                    'order_no' => (string) $openOrder->order_no,
                    'status' => (string) $openOrder->status,
                    'payment_status' => (string) $openOrder->payment_status,
                    'items_count' => (int) $openOrder->items_count,
                    'total' => (int) $openOrder->total,
                ] : null,
            ];
        })->values();

        $categoriesPayload = $categories->map(function (Category $category) use ($store) {
            return [
                'id' => (int) $category->id,
                'name' => (string) $category->name,
                'prep_time_minutes' => $category->prep_time_minutes ? (int) $category->prep_time_minutes : null,
                'product_count' => $category->products->count(),
                'products' => $category->products->map(function (Product $product) use ($store, $category) {
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
                        'name' => (string) $product->name,
                        'description' => (string) ($product->description ?? ''),
                        'price' => (int) $product->price,
                        'price_display' => $this->currencySymbol($store).' '.number_format((int) $product->price),
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

        $initialCartItems = $this->buildInitialCartItemsFromOldInput($store);

        return view('admin.orders.create', [
            'store' => $store,
            'tables' => $tables,
            'categories' => $categories,
            'tablesPayload' => $tablesPayload,
            'categoriesPayload' => $categoriesPayload,
            'initialCartItems' => $initialCartItems,
            'currencySymbol' => $this->currencySymbol($store),
            'defaultTableId' => (int) old('dining_table_id', 0),
            'defaultCustomerName' => (string) old('customer_name', ''),
            'defaultCustomerPhone' => (string) old('customer_phone', ''),
            'defaultNote' => (string) old('note', ''),
        ]);
    }

    public function store(Request $request, Store $store): RedirectResponse
    {
        $this->authorize('update', $store);

        if (! $store->isOrderingAvailable()) {
            return redirect()
                ->route('admin.stores.orders.create', $store)
                ->withInput()
                ->with('error', $store->orderingClosedMessage());
        }

        $validated = $request->validate([
            'dining_table_id' => ['required', 'integer', 'exists:dining_tables,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => $this->customerPhoneValidationRules($store),
            'note' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.option_payload' => ['nullable', 'string'],
            'items.*.item_note' => ['nullable', 'string', 'max:255'],
        ], [
            'dining_table_id.required' => '請先選擇桌次。',
            'items.required' => '請先加入至少一個品項。',
            'items.min' => '請先加入至少一個品項。',
        ]);

        $table = DiningTable::query()
            ->where('store_id', $store->id)
            ->findOrFail((int) $validated['dining_table_id']);

        if ((string) $table->status === 'inactive') {
            throw ValidationException::withMessages([
                'dining_table_id' => '此桌次目前停用中，請改選其他桌次。',
            ]);
        }

        $normalizedCustomerName = $this->normalizeOptionalText($validated['customer_name'] ?? null);
        $normalizedCustomerPhone = $this->normalizeCustomerPhone($validated['customer_phone'] ?? null, $store);
        $normalizedOrderNote = $this->normalizeOptionalText($validated['note'] ?? null);

        try {
            $order = DB::transaction(function () use (
                $store,
                $table,
                $validated,
                $normalizedCustomerName,
                $normalizedCustomerPhone,
                $normalizedOrderNote
            ) {
                $lineItems = $this->resolveOrderItems($store, $validated['items']);
                $lineSubtotal = (int) collect($lineItems)->sum('subtotal');

                $order = $this->findAppendableDineInOrder($store, $table);

                if (! $order) {
                    $order = Order::query()->create([
                        'store_id' => $store->id,
                        'dining_table_id' => $table->id,
                        'order_type' => 'dine_in',
                        'cart_token' => null,
                        'order_no' => Order::generateOrderNoForStore((int) $store->id),
                        'status' => 'pending',
                        'payment_status' => 'unpaid',
                        'invoice_flow' => InvoiceFlow::NONE,
                        'customer_name' => $normalizedCustomerName ?: '現場口頭點餐',
                        'customer_phone' => $normalizedCustomerPhone,
                        'customer_email' => null,
                        'note' => $normalizedOrderNote,
                        'subtotal' => 0,
                        'total' => 0,
                    ]);
                } elseif (in_array(strtolower((string) $order->status), self::COMPLETED_ORDER_STATUSES, true)) {
                    $order->status = 'preparing';
                }

                if ($order->customer_name === null && $normalizedCustomerName !== null) {
                    $order->customer_name = $normalizedCustomerName;
                }

                if ($order->getRawOriginal('customer_phone') === null && $normalizedCustomerPhone !== null) {
                    $order->customer_phone = $normalizedCustomerPhone;
                }

                if ($normalizedOrderNote !== null) {
                    $existingNote = $this->normalizeOptionalText($order->note);
                    $order->note = $existingNote === null
                        ? $normalizedOrderNote
                        : $existingNote.' | '.$normalizedOrderNote;
                }

                foreach ($lineItems as $item) {
                    $order->items()->create([
                        'product_id' => $item['product_id'],
                        'product_name' => $item['product_name'],
                        'price' => $item['price'],
                        'qty' => $item['qty'],
                        'subtotal' => $item['subtotal'],
                        'note' => $item['note'],
                    ]);
                }

                $order->subtotal = (int) $order->subtotal + $lineSubtotal;
                $order->total = (int) $order->total + $lineSubtotal;
                $order->save();

                return $order;
            });
        } catch (ValidationException $exception) {
            return redirect()
                ->route('admin.stores.orders.create', $store)
                ->withInput()
                ->withErrors($exception->errors());
        }

        return redirect()
            ->route('admin.stores.orders.create', $store)
            ->with('success', '商家點餐已建立，訂單編號 #'.$order->order_no.'。');
    }

    private function resolveOrderItems(Store $store, array $items): array
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
                $unavailableNames[] = '已下架商品';

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
                'items' => '部分商品目前無法點餐，請重新確認品項。',
            ]);
        }

        if ($resolved === []) {
            throw ValidationException::withMessages([
                'items' => '請先加入至少一個品項。',
            ]);
        }

        return $resolved;
    }

    private function buildInitialCartItemsFromOldInput(Store $store): array
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

    private function resolveSelectedOptions(Product $product, ?string $optionPayload): array
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
                    'items' => '商品選項格式錯誤，請重新選擇。',
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
                    'items' => '請完成「'.$groupName.'」的必選項。',
                ]);
            }

            if ($type === 'multiple' && count($rawSelection) > $maxSelect) {
                throw ValidationException::withMessages([
                    'items' => '「'.$groupName.'」最多只能選 '.$maxSelect.' 項。',
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
                    'items' => '請完成「'.$groupName.'」的必選項。',
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
            $parts[] = '備註 '.trim($itemNote);
        }

        return $parts === [] ? null : implode(' | ', $parts);
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
                    $fail('電話需要輸入 '.$expectedLength.' 碼數字。');
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

    private function currencySymbol(Store $store): string
    {
        return match (strtolower((string) ($store->currency ?? 'twd'))) {
            'vnd' => 'VND',
            'cny' => 'CNY',
            'usd' => 'USD',
            default => 'NT$',
        };
    }
}
