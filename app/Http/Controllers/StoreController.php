<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Support\Str;

class StoreController extends Controller
{
    public function enter(Store $store)
    {
        abort_unless($store->is_active, 404);

        $categories = $store->categories()
            ->select(['id', 'store_id', 'name', 'sort'])
            ->where('is_active', true)
            ->whereHas('products', function ($query) use ($store) {
                $query->where('store_id', $store->id)
                    ->where('is_active', true)
                    ->where('is_sold_out', false);
            })
            ->withCount(['products' => function ($query) use ($store) {
                $query->where('store_id', $store->id)
                    ->where('is_active', true)
                    ->where('is_sold_out', false);
            }])
            ->orderBy('sort')
            ->limit(6)
            ->get();

        $featuredProducts = $store->products()
            ->select(['id', 'store_id', 'category_id', 'name', 'description', 'price', 'image'])
            ->with('category:id,name')
            ->where('is_active', true)
            ->where('is_sold_out', false)
            ->orderBy('category_id')
            ->orderBy('id')
            ->limit(6)
            ->get()
            ->each(function ($product): void {
                $product->seo_image_url = filled($product->image)
                    ? (Str::startsWith($product->image, ['http://', 'https://'])
                        ? $product->image
                        : asset('storage/' . ltrim($product->image, '/')))
                    : null;
            });

        $store->loadCount([
            'categories as active_categories_count' => fn ($query) => $query->where('is_active', true),
            'products as active_products_count' => fn ($query) => $query->where('is_active', true)->where('is_sold_out', false),
        ]);

        return view('stores.show', [
            'store' => $store,
            'categories' => $categories,
            'featuredProducts' => $featuredProducts,
            'orderingAvailable' => $store->isOrderingAvailable(),
            'takeoutUrl' => $store->takeout_qr_enabled
                ? route('customer.takeout.menu', ['store' => $store])
                : null,
        ]);
    }
}
