<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use App\Models\Store;

class DineInMenuController extends Controller
{
    protected function getDineInCartSessionKey(Store $store, DiningTable $table): string
    {
        return 'dinein_cart.' . $store->id . '.' . $table->id;
    }

    public function index(Store $store, DiningTable $table)
    {
        abort_unless($table->store_id === $store->id, 404);

        $store->load([
            'categories' => fn ($query) => $query->where('is_active', true)->orderBy('sort'),
            'products' => fn ($query) => $query->where('is_active', true)->where('is_sold_out', false)->orderBy('sort'),
        ]);

        $categories = $store->categories;
        $products = $store->products->groupBy('category_id');
        $orderingAvailable = $store->isOrderingAvailable();
        $cart = session()->get($this->getDineInCartSessionKey($store, $table), []);
        $cartCount = collect($cart)->sum('qty');
        $cartTotal = collect($cart)->sum('subtotal');

        return view('customer.dine-in.menu-mobile', compact(
            'store',
            'table',
            'categories',
            'products',
            'orderingAvailable',
            'cartCount',
            'cartTotal'
        ));
    }
}
