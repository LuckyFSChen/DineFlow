<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class OrderHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $stores = Store::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name', 'currency']);

        $selectedStoreId = $stores->count() === 1
            ? (int) $stores->first()->id
            : null;

        if ($request->filled('store_id')) {
            $candidateStoreId = (int) $request->input('store_id');
            if ($stores->contains('id', $candidateStoreId)) {
                $selectedStoreId = $candidateStoreId;
            }
        }

        $validated = $request->validate([
            'status' => ['nullable', 'string', 'max:50'],
            'payment_status' => ['nullable', 'in:paid,unpaid'],
            'order_type' => ['nullable', 'in:dine_in,dinein,takeout,take_out'],
            'keyword' => ['nullable', 'string', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'sort_by' => ['nullable', 'in:order_no,store_name,customer_name,order_type,status,total,created_at'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
        ]);
        $sortBy = (string) ($validated['sort_by'] ?? 'created_at');
        $sortDir = (string) ($validated['sort_dir'] ?? 'desc');
        $defaultEndDate = Carbon::today();
        $defaultStartDate = $defaultEndDate->copy()->subMonthNoOverflow();
        $startDate = (string) ($validated['start_date'] ?? $defaultStartDate->toDateString());
        $endDate = (string) ($validated['end_date'] ?? $defaultEndDate->toDateString());
        $startAt = Carbon::parse($startDate)->startOfDay();
        $endAt = Carbon::parse($endDate)->endOfDay();
        $keyword = trim((string) ($validated['keyword'] ?? ''));
        $keywordLike = $keyword !== '' ? "%{$keyword}%" : '';

        $storeIds = $stores->pluck('id')->all();

        $baseQuery = Order::query()
            ->whereIn('orders.store_id', $storeIds)
            ->when($selectedStoreId !== null, fn (Builder $builder) => $builder->where('orders.store_id', $selectedStoreId))
            ->when(! empty($validated['status']), fn (Builder $builder) => $builder->where('orders.status', (string) $validated['status']))
            ->when(! empty($validated['payment_status']), fn (Builder $builder) => $builder->where('orders.payment_status', (string) $validated['payment_status']))
            ->when(! empty($validated['order_type']), function (Builder $builder) use ($validated) {
                $orderType = strtolower((string) $validated['order_type']);

                if (in_array($orderType, ['dine_in', 'dinein'], true)) {
                    $builder->whereIn('orders.order_type', ['dine_in', 'dinein']);

                    return;
                }

                if (in_array($orderType, ['takeout', 'take_out'], true)) {
                    $builder->whereIn('orders.order_type', ['takeout', 'take_out']);

                    return;
                }

                $builder->where('orders.order_type', $orderType);
            })
            ->when($keywordLike !== '', function (Builder $builder) use ($keywordLike) {
                $builder->where(function (Builder $inner) use ($keywordLike) {
                    $inner->where('orders.order_no', 'like', $keywordLike)
                        ->orWhere('orders.customer_name', 'like', $keywordLike)
                        ->orWhere('orders.customer_phone', 'like', $keywordLike)
                        ->orWhereHas('store', fn (Builder $store) => $store->where('name', 'like', $keywordLike))
                        ->orWhereHas('items', fn (Builder $items) => $items->where('product_name', 'like', $keywordLike));
                });
            })
            ->whereBetween('orders.created_at', [$startAt, $endAt]);

        $sortColumnMap = [
            'order_no' => 'orders.order_no',
            'customer_name' => 'orders.customer_name',
            'order_type' => 'orders.order_type',
            'status' => 'orders.status',
            'total' => 'orders.total',
            'created_at' => 'orders.created_at',
        ];
        $sortColumn = $sortColumnMap[$sortBy] ?? $sortColumnMap['created_at'];

        $summary = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw('COALESCE(SUM(orders.total), 0) as total_amount')
            ->selectRaw("COALESCE(SUM(CASE WHEN orders.payment_status = 'paid' THEN 1 ELSE 0 END), 0) as paid_orders")
            ->first();

        $totalOrders = (int) ($summary->total_orders ?? 0);
        $totalAmount = (int) ($summary->total_amount ?? 0);
        $paidOrders = (int) ($summary->paid_orders ?? 0);
        $averageOrderAmount = $totalOrders > 0
            ? (int) round($totalAmount / $totalOrders)
            : 0;

        $ordersQuery = (clone $baseQuery)
            ->select('orders.*')
            ->with([
                'store:id,name,currency',
                'items:id,order_id,product_name,qty,subtotal',
            ]);

        if ($sortBy === 'store_name') {
            $ordersQuery->leftJoin('stores as sort_store', 'sort_store.id', '=', 'orders.store_id')
                ->orderBy('sort_store.name', $sortDir);
        } else {
            $ordersQuery->orderBy($sortColumn, $sortDir);
        }

        $orders = $ordersQuery
            ->orderByDesc('orders.id')
            ->paginate(20)
            ->withQueryString();

        return view('merchant.orders.index', [
            'stores' => $stores,
            'selectedStoreId' => $selectedStoreId,
            'orders' => $orders,
            'filters' => [
                'status' => (string) ($validated['status'] ?? ''),
                'payment_status' => (string) ($validated['payment_status'] ?? ''),
                'order_type' => (string) ($validated['order_type'] ?? ''),
                'keyword' => $keyword,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'sort' => [
                'by' => $sortBy,
                'dir' => $sortDir,
            ],
            'totalOrders' => $totalOrders,
            'totalAmount' => $totalAmount,
            'paidOrders' => $paidOrders,
            'averageOrderAmount' => $averageOrderAmount,
        ]);
    }
}
