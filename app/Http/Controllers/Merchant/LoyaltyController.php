<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\MemberPointLedger;
use App\Models\Order;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoyaltyController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $stores = Store::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'currency', 'loyalty_enabled', 'points_per_amount']);

        $selectedStoreId = (int) ($request->input('store_id') ?: ($stores->first()->id ?? 0));
        $selectedStore = $stores->firstWhere('id', $selectedStoreId);

        abort_unless($selectedStore !== null, 404);

        $startDate = (string) ($request->input('start_date') ?: now()->subDays(29)->toDateString());
        $endDate = (string) ($request->input('end_date') ?: now()->toDateString());
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        $keyword = trim((string) $request->input('keyword', ''));

        $membersQuery = Member::query()
            ->where('store_id', $selectedStore->id)
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($inner) use ($keyword) {
                    $inner->where('name', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%")
                        ->orWhere('phone', 'like', "%{$keyword}%");
                });
            });

        $members = (clone $membersQuery)
            ->orderByDesc('last_order_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $totalMembers = (clone $membersQuery)->count();
        $newMembers = (clone $membersQuery)->whereBetween('created_at', [$start, $end])->count();
        $repeatMembers = (clone $membersQuery)->where('total_orders', '>=', 2)->count();
        $avgSpentPerMember = (int) round((clone $membersQuery)->avg('total_spent') ?: 0);

        $topMembers = Member::query()
            ->where('store_id', $selectedStore->id)
            ->orderByDesc('total_spent')
            ->limit(10)
            ->get();

        $pointsIssued = (int) MemberPointLedger::query()
            ->where('store_id', $selectedStore->id)
            ->where('points_change', '>', 0)
            ->whereBetween('created_at', [$start, $end])
            ->sum('points_change');
        $pointsRedeemed = (int) abs((int) MemberPointLedger::query()
            ->where('store_id', $selectedStore->id)
            ->where('points_change', '<', 0)
            ->whereBetween('created_at', [$start, $end])
            ->sum('points_change'));

        $couponOrders = Order::query()
            ->where('store_id', $selectedStore->id)
            ->whereNotNull('coupon_id')
            ->whereBetween('created_at', [$start, $end])
            ->count();

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
            'totalMembers' => $totalMembers,
            'newMembers' => $newMembers,
            'repeatMembers' => $repeatMembers,
            'avgSpentPerMember' => $avgSpentPerMember,
            'topMembers' => $topMembers,
            'pointsIssued' => $pointsIssued,
            'pointsRedeemed' => $pointsRedeemed,
            'couponOrders' => $couponOrders,
            'coupons' => $coupons,
        ]);
    }

    public function updateSettings(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'store_id' => ['required', 'integer'],
            'loyalty_enabled' => ['nullable', 'boolean'],
            'points_per_amount' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);

        $store = Store::query()
            ->where('user_id', $user->id)
            ->where('id', (int) $validated['store_id'])
            ->firstOrFail();

        $store->update([
            'loyalty_enabled' => $request->boolean('loyalty_enabled'),
            'points_per_amount' => (int) $validated['points_per_amount'],
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
        $user = $request->user();
        $validated = $request->validate([
            'store_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:64'],
            'discount_type' => ['required', 'in:fixed,percent'],
            'discount_value' => ['required', 'integer', 'min:1'],
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
            'discount_value' => (int) $validated['discount_value'],
            'min_order_amount' => (int) ($validated['min_order_amount'] ?? 0),
            'points_cost' => (int) ($validated['points_cost'] ?? 0),
            'usage_limit' => isset($validated['usage_limit']) ? (int) $validated['usage_limit'] : null,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('status', '優惠券已建立');
    }

    public function updateCoupon(Request $request, Coupon $coupon)
    {
        $this->authorizeCoupon($request, $coupon);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'discount_type' => ['required', 'in:fixed,percent'],
            'discount_value' => ['required', 'integer', 'min:1'],
            'min_order_amount' => ['nullable', 'integer', 'min:0'],
            'points_cost' => ['nullable', 'integer', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $coupon->update([
            'name' => $validated['name'],
            'discount_type' => $validated['discount_type'],
            'discount_value' => (int) $validated['discount_value'],
            'min_order_amount' => (int) ($validated['min_order_amount'] ?? 0),
            'points_cost' => (int) ($validated['points_cost'] ?? 0),
            'usage_limit' => isset($validated['usage_limit']) ? (int) $validated['usage_limit'] : null,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', '優惠券已更新');
    }

    public function toggleCoupon(Request $request, Coupon $coupon)
    {
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
}

