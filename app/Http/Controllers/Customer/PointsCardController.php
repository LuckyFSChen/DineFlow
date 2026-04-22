<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\Order;
use App\Models\User;
use App\Support\PhoneFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PointsCardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isCustomer(), 403);

        $email = strtolower(trim((string) ($user->email ?? '')));
        $phone = PhoneFormatter::digitsOnly((string) ($user->phone ?? ''), 32);

        $memberPointSummaries = collect();
        if ($email !== '' || $phone !== null) {
            $memberPointSummaries = Member::query()
                ->where(function ($query) use ($email, $phone): void {
                    if ($email !== '') {
                        $query->orWhereRaw('LOWER(email) = ?', [$email]);
                    }

                    if ($phone !== null) {
                        $query->orWhere('phone', $phone);
                    }
                })
                ->whereHas('store', fn ($query) => $query->where('is_active', true))
                ->with('store:id,slug,name,currency,banner_image,loyalty_enabled')
                ->orderByDesc('points_balance')
                ->orderByDesc('last_order_at')
                ->get();
        }

        $monthlySpentByMemberId = $this->buildMonthlySpentByMemberId($memberPointSummaries);
        $memberPointSummaries->each(function (Member $member) use ($monthlySpentByMemberId): void {
            $member->setAttribute('monthly_total_spent', (int) ($monthlySpentByMemberId->get((int) $member->id) ?? 0));
        });

        $storeCouponsByStoreId = $this->buildStoreCouponsByStoreId($memberPointSummaries);

        return view('customer.points-card', [
            'memberPointSummaries' => $memberPointSummaries,
            'totalPointsBalance' => (int) $memberPointSummaries->sum('points_balance'),
            'activeStoreCount' => $memberPointSummaries->filter(fn (Member $member) => $member->points_balance > 0)->count(),
            'storeCouponsByStoreId' => $storeCouponsByStoreId,
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function buildMonthlySpentByMemberId(Collection $memberPointSummaries): Collection
    {
        $memberIds = $memberPointSummaries
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($memberIds->isEmpty()) {
            return collect();
        }

        $monthStart = now()->startOfMonth();
        $nextMonthStart = (clone $monthStart)->addMonth();

        return Order::query()
            ->selectRaw('member_id, COALESCE(SUM(total), 0) as monthly_total_spent')
            ->whereIn('member_id', $memberIds->all())
            ->where('created_at', '>=', $monthStart)
            ->where('created_at', '<', $nextMonthStart)
            ->whereNotIn('status', ['cancel', 'cancelled', 'canceled'])
            ->groupBy('member_id')
            ->pluck('monthly_total_spent', 'member_id')
            ->map(fn ($amount) => (int) $amount);
    }

    /**
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, array{code:string,summary:string}>>
     */
    private function buildStoreCouponsByStoreId(Collection $memberPointSummaries): Collection
    {
        $storesById = $memberPointSummaries
            ->mapWithKeys(fn (Member $member) => $member->store ? [$member->store->id => $member->store] : [])
            ->filter();

        if ($storesById->isEmpty()) {
            return collect();
        }

        return Coupon::query()
            ->whereIn('store_id', $storesById->keys()->all())
            ->where('is_active', true)
            ->orderBy('store_id')
            ->orderBy('code')
            ->get()
            ->filter(fn (Coupon $coupon) => $coupon->isCurrentlyValid())
            ->map(function (Coupon $coupon) use ($storesById): array {
                $store = $storesById->get((int) $coupon->store_id);
                $currencySymbol = match (strtolower((string) ($store?->currency ?? 'twd'))) {
                    'vnd' => 'VND',
                    'cny' => 'CNY',
                    'usd' => 'USD',
                    default => 'NT$',
                };

                return [
                    'store_id' => (int) $coupon->store_id,
                    'code' => (string) $coupon->code,
                    'summary' => $this->formatCouponSummary($coupon, $currencySymbol),
                ];
            })
            ->groupBy('store_id')
            ->map(fn (Collection $coupons) => $coupons
                ->map(fn (array $coupon) => [
                    'code' => $coupon['code'],
                    'summary' => $coupon['summary'],
                ])
                ->values()
            );
    }

    private function formatCouponSummary(Coupon $coupon, string $currencySymbol): string
    {
        $parts = [];

        if ($coupon->normalizedDiscountType() === 'percent') {
            $parts[] = __('customer.coupon_summary_percent', [
                'value' => (int) $coupon->discount_value,
            ]);
        } elseif ($coupon->hasDiscount()) {
            $parts[] = __('customer.coupon_summary_fixed', [
                'amount' => $currencySymbol . ' ' . number_format(max((int) $coupon->discount_value, 0)),
            ]);
        }

        if ($coupon->hasBonusPointsReward()) {
            $parts[] = __('customer.coupon_summary_points_reward', [
                'amount' => $currencySymbol . ' ' . number_format(max((int) $coupon->reward_per_amount, 0)),
                'points' => number_format((int) $coupon->reward_points),
            ]);
        }

        $summary = implode(' | ', array_filter($parts));
        if ($summary === '') {
            $summary = __('customer.coupon_summary_fixed', [
                'amount' => $currencySymbol . ' ' . number_format(max((int) $coupon->discount_value, 0)),
            ]);
        }

        $minimum = max((int) $coupon->min_order_amount, 0);
        if ($minimum > 0) {
            $summary .= ' | ' . __('customer.coupon_summary_minimum', [
                'amount' => $currencySymbol . ' ' . number_format($minimum),
            ]);
        }

        return $summary;
    }
}
