<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FinancialReportController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $today = now();
        $defaultStart = $today->copy()->subDays(29)->toDateString();
        $defaultEnd = $today->toDateString();

        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = (string) ($validated['start_date'] ?? $defaultStart);
        $endDate = (string) ($validated['end_date'] ?? $defaultEnd);

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $stores = Store::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $selectedStoreId = null;
        if ($request->filled('store_id')) {
            $candidateStoreId = (int) $request->input('store_id');
            if ($stores->contains('id', $candidateStoreId)) {
                $selectedStoreId = $candidateStoreId;
            }
        }

        $baseOrders = Order::query()
            ->join('stores', 'stores.id', '=', 'orders.store_id')
            ->where('stores.user_id', $user->id)
            ->whereBetween('orders.created_at', [$start, $end])
            ->whereNotIn('orders.status', ['cancelled', 'canceled']);

        if ($selectedStoreId !== null) {
            $baseOrders->where('orders.store_id', $selectedStoreId);
        }

        $summary = (clone $baseOrders)
            ->selectRaw('COUNT(*) as total_orders, COALESCE(SUM(orders.total), 0) as total_revenue')
            ->first();

        $totalOrders = (int) ($summary->total_orders ?? 0);
        $totalRevenue = (int) ($summary->total_revenue ?? 0);
        $avgOrderValue = $totalOrders > 0 ? (int) round($totalRevenue / $totalOrders) : 0;

        $itemsSold = (int) ((clone $baseOrders)
            ->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->sum('order_items.qty'));

        $orderTypeStats = (clone $baseOrders)
            ->selectRaw("orders.order_type, COUNT(*) as order_count, COALESCE(SUM(orders.total), 0) as revenue")
            ->groupBy('orders.order_type')
            ->get();

        $orderTypeMap = $orderTypeStats->keyBy(fn ($row) => (string) $row->order_type);
        $takeoutRevenue = (int) ($orderTypeMap->get('takeout')->revenue ?? 0);
        $dineInRevenue = (int) ($orderTypeMap->get('dine_in')->revenue ?? $orderTypeMap->get('dinein')->revenue ?? 0);

        $topProducts = (clone $baseOrders)
            ->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->selectRaw('orders.store_id, stores.name as store_name, order_items.product_id, order_items.product_name, SUM(order_items.qty) as sold_qty, COALESCE(SUM(order_items.subtotal), 0) as sold_amount')
            ->groupBy('orders.store_id', 'stores.name', 'order_items.product_id', 'order_items.product_name')
            ->orderByDesc('sold_amount')
            ->orderByDesc('sold_qty')
            ->limit(12)
            ->get();

        $isMultiStoreView = $selectedStoreId === null && $stores->count() > 1;
        $topProducts = $topProducts->map(function ($row) use ($isMultiStoreView) {
            $row->display_name = $isMultiStoreView
                ? sprintf('【%s】%s', (string) $row->store_name, (string) $row->product_name)
                : (string) $row->product_name;

            return $row;
        })->values();

        $productTrendTargets = $topProducts->values();
        $productTrendRows = collect();

        if ($productTrendTargets->isNotEmpty()) {
            $productTrendRows = (clone $baseOrders)
                ->join('order_items', 'order_items.order_id', '=', 'orders.id')
                ->whereIn('order_items.product_id', $productTrendTargets->pluck('product_id')->all())
                ->selectRaw('DATE(orders.created_at) as day, order_items.product_id, SUM(order_items.qty) as sold_qty')
                ->groupBy(DB::raw('DATE(orders.created_at)'), 'order_items.product_id')
                ->orderBy('day')
                ->get();
        }

        $storeRevenue = (clone $baseOrders)
            ->selectRaw('orders.store_id, stores.name as store_name, COUNT(*) as order_count, COALESCE(SUM(orders.total), 0) as revenue')
            ->groupBy('orders.store_id', 'stores.name')
            ->orderByDesc('revenue')
            ->get();

        $dailyRows = (clone $baseOrders)
            ->selectRaw('DATE(orders.created_at) as day, COALESCE(SUM(orders.total), 0) as revenue, COUNT(*) as order_count')
            ->groupBy(DB::raw('DATE(orders.created_at)'))
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $labels = [];
        $dailyRevenue = [];
        $dailyOrderCount = [];

        $cursor = $start->copy()->startOfDay();
        $endDay = $end->copy()->startOfDay();
        while ($cursor->lte($endDay)) {
            $key = $cursor->toDateString();
            $labels[] = $cursor->format('m/d');
            $dailyRevenue[] = (int) ($dailyRows->get($key)->revenue ?? 0);
            $dailyOrderCount[] = (int) ($dailyRows->get($key)->order_count ?? 0);
            $cursor->addDay();
        }

        $trendRowsByProduct = $productTrendRows->groupBy('product_id');
        $trendColors = ['#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316', '#6366f1', '#84cc16', '#06b6d4', '#e11d48'];

        $productTrendDatasets = $productTrendTargets->values()->map(function ($product, $index) use ($start, $end, $trendRowsByProduct, $trendColors) {
            $rowsByDay = $trendRowsByProduct
                ->get($product->product_id, collect())
                ->keyBy('day');

            $data = [];
            $cursor = $start->copy()->startOfDay();
            $endDay = $end->copy()->startOfDay();

            while ($cursor->lte($endDay)) {
                $key = $cursor->toDateString();
                $data[] = (int) ($rowsByDay->get($key)->sold_qty ?? 0);
                $cursor->addDay();
            }

            return [
                'id' => (string) $product->product_id,
                'label' => (string) $product->product_name,
                'data' => $data,
                'borderColor' => $trendColors[$index % count($trendColors)],
                'backgroundColor' => $trendColors[$index % count($trendColors)],
            ];
        })->all();

        return view('merchant.reports.financial', [
            'startDate' => $start->toDateString(),
            'endDate' => $end->toDateString(),
            'stores' => $stores,
            'selectedStoreId' => $selectedStoreId,
            'isMultiStoreView' => $isMultiStoreView,
            'totalOrders' => $totalOrders,
            'totalRevenue' => $totalRevenue,
            'avgOrderValue' => $avgOrderValue,
            'itemsSold' => $itemsSold,
            'takeoutRevenue' => $takeoutRevenue,
            'dineInRevenue' => $dineInRevenue,
            'topProducts' => $topProducts,
            'storeRevenue' => $storeRevenue,
            'chartLabels' => $labels,
            'chartRevenue' => $dailyRevenue,
            'chartOrders' => $dailyOrderCount,
            'productTrendLabels' => $labels,
            'productTrendDatasets' => $productTrendDatasets,
        ]);
    }
}
