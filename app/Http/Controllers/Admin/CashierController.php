<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashierController extends Controller
{
    private const PENDING_STATUSES = ['pending', 'accepted', 'confirmed', 'received'];

    private const COMPLETED_STATUSES = ['complete', 'completed', 'ready', 'ready_for_pickup'];

    public function index(Request $request, Store $store)
    {
        $this->authorizeStore($request, $store);

        $orders = $this->fetchCashierOrders($store);

        $checkoutTiming = $store->checkout_timing ?? 'postpay';

        return view('admin.cashier.index', compact('store', 'orders', 'checkoutTiming'));
    }

    public function orders(Request $request, Store $store): JsonResponse
    {
        $this->authorizeStore($request, $store);

        $orders = $this->fetchCashierOrders($store);

        $data = $orders->map(fn (Order $o) => [
            'id'             => $o->id,
            'order_no'       => $o->order_no,
            'status'         => $o->status,
            'payment_status' => $o->payment_status,
            'order_type'     => $o->order_type,
            'note'           => $o->note,
            'customer_name'  => $o->customer_name,
            'created_at'     => $o->created_at?->toIso8601String(),
            'table'          => ($t = $o->getRelation('table')) ? ['table_no' => $t->table_no] : null,
            'items'          => $o->items->map(fn ($i) => [
                'id'             => $i->id,
                'product_name'   => $i->product_name,
                'qty'            => $i->qty,
                'note'           => $i->note,
                'option_summary' => null,
            ])->values()->all(),
            '_loading' => false,
        ])->values();

        return response()->json($data);
    }

    public function updateStatus(Request $request, Store $store, Order $order): JsonResponse
    {
        $this->authorizeStore($request, $store);

        abort_if($order->store_id !== $store->id, 403);

        $status = (string) $request->input('status');
        $allowed = ['preparing', 'paid'];

        if (! in_array($status, $allowed, true)) {
            return response()->json(['ok' => false, 'message' => 'Invalid status'], 422);
        }

        if ($status === 'paid') {
            // Collect payment for a completed (postpay) order
            $order->update(['payment_status' => 'paid']);

            return response()->json([
                'ok'             => true,
                'status'         => $order->status,
                'payment_status' => $order->payment_status,
            ]);
        }

        // Accept order: pending → preparing
        $updates = ['status' => 'preparing'];

        if ($store->isPrepayCheckout()) {
            // Prepay: mark as paid immediately when accepting
            $updates['payment_status'] = 'paid';
        }

        $order->update($updates);

        return response()->json([
            'ok'             => true,
            'status'         => $order->status,
            'payment_status' => $order->payment_status,
        ]);
    }

    private function authorizeStore(Request $request, Store $store): void
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return;
        }

        if ($user->isMerchant() && (int) $store->user_id !== (int) $user->id) {
            abort(403);
        }

        if ($user->isCashier() && (int) $user->store_id !== (int) $store->id) {
            abort(403);
        }
    }

    private function fetchCashierOrders(Store $store): \Illuminate\Database\Eloquent\Collection
    {
        return Order::with(['items', 'table'])
            ->where('store_id', $store->id)
            ->where(function ($query) {
                // Pending orders waiting to be accepted
                $query->whereIn('status', self::PENDING_STATUSES)
                    // OR completed orders with outstanding payment (postpay)
                    ->orWhere(function ($q) {
                        $q->whereIn('status', self::COMPLETED_STATUSES)
                            ->where(function ($pq) {
                                $pq->where('payment_status', 'unpaid')
                                    ->orWhereNull('payment_status');
                            });
                    });
            })
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
