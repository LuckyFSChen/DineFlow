<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(string $token)
    {
        $table = DiningTable::with([
            'store.categories' => function ($query) {
                $query->where('is_active', true)->orderBy('sort');
            },
            'store.products' => function ($query) {
                $query->where('is_active', true)
                    ->where('is_sold_out', false);
            }
        ])->where('qr_token', $token)->firstOrFail();

        $store = $table->store;
        $categories = $store->categories;
        $products = $store->products->groupBy('category_id');

        return view('customer.menu', compact('table', 'store', 'categories', 'products'));
    }
}
