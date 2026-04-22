<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\BuildsMerchantOrderPageData;
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
    use BuildsMerchantOrderPageData;

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

        return view('admin.orders.create', $this->merchantOrderPageViewData($store));
    }

    public function store(Request $request, Store $store): RedirectResponse
    {
        $this->authorize('update', $store);

        if (! $store->isOrderingAvailable()) {
            return $this->merchantOrderPageRedirect($request, $store)
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
            'dining_table_id.required' => __('merchant_order.validation_dining_table_required'),
            'items.required' => __('merchant_order.validation_items_required'),
            'items.min' => __('merchant_order.validation_items_required'),
        ]);

        $table = DiningTable::query()
            ->where('store_id', $store->id)
            ->findOrFail((int) $validated['dining_table_id']);

        if ((string) $table->status === 'inactive') {
            throw ValidationException::withMessages([
                'dining_table_id' => __('merchant_order.validation_table_inactive'),
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
                        'customer_name' => $normalizedCustomerName ?: __('merchant_order.default_customer_name'),
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
            return $this->merchantOrderPageRedirect($request, $store)
                ->withInput()
                ->withErrors($exception->errors());
        }

        return $this->merchantOrderPageRedirect($request, $store)
            ->with('success', __('merchant_order.success_order_saved', ['order_no' => $order->order_no]));
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

    private function sanitizeItemNote(?string $note, bool $allowItemNote): ?string
    {
        if (! $allowItemNote || $note === null) {
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
            $parts[] = __('merchant_order.item_note_prefix').trim($itemNote);
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
                    $fail(__('merchant_order.validation_customer_phone_length', ['digits' => $expectedLength]));
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

    private function orderCreateRouteParams(Store $store, Request $request): array
    {
        $params = ['store' => $store];

        if ($request->boolean('embedded')) {
            $params['embedded'] = 1;
        }

        return $params;
    }

    private function merchantOrderPageRedirect(Request $request, Store $store): RedirectResponse
    {
        if ($request->boolean('workspace')) {
            return redirect()->route('admin.stores.workspace', ['store' => $store, 'tab' => 'orders']);
        }

        return redirect()->route('admin.stores.orders.create', $this->orderCreateRouteParams($store, $request));
    }
}
