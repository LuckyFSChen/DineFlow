<?php

namespace App\Services;

use App\Exceptions\UberEatsApiException;
use App\Models\Store;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class UberEatsApiClient
{
    public function hasCredentials(Store $store): bool
    {
        return $this->clientId() !== ''
            && $this->clientSecret() !== ''
            && $this->apiBaseUrl() !== ''
            && $this->authUrl() !== '';
    }

    public function verifySignature(Store $store, string $rawBody, ?string $signature): bool
    {
        $signingKey = $this->webhookSigningKey($store);
        $receivedSignature = strtolower(trim((string) $signature));

        if ($signingKey === '' || $receivedSignature === '') {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $rawBody, $signingKey);

        return hash_equals($expectedSignature, $receivedSignature);
    }

    public function fetchOrder(Store $store, string $orderId): array
    {
        $response = $this->authorizedRequest($store)
            ->withHeaders([
                'Accept-Encoding' => 'gzip',
            ])
            ->get($this->apiBaseUrl().'/v2/eats/order/'.rawurlencode($orderId));

        return $this->decodeJsonResponse($response, 'fetch Uber Eats order');
    }

    public function fetchStore(Store $store, string $storeId): array
    {
        $response = $this->authorizedRequest($store)
            ->get($this->apiBaseUrl().'/v1/eats/stores/'.rawurlencode($storeId));

        return $this->decodeJsonResponse($response, 'fetch Uber Eats store');
    }

    public function fetchMenu(Store $store, string $storeId): array
    {
        $response = $this->authorizedRequest($store)
            ->withHeaders([
                'Accept-Encoding' => 'gzip',
            ])
            ->get($this->apiBaseUrl().'/v2/eats/stores/'.rawurlencode($storeId).'/menus');

        return $this->decodeJsonResponse($response, 'fetch Uber Eats menu');
    }

    public function acceptOrder(Store $store, string $orderId, array $payload): void
    {
        $response = $this->authorizedRequest($store)
            ->post($this->apiBaseUrl().'/v1/eats/orders/'.rawurlencode($orderId).'/accept_pos_order', $payload);

        $this->ensureSuccessfulResponse($response, 'accept Uber Eats order');
    }

    public function denyOrder(Store $store, string $orderId, string $code, string $explanation, array $context = []): void
    {
        $payload = [
            'reason' => array_filter([
                'code' => $code,
                'explanation' => $explanation,
                'out_of_stock_items' => $context['out_of_stock_items'] ?? null,
                'invalid_items' => $context['invalid_items'] ?? null,
            ], fn ($value) => $value !== null && $value !== []),
        ];

        $response = $this->authorizedRequest($store)
            ->post($this->apiBaseUrl().'/v1/eats/orders/'.rawurlencode($orderId).'/deny_pos_order', $payload);

        $this->ensureSuccessfulResponse($response, 'deny Uber Eats order');
    }

    public function cancelOrder(Store $store, string $orderId, string $reason = 'OTHER', ?string $details = null): void
    {
        $payload = array_filter([
            'reason' => $reason,
            'details' => $details,
        ], fn ($value) => $value !== null && $value !== '');

        $response = $this->authorizedRequest($store)
            ->post($this->apiBaseUrl().'/v1/eats/orders/'.rawurlencode($orderId).'/cancel', $payload);

        $this->ensureSuccessfulResponse($response, 'cancel Uber Eats order');
    }

    private function authorizedRequest(Store $store): PendingRequest
    {
        if (! $this->hasCredentials($store)) {
            throw new RuntimeException('Uber Eats API credentials are not configured for this store.');
        }

        return Http::timeout(max($this->timeout(), 1))
            ->acceptJson()
            ->withToken($this->accessToken($store));
    }

    private function decodeJsonResponse(Response $response, string $action): array
    {
        $this->ensureSuccessfulResponse($response, $action);

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('Uber Eats API returned an invalid JSON payload while attempting to '.$action.'.');
        }

        return $payload;
    }

    private function ensureSuccessfulResponse(Response $response, string $action): void
    {
        if ($response->successful()) {
            return;
        }

        $body = $response->body();

        throw new UberEatsApiException(
            sprintf('Failed to %s: HTTP %d %s', $action, $response->status(), $body),
            $action,
            $response->status(),
            $body,
        );
    }

    private function accessToken(Store $store): string
    {
        $cacheKey = 'uber_eats_access_token_'.hash('sha256', implode('|', [
            $this->clientId(),
            $this->clientSecret(),
            $this->scopes(),
            $this->authUrl(),
        ]));

        $cachedToken = Cache::get($cacheKey);
        if (is_string($cachedToken) && $cachedToken !== '') {
            return $cachedToken;
        }

        $response = Http::asForm()
            ->timeout(max($this->timeout(), 1))
            ->acceptJson()
            ->post($this->authUrl(), [
                'client_id' => $this->clientId(),
                'client_secret' => $this->clientSecret(),
                'grant_type' => 'client_credentials',
                'scope' => $this->scopes(),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to authenticate with Uber Eats: HTTP '.$response->status().' '.$response->body());
        }

        $token = trim((string) ($response->json('access_token') ?? ''));
        if ($token === '') {
            throw new RuntimeException('Uber Eats OAuth response did not include an access_token.');
        }

        $expiresIn = max((int) ($response->json('expires_in') ?? 3600), 3600);

        Cache::put($cacheKey, $token, now()->addSeconds(max(60, $expiresIn - 300)));

        return $token;
    }

    private function clientId(): string
    {
        return trim((string) config('services.uber_eats.client_id', ''));
    }

    private function clientSecret(): string
    {
        return trim((string) config('services.uber_eats.client_secret', ''));
    }

    private function webhookSigningKey(Store $store): string
    {
        return trim((string) config('services.uber_eats.webhook_signing_key', ''));
    }

    private function apiBaseUrl(): string
    {
        return rtrim((string) config('services.uber_eats.api_base_url', 'https://api.uber.com'), '/');
    }

    private function authUrl(): string
    {
        return trim((string) config('services.uber_eats.auth_url', 'https://auth.uber.com/oauth/v2/token'));
    }

    private function scopes(): string
    {
        return trim((string) config('services.uber_eats.scopes', 'eats.store eats.order eats.store.orders.read'));
    }

    private function timeout(): int
    {
        return (int) config('services.uber_eats.timeout', 15);
    }
}
