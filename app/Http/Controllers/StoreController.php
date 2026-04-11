<?php

namespace App\Http\Controllers;

use App\Models\Store;

class StoreController extends Controller
{
    public function enter(Store $store)
    {
        abort_unless($store->is_active, 404);

        $takeoutTable = $store->tables()
            ->where('is_active', true)
            ->where('name', '外帶')
            ->first();

        abort_unless($takeoutTable, 404, '此餐廳尚未設定外帶桌號');

        return redirect()->route('customer.menu', [
            'store' => $store->slug,
            'table' => $takeoutTable->qr_token,
        ]);
    }
}