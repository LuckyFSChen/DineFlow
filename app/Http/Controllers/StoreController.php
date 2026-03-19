<?php

namespace App\Http\Controllers;

use App\Models\Store;

class StoreController extends Controller
{
    public function show(Store $store)
    {
        abort_unless($store->is_active, 404);

        $store->loadCount([
            'products as active_products_count' => function ($query) {
                $query->where('is_active', true)->where('is_sold_out', false);
            },
            'categories as active_categories_count' => function ($query) {
                $query->where('is_active', true);
            },
        ]);

        return view('stores.show', compact('store'));
    }

    public function menu(Store $store)
    {
        abort_unless($store->is_active, 404);

        $store->load([
            'categories' => function ($query) {
                $query->where('is_active', true)->orderBy('sort');
            },
            'products' => function ($query) {
                $query->where('is_active', true)
                    ->where('is_sold_out', false)
                    ->orderBy('sort');
            },
        ]);

        $categories = $store->categories;
        $products = $store->products->groupBy('category_id');

        return view('stores.menu', compact('store', 'categories', 'products'));
    }
}