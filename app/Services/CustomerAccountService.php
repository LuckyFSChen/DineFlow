<?php

namespace App\Services;

use App\Models\User;
use App\Support\PhoneFormatter;
use Illuminate\Support\Facades\Hash;

class CustomerAccountService
{
    public const DEFAULT_FIRST_LOGIN_PASSWORD = '0000';

    public function isPhoneRegistered(?string $phone): bool
    {
        $normalizedPhone = PhoneFormatter::digitsOnly($phone, 32);

        if ($normalizedPhone === null) {
            return false;
        }

        return User::query()->where('phone', $normalizedPhone)->exists();
    }

    public function registerOrUpdateFromOrder(?string $phone, ?string $name, ?string $email): ?User
    {
        $normalizedPhone = PhoneFormatter::digitsOnly($phone, 32);
        if ($normalizedPhone === null) {
            return null;
        }

        $normalizedName = $this->normalizeText($name);
        $normalizedEmail = $this->normalizeText($email);

        $user = User::query()->where('phone', $normalizedPhone)->first();
        if (! $user) {
            $payload = [
                'name' => $normalizedName ?? $normalizedPhone,
                'phone' => $normalizedPhone,
                'password' => Hash::make(self::DEFAULT_FIRST_LOGIN_PASSWORD),
                'must_change_password' => true,
                'role' => 'customer',
                'merchant_region' => null,
            ];

            if ($normalizedEmail !== null) {
                $payload['email'] = $normalizedEmail;
            }

            return User::query()->create($payload);
        }

        if (! $user->isCustomer()) {
            return $user;
        }

        $dirty = false;

        if ($this->normalizeText($user->name) === null && $normalizedName !== null) {
            $user->name = $normalizedName;
            $dirty = true;
        }

        if ($user->email === null && $normalizedEmail !== null) {
            $user->email = $normalizedEmail;
            $dirty = true;
        }

        if ($dirty) {
            $user->save();
        }

        return $user;
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
