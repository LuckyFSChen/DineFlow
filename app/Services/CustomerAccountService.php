<?php

namespace App\Services;

use App\Models\User;
use App\Support\PhoneFormatter;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomerAccountService
{
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
                'password' => Hash::make(Str::password(32)),
                'role' => 'customer',
                'merchant_region' => null,
            ];

            if ($normalizedEmail !== null && ! User::query()->where('email', $normalizedEmail)->exists()) {
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

        if ($user->email === null && $normalizedEmail !== null && ! User::query()->where('email', $normalizedEmail)->whereKeyNot($user->id)->exists()) {
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
