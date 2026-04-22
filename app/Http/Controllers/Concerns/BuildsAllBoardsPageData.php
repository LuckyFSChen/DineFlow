<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Order;
use App\Models\Store;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait BuildsAllBoardsPageData
{
    protected function allBoardsPageViewData(Request $request, Store $store): array
    {
        $user = $request->user();

        return [
            'store' => $store,
            'availableStores' => $this->resolveBoardAccessibleStores($request),
            'ordersData' => $this->buildBoardOrdersPayload($store)->values()->all(),
            'boardSummary' => $this->buildBoardSummary($store),
            'checkoutTiming' => $store->checkout_timing ?? 'postpay',
            'canCashierActions' => $user->isAdmin() || $user->isMerchant() || $user->isCashier(),
            'canKitchenActions' => $user->isAdmin() || $user->isMerchant(),
        ];
    }

    protected function buildBoardOrdersPayload(Store $store): Collection
    {
        $cashierPayload = $this->fetchBoardCashierOrders($store)
            ->toBase()
            ->map(fn (Order $order) => $this->formatBoardOrder($order, 'cashier'));

        $kitchenPayload = $this->fetchBoardKitchenOrders($store)
            ->toBase()
            ->map(fn (Order $order) => $this->formatBoardOrder($order, 'kitchen'));

        return $cashierPayload
            ->merge($kitchenPayload)
            ->sortBy('created_at')
            ->values();
    }

    protected function buildBoardSummary(Store $store): array
    {
        $businessDayBounds = $store->businessDayBounds();

        $ordersToday = Order::query()
            ->where('store_id', $store->id)
            ->whereBetween('created_at', [$businessDayBounds['start'], $businessDayBounds['end']])
            ->whereNotIn('status', $this->boardCancelledStatuses())
            ->count();

        $avgPrepMinutes = $store->averageCompletedPrepTimeMinutesForBusinessDate();

        $repeatIdentityCounts = Order::query()
            ->select(['customer_name', 'customer_phone'])
            ->where('store_id', $store->id)
            ->where('created_at', '>=', now()->subDays($this->boardSummaryRepeatLookbackDays()))
            ->whereNotIn('status', $this->boardCancelledStatuses())
            ->get()
            ->map(fn (Order $order) => $this->resolveBoardCustomerIdentity(
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

    protected function resolveBoardAccessibleStores(Request $request): EloquentCollection
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

    protected function fetchBoardCashierOrders(Store $store): Collection
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
                $query->whereIn('status', $this->boardCashierPendingStatuses())
                    ->orWhere(function ($q) {
                        $q->whereIn('status', $this->boardCashierCompletedStatuses())
                            ->where(function ($paymentQuery) {
                                $paymentQuery->where('payment_status', 'unpaid')
                                    ->orWhereNull('payment_status');
                            });
                    });
            })
            ->orderBy('created_at', 'asc')
            ->get();
    }

    protected function fetchBoardKitchenOrders(Store $store): Collection
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
            ->whereIn('status', $this->boardKitchenPreparingStatuses())
            ->orderBy('created_at', 'asc')
            ->get();
    }

    protected function formatBoardOrder(Order $order, string $board): array
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

    protected function resolveBoardCustomerIdentity(string $name, string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits !== '') {
            return 'phone:'.$digits;
        }

        $normalizedName = mb_strtolower(trim($name));

        if ($normalizedName === '' || in_array($normalizedName, ['dineflow guest', 'guest', 'walk-in', '?曉??暺?', '?曉暺?'], true)) {
            return null;
        }

        return 'name:'.$normalizedName;
    }

    protected function boardCashierPendingStatuses(): array
    {
        return ['pending', 'accepted', 'confirmed', 'received'];
    }

    protected function boardCashierCompletedStatuses(): array
    {
        return ['complete', 'completed', 'ready', 'ready_for_pickup'];
    }

    protected function boardKitchenPreparingStatuses(): array
    {
        return ['preparing', 'processing', 'cooking', 'in_progress'];
    }

    protected function boardCancelledStatuses(): array
    {
        return ['cancel', 'cancelled', 'canceled'];
    }

    protected function boardSummaryRepeatLookbackDays(): int
    {
        return 30;
    }
}
