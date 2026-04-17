<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TakeoutController extends Controller
{
    protected function getTakeoutCartToken(Store $store): string
    {
        $sessionKey = 'takeout_cart_token_' . $store->id;

        if (!session()->has($sessionKey)) {
            session([$sessionKey => (string) Str::uuid()]);
        }

        return session($sessionKey);
    }

    protected function getTakeoutCartSessionKey(Store $store): string
    {
        $token = $this->getTakeoutCartToken($store);

        return 'takeout_cart.' . $store->id . '.' . $token;
    }

    public function menu(Store $store)
    {
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
                    'sort',
                ])
                    ->where('store_id', $store->id)
                    ->where('is_active', true)
                    ->where('is_sold_out', false)
                    ->orderBy('sort');
            }])
            ->orderBy('sort')
            ->get();

        return view('customer.takeout.menu', compact('store', 'categories'));
    }

    public function addToCart(Request $request, Store $store)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'qty' => ['required', 'integer', 'min:1'],
        ]);

        $product = Product::where('id', $validated['product_id'])
            ->where('store_id', $store->id)
            ->where('is_active', true)
            ->where('is_sold_out', false)
            ->firstOrFail();

        $cartKey = $this->getTakeoutCartSessionKey($store);
        $cart = session()->get($cartKey, []);

        if (isset($cart[$product->id])) {
            $cart[$product->id]['qty'] += $validated['qty'];
        } else {
            $cart[$product->id] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'price' => $product->price,
                'qty' => $validated['qty'],
                'subtotal' => 0,
            ];
        }

        foreach ($cart as &$item) {
            $item['subtotal'] = $item['price'] * $item['qty'];
        }
        unset($item);

        session()->put($cartKey, $cart);

        return redirect()
            ->route('customer.takeout.cart.show', ['store' => $store])
            ->with('success', __('customer.item_added_to_cart'));
    }

    public function cart(Store $store)
    {
        $cartKey = $this->getTakeoutCartSessionKey($store);
        $cart = session()->get($cartKey, []);
        $total = collect($cart)->sum('subtotal');

        return view('customer.takeout.cart', compact('store', 'cart', 'total'));
    }

    public function checkout(Request $request, Store $store)
    {
        $validated = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string'],
        ]);

        $cartKey = $this->getTakeoutCartSessionKey($store);
        $cart = session()->get($cartKey, []);

        if (empty($cart)) {
            return redirect()
                ->route('customer.takeout.cart.show', ['store' => $store])
                ->with('error', __('customer.error_cart_empty'));
        }

        $total = collect($cart)->sum('subtotal');
        $cartToken = $this->getTakeoutCartToken($store);

        $order = DB::transaction(function () use ($store, $validated, $cart, $total, $cartToken) {
            $order = Order::create([
                'store_id' => $store->id,
                'dining_table_id' => null,
                'order_type' => 'takeout',
                'cart_token' => $cartToken,
                'order_no' => $this->generateOrderNo($store),
                'status' => 'pending',
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_email' => $validated['customer_email'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'note' => $validated['note'] ?? null,
                'subtotal' => $total,
                'total' => $total,
            ]);

            foreach ($cart as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'price' => $item['price'],
                    'qty' => $item['qty'],
                    'subtotal' => $item['subtotal'],
                    'note' => null,
                ]);
            }

            return $order;
        });

        session()->forget($cartKey);
        session()->forget('takeout_cart_token_' . $store->id);

        return redirect()->route('customer.order.success', [
            'store' => $store->slug,
            'order' => $order->uuid,
        ]);
    }

    private function generateOrderNo(Store $store): string
    {
        $storeToken = str_pad((string) $store->id, 2, '0', STR_PAD_LEFT);

        for ($attempt = 0; $attempt < 8; $attempt++) {
            $candidate = now()->format('mdHisv') . '-' . $storeToken . random_int(10, 99);

            if (! Order::where('order_no', $candidate)->exists()) {
                return $candidate;
            }

            usleep(10000);
        }

        return now()->format('mdHisv') . '-' . $storeToken . random_int(100, 999);
    }
}
