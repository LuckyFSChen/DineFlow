<?php

namespace App\Support;

use App\Models\CustomerTakeoutCart;
use App\Models\User;
use Illuminate\Http\Request;

class TakeoutCartSession
{
    private const TOKEN_SESSION_PREFIX = 'takeout_cart_token_';

    private const CART_SESSION_PREFIX = 'takeout_cart.';

    public static function tokenSessionKey(int|string $storeId): string
    {
        return self::TOKEN_SESSION_PREFIX . $storeId;
    }

    public static function cartSessionKey(int|string $storeId, string $token): string
    {
        return self::CART_SESSION_PREFIX . $storeId . '.' . $token;
    }

    public static function userToken(User $user): string
    {
        return 'user_' . $user->getAuthIdentifier();
    }

    public static function currentToken(Request $request, int|string $storeId): string
    {
        $sessionKey = self::tokenSessionKey($storeId);
        $user = $request->user();

        if ($user instanceof User && $user->isCustomer()) {
            $token = $request->session()->get($sessionKey);

            if (! is_string($token) || $token === '') {
                $token = self::userToken($user);
                $request->session()->put($sessionKey, $token);
            }

            return $token;
        }

        $token = $request->session()->get($sessionKey);
        if (! is_string($token) || $token === '') {
            $token = (string) str()->uuid();
            $request->session()->put($sessionKey, $token);
        }

        return $token;
    }

    public static function currentCartSessionKey(Request $request, int|string $storeId): string
    {
        $cartSessionKey = self::cartSessionKey($storeId, self::currentToken($request, $storeId));

        self::restorePersistedCartIfMissing($request, $storeId, $cartSessionKey);

        return $cartSessionKey;
    }

    public static function inheritGuestCarts(Request $request, User $user): void
    {
        if (! $user->isCustomer()) {
            return;
        }

        $userToken = self::userToken($user);
        $session = $request->session();

        foreach (array_keys($session->all()) as $key) {
            if (! str_starts_with($key, self::TOKEN_SESSION_PREFIX)) {
                continue;
            }

            $storeId = substr($key, strlen(self::TOKEN_SESSION_PREFIX));
            if ($storeId === '') {
                continue;
            }

            $currentToken = $session->get($key);
            if (! is_string($currentToken) || $currentToken === '') {
                $session->put($key, $userToken);
                continue;
            }

            if ($currentToken === $userToken) {
                continue;
            }

            $guestCartKey = self::cartSessionKey($storeId, $currentToken);
            $userCartKey = self::cartSessionKey($storeId, $userToken);
            $guestCart = $session->get($guestCartKey, []);
            $persistedUserCart = self::loadPersistedUserCart($user, $storeId);
            $sessionUserCart = $session->get($userCartKey, []);
            $userCart = self::mergeCarts(
                is_array($persistedUserCart) ? $persistedUserCart : [],
                is_array($sessionUserCart) ? $sessionUserCart : [],
            );

            if (is_array($guestCart) || is_array($userCart)) {
                $mergedCart = self::mergeCarts(
                    is_array($userCart) ? $userCart : [],
                    is_array($guestCart) ? $guestCart : [],
                );

                $session->put($userCartKey, $mergedCart);
                self::persistUserCart($user, $storeId, $mergedCart);
            }

            $session->put($key, $userToken);
            $session->forget($guestCartKey);
        }

        self::hydrateSessionFromPersistedCarts($request, $user, $userToken);
    }

    public static function persistCurrentSessionCart(Request $request, int|string $storeId, array $cart): void
    {
        $user = $request->user();
        if (! $user instanceof User || ! $user->isCustomer()) {
            return;
        }

        self::persistUserCart($user, $storeId, $cart);
    }

    public static function clearPersistedCartForCurrentUser(Request $request, int|string $storeId): void
    {
        $user = $request->user();
        if (! $user instanceof User || ! $user->isCustomer()) {
            return;
        }

        CustomerTakeoutCart::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->where('store_id', (int) $storeId)
            ->delete();
    }

    private static function mergeCarts(array $targetCart, array $guestCart): array
    {
        foreach ($guestCart as $lineKey => $item) {
            if (! is_array($item)) {
                continue;
            }

            if (isset($targetCart[$lineKey]) && is_array($targetCart[$lineKey])) {
                $targetCart[$lineKey]['qty'] = (int) ($targetCart[$lineKey]['qty'] ?? 0) + (int) ($item['qty'] ?? 0);
                $targetCart[$lineKey]['subtotal'] = (int) ($targetCart[$lineKey]['price'] ?? $item['price'] ?? 0)
                    * (int) $targetCart[$lineKey]['qty'];
                continue;
            }

            $item['line_key'] = (string) ($item['line_key'] ?? $lineKey);
            $item['qty'] = (int) ($item['qty'] ?? 0);
            $item['subtotal'] = (int) ($item['price'] ?? 0) * (int) $item['qty'];
            $targetCart[$lineKey] = $item;
        }

        foreach ($targetCart as $lineKey => $item) {
            if (! is_array($item)) {
                unset($targetCart[$lineKey]);
                continue;
            }

            $targetCart[$lineKey]['line_key'] = (string) ($item['line_key'] ?? $lineKey);
            $targetCart[$lineKey]['qty'] = (int) ($item['qty'] ?? 0);
            $targetCart[$lineKey]['subtotal'] = (int) ($item['price'] ?? 0) * (int) $targetCart[$lineKey]['qty'];
        }

        return $targetCart;
    }

    private static function restorePersistedCartIfMissing(Request $request, int|string $storeId, string $cartSessionKey): void
    {
        $user = $request->user();
        if (! $user instanceof User || ! $user->isCustomer()) {
            return;
        }

        if ($request->session()->has($cartSessionKey)) {
            return;
        }

        $persistedCart = self::loadPersistedUserCart($user, $storeId);
        if (! is_array($persistedCart)) {
            $persistedCart = [];
        }

        $request->session()->put($cartSessionKey, self::mergeCarts([], $persistedCart));
    }

    private static function loadPersistedUserCart(User $user, int|string $storeId): array
    {
        $record = CustomerTakeoutCart::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->where('store_id', (int) $storeId)
            ->first();

        $cartItems = $record?->cart_items;

        return is_array($cartItems) ? self::mergeCarts([], $cartItems) : [];
    }

    private static function persistUserCart(User $user, int|string $storeId, array $cart): void
    {
        $normalizedCart = self::mergeCarts([], $cart);

        if ($normalizedCart === []) {
            CustomerTakeoutCart::query()
                ->where('user_id', $user->getAuthIdentifier())
                ->where('store_id', (int) $storeId)
                ->delete();

            return;
        }

        CustomerTakeoutCart::query()->updateOrCreate(
            [
                'user_id' => $user->getAuthIdentifier(),
                'store_id' => (int) $storeId,
            ],
            [
                'cart_items' => $normalizedCart,
            ]
        );
    }

    private static function hydrateSessionFromPersistedCarts(Request $request, User $user, string $userToken): void
    {
        $session = $request->session();

        $records = CustomerTakeoutCart::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->get(['store_id', 'cart_items']);

        foreach ($records as $record) {
            $storeId = (string) $record->store_id;
            if ($storeId === '') {
                continue;
            }

            $tokenKey = self::tokenSessionKey($storeId);
            $userCartKey = self::cartSessionKey($storeId, $userToken);

            $session->put($tokenKey, $userToken);

            if ($session->has($userCartKey)) {
                continue;
            }

            $persistedCart = is_array($record->cart_items) ? $record->cart_items : [];
            $session->put($userCartKey, self::mergeCarts([], $persistedCart));
        }
    }
}
