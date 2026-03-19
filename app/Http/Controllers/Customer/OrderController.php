<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function addToCart(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'product_id' => 'required|integer|exists:products,id',
            'qty' => 'required|integer|min:1',
        ]);

        $table = DiningTable::with('store')->where('qr_token', $validated['token'])->firstOrFail();
        $product = Product::where('id', $validated['product_id'])
            ->where('store_id', $table->store_id)
            ->where('is_active', true)
            ->where('is_sold_out', false)
            ->firstOrFail();

        $cartKey = 'cart_' . $validated['token'];
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
            ->route('customer.menu', ['token' => $validated['token']])
            ->with('success', '已加入購物車');
    }

    public function cart(string $token)
    {
        $table = DiningTable::with('store')->where('qr_token', $token)->firstOrFail();
        $cartKey = 'cart_' . $token;
        $cart = session()->get($cartKey, []);
        $total = collect($cart)->sum('subtotal');

        return view('customer.cart', compact('table', 'cart', 'total', 'token'));
    }

    public function submit(Request $request, string $token)
    {
        $table = DiningTable::with('store')->where('qr_token', $token)->firstOrFail();

        $validated = $request->validate([
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'note' => 'nullable|string',
        ]);

        $cartKey = 'cart_' . $token;
        $cart = session()->get($cartKey, []);

        if (empty($cart)) {
            return redirect()
                ->route('customer.cart', ['token' => $token])
                ->with('error', '購物車為空');
        }

        $total = collect($cart)->sum('subtotal');

        $order = DB::transaction(function () use ($table, $validated, $cart, $total) {
            $order = Order::create([
                'store_id' => $table->store_id,
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

        return redirect()->route('customer.order.success', $order->id);
    }

    public function success(Order $order)
    {
        $order->load('items', 'table', 'store');

        return view('customer.success', compact('order'));
    }

    private function generateOrderNo()
    {
        return 'ORD' . now()->format('YmdHis') . random_int(1000, 9999);
    }
}
