<?php

namespace App\Support;

class PhoneFormatter
{
    public static function digitsOnly(?string $value, ?int $maxDigits = null): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', trim($value));
        if (! is_string($digits) || $digits === '') {
            return null;
        }

        if ($maxDigits !== null && $maxDigits > 0) {
            return substr($digits, 0, $maxDigits);
        }

        return $digits;
    }

    public static function format(?string $value): ?string
    {
        $digits = self::digitsOnly($value);
        if ($digits === null) {
            return null;
        }

        $length = strlen($digits);

        if ($length <= 4) {
            return $digits;
        }

        if ($length === 11) {
            return sprintf('%s-%s-%s', substr($digits, 0, 3), substr($digits, 3, 4), substr($digits, 7));
        }

        if ($length === 10) {
            if (str_starts_with($digits, '09')) {
                return sprintf('%s-%s-%s', substr($digits, 0, 4), substr($digits, 4, 3), substr($digits, 7));
            }

            return sprintf('%s-%s-%s', substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6));
        }

        if ($length <= 7) {
            return sprintf('%s-%s', substr($digits, 0, 4), substr($digits, 4));
        }

        return sprintf('%s-%s-%s', substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6));
    }
}

