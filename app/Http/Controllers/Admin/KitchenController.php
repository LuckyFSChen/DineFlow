<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KitchenController extends Controller
{
    private const PREPARING_STATUSES = ['preparing', 'processing', 'cooking', 'in_progress'];

    public function index(Request $request, Store $store)
    {
        $this->authorizeStore($request, $store);

        $orders = $this->fetchActiveOrders($store);
        $availableStores = $this->resolveAccessibleStores($request);

        $checkoutTiming = $store->checkout_timing ?? 'postpay';

        return view('admin.kitchen.index', compact('store', 'orders', 'checkoutTiming', 'availableStores'));
    }

    public function orders(Request $request, Store $store): JsonResponse
    {
        $this->authorizeStore($request, $store);

        $orders = $this->fetchActiveOrders($store);

        $data = $orders->map(fn (Order $o) => [
            'id' => $o->id,
            'order_no' => $o->order_no,
            'order_locale' => $o->order_locale,
            'status' => $o->status,
            'payment_status' => $o->payment_status,
            'order_type' => $o->order_type,
            'note' => $o->note,
            'customer_name' => $o->customer_name,
            'created_at' => $o->created_at?->toIso8601String(),
            'table' => ($t = $o->getRelation('table')) ? ['table_no' => $t->table_no] : null,
            'items' => $o->items->map(fn ($i) => [
                'id' => $i->id,
                'product_name' => $i->product_name,
                'qty' => $i->qty,
                'note' => $i->note,
                'option_summary' => null,
            ])->values()->all(),
            '_loading' => false,
        ])->values();

        return response()->json($data);
    }

    public function complete(Request $request, Store $store, Order $order): JsonResponse
    {
        $this->authorizeStore($request, $store);

        abort_if($order->store_id !== $store->id, 403);

        $order->update(['status' => 'completed']);

        return response()->json(['ok' => true]);
    }

    public function updateStatus(Request $request, Store $store, Order $order): JsonResponse
    {
        $this->authorizeStore($request, $store);

        abort_if($order->store_id !== $store->id, 403);

        $status = (string) $request->input('status');

        // Kitchen board only transitions: preparing → completed
        if ($status !== 'completed') {
            return response()->json(['ok' => false, 'message' => 'Invalid status'], 422);
        }

        $order->update(['status' => 'completed']);

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

        if ($user->isChef() && (int) $user->store_id !== (int) $store->id) {
            abort(403);
        }
    }

    private function fetchActiveOrders(Store $store): \Illuminate\Database\Eloquent\Collection
    {
        // Kitchen board only shows orders currently being prepared
        return Order::with(['items', 'table'])
            ->where('store_id', $store->id)
            ->whereIn('status', self::PREPARING_STATUSES)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    private function resolveAccessibleStores(Request $request): \Illuminate\Database\Eloquent\Collection
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return Store::query()->orderBy('name')->orderBy('id')->get(['id', 'name']);
        }

        if ($user->isMerchant()) {
            return Store::query()
                ->where('user_id', $user->id)
                ->orderBy('name')
                ->orderBy('id')
                ->get(['id', 'name']);
        }

        if ($user->isChef() && $user->store_id) {
            return Store::query()->whereKey($user->store_id)->get(['id', 'name']);
        }

        return Store::query()->whereRaw('1 = 0')->get(['id', 'name']);
    }
}
