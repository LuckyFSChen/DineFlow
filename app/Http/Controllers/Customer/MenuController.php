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

        $categories = $store->categories()
            ->select(['id', 'store_id', 'name', 'sort'])
            ->where('is_active', true)
            ->orderBy('sort')
            ->get();

        $products = $store->products()
            ->select([
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
            ->where('is_active', true)
            ->where('is_sold_out', false)
            ->orderBy('sort')
            ->get()
            ->groupBy('category_id');

        return view('customer.menu', compact('store', 'table', 'categories', 'products'));
    }
}
