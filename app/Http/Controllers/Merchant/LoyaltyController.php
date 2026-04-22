<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Concerns\ResolvesAccessibleStores;
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
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Closure;

class LoyaltyController extends Controller
{
    use ResolvesAccessibleStores;

    private const CACHE_TTL_SECONDS = 120;

    public function index(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->ensureHasAccessibleStore($request)) {
            return $redirect;
        }

        $user = $request->user();
        $stores = $this->accessibleStoresQuery($user)
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
        if ($redirect = $this->ensureHasAccessibleStore($request)) {
            return $redirect;
        }

        $validated = $request->validate([
            'store_id' => ['required', 'integer'],
            'loyalty_enabled' => ['nullable', 'boolean'],
            'points_per_amount' => ['required', 'integer', 'min:1', 'max:100000'],
            'points_reward' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        $store = $this->resolveAccessibleStore($request, (int) $validated['store_id']);

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
            ->with('status', __('loyalty.settings_updated'));
    }

    public function storeCoupon(Request $request)
    {
        if ($redirect = $this->ensureHasAccessibleStore($request)) {
            return $redirect;
        }

        $validated = $this->validateCouponRequest($request);

        if (! $request->boolean('allow_dine_in') && ! $request->boolean('allow_takeout')) {
            return back()
                ->withErrors(['allow_dine_in' => __('loyalty.order_type_required')])
                ->withInput();
        }

        $store = $this->resolveAccessibleStore($request, (int) $validated['store_id']);

        Coupon::query()->create([
            'store_id' => $store->id,
            ...$this->buildCouponAttributes($request, $validated),
        ]);

        return back()->with('status', __('loyalty.coupon_created'));
    }

    public function updateCoupon(Request $request, Coupon $coupon)
    {
        if ($redirect = $this->ensureHasAccessibleStore($request)) {
            return $redirect;
        }

        $this->authorizeCoupon($request, $coupon);

        $validated = $this->validateCouponRequest($request, $coupon);

        if (! $request->boolean('allow_dine_in') && ! $request->boolean('allow_takeout')) {
            $message = __('loyalty.order_type_required');

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()
                ->withErrors(['allow_dine_in' => $message])
                ->withInput();
        }

        $coupon->update($this->buildCouponAttributes($request, $validated));

        if ($request->expectsJson()) {
            $coupon->refresh();

            return response()->json([
                'message' => __('loyalty.coupon_updated'),
                'coupon' => [
                    'id' => (int) $coupon->id,
                    'name' => (string) $coupon->name,
                    'code' => (string) $coupon->code,
                    'discount_type' => (string) $coupon->discount_type,
                    'discount_value' => (int) $coupon->discount_value,
                    'reward_per_amount' => (int) $coupon->reward_per_amount,
                    'reward_points' => (int) $coupon->reward_points,
                    'min_order_amount' => (int) $coupon->min_order_amount,
                    'points_cost' => (int) $coupon->points_cost,
                    'usage_limit' => $coupon->usage_limit !== null ? (int) $coupon->usage_limit : null,
                    'used_count' => (int) $coupon->used_count,
                    'starts_at' => optional($coupon->starts_at)->format('Y-m-d\\TH:i'),
                    'ends_at' => optional($coupon->ends_at)->format('Y-m-d\\TH:i'),
                    'allow_dine_in' => $coupon->allowsDineIn(),
                    'allow_takeout' => $coupon->allowsTakeout(),
                    'is_active' => (bool) $coupon->is_active,
                ],
            ]);
        }

        return back()->with('status', __('loyalty.coupon_updated'));
    }

    public function destroyCoupon(Request $request, Coupon $coupon)
    {
        if ($redirect = $this->ensureHasAccessibleStore($request)) {
            return $redirect;
        }

        $this->authorizeCoupon($request, $coupon);

        $coupon->delete();

        return back()->with('status', __('loyalty.coupon_deleted'));
    }

    public function toggleCoupon(Request $request, Coupon $coupon)
    {
        if ($redirect = $this->ensureHasAccessibleStore($request)) {
            return $redirect;
        }

        $this->authorizeCoupon($request, $coupon);

        $coupon->is_active = ! $coupon->is_active;
        $coupon->save();

        return back()->with('status', $coupon->is_active ? __('loyalty.coupon_enabled') : __('loyalty.coupon_disabled'));
    }

    private function authorizeCoupon(Request $request, Coupon $coupon): void
    {
        $store = $this->accessibleStoresQuery($request->user())
            ->whereKey($coupon->store_id)
            ->first();

        abort_unless($store !== null, 403);
    }

    private function validateCouponRequest(Request $request, ?Coupon $coupon = null): array
    {
        $storeId = $coupon?->store_id ?? (int) $request->input('store_id');

        $validated = $request->validate([
            'store_id' => $coupon ? ['sometimes', 'integer'] : ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:64',
                Rule::unique('coupons', 'code')
                    ->where(fn ($query) => $query->where('store_id', $storeId))
                    ->when($coupon !== null, fn ($rule) => $rule->ignore($coupon->id)),
            ],
            'discount_type' => ['required', 'in:fixed,percent,points_reward'],
            'discount_value' => [
                Rule::requiredIf(fn () => in_array($request->input('discount_type'), ['fixed', 'percent'], true)),
                'nullable',
                'integer',
                'min:0',
                function (string $attribute, mixed $value, Closure $fail) use ($request): void {
                    $type = (string) $request->input('discount_type');
                    $amount = (int) ($value ?? 0);

                    if ($type === 'fixed' && $amount < 1) {
                        $fail(__('loyalty.fixed_discount_min'));
                    }

                    if ($type === 'percent' && ($amount < 1 || $amount > 100)) {
                        $fail(__('loyalty.percent_discount_range'));
                    }
                },
            ],
            'reward_per_amount' => [
                Rule::requiredIf(fn () => $request->input('discount_type') === 'points_reward' || (int) $request->input('reward_points') > 0),
                'nullable',
                'integer',
                'min:0',
            ],
            'reward_points' => [
                Rule::requiredIf(fn () => $request->input('discount_type') === 'points_reward' || (int) $request->input('reward_per_amount') > 0),
                'nullable',
                'integer',
                'min:0',
            ],
            'min_order_amount' => ['nullable', 'integer', 'min:0'],
            'points_cost' => ['nullable', 'integer', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'allow_dine_in' => ['nullable', 'boolean'],
            'allow_takeout' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $this->validateRewardConfiguration(
            (string) ($validated['discount_type'] ?? 'fixed'),
            max((int) ($validated['reward_per_amount'] ?? 0), 0),
            max((int) ($validated['reward_points'] ?? 0), 0)
        );

        return $validated;
    }

    private function validateRewardConfiguration(string $discountType, int $rewardPerAmount, int $rewardPoints): void
    {
        $hasRewardConfiguration = $discountType === 'points_reward'
            || $rewardPerAmount > 0
            || $rewardPoints > 0;

        if (! $hasRewardConfiguration) {
            return;
        }

        if ($rewardPerAmount > 0 && $rewardPoints > 0) {
            return;
        }

        throw ValidationException::withMessages([
            'reward_per_amount' => __('loyalty.reward_pair_required'),
            'reward_points' => __('loyalty.reward_pair_required'),
        ]);
    }

    private function buildCouponAttributes(Request $request, array $validated): array
    {
        $discountType = (string) ($validated['discount_type'] ?? 'fixed');
        $rewardPerAmount = max((int) ($validated['reward_per_amount'] ?? 0), 0);
        $rewardPoints = max((int) ($validated['reward_points'] ?? 0), 0);

        if ($discountType !== 'points_reward' && ($rewardPerAmount === 0 || $rewardPoints === 0)) {
            $rewardPerAmount = 0;
            $rewardPoints = 0;
        }

        return [
            'name' => $validated['name'],
            'code' => strtoupper(trim((string) $validated['code'])),
            'discount_type' => $discountType,
            'discount_value' => $discountType === 'points_reward' ? 0 : (int) ($validated['discount_value'] ?? 0),
            'min_order_amount' => (int) ($validated['min_order_amount'] ?? 0),
            'points_cost' => (int) ($validated['points_cost'] ?? 0),
            'reward_per_amount' => $rewardPerAmount,
            'reward_points' => $rewardPoints,
            'usage_limit' => isset($validated['usage_limit']) ? (int) $validated['usage_limit'] : null,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'allow_dine_in' => $request->boolean('allow_dine_in', true),
            'allow_takeout' => $request->boolean('allow_takeout', true),
            'is_active' => array_key_exists('is_active', $validated)
                ? $request->boolean('is_active')
                : false,
        ];
    }

    private function ensureHasAccessibleStore(Request $request): ?RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        if ($this->accessibleStoresQuery($user)->exists()) {
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
