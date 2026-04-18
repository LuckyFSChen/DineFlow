<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\MemberPointLedger;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LoyaltyController extends Controller
{
    private const CACHE_TTL_SECONDS = 120;

    public function index(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->ensureHasOwnedStore($request)) {
            return $redirect;
        }

        $user = $request->user();
        $stores = Store::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'currency', 'loyalty_enabled', 'points_per_amount', 'points_reward']);

        $selectedStoreId = (int) ($request->input('store_id') ?: ($stores->first()->id ?? 0));
        $selectedStore = $stores->firstWhere('id', $selectedStoreId);

        abort_unless($selectedStore !== null, 404);

        $startDate = (string) ($request->input('start_date') ?: now()->subDays(29)->toDateString());
        $endDate = (string) ($request->input('end_date') ?: now()->toDateString());
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        $keyword = trim((string) $request->input('keyword', ''));

        $membersQuery = $this->applyMemberKeywordFilter(
            Member::query()->where('store_id', $selectedStore->id),
            $keyword
        );

        $members = (clone $membersQuery)
            ->select([
                'id',
                'store_id',
                'name',
                'email',
                'phone',
                'points_balance',
                'total_spent',
                'total_orders',
                'last_order_at',
            ])
            ->orderByDesc('last_order_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $memberIds = $members->getCollection()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $memberDetailData = $this->cachedMemberDetailData($selectedStore->id, $memberIds);
        $summaryData = $this->cachedSummaryData($selectedStore->id, $start, $end, $keyword);

        $coupons = Coupon::query()
            ->where('store_id', $selectedStore->id)
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'coupon_page')
            ->withQueryString();

        return view('merchant.loyalty.index', [
            'stores' => $stores,
            'selectedStore' => $selectedStore,
            'startDate' => $start->toDateString(),
            'endDate' => $end->toDateString(),
            'keyword' => $keyword,
            'members' => $members,
            'totalMembers' => $summaryData['totalMembers'],
            'newMembers' => $summaryData['newMembers'],
            'repeatMembers' => $summaryData['repeatMembers'],
            'avgSpentPerMember' => $summaryData['avgSpentPerMember'],
            'topMembers' => $summaryData['topMembers'],
            'favoriteItemsByMember' => $memberDetailData['favoriteItemsByMember'],
            'recentOrdersByMember' => $memberDetailData['recentOrdersByMember'],
            'pointsIssued' => $summaryData['pointsIssued'],
            'pointsRedeemed' => $summaryData['pointsRedeemed'],
            'couponOrders' => $summaryData['couponOrders'],
            'coupons' => $coupons,
        ]);
    }

    public function updateSettings(Request $request)
    {
        if ($redirect = $this->ensureHasOwnedStore($request)) {
            return $redirect;
        }

        $user = $request->user();
        $validated = $request->validate([
            'store_id' => ['required', 'integer'],
            'loyalty_enabled' => ['nullable', 'boolean'],
            'points_per_amount' => ['required', 'integer', 'min:1', 'max:100000'],
            'points_reward' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        $store = Store::query()
            ->where('user_id', $user->id)
            ->where('id', (int) $validated['store_id'])
            ->firstOrFail();

        $store->update([
            'loyalty_enabled' => $request->boolean('loyalty_enabled'),
            'points_per_amount' => (int) $validated['points_per_amount'],
            'points_reward' => (int) $validated['points_reward'],
        ]);

        return redirect()
            ->route('merchant.loyalty.index', [
                'store_id' => $store->id,
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'keyword' => $request->input('keyword'),
            ])
            ->with('status', '會員集點設定已更新');
    }

    public function storeCoupon(Request $request)
    {
        if ($redirect = $this->ensureHasOwnedStore($request)) {
            return $redirect;
        }

        $user = $request->user();
        $storeId = (int) $request->input('store_id');
        $validated = $request->validate([
            'store_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:64',
                Rule::unique('coupons', 'code')->where(fn ($query) => $query->where('store_id', $storeId)),
            ],
            'discount_type' => ['required', 'in:fixed,percent,points_reward'],
            'discount_value' => [
                Rule::requiredIf(fn () => in_array($request->input('discount_type'), ['fixed', 'percent'], true)),
                'nullable',
                'integer',
                'min:0',
            ],
            'reward_per_amount' => [
                Rule::requiredIf(fn () => $request->input('discount_type') === 'points_reward'),
                'nullable',
                'integer',
                'min:1',
            ],
            'reward_points' => [
                Rule::requiredIf(fn () => $request->input('discount_type') === 'points_reward'),
                'nullable',
                'integer',
                'min:1',
            ],
            'min_order_amount' => ['nullable', 'integer', 'min:0'],
            'points_cost' => ['nullable', 'integer', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $store = Store::query()
            ->where('user_id', $user->id)
            ->where('id', (int) $validated['store_id'])
            ->firstOrFail();

        Coupon::query()->create([
            'store_id' => $store->id,
            'name' => $validated['name'],
            'code' => strtoupper(trim((string) $validated['code'])),
            'discount_type' => $validated['discount_type'],
            'discount_value' => $validated['discount_type'] === 'points_reward' ? 0 : (int) ($validated['discount_value'] ?? 0),
            'min_order_amount' => (int) ($validated['min_order_amount'] ?? 0),
            'points_cost' => (int) ($validated['points_cost'] ?? 0),
            'reward_per_amount' => (int) ($validated['reward_per_amount'] ?? 0),
            'reward_points' => (int) ($validated['reward_points'] ?? 0),
            'usage_limit' => isset($validated['usage_limit']) ? (int) $validated['usage_limit'] : null,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('status', '優惠券已建立');
    }

    public function updateCoupon(Request $request, Coupon $coupon)
    {
        if ($redirect = $this->ensureHasOwnedStore($request)) {
            return $redirect;
        }

        $this->authorizeCoupon($request, $coupon);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:64',
                Rule::unique('coupons', 'code')
                    ->where(fn ($query) => $query->where('store_id', $coupon->store_id))
                    ->ignore($coupon->id),
            ],
            'discount_type' => ['required', 'in:fixed,percent,points_reward'],
            'discount_value' => [
                Rule::requiredIf(fn () => in_array($request->input('discount_type'), ['fixed', 'percent'], true)),
                'nullable',
                'integer',
                'min:0',
            ],
            'reward_per_amount' => [
                Rule::requiredIf(fn () => $request->input('discount_type') === 'points_reward'),
                'nullable',
                'integer',
                'min:1',
            ],
            'reward_points' => [
                Rule::requiredIf(fn () => $request->input('discount_type') === 'points_reward'),
                'nullable',
                'integer',
                'min:1',
            ],
            'min_order_amount' => ['nullable', 'integer', 'min:0'],
            'points_cost' => ['nullable', 'integer', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $coupon->update([
            'name' => $validated['name'],
            'code' => strtoupper(trim((string) $validated['code'])),
            'discount_type' => $validated['discount_type'],
            'discount_value' => $validated['discount_type'] === 'points_reward' ? 0 : (int) ($validated['discount_value'] ?? 0),
            'min_order_amount' => (int) ($validated['min_order_amount'] ?? 0),
            'points_cost' => (int) ($validated['points_cost'] ?? 0),
            'reward_per_amount' => (int) ($validated['reward_per_amount'] ?? 0),
            'reward_points' => (int) ($validated['reward_points'] ?? 0),
            'usage_limit' => isset($validated['usage_limit']) ? (int) $validated['usage_limit'] : null,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', '優惠券已更新');
    }

    public function destroyCoupon(Request $request, Coupon $coupon)
    {
        if ($redirect = $this->ensureHasOwnedStore($request)) {
            return $redirect;
        }

        $this->authorizeCoupon($request, $coupon);

        $coupon->delete();

        return back()->with('status', '優惠券已刪除');
    }

    public function toggleCoupon(Request $request, Coupon $coupon)
    {
        if ($redirect = $this->ensureHasOwnedStore($request)) {
            return $redirect;
        }

        $this->authorizeCoupon($request, $coupon);

        $coupon->is_active = ! $coupon->is_active;
        $coupon->save();

        return back()->with('status', $coupon->is_active ? '優惠券已啟用' : '優惠券已停用');
    }

    private function authorizeCoupon(Request $request, Coupon $coupon): void
    {
        $user = $request->user();
        $store = Store::query()
            ->where('id', $coupon->store_id)
            ->where('user_id', $user->id)
            ->first();

        abort_unless($store !== null, 403);
    }

    private function ensureHasOwnedStore(Request $request): ?RedirectResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isMerchant()) {
            return null;
        }

        if ($user->stores()->exists()) {
            return null;
        }

        $message = __('merchant.error_store_required_for_loyalty');

        return redirect()->route('admin.stores.index')->with('error', $message);
    }

    private function cachedMemberDetailData(int $storeId, Collection $memberIds): array
    {
        if ($memberIds->isEmpty()) {
            return [
                'favoriteItemsByMember' => collect(),
                'recentOrdersByMember' => collect(),
            ];
        }

        $cacheKey = 'loyalty:member-details:' . md5(json_encode([
            'store_id' => $storeId,
            'member_ids' => $memberIds->values()->all(),
        ], JSON_UNESCAPED_SLASHES));

        return Cache::remember($cacheKey, now()->addSeconds(self::CACHE_TTL_SECONDS), function () use ($storeId, $memberIds): array {
            $favoriteItemsBaseQuery = OrderItem::query()
                ->selectRaw('orders.member_id as member_id, order_items.product_name as product_name, SUM(order_items.qty) as total_qty, SUM(order_items.subtotal) as total_spent, COUNT(DISTINCT order_items.order_id) as order_count')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.store_id', $storeId)
                ->whereIn('orders.member_id', $memberIds)
                ->groupBy('orders.member_id', 'order_items.product_name')
                ->whereNotNull('orders.member_id');

            $rankedFavoriteItems = DB::query()
                ->fromSub($favoriteItemsBaseQuery, 'favorite_items')
                ->selectRaw('favorite_items.member_id, favorite_items.product_name, favorite_items.total_qty, favorite_items.total_spent, favorite_items.order_count')
                ->selectRaw('ROW_NUMBER() OVER (PARTITION BY favorite_items.member_id ORDER BY favorite_items.total_qty DESC, favorite_items.order_count DESC, favorite_items.total_spent DESC, favorite_items.product_name ASC) as item_rank');

            $favoriteItemsByMember = DB::query()
                ->fromSub($rankedFavoriteItems, 'ranked_favorite_items')
                ->select(['member_id', 'product_name', 'total_qty', 'total_spent', 'order_count'])
                ->where('item_rank', '<=', 3)
                ->orderBy('member_id')
                ->orderBy('item_rank')
                ->get()
                ->groupBy(fn ($row) => (int) $row->member_id)
                ->map(fn ($items) => $items->values());

            $recentOrdersBaseQuery = Order::query()
                ->where('store_id', $storeId)
                ->whereIn('member_id', $memberIds)
                ->whereNotNull('member_id')
                ->select(['id', 'member_id', 'order_no', 'status', 'payment_status', 'total', 'created_at']);

            $rankedRecentOrders = DB::query()
                ->fromSub($recentOrdersBaseQuery, 'recent_orders')
                ->selectRaw('recent_orders.id, recent_orders.member_id, recent_orders.order_no, recent_orders.status, recent_orders.payment_status, recent_orders.total, recent_orders.created_at')
                ->selectRaw('ROW_NUMBER() OVER (PARTITION BY recent_orders.member_id ORDER BY recent_orders.created_at DESC, recent_orders.id DESC) as order_rank');

            $recentOrdersByMember = DB::query()
                ->fromSub($rankedRecentOrders, 'ranked_recent_orders')
                ->select(['id', 'member_id', 'order_no', 'status', 'payment_status', 'total', 'created_at'])
                ->where('order_rank', '<=', 5)
                ->orderBy('member_id')
                ->orderBy('order_rank')
                ->get()
                ->map(function ($order) {
                    $order->created_at = $order->created_at !== null ? Carbon::parse($order->created_at) : null;

                    return $order;
                })
                ->groupBy(fn ($order) => (int) $order->member_id)
                ->map(fn ($orders) => $orders->values());

            return [
                'favoriteItemsByMember' => $favoriteItemsByMember,
                'recentOrdersByMember' => $recentOrdersByMember,
            ];
        });
    }

    private function cachedSummaryData(int $storeId, Carbon $start, Carbon $end, string $keyword): array
    {
        $cacheKey = 'loyalty:summary:' . md5(json_encode([
            'store_id' => $storeId,
            'start' => $start->toIso8601String(),
            'end' => $end->toIso8601String(),
            'keyword' => $keyword,
        ], JSON_UNESCAPED_SLASHES));

        return Cache::remember($cacheKey, now()->addSeconds(self::CACHE_TTL_SECONDS), function () use ($storeId, $start, $end, $keyword): array {
            $memberSummary = $this->applyMemberKeywordFilter(
                Member::query()->where('store_id', $storeId),
                $keyword
            )
                ->selectRaw('COUNT(*) as total_members')
                ->selectRaw('SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as new_members', [$start, $end])
                ->selectRaw('SUM(CASE WHEN total_orders >= 2 THEN 1 ELSE 0 END) as repeat_members')
                ->selectRaw('COALESCE(AVG(total_spent), 0) as avg_spent_per_member')
                ->first();

            $topMembers = Member::query()
                ->where('store_id', $storeId)
                ->select(['id', 'name', 'email', 'phone', 'points_balance', 'total_spent', 'total_orders'])
                ->orderByDesc('total_spent')
                ->limit(10)
                ->get();

            $pointsSummary = MemberPointLedger::query()
                ->where('store_id', $storeId)
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw('COALESCE(SUM(CASE WHEN points_change > 0 THEN points_change ELSE 0 END), 0) as points_issued')
                ->selectRaw('COALESCE(ABS(SUM(CASE WHEN points_change < 0 THEN points_change ELSE 0 END)), 0) as points_redeemed')
                ->first();

            $couponOrders = (int) Order::query()
                ->where('store_id', $storeId)
                ->whereNotNull('coupon_id')
                ->whereBetween('created_at', [$start, $end])
                ->count();

            return [
                'totalMembers' => (int) ($memberSummary->total_members ?? 0),
                'newMembers' => (int) ($memberSummary->new_members ?? 0),
                'repeatMembers' => (int) ($memberSummary->repeat_members ?? 0),
                'avgSpentPerMember' => (int) round((float) ($memberSummary->avg_spent_per_member ?? 0)),
                'topMembers' => $topMembers,
                'pointsIssued' => (int) ($pointsSummary->points_issued ?? 0),
                'pointsRedeemed' => (int) ($pointsSummary->points_redeemed ?? 0),
                'couponOrders' => $couponOrders,
            ];
        });
    }

    private function applyMemberKeywordFilter(Builder $query, string $keyword): Builder
    {
        if ($keyword === '') {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($keyword): void {
            $operator = $this->caseInsensitiveLikeOperator();

            $inner->where('name', $operator, "%{$keyword}%")
                ->orWhere('email', $operator, "%{$keyword}%")
                ->orWhere('phone', $operator, "%{$keyword}%");
        });
    }

    private function caseInsensitiveLikeOperator(): string
    {
        return DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'like';
    }
}
