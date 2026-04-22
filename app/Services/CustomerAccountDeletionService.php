<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Order;
use App\Models\User;
use App\Support\PhoneFormatter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerAccountDeletionService
{
    public function delete(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $rawEmail = $this->normalizeEmail($user->getRawOriginal('email'));
            $rawPhone = PhoneFormatter::digitsOnly($user->getRawOriginal('phone'), 32);

            if ($user->isCustomer()) {
                $this->detachCustomerData($rawEmail, $rawPhone);
                $this->archiveUserIdentifiers($user);
            }

            $user->delete();
        });
    }

    private function detachCustomerData(?string $email, ?string $phone): void
    {
        if ($email === null && $phone === null) {
            return;
        }

        $memberQuery = Member::query()->where(function ($query) use ($email, $phone): void {
            if ($email !== null) {
                $query->orWhereRaw('LOWER(email) = ?', [$email]);
            }

            if ($phone !== null) {
                $query->orWhere('phone', $phone);
            }
        });

        $memberQuery->update([
            'name' => null,
            'email' => null,
            'phone' => null,
            'invoice_carrier_code' => null,
            'invoice_carrier_bound_at' => null,
            'updated_at' => now(),
        ]);

        $orderQuery = Order::query()->where(function ($query) use ($email, $phone): void {
            if ($email !== null) {
                $query->orWhereRaw('LOWER(customer_email) = ?', [$email]);
            }

            if ($phone !== null) {
                $query->orWhere('customer_phone', $phone);
            }
        });

        $orderQuery->update([
            'customer_email' => null,
            'customer_phone' => null,
            'updated_at' => now(),
        ]);
    }

    private function archiveUserIdentifiers(User $user): void
    {
        $suffix = (string) $user->getKey();
        $token = Str::lower(Str::random(12));

        $user->forceFill([
            'email' => sprintf('deleted+%s.%s@deleted.local', $suffix, $token),
            'phone' => substr(now()->format('YmdHis') . random_int(100000, 999999) . $suffix, 0, 32),
            'remember_token' => Str::random(60),
            'email_verified_at' => null,
        ]);

        $user->saveQuietly();
    }

    private function normalizeEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $normalized = Str::lower(trim($email));

        return $normalized === '' ? null : $normalized;
    }
}
