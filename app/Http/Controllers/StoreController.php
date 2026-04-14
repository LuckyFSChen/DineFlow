<?php

namespace App\Http\Controllers;

use App\Models\Store;

class StoreController extends Controller
{
    public function enter(Store $store)
    {
        abort_unless($store->is_active, 404);

        return redirect()->route('customer.takeout.menu', [
            'store' => $store,
        ]);
    }
}