<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function addToCart(Request $request, Store $store, DiningTable $table)
    {
        abort_unless($table->store_id === $store->id, 404);

        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'qty' => ['required', 'integer', 'min:1'],
        ]);

        $product = Product::where('id', $validated['product_id'])
            ->where('store_id', $table->store_id)
            ->where('is_active', true)
            ->where('is_sold_out', false)
            ->firstOrFail();

        $cartKey = "cart:store:{$table->store_id}:table:{$table->id}";
        $cart = session()->get($cartKey, []);

        if(isset($cart[$product->id])) {
            $cart[$product->id]['qty'] += $validated['qty'];
            $cart[$product->id]['subtotal'] = $cart[$product->id]['price'] * $cart[$product->id]['qty'];
        } else {
            $cart[$product->id] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'price' => $product->price,
                'qty' => $validated['qty'],
                'subtotal' => $product->price * $validated['qty'],
            ];
        }

        session()->put($cartKey, $cart);

        return redirect()
            ->route('customer.menu', [
                'store' => $store->slug,
                'table' => $table->qr_token,
            ])
            ->with('success', '已加入購物車');
    }

    public function cart(string $token)
    {
        $table = DiningTable::with('store')->where('qr_token', $token)->firstOrFail();
        $cartKey = "cart:store:{$table->store_id}:table:{$table->id}";
        $cart = session()->get($cartKey, []);
        $total = collect($cart)->sum('subtotal');

        return view('customer.cart', compact('table', 'cart', 'total', 'token'));
    }

    public function submit(Request $request, Store $store, DiningTable $table)
    {
        abort_unless($table->store_id === $store->id, 404);

        $validated = $request->validate([
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'note' => 'nullable|string',
        ]);

        $cartKey = "cart:store:{$store->id}:table:{$table->id}";
        $cart = session()->get($cartKey, []);

        if (empty($cart)) {
            return redirect()
                ->route('customer.cart.show', [
                    'store' => $store->slug,
                    'table' => $table->qr_token,
                ])
                ->with('error', '購物車為空');
        }

        $total = collect($cart)->sum('subtotal');

        $order = DB::transaction(function () use ($store, $table, $validated, $cart, $total) {
            $order = Order::create([
                'store_id' => $store->id,
                'dining_table_id' => $table->id,
                'order_no' => $this->generateOrderNo(),
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

    public function success(Order $order)
    {
        $order->load('items', 'table', 'store');

        return view('customer.success', compact('order'));
    }

    private function generateOrderNo(Store $store): string
    {
        return DB::transaction(function () use ($store) {
            $date = now()->format('md');

            $count = Order::where('store_id', $store->id)
                ->whereDate('created_at', today())
                ->lockForUpdate()
                ->count() + 1;

            return $date . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
        });
    }
}
