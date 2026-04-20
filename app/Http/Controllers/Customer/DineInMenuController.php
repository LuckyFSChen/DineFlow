<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\Store;
use App\Support\DineInCartStore;

class DineInMenuController extends Controller
{
    private const ORDER_HISTORY_SESSION_PREFIX = 'dinein_order_history_';

    private const CANCELLED_STATUSES = ['cancel', 'cancelled', 'canceled'];

    private const COMPLETED_STATUSES = ['complete', 'completed', 'ready', 'ready_for_pickup'];

    protected function getDineInOrderHistorySessionKey(Store $store, DiningTable $table): string
    {
        return self::ORDER_HISTORY_SESSION_PREFIX . $store->id . '_' . $table->id;
    }

    public function index(Store $store, DiningTable $table)
    {
        abort_unless($table->store_id === $store->id, 404);

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
        $cart = DineInCartStore::getCart(request(), $store->id, $table->id);
        $cartCount = collect($cart)->sum('qty');
        $cartTotal = collect($cart)->sum('subtotal');
        $cartPreviewItems = collect($cart)->values();
        $products = $categories->mapWithKeys(function ($category) {
            return [$category->id => $category->products];
        });
        $history = session()->get($this->getDineInOrderHistorySessionKey($store, $table), []);
        $orderHistory = collect();

        if (is_array($history) && ! empty($history)) {
            $uuids = array_values(array_filter(array_map('strval', $history), fn ($v) => $v !== ''));
            if (! empty($uuids)) {
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
                    ->whereNotIn('status', self::CANCELLED_STATUSES)
                    ->where(function ($query) use ($store) {
                        if ($store->isPrepayCheckout()) {
                            $query->whereNotIn('status', self::COMPLETED_STATUSES);

                            return;
                        }

                        $query->where('payment_status', 'unpaid')
                            ->orWhereNull('payment_status');
                    })
                    ->orderByDesc('created_at')
                    ->get();

                $orderMap = $orders->keyBy('uuid');
                $orderHistory = collect($uuids)
                    ->map(fn ($uuid) => $orderMap->get($uuid))
                    ->filter()
                    ->values();
            }
        }

        return view('customer.dine-in.menu-mobile', compact(
            'store',
            'table',
            'categories',
            'products',
            'orderingAvailable',
            'cartCount',
            'cartTotal',
            'cartPreviewItems',
            'orderHistory'
        ));
    }
}
