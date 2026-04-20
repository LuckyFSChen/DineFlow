<?php

namespace App\Support;

use App\Models\CustomerDineInCart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DineInCartStore
{
    private const LEGACY_CART_SESSION_PREFIX = 'dinein_cart.';

    public static function legacyCartSessionKey(int|string $storeId, int|string $tableId): string
    {
        return self::LEGACY_CART_SESSION_PREFIX . $storeId . '.' . $tableId;
    }

    public static function getCart(Request $request, int|string $storeId, int|string $tableId): array
    {
        $legacyCart = $request->session()->get(self::legacyCartSessionKey($storeId, $tableId), []);

        $record = CustomerDineInCart::query()
            ->where('store_id', (int) $storeId)
            ->where('dining_table_id', (int) $tableId)
            ->first();

        if ($record instanceof CustomerDineInCart) {
            return self::normalizeCart(is_array($record->cart_items) ? $record->cart_items : []);
        }

        $normalizedLegacyCart = self::normalizeCart(is_array($legacyCart) ? $legacyCart : []);

        if ($normalizedLegacyCart !== []) {
            CustomerDineInCart::query()->updateOrCreate([
                'store_id' => (int) $storeId,
                'dining_table_id' => (int) $tableId,
            ], [
                'cart_items' => $normalizedLegacyCart,
            ]);
        }

        return $normalizedLegacyCart;
    }

    public static function putCart(Request $request, int|string $storeId, int|string $tableId, array $cart): array
    {
        $normalizedCart = self::normalizeCart($cart);

        DB::transaction(function () use ($storeId, $tableId, $normalizedCart): void {
            $record = CustomerDineInCart::query()
                ->where('store_id', (int) $storeId)
                ->where('dining_table_id', (int) $tableId)
                ->lockForUpdate()
                ->first();

            if ($normalizedCart === []) {
                $record?->delete();

                return;
            }

            if ($record instanceof CustomerDineInCart) {
                $record->update([
                    'cart_items' => $normalizedCart,
                ]);

                return;
            }

            CustomerDineInCart::query()->create([
                'store_id' => (int) $storeId,
                'dining_table_id' => (int) $tableId,
                'cart_items' => $normalizedCart,
            ]);
        });

        if ($normalizedCart === []) {
            $request->session()->forget(self::legacyCartSessionKey($storeId, $tableId));
        } else {
            $request->session()->put(self::legacyCartSessionKey($storeId, $tableId), $normalizedCart);
        }

        return $normalizedCart;
    }

    public static function clearCart(Request $request, int|string $storeId, int|string $tableId): void
    {
        CustomerDineInCart::query()
            ->where('store_id', (int) $storeId)
            ->where('dining_table_id', (int) $tableId)
            ->delete();

        $request->session()->forget(self::legacyCartSessionKey($storeId, $tableId));
    }

    private static function normalizeCart(array $cart): array
    {
        $normalized = [];

        foreach ($cart as $lineKey => $item) {
            if (! is_array($item)) {
                continue;
            }

            $resolvedLineKey = (string) ($item['line_key'] ?? $lineKey);
            $qty = max(0, (int) ($item['qty'] ?? 0));
            if ($qty <= 0) {
                continue;
            }

            $price = (int) ($item['price'] ?? 0);
            $item['line_key'] = $resolvedLineKey;
            $item['qty'] = $qty;
            $item['subtotal'] = $price * $qty;
            $normalized[$resolvedLineKey] = $item;
        }

        return $normalized;
    }
}
