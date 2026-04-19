<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FinancialReportController extends Controller
{
    private const REPORT_CACHE_TTL_SECONDS = 120;

    public function index(Request $request): View
    {
        $user = $request->user();

        $today = now();
        $defaultStart = $today->copy()->subMonthNoOverflow()->toDateString();
        $defaultEnd = $today->toDateString();
        $defaultCompareStart = $today->copy()->subMonthsNoOverflow(2)->toDateString();
        $defaultCompareEnd = $today->copy()->subMonthNoOverflow()->toDateString();

        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'compare_start_date' => ['nullable', 'date', 'required_with:compare_end_date'],
            'compare_end_date' => ['nullable', 'date', 'required_with:compare_start_date', 'after_or_equal:compare_start_date'],
            'trend_granularity' => ['nullable', 'in:day,hour'],
            'hour_step' => ['nullable', 'integer', 'in:1,2,3,4,6,12'],
        ]);

        $startDate = (string) ($validated['start_date'] ?? $defaultStart);
        $endDate = (string) ($validated['end_date'] ?? $defaultEnd);
        $hasCompareInput = $request->query->has('compare_start_date') || $request->query->has('compare_end_date');
        $compareStartDate = $hasCompareInput
            ? (isset($validated['compare_start_date']) ? (string) $validated['compare_start_date'] : null)
            : $defaultCompareStart;
        $compareEndDate = $hasCompareInput
            ? (isset($validated['compare_end_date']) ? (string) $validated['compare_end_date'] : null)
            : $defaultCompareEnd;
        $trendGranularity = (string) ($validated['trend_granularity'] ?? 'day');
        $hourStep = (int) ($validated['hour_step'] ?? 1);

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $stores = Store::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'currency', 'monthly_revenue_target']);

        $selectedStoreId = $stores->count() === 1
            ? (int) $stores->first()->id
            : null;

        if ($request->filled('store_id')) {
            $candidateStoreId = (int) $request->input('store_id');
            if ($stores->contains('id', $candidateStoreId)) {
                $selectedStoreId = $candidateStoreId;
            }
        }

        $trendColors = ['#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316', '#6366f1', '#84cc16', '#06b6d4', '#e11d48'];
        $report = $this->getCachedReport(
            userId: (int) $user->id,
            start: $start,
            end: $end,
            selectedStoreId: $selectedStoreId,
            compareStartDate: $compareStartDate,
            compareEndDate: $compareEndDate,
            trendGranularity: $trendGranularity,
            hourStep: $hourStep,
            hasMultipleStores: $stores->count() > 1,
            trendColors: $trendColors,
        );

        $selectedStore = $selectedStoreId !== null
            ? $stores->firstWhere('id', $selectedStoreId)
            : null;
        $monthlyRevenueTarget = $selectedStore !== null
            ? (int) ($selectedStore->monthly_revenue_target ?? 0)
            : (int) $stores->sum('monthly_revenue_target');
        $canEditMonthlyTarget = $selectedStore !== null;
        $currentMonthRevenue = (int) $report['currentMonthRevenue'];
        $monthlyTargetProgress = $monthlyRevenueTarget > 0
            ? round(($currentMonthRevenue / $monthlyRevenueTarget) * 100, 1)
            : 0;
        $monthlyTargetRemaining = max(0, $monthlyRevenueTarget - $currentMonthRevenue);

        return view('merchant.reports.financial', [
            'startDate' => $start->toDateString(),
            'endDate' => $end->toDateString(),
            'compareStartDate' => $compareStartDate,
            'compareEndDate' => $compareEndDate,
            'trendGranularity' => $trendGranularity,
            'hourStep' => $hourStep,
            'stores' => $stores,
            'selectedStoreId' => $selectedStoreId,
            'isMultiStoreView' => $report['isMultiStoreView'],
            'totalOrders' => $report['totalOrders'],
            'totalRevenue' => $report['totalRevenue'],
            'totalCost' => $report['totalCost'],
            'totalProfit' => $report['totalProfit'],
            'grossMarginRate' => $report['grossMarginRate'],
            'avgOrderValue' => $report['avgOrderValue'],
            'itemsSold' => $report['itemsSold'],
            'takeoutRevenue' => $report['takeoutRevenue'],
            'dineInRevenue' => $report['dineInRevenue'],
            'takeoutOrders' => $report['takeoutOrders'],
            'dineInOrders' => $report['dineInOrders'],
            'takeoutRevenueRatio' => $report['takeoutRevenueRatio'],
            'dineInRevenueRatio' => $report['dineInRevenueRatio'],
            'takeoutOrdersRatio' => $report['takeoutOrdersRatio'],
            'dineInOrdersRatio' => $report['dineInOrdersRatio'],
            'comparison' => $report['comparison'],
            'monthlyRevenueTarget' => $monthlyRevenueTarget,
            'canEditMonthlyTarget' => $canEditMonthlyTarget,
            'currentMonthRevenue' => $currentMonthRevenue,
            'monthlyTargetProgress' => $monthlyTargetProgress,
            'monthlyTargetRemaining' => $monthlyTargetRemaining,
            'topProducts' => $report['topProducts'],
            'storeRevenue' => $report['storeRevenue'],
            'chartLabels' => $report['labels'],
            'chartRevenue' => $report['dailyRevenue'],
            'chartOrders' => $report['dailyOrderCount'],
            'productTrendLabels' => $report['labels'],
            'productTrendDatasets' => $report['productTrendDatasets'],
        ]);
    }

    public function updateMonthlyTarget(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'store_id' => ['required', 'integer'],
            'monthly_revenue_target' => ['required', 'integer', 'min:0'],
        ]);

        $store = Store::query()
            ->where('user_id', $user->id)
            ->where('id', (int) $validated['store_id'])
            ->firstOrFail();

        $store->monthly_revenue_target = (int) $validated['monthly_revenue_target'];
        $store->save();

        return redirect()
            ->route('merchant.reports.financial', [
                'store_id' => $store->id,
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'compare_start_date' => $request->input('compare_start_date'),
                'compare_end_date' => $request->input('compare_end_date'),
                'trend_granularity' => $request->input('trend_granularity', 'day'),
                'hour_step' => $request->input('hour_step', 1),
            ])
            ->with('status', __('merchant.monthly_target_updated'));
    }

    private function getCachedReport(
        int $userId,
        Carbon $start,
        Carbon $end,
        ?int $selectedStoreId,
        ?string $compareStartDate,
        ?string $compareEndDate,
        string $trendGranularity,
        int $hourStep,
        bool $hasMultipleStores,
        array $trendColors,
    ): array {
        $cacheKey = 'financial-report:' . md5(json_encode([
            'user_id' => $userId,
            'start' => $start->toIso8601String(),
            'end' => $end->toIso8601String(),
            'store_id' => $selectedStoreId,
            'compare_start_date' => $compareStartDate,
            'compare_end_date' => $compareEndDate,
            'trend_granularity' => $trendGranularity,
            'hour_step' => $hourStep,
        ], JSON_UNESCAPED_SLASHES));

        return Cache::remember($cacheKey, now()->addSeconds(self::REPORT_CACHE_TTL_SECONDS), function () use (
            $userId,
            $start,
            $end,
            $selectedStoreId,
            $compareStartDate,
            $compareEndDate,
            $trendGranularity,
            $hourStep,
            $hasMultipleStores,
            $trendColors,
        ): array {
            $summary = $this->buildSummarySnapshot($userId, $start, $end, $selectedStoreId);
            $topProducts = $this->buildTopProducts($userId, $start, $end, $selectedStoreId, $hasMultipleStores);
            $trendSeries = $this->buildTrendSeries($userId, $start, $end, $selectedStoreId, $trendGranularity, $hourStep);
            $comparison = $this->buildComparisonSnapshot($userId, $selectedStoreId, $compareStartDate, $compareEndDate, $summary);

            $monthStart = now()->copy()->startOfMonth()->startOfDay();
            $monthEnd = now()->copy()->endOfMonth()->endOfDay();
            $currentMonthRevenue = (int) ((clone $this->buildBaseOrdersQuery($userId, $monthStart, $monthEnd, $selectedStoreId))
                ->sum('orders.total'));

            return array_merge($summary, [
                'isMultiStoreView' => $selectedStoreId === null && $hasMultipleStores,
                'comparison' => $comparison,
                'topProducts' => $topProducts,
                'storeRevenue' => $this->buildStoreRevenue($userId, $start, $end, $selectedStoreId),
                'labels' => $trendSeries['labels'],
                'dailyRevenue' => $trendSeries['dailyRevenue'],
                'dailyOrderCount' => $trendSeries['dailyOrderCount'],
                'productTrendDatasets' => $this->buildProductTrendDatasets($userId, $start, $end, $selectedStoreId, $topProducts, $trendColors),
                'currentMonthRevenue' => $currentMonthRevenue,
            ]);
        });
    }

    private function buildSummarySnapshot(int $userId, Carbon $start, Carbon $end, ?int $selectedStoreId): array
    {
        $itemTotalsByOrder = Order::query()
            ->from('order_items')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->selectRaw('order_items.order_id')
            ->selectRaw('COALESCE(SUM(order_items.qty), 0) as items_sold')
            ->selectRaw('COALESCE(SUM(order_items.qty * COALESCE(products.cost, 0)), 0) as total_cost')
            ->groupBy('order_items.order_id');

        $summary = $this->buildBaseOrdersQuery($userId, $start, $end, $selectedStoreId)
            ->leftJoinSub($itemTotalsByOrder, 'item_totals', function ($join) {
                $join->on('item_totals.order_id', '=', 'orders.id');
            })
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw('COALESCE(SUM(orders.total), 0) as total_revenue')
            ->selectRaw('COALESCE(SUM(COALESCE(item_totals.total_cost, 0)), 0) as total_cost')
            ->selectRaw('COALESCE(SUM(COALESCE(item_totals.items_sold, 0)), 0) as items_sold')
            ->selectRaw("COALESCE(SUM(CASE WHEN LOWER(COALESCE(orders.order_type, '')) IN ('takeout', 'take_out') THEN orders.total ELSE 0 END), 0) as takeout_revenue")
            ->selectRaw("COALESCE(SUM(CASE WHEN LOWER(COALESCE(orders.order_type, '')) IN ('dine_in', 'dinein') THEN orders.total ELSE 0 END), 0) as dine_in_revenue")
            ->selectRaw("COALESCE(SUM(CASE WHEN LOWER(COALESCE(orders.order_type, '')) IN ('takeout', 'take_out') THEN 1 ELSE 0 END), 0) as takeout_orders")
            ->selectRaw("COALESCE(SUM(CASE WHEN LOWER(COALESCE(orders.order_type, '')) IN ('dine_in', 'dinein') THEN 1 ELSE 0 END), 0) as dine_in_orders")
            ->first();

        $totalOrders = (int) ($summary->total_orders ?? 0);
        $totalRevenue = (int) ($summary->total_revenue ?? 0);
        $totalCost = (int) ($summary->total_cost ?? 0);
        $itemsSold = (int) ($summary->items_sold ?? 0);

        $totalProfit = $totalRevenue - $totalCost;
        $grossMarginRate = $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 1) : 0;
        $avgOrderValue = $totalOrders > 0 ? (int) round($totalRevenue / $totalOrders) : 0;
        $takeoutRevenue = (int) ($summary->takeout_revenue ?? 0);
        $dineInRevenue = (int) ($summary->dine_in_revenue ?? 0);
        $takeoutOrders = (int) ($summary->takeout_orders ?? 0);
        $dineInOrders = (int) ($summary->dine_in_orders ?? 0);

        return [
            'totalOrders' => $totalOrders,
            'totalRevenue' => $totalRevenue,
            'totalCost' => $totalCost,
            'totalProfit' => $totalProfit,
            'grossMarginRate' => $grossMarginRate,
            'avgOrderValue' => $avgOrderValue,
            'itemsSold' => $itemsSold,
            'takeoutRevenue' => $takeoutRevenue,
            'dineInRevenue' => $dineInRevenue,
            'takeoutOrders' => $takeoutOrders,
            'dineInOrders' => $dineInOrders,
            'takeoutRevenueRatio' => $totalRevenue > 0 ? round(($takeoutRevenue / $totalRevenue) * 100, 1) : 0,
            'dineInRevenueRatio' => $totalRevenue > 0 ? round(($dineInRevenue / $totalRevenue) * 100, 1) : 0,
            'takeoutOrdersRatio' => $totalOrders > 0 ? round(($takeoutOrders / $totalOrders) * 100, 1) : 0,
            'dineInOrdersRatio' => $totalOrders > 0 ? round(($dineInOrders / $totalOrders) * 100, 1) : 0,
        ];
    }

    private function buildComparisonSnapshot(
        int $userId,
        ?int $selectedStoreId,
        ?string $compareStartDate,
        ?string $compareEndDate,
        array $currentSummary,
    ): ?array {
        if ($compareStartDate === null || $compareEndDate === null) {
            return null;
        }

        $compareStart = Carbon::parse($compareStartDate)->startOfDay();
        $compareEnd = Carbon::parse($compareEndDate)->endOfDay();
        $compareSummary = $this->buildSummarySnapshot($userId, $compareStart, $compareEnd, $selectedStoreId);

        return [
            'start_date' => $compareStart->toDateString(),
            'end_date' => $compareEnd->toDateString(),
            'total_orders' => $compareSummary['totalOrders'],
            'total_revenue' => $compareSummary['totalRevenue'],
            'total_cost' => $compareSummary['totalCost'],
            'total_profit' => $compareSummary['totalProfit'],
            'gross_margin_rate' => $compareSummary['grossMarginRate'],
            'avg_order_value' => $compareSummary['avgOrderValue'],
            'delta_revenue' => $currentSummary['totalRevenue'] - $compareSummary['totalRevenue'],
            'delta_cost' => $currentSummary['totalCost'] - $compareSummary['totalCost'],
            'delta_profit' => $currentSummary['totalProfit'] - $compareSummary['totalProfit'],
            'delta_orders' => $currentSummary['totalOrders'] - $compareSummary['totalOrders'],
            'delta_avg_order_value' => $currentSummary['avgOrderValue'] - $compareSummary['avgOrderValue'],
            'delta_gross_margin_rate' => round($currentSummary['grossMarginRate'] - $compareSummary['grossMarginRate'], 1),
            'delta_revenue_ratio' => $this->calculateRatioChange($currentSummary['totalRevenue'], $compareSummary['totalRevenue']),
            'delta_cost_ratio' => $this->calculateRatioChange($currentSummary['totalCost'], $compareSummary['totalCost']),
            'delta_profit_ratio' => $this->calculateRatioChange($currentSummary['totalProfit'], $compareSummary['totalProfit']),
            'delta_orders_ratio' => $this->calculateRatioChange($currentSummary['totalOrders'], $compareSummary['totalOrders']),
            'delta_avg_order_value_ratio' => $this->calculateRatioChange($currentSummary['avgOrderValue'], $compareSummary['avgOrderValue']),
        ];
    }

    private function buildTopProducts(int $userId, Carbon $start, Carbon $end, ?int $selectedStoreId, bool $hasMultipleStores): Collection
    {
        $isMultiStoreView = $selectedStoreId === null && $hasMultipleStores;

        return (clone $this->buildBaseOrdersQuery($userId, $start, $end, $selectedStoreId))
            ->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->selectRaw('orders.store_id, stores.name as store_name, order_items.product_id, order_items.product_name, SUM(order_items.qty) as sold_qty, COALESCE(SUM(order_items.subtotal), 0) as sold_amount')
            ->groupBy('orders.store_id', 'stores.name', 'order_items.product_id', 'order_items.product_name')
            ->orderByDesc('sold_amount')
            ->orderByDesc('sold_qty')
            ->limit(12)
            ->get()
            ->map(function ($row) use ($isMultiStoreView) {
                $row->display_name = $isMultiStoreView
                    ? sprintf('%s / %s', (string) $row->store_name, (string) $row->product_name)
                    : (string) $row->product_name;

                return $row;
            })
            ->values();
    }

    private function buildStoreRevenue(int $userId, Carbon $start, Carbon $end, ?int $selectedStoreId): Collection
    {
        return (clone $this->buildBaseOrdersQuery($userId, $start, $end, $selectedStoreId))
            ->selectRaw('orders.store_id, stores.name as store_name, COUNT(*) as order_count, COALESCE(SUM(orders.total), 0) as revenue')
            ->groupBy('orders.store_id', 'stores.name')
            ->orderByDesc('revenue')
            ->get();
    }

    private function buildTrendSeries(int $userId, Carbon $start, Carbon $end, ?int $selectedStoreId, string $trendGranularity, int $hourStep): array
    {
        $baseOrders = $this->buildBaseOrdersQuery($userId, $start, $end, $selectedStoreId);
        $labels = [];
        $dailyRevenue = [];
        $dailyOrderCount = [];

        if ($trendGranularity === 'hour') {
            $driver = DB::getDriverName();
            if ($driver === 'pgsql') {
                $bucketExpression = $hourStep === 1
                    ? "to_char(date_trunc('hour', orders.created_at), 'YYYY-MM-DD HH24:00:00')"
                    : "to_char(date_trunc('hour', orders.created_at) + INTERVAL '1 hour' * FLOOR(EXTRACT(hour from orders.created_at)::int / {$hourStep}) * {$hourStep}, 'YYYY-MM-DD HH24:00:00')";
            } else {
                $bucketExpression = $hourStep === 1
                    ? "DATE_FORMAT(orders.created_at, '%Y-%m-%d %H:00:00')"
                    : "CONCAT(DATE_FORMAT(orders.created_at, '%Y-%m-%d '), LPAD(FLOOR(HOUR(orders.created_at) / {$hourStep}) * {$hourStep}, 2, '0'), ':00:00')";
            }

            $hourlyRows = (clone $baseOrders)
                ->selectRaw("{$bucketExpression} as bucket, COALESCE(SUM(orders.total), 0) as revenue, COUNT(*) as order_count")
                ->groupBy(DB::raw($bucketExpression))
                ->orderBy('bucket')
                ->get()
                ->keyBy('bucket');

            $cursor = $start->copy()->startOfHour();
            $cursorHour = (int) $cursor->format('G');
            $cursor->setTime((int) (floor($cursorHour / $hourStep) * $hourStep), 0, 0);

            $endCursor = $end->copy()->startOfHour();
            $endHour = (int) $endCursor->format('G');
            $endCursor->setTime((int) (floor($endHour / $hourStep) * $hourStep), 0, 0);

            while ($cursor->lte($endCursor)) {
                $key = $cursor->format('Y-m-d H:00:00');
                $labels[] = $cursor->format('m/d H:00');
                $dailyRevenue[] = (int) ($hourlyRows->get($key)->revenue ?? 0);
                $dailyOrderCount[] = (int) ($hourlyRows->get($key)->order_count ?? 0);
                $cursor->addHours($hourStep);
            }
        } else {
            $dailyRows = (clone $baseOrders)
                ->selectRaw('DATE(orders.created_at) as day, COALESCE(SUM(orders.total), 0) as revenue, COUNT(*) as order_count')
                ->groupBy(DB::raw('DATE(orders.created_at)'))
                ->orderBy('day')
                ->get()
                ->keyBy('day');

            $cursor = $start->copy()->startOfDay();
            $endDay = $end->copy()->startOfDay();

            while ($cursor->lte($endDay)) {
                $key = $cursor->toDateString();
                $labels[] = $cursor->format('m/d');
                $dailyRevenue[] = (int) ($dailyRows->get($key)->revenue ?? 0);
                $dailyOrderCount[] = (int) ($dailyRows->get($key)->order_count ?? 0);
                $cursor->addDay();
            }
        }

        return [
            'labels' => $labels,
            'dailyRevenue' => $dailyRevenue,
            'dailyOrderCount' => $dailyOrderCount,
        ];
    }

    private function buildProductTrendDatasets(
        int $userId,
        Carbon $start,
        Carbon $end,
        ?int $selectedStoreId,
        Collection $topProducts,
        array $trendColors,
    ): array {
        if ($topProducts->isEmpty()) {
            return [];
        }

        $productTrendRows = (clone $this->buildBaseOrdersQuery($userId, $start, $end, $selectedStoreId))
            ->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('order_items.product_id', $topProducts->pluck('product_id')->all())
            ->selectRaw('DATE(orders.created_at) as day, order_items.product_id, SUM(order_items.qty) as sold_qty')
            ->groupBy(DB::raw('DATE(orders.created_at)'), 'order_items.product_id')
            ->orderBy('day')
            ->get()
            ->groupBy('product_id');

        return $topProducts->map(function ($product, $index) use ($start, $end, $productTrendRows, $trendColors) {
            $rowsByDay = $productTrendRows
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
    }

    private function buildBaseOrdersQuery(int $userId, Carbon $start, Carbon $end, ?int $selectedStoreId)
    {
        $query = Order::query()
            ->join('stores', 'stores.id', '=', 'orders.store_id')
            ->where('stores.user_id', $userId)
            ->whereBetween('orders.created_at', [$start, $end])
            ->where(function ($builder) {
                $builder->whereNull('orders.status')
                    ->orWhereNotIn(DB::raw('LOWER(orders.status)'), ['cancel', 'cancelled', 'canceled']);
            });

        if ($selectedStoreId !== null) {
            $query->where('orders.store_id', $selectedStoreId);
        }

        return $query;
    }

    private function calculateRatioChange(int $currentValue, int $baseValue): float
    {
        if ($baseValue === 0) {
            return $currentValue > 0 ? 100 : 0;
        }

        return round((($currentValue - $baseValue) / $baseValue) * 100, 1);
    }
}
