<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\Store;

class DineInMenuController extends Controller
{
    private const ORDER_HISTORY_SESSION_PREFIX = 'dinein_order_history_';

    protected function getDineInCartSessionKey(Store $store, DiningTable $table): string
    {
        return 'dinein_cart.' . $store->id . '.' . $table->id;
    }

    protected function getDineInOrderHistorySessionKey(Store $store, DiningTable $table): string
    {
        return self::ORDER_HISTORY_SESSION_PREFIX . $store->id . '_' . $table->id;
    }

    public function index(Store $store, DiningTable $table)
    {
        abort_unless($table->store_id === $store->id, 404);

        $categories = $store->categories()
            ->where('is_active', true)
            ->with(['products' => function ($query) use ($store) {
                $query->where('store_id', $store->id)
                    ->where('is_active', true)
                    ->where('is_sold_out', false)
                    ->orderBy('sort');
            }])
            ->orderBy('sort')
            ->get();

        $orderingAvailable = $store->isOrderingAvailable();
        $cart = session()->get($this->getDineInCartSessionKey($store, $table), []);
        $cartCount = collect($cart)->sum('qty');
        $cartTotal = collect($cart)->sum('subtotal');
        $cartPreviewItems = collect($cart)->values();
        $history = session()->get($this->getDineInOrderHistorySessionKey($store, $table), []);
        $orderHistory = collect();

        if (is_array($history) && ! empty($history)) {
            $uuids = array_values(array_filter(array_map('strval', $history), fn ($v) => $v !== ''));
            if (! empty($uuids)) {
                $orders = Order::query()
                    ->where('store_id', $store->id)
                    ->where('dining_table_id', $table->id)
                    ->whereIn('uuid', $uuids)
                    ->orderByDesc('created_at')
                    ->get();

                $orderMap = $orders->keyBy('uuid');
                $orderHistory = collect($uuids)
                    ->map(fn ($uuid) => $orderMap->get($uuid))
                    ->filter()
                    ->values();
            }
        }

        return view('customer.menu.mobile', compact(
            'store',
            'table',
            'categories',
            'orderingAvailable',
            'cartCount',
            'cartTotal',
            'cartPreviewItems',
            'orderHistory'
        ) + [
            'mode' => 'dine_in',
        ]);
    }
}
