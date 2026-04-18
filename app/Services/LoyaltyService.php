<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Member;
use App\Models\MemberPointLedger;
use App\Models\Order;
use App\Models\Store;

class LoyaltyService
{
    public function resolveMember(Store $store, ?string $name, ?string $email, ?string $phone): ?Member
    {
        $normalizedEmail = $this->normalizeText($email);
        $normalizedPhone = $this->normalizeText($phone);
        $normalizedName = $this->normalizeText($name);

        if ($normalizedEmail === null && $normalizedPhone === null) {
            return null;
        }

        $member = Member::query()
            ->where('store_id', $store->id)
            ->where(function ($query) use ($normalizedEmail, $normalizedPhone) {
                if ($normalizedEmail !== null) {
                    $query->orWhere('email', $normalizedEmail);
                }
                if ($normalizedPhone !== null) {
                    $query->orWhere('phone', $normalizedPhone);
                }
            })
            ->first();

        if (! $member) {
            $member = Member::query()->create([
                'store_id' => $store->id,
                'name' => $normalizedName,
                'email' => $normalizedEmail,
                'phone' => $normalizedPhone,
            ]);

            return $member;
        }

        if ($member->name === null && $normalizedName !== null) {
            $member->name = $normalizedName;
        }
        if ($member->email === null && $normalizedEmail !== null) {
            $member->email = $normalizedEmail;
        }
        if ($member->phone === null && $normalizedPhone !== null) {
            $member->phone = $normalizedPhone;
        }
        $member->save();

        return $member;
    }

    public function resolveCoupon(Store $store, ?string $couponCode, int $subtotal, ?Member $member): array
    {
        $code = strtoupper((string) $this->normalizeText($couponCode));
        if ($code === '') {
            return [
                'coupon' => null,
                'discount' => 0,
                'points_cost' => 0,
                'bonus_points' => 0,
                'error' => null,
            ];
        }

        $coupon = Coupon::query()
            ->where('store_id', $store->id)
            ->where('code', $code)
            ->first();

        if (! $coupon) {
            return ['coupon' => null, 'discount' => 0, 'points_cost' => 0, 'bonus_points' => 0, 'error' => __('customer.coupon_not_found')];
        }

        if (! $coupon->isCurrentlyValid()) {
            return ['coupon' => null, 'discount' => 0, 'points_cost' => 0, 'bonus_points' => 0, 'error' => __('customer.coupon_unavailable')];
        }

        if ($subtotal < (int) $coupon->min_order_amount) {
            return ['coupon' => null, 'discount' => 0, 'points_cost' => 0, 'bonus_points' => 0, 'error' => __('customer.coupon_min_order_not_met')];
        }

        $pointsCost = max((int) $coupon->points_cost, 0);
        if ($pointsCost > 0) {
            if (! $member) {
                return ['coupon' => null, 'discount' => 0, 'points_cost' => 0, 'bonus_points' => 0, 'error' => __('customer.coupon_member_required')];
            }

            if ((int) $member->points_balance < $pointsCost) {
                return ['coupon' => null, 'discount' => 0, 'points_cost' => 0, 'bonus_points' => 0, 'error' => __('customer.coupon_points_insufficient')];
            }
        }

        return [
            'coupon' => $coupon,
            'discount' => $coupon->calculateDiscountAmount($subtotal),
            'points_cost' => $pointsCost,
            'bonus_points' => $coupon->calculateBonusPoints($subtotal),
            'error' => null,
        ];
    }

    public function finalizeOrderLoyalty(Order $order, ?Member $member, ?Coupon $coupon, int $pointsUsed, int $pointsEarned): void
    {
        if (! $member) {
            return;
        }

        $pointsUsed = max($pointsUsed, 0);
        $pointsEarned = max($pointsEarned, 0);
        $balance = max((int) $member->points_balance - $pointsUsed, 0);

        if ($pointsUsed > 0) {
            MemberPointLedger::query()->create([
                'store_id' => $member->store_id,
                'member_id' => $member->id,
                'order_id' => $order->id,
                'points_change' => -$pointsUsed,
                'balance_after' => $balance,
                'type' => 'coupon_redeem',
                'note' => $coupon ? '使用優惠券 ' . $coupon->code : '點數折抵',
            ]);
        }

        if ($pointsEarned > 0) {
            $balance += $pointsEarned;
            MemberPointLedger::query()->create([
                'store_id' => $member->store_id,
                'member_id' => $member->id,
                'order_id' => $order->id,
                'points_change' => $pointsEarned,
                'balance_after' => $balance,
                'type' => 'order_reward',
                'note' => '消費回饋點數',
            ]);
        }

        $member->points_balance = $balance;
        $member->total_orders = (int) $member->total_orders + 1;
        $member->total_spent = (int) $member->total_spent + (int) $order->total;
        $member->last_order_at = $order->created_at ?? now();
        $member->save();
    }

    public function reverseOrderLoyaltyOnCancellation(Order $order): void
    {
        $memberId = (int) $order->member_id;
        if ($memberId <= 0) {
            return;
        }

        $pointsUsed = max((int) $order->points_used, 0);
        $pointsEarned = max((int) $order->points_earned, 0);
        if ($pointsUsed === 0 && $pointsEarned === 0) {
            return;
        }

        $member = Member::query()->lockForUpdate()->find($memberId);
        if (! $member) {
            return;
        }

        $alreadyReversed = MemberPointLedger::query()
            ->where('order_id', $order->id)
            ->where('member_id', $member->id)
            ->whereIn('type', ['coupon_redeem_refund', 'order_reward_reversal'])
            ->exists();

        if ($alreadyReversed) {
            return;
        }

        $balance = (int) $member->points_balance;

        if ($pointsEarned > 0) {
            $balance = max($balance - $pointsEarned, 0);
            MemberPointLedger::query()->create([
                'store_id' => $member->store_id,
                'member_id' => $member->id,
                'order_id' => $order->id,
                'points_change' => -$pointsEarned,
                'balance_after' => $balance,
                'type' => 'order_reward_reversal',
                'note' => '訂單取消，回收回饋點數',
            ]);
        }

        if ($pointsUsed > 0) {
            $balance += $pointsUsed;
            MemberPointLedger::query()->create([
                'store_id' => $member->store_id,
                'member_id' => $member->id,
                'order_id' => $order->id,
                'points_change' => $pointsUsed,
                'balance_after' => $balance,
                'type' => 'coupon_redeem_refund',
                'note' => '訂單取消，退還折抵點數',
            ]);
        }

        $member->points_balance = $balance;
        $member->save();
    }

    private function normalizeText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }
}

