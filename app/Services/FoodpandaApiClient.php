<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FoodpandaApiClient
{
    public function isConfiguredForStore(Store $store): bool
    {
        return $this->clientId($store) !== ''
            && $this->clientSecret($store) !== ''
            && $this->chainId($store) !== ''
            && $this->webhookSecret($store) !== ''
            && $this->apiBaseUrl() !== ''
            && $this->authUrl() !== '';
    }

    public function webhookSecret(Store $store): string
    {
        return trim((string) ($store->foodpanda_webhook_secret ?? ''));
    }

    public function chainId(Store $store, ?array $payload = null): string
    {
        return trim((string) ($store->foodpanda_chain_id ?? ''));
    }

    public function updateOrderStatus(Store $store, string $orderId, string $status, array $items, ?array $cancellation = null): array
    {
        $chainId = $this->chainId($store);

        if ($chainId === '') {
            throw new RuntimeException('Foodpanda chain ID is not configured for this store.');
        }

        $payload = array_filter([
            'order_id' => $orderId,
            'status' => strtoupper(trim($status)),
            'items' => $items,
            'cancellation' => $cancellation,
        ], fn ($value) => $value !== null);

        $response = $this->authorizedRequest($store)
            ->put($this->apiBaseUrl().'/v2/chains/'.rawurlencode($chainId).'/orders/'.rawurlencode($orderId), $payload);

        return $this->decodeJsonResponse($response, 'update Foodpanda order');
    }

    private function authorizedRequest(Store $store): PendingRequest
    {
        if (! $this->isConfiguredForStore($store)) {
            throw new RuntimeException('Foodpanda API credentials are not configured for this store.');
        }

        return Http::timeout(max($this->timeout(), 1))
            ->acceptJson()
            ->withToken($this->accessToken($store));
    }

    private function decodeJsonResponse(Response $response, string $action): array
    {
        $this->ensureSuccessfulResponse($response, $action);

        $payload = $response->json();

        if ($payload === null || $payload === '') {
            return [];
        }

        if (! is_array($payload)) {
            throw new RuntimeException('Foodpanda API returned an invalid JSON payload while attempting to '.$action.'.');
        }

        return $payload;
    }

    private function ensureSuccessfulResponse(Response $response, string $action): void
    {
        if ($response->successful()) {
            return;
        }

        throw new RuntimeException(
            sprintf(
                'Failed to %s: HTTP %d %s',
                $action,
                $response->status(),
                $response->body()
            )
        );
    }

    private function accessToken(Store $store): string
    {
        $cacheKey = 'foodpanda_access_token_'.md5($this->clientId($store).'|'.$this->clientSecret($store));

        $cachedToken = Cache::get($cacheKey);
        if (is_string($cachedToken) && $cachedToken !== '') {
            return $cachedToken;
        }

        $response = Http::asForm()
            ->timeout(max($this->timeout(), 1))
            ->acceptJson()
            ->post($this->authUrl(), [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId($store),
                'client_secret' => $this->clientSecret($store),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to authenticate with Foodpanda: HTTP '.$response->status().' '.$response->body());
        }

        $token = trim((string) ($response->json('access_token') ?? ''));
        if ($token === '') {
            throw new RuntimeException('Foodpanda OAuth response did not include an access_token.');
        }

        $expiresIn = max((int) ($response->json('expires_in') ?? 7200), 300);

        Cache::put($cacheKey, $token, now()->addSeconds(max(60, $expiresIn - 300)));

        return $token;
    }

    private function clientId(Store $store): string
    {
        return trim((string) ($store->foodpanda_client_id ?? ''));
    }

    private function clientSecret(Store $store): string
    {
        return trim((string) ($store->foodpanda_client_secret ?? ''));
    }

    private function apiBaseUrl(): string
    {
        return rtrim((string) config('services.foodpanda.api_base_url', 'https://foodpanda.partner.deliveryhero.io'), '/');
    }

    private function authUrl(): string
    {
        return trim((string) config('services.foodpanda.auth_url', 'https://foodpanda.partner.deliveryhero.io/v2/oauth/token'));
    }

    private function timeout(): int
    {
        return (int) config('services.foodpanda.timeout', 15);
    }
}
