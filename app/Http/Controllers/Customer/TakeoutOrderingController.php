<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TakeoutOrderingController extends Controller
{
    protected function getTakeoutCartToken(Store $store): string
    {
        $sessionKey = 'takeout_cart_token_' . $store->id;

        if (! session()->has($sessionKey)) {
            session([$sessionKey => (string) Str::uuid()]);
        }

        return session($sessionKey);
    }

    protected function getTakeoutCartSessionKey(Store $store): string
    {
        return 'takeout_cart.' . $store->id . '.' . $this->getTakeoutCartToken($store);
    }

    public function menu(Store $store)
    {
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
        $cart = session()->get($this->getTakeoutCartSessionKey($store), []);
        $cartCount = collect($cart)->sum('qty');
        $cartTotal = collect($cart)->sum('subtotal');

        return view('customer.takeout.menu-mobile-v3', compact(
            'store',
            'categories',
            'orderingAvailable',
            'cartCount',
            'cartTotal'
        ));
    }

    public function addToCart(Request $request, Store $store)
    {
        if (! $store->isOrderingAvailable()) {
            return redirect()
                ->route('customer.takeout.menu', ['store' => $store])
                ->with('error', $store->orderingClosedMessage());
        }

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
            ->with('success', '商品已加入購物車。');
    }

    public function cart(Store $store)
    {
        $cartKey = $this->getTakeoutCartSessionKey($store);
        $cart = session()->get($cartKey, []);
        $total = collect($cart)->sum('subtotal');
        $orderingAvailable = $store->isOrderingAvailable();

        return view('customer.takeout.cart-v2', compact('store', 'cart', 'total', 'orderingAvailable'));
    }

    public function checkout(Request $request, Store $store)
    {
        if (! $store->isOrderingAvailable()) {
            return redirect()
                ->route('customer.takeout.cart.show', ['store' => $store])
                ->with('error', $store->orderingClosedMessage());
        }

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
                ->with('error', '購物車是空的。');
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
        $date = now()->format('md');
        $count = Order::where('store_id', $store->id)
            ->whereDate('created_at', today())
            ->count() + 1;

        return $date . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}
