<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AllBoardsController extends Controller
{
    private const CASHIER_PENDING_STATUSES = ['pending', 'accepted', 'confirmed', 'received'];

    private const CASHIER_COMPLETED_STATUSES = ['complete', 'completed', 'ready', 'ready_for_pickup'];

    private const KITCHEN_PREPARING_STATUSES = ['preparing', 'processing', 'cooking', 'in_progress'];

    public function index(Request $request, Store $store)
    {
        $this->authorizeStore($request, $store);

        $availableStores = $this->resolveAccessibleStores($request);
        $ordersData = $this->buildOrdersPayload($store);

        $checkoutTiming = $store->checkout_timing ?? 'postpay';
        $user = $request->user();

        $canCashierActions = $user->isAdmin() || $user->isMerchant() || $user->isCashier();
        $canKitchenActions = $user->isAdmin() || $user->isMerchant() || $user->isChef();

        return view('admin.boards.index', [
            'store' => $store,
            'availableStores' => $availableStores,
            'ordersData' => $ordersData,
            'checkoutTiming' => $checkoutTiming,
            'canCashierActions' => $canCashierActions,
            'canKitchenActions' => $canKitchenActions,
        ]);
    }

    public function orders(Request $request, Store $store): JsonResponse
    {
        $this->authorizeStore($request, $store);

        return response()->json($this->buildOrdersPayload($store)->values());
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

        if (($user->isChef() || $user->isCashier()) && (int) $user->store_id !== (int) $store->id) {
            abort(403);
        }
    }

    private function fetchCashierOrders(Store $store): Collection
    {
        return Order::query()
            ->select([
                'id',
                'store_id',
                'dining_table_id',
                'order_no',
                'order_locale',
                'status',
                'payment_status',
                'order_type',
                'note',
                'customer_name',
                'created_at',
            ])
            ->with([
                'items:id,order_id,product_name,qty,note,item_status,completed_at',
                'table:id,table_no',
            ])
            ->where('store_id', $store->id)
            ->where(function ($query) {
                $query->whereIn('status', self::CASHIER_PENDING_STATUSES)
                    ->orWhere(function ($q) {
                        $q->whereIn('status', self::CASHIER_COMPLETED_STATUSES)
                            ->where(function ($paymentQuery) {
                                $paymentQuery->where('payment_status', 'unpaid')
                                    ->orWhereNull('payment_status');
                            });
                    });
            })
            ->orderBy('created_at', 'asc')
            ->get();
    }

    private function fetchKitchenOrders(Store $store): Collection
    {
        return Order::query()
            ->select([
                'id',
                'store_id',
                'dining_table_id',
                'order_no',
                'order_locale',
                'status',
                'payment_status',
                'order_type',
                'note',
                'customer_name',
                'created_at',
            ])
            ->with([
                'items:id,order_id,product_name,qty,note,item_status,completed_at',
                'table:id,table_no',
            ])
            ->where('store_id', $store->id)
            ->whereIn('status', self::KITCHEN_PREPARING_STATUSES)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    private function buildOrdersPayload(Store $store): Collection
    {
        $cashierOrders = $this->fetchCashierOrders($store);
        $kitchenOrders = $this->fetchKitchenOrders($store);

        return $cashierOrders
            ->map(fn (Order $order) => $this->formatOrder($order, 'cashier'))
            ->merge($kitchenOrders->map(fn (Order $order) => $this->formatOrder($order, 'kitchen')))
            ->sortBy('created_at')
            ->values();
    }

    private function formatOrder(Order $order, string $board): array
    {
        return [
            'id' => $order->id,
            'order_no' => $order->order_no,
            'order_locale' => $order->order_locale,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'order_type' => $order->order_type,
            'note' => $order->note,
            'customer_name' => $order->customer_name,
            'created_at' => $order->created_at?->toIso8601String(),
            'table' => ($table = $order->getRelation('table')) ? ['table_no' => $table->table_no] : null,
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'product_name' => $item->product_name,
                'qty' => $item->qty,
                'note' => $item->note,
                'item_status' => $item->item_status ?: 'preparing',
                'completed_at' => $item->completed_at?->toIso8601String(),
                'option_summary' => null,
                '_loading' => false,
            ])->values()->all(),
            'board' => $board,
            '_loading' => false,
        ];
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

        if (($user->isChef() || $user->isCashier()) && $user->store_id) {
            return Store::query()->whereKey($user->store_id)->get(['id', 'name', 'slug']);
        }

        return Store::query()->whereRaw('1 = 0')->get(['id', 'name', 'slug']);
    }
}
