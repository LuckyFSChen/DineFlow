<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DineInOrderController extends Controller
{
    protected function getDineInCartSessionKey(Store $store, DiningTable $table): string
    {
        return 'dinein_cart.' . $store->id . '.' . $table->id;
    }

    public function addToCart(Request $request, Store $store, DiningTable $table)
    {
        abort_unless($table->store_id === $store->id, 404);

        if (! $store->isOrderingAvailable()) {
            return redirect()
                ->route('customer.dinein.menu', ['store' => $store, 'table' => $table])
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

        $cartKey = $this->getDineInCartSessionKey($store, $table);
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
            ->route('customer.dinein.menu', ['store' => $store, 'table' => $table])
            ->with('success', '商品已加入購物車。');
    }

    public function cart(Store $store, DiningTable $table)
    {
        abort_unless($table->store_id === $store->id, 404);

        $cartKey = $this->getDineInCartSessionKey($store, $table);
        $cart = session()->get($cartKey, []);
        $total = collect($cart)->sum('subtotal');
        $orderingAvailable = $store->isOrderingAvailable();

        return view('customer.dine-in.cart-v2', compact('store', 'table', 'cart', 'total', 'orderingAvailable'));
    }

    public function submit(Request $request, Store $store, DiningTable $table)
    {
        abort_unless($table->store_id === $store->id, 404);

        if (! $store->isOrderingAvailable()) {
            return redirect()
                ->route('customer.dinein.cart.show', ['store' => $store, 'table' => $table])
                ->with('error', $store->orderingClosedMessage());
        }

        $validated = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string'],
        ]);

        $cartKey = $this->getDineInCartSessionKey($store, $table);
        $cart = session()->get($cartKey, []);

        if (empty($cart)) {
            return redirect()
                ->route('customer.dinein.cart.show', ['store' => $store, 'table' => $table])
                ->with('error', '購物車是空的。');
        }

        $total = collect($cart)->sum('subtotal');

        $order = DB::transaction(function () use ($store, $table, $validated, $cart, $total) {
            $order = Order::create([
                'store_id' => $store->id,
                'dining_table_id' => $table->id,
                'order_type' => 'dine_in',
                'cart_token' => null,
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

        return redirect()->route('customer.order.success', [
            'store' => $store->slug,
            'order' => $order->uuid,
        ]);
    }

    public function success(Store $store, Order $order)
    {
        abort_unless($order->store_id === $store->id, 404);

        $order->load('items', 'store', 'table');

        return view('customer.success-v2', compact('order', 'store'));
    }

    private function generateOrderNo(Store $store): string
    {
        $date = now()->format('md');
        $count = Order::where('store_id', $store->id)
            ->whereDate('created_at', today())
            ->lockForUpdate()
            ->count() + 1;

        return $date . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}
