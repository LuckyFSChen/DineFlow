<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use App\Models\Store;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(Store $store, DiningTable $table)
    {
        abort_unless($table->store_id === $store->id, 404);

        $table->load([
            'store.categories' => fn ($query) => $query->where('is_active', true)->orderBy('sort'),
            'store.products' => fn ($query) => $query->where('is_active', true)->where('is_sold_out', false),
        ]);

        $categories = $store->categories;
        $products = $store->products->groupBy('category_id');

        return view('customer.menu', compact('store', 'table', 'categories', 'products'));
    }
}
