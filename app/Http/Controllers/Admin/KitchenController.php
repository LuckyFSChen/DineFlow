<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KitchenController extends Controller
{
    private const PREPARING_STATUSES = ['preparing', 'processing', 'cooking', 'in_progress'];
    private const COMPLETED_STATUSES = ['complete', 'completed', 'ready', 'ready_for_pickup'];

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
                'item_status' => $i->item_status ?: 'preparing',
                'completed_at' => $i->completed_at?->toIso8601String(),
                'option_summary' => null,
                '_loading' => false,
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

        $itemId = $request->input('item_id');
        if ($itemId !== null && $itemId !== '') {
            $item = $order->items()->whereKey((int) $itemId)->first();
            if (! $item) {
                return response()->json(['ok' => false, 'message' => 'Order item not found'], 404);
            }

            $itemStatus = strtolower((string) $request->input('item_status', $request->input('status', '')));
            if (! in_array($itemStatus, ['preparing', 'completed'], true)) {
                return response()->json(['ok' => false, 'message' => 'Invalid item status'], 422);
            }

            return $this->applyItemStatusUpdate($order, $item, $itemStatus);
        }

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

    public function updateItemStatus(Request $request, Store $store, Order $order, OrderItem $item): JsonResponse
    {
        $this->authorizeStore($request, $store);

        abort_if($order->store_id !== $store->id, 403);
        abort_if($item->order_id !== $order->id, 403);

        $status = strtolower((string) $request->input('status', ''));
        if (! in_array($status, ['preparing', 'completed'], true)) {
            return response()->json(['ok' => false, 'message' => 'Invalid item status'], 422);
        }

        return $this->applyItemStatusUpdate($order, $item, $status);
    }

    private function applyItemStatusUpdate(Order $order, OrderItem $item, string $status): JsonResponse
    {
        $item->item_status = $status;
        $item->completed_at = $status === 'completed' ? now() : null;
        $item->save();

        $hasPendingItems = $order->items()
            ->where(function ($query) {
                $query->whereNull('item_status')
                    ->orWhere('item_status', '!=', 'completed');
            })
            ->exists();

        if (! $hasPendingItems && ! in_array(strtolower((string) $order->status), self::COMPLETED_STATUSES, true)) {
            $order->status = 'completed';
            $order->save();
        }

        return response()->json([
            'ok' => true,
            'item' => [
                'id' => $item->id,
                'item_status' => $item->item_status,
                'completed_at' => $item->completed_at?->toIso8601String(),
            ],
            'order' => [
                'id' => $order->id,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
            ],
            'status' => $order->status,
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
            return Store::query()->orderBy('name')->orderBy('id')->get(['id', 'name', 'slug']);
        }

        if ($user->isMerchant()) {
            return Store::query()
                ->where('user_id', $user->id)
                ->orderBy('name')
                ->orderBy('id')
                ->get(['id', 'name', 'slug']);
        }

        if ($user->isChef() && $user->store_id) {
            return Store::query()->whereKey($user->store_id)->get(['id', 'name', 'slug']);
        }

        return Store::query()->whereRaw('1 = 0')->get(['id', 'name', 'slug']);
    }
}
