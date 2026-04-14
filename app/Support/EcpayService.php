<?php

namespace App\Support;

class EcpayService
{
    public static function generateCheckMacValue(array $parameters, string $hashKey, string $hashIv): string
    {
        unset($parameters['CheckMacValue']);

        ksort($parameters);

        $query = urldecode(http_build_query($parameters));
        $raw = "HashKey={$hashKey}&{$query}&HashIV={$hashIv}";

        $encoded = urlencode($raw);
        $encoded = strtolower($encoded);

        $encoded = str_replace(
            ['%2d', '%5f', '%2e', '%21', '%2a', '%28', '%29'],
            ['-', '_', '.', '!', '*', '(', ')'],
            $encoded
        );

        return strtoupper(hash('sha256', $encoded));
    }

    public static function verifyCheckMacValue(array $parameters, string $hashKey, string $hashIv): bool
    {
        $received = (string) ($parameters['CheckMacValue'] ?? '');
        if ($received === '') {
            return false;
        }

        $calculated = self::generateCheckMacValue($parameters, $hashKey, $hashIv);

        return hash_equals($calculated, $received);
    }
}
