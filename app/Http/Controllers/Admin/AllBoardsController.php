<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\BuildsAllBoardsPageData;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AllBoardsController extends Controller
{
    use BuildsAllBoardsPageData;

    private const CASHIER_PENDING_STATUSES = ['pending', 'accepted', 'confirmed', 'received'];

    private const CASHIER_COMPLETED_STATUSES = ['complete', 'completed', 'ready', 'ready_for_pickup'];

    private const KITCHEN_PREPARING_STATUSES = ['preparing', 'processing', 'cooking', 'in_progress'];

    private const CANCELLED_STATUSES = ['cancel', 'cancelled', 'canceled'];

    private const SUMMARY_COMPLETED_SAMPLE_LIMIT = 30;

    private const SUMMARY_REPEAT_LOOKBACK_DAYS = 30;

    public function index(Request $request, Store $store)
    {
        $this->authorize('viewBoards', $store);

        return view('admin.boards.index', $this->allBoardsPageViewData($request, $store));
    }

    public function orders(Request $request, Store $store): JsonResponse
    {
        $this->authorize('viewBoards', $store);

        return response()->json([
            'orders' => $this->buildBoardOrdersPayload($store)->values()->all(),
            'summary' => $this->buildBoardSummary($store),
        ]);
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
                'subtotal',
                'total',
                'coupon_code',
                'coupon_discount',
                'created_at',
            ])
            ->with([
                'items:id,order_id,product_name,price,qty,subtotal,note,item_status,completed_at',
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
                'subtotal',
                'total',
                'coupon_code',
                'coupon_discount',
                'created_at',
            ])
            ->with([
                'items:id,order_id,product_name,price,qty,subtotal,note,item_status,completed_at',
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

        $cashierPayload = $cashierOrders
            ->toBase()
            ->map(fn (Order $order) => $this->formatOrder($order, 'cashier'));

        $kitchenPayload = $kitchenOrders
            ->toBase()
            ->map(fn (Order $order) => $this->formatOrder($order, 'kitchen'));

        return $cashierPayload
            ->merge($kitchenPayload)
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
            'subtotal' => (int) $order->subtotal,
            'total' => (int) $order->total,
            'coupon_code' => $order->coupon_code,
            'coupon_discount' => (int) $order->coupon_discount,
            'created_at' => $order->created_at?->toIso8601String(),
            'table' => ($table = $order->getRelation('table')) ? ['table_no' => $table->table_no] : null,
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'product_name' => $item->product_name,
                'price' => (int) $item->price,
                'qty' => $item->qty,
                'subtotal' => (int) $item->subtotal,
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

    private function buildBoardSummary(Store $store): array
    {
        $ordersToday = Order::query()
            ->where('store_id', $store->id)
            ->where('created_at', '>=', now()->startOfDay())
            ->whereNotIn('status', self::CANCELLED_STATUSES)
            ->count();

        $avgPrepMinutes = $store->averageCompletedPrepTimeMinutes(self::SUMMARY_COMPLETED_SAMPLE_LIMIT);

        $repeatIdentityCounts = Order::query()
            ->select(['customer_name', 'customer_phone'])
            ->where('store_id', $store->id)
            ->where('created_at', '>=', now()->subDays(self::SUMMARY_REPEAT_LOOKBACK_DAYS))
            ->whereNotIn('status', self::CANCELLED_STATUSES)
            ->get()
            ->map(fn (Order $order) => $this->resolveCustomerIdentity(
                (string) ($order->customer_name ?? ''),
                (string) ($order->customer_phone ?? ''),
            ))
            ->filter()
            ->countBy();

        $repeatRate = $repeatIdentityCounts->isNotEmpty()
            ? (int) round(
                $repeatIdentityCounts
                    ->filter(fn (int $count) => $count > 1)
                    ->count() / $repeatIdentityCounts->count() * 100
            )
            : null;

        return [
            'orders_today' => (int) $ordersToday,
            'avg_prep_minutes' => $avgPrepMinutes === null ? null : round((float) $avgPrepMinutes, 1),
            'repeat_rate' => $repeatRate,
        ];
    }

    private function resolveCustomerIdentity(string $name, string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits !== '') {
            return 'phone:'.$digits;
        }

        $normalizedName = mb_strtolower(trim($name));

        if ($normalizedName === '' || in_array($normalizedName, ['dineflow guest', 'guest', 'walk-in', '現場口頭點餐', '現場點餐'], true)) {
            return null;
        }

        return 'name:'.$normalizedName;
    }
}
