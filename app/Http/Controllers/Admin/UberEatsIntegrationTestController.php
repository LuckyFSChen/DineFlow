<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\UberEatsWebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UberEatsIntegrationTestController extends Controller
{
    private const SANDBOX_API_BASE_URL = 'https://test-api.uber.com';

    private const SANDBOX_AUTH_URL = 'https://sandbox-login.uber.com/oauth/v2/token';

    private const PRODUCTION_API_BASE_URL = 'https://api.uber.com';

    private const PRODUCTION_AUTH_URL = 'https://auth.uber.com/oauth/v2/token';

    private const POS_PROVISIONING_SCOPE = 'eats.pos_provisioning';

    public function index(Request $request): View
    {
        $stores = Store::query()
            ->whereNotNull('uber_eats_store_id')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'uber_eats_enabled', 'uber_eats_store_id']);

        $selectedStoreId = (int) $request->query('store_id', (int) optional($stores->first())->id);

        return view('admin.integrations.uber-eats', [
            'mode' => $this->currentMode(),
            'apiBaseUrl' => (string) config('services.uber_eats.api_base_url'),
            'authUrl' => (string) config('services.uber_eats.auth_url'),
            'scopes' => (string) config('services.uber_eats.scopes'),
            'timeout' => (int) config('services.uber_eats.timeout', 15),
            'platformCredentials' => $this->platformCredentialsStatus(),
            'activationCallbackUrl' => route('super-admin.integrations.uber-eats.oauth.callback'),
            'stores' => $stores,
            'selectedStoreId' => $selectedStoreId,
            'latestEvents' => UberEatsWebhookEvent::query()
                ->latest()
                ->take(10)
                ->get(['event_id', 'event_type', 'uber_store_id', 'uber_order_id', 'local_store_id', 'status', 'error_message', 'created_at', 'processed_at']),
            'queueStats' => [
                'uber_eats_jobs' => DB::table('jobs')->where('queue', 'uber-eats')->count(),
                'failed_jobs' => DB::table('failed_jobs')->count(),
            ],
            'activationResult' => session('uber_eats_activation_result'),
            'testResult' => session('uber_eats_test_result'),
        ]);
    }

    public function switchMode(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'mode' => ['required', 'in:sandbox,production'],
        ]);

        $mode = (string) $data['mode'];
        $apiBaseUrl = $mode === 'sandbox' ? self::SANDBOX_API_BASE_URL : self::PRODUCTION_API_BASE_URL;
        $authUrl = $mode === 'sandbox' ? self::SANDBOX_AUTH_URL : self::PRODUCTION_AUTH_URL;

        $this->writeEnvValues([
            'UBER_EATS_API_BASE_URL' => $apiBaseUrl,
            'UBER_EATS_AUTH_URL' => $authUrl,
        ]);

        config([
            'services.uber_eats.api_base_url' => $apiBaseUrl,
            'services.uber_eats.auth_url' => $authUrl,
        ]);

        Artisan::call('config:clear');

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => __('uber_eats.env_switched', ['mode' => $mode]),
                'mode' => $mode,
                'api_base_url' => $apiBaseUrl,
                'auth_url' => $authUrl,
                'scopes' => (string) config('services.uber_eats.scopes'),
            ]);
        }

        return redirect()
            ->route('super-admin.integrations.uber-eats.index')
            ->with('success', __('uber_eats.env_switched', ['mode' => $mode]));
    }

    public function updateCredentials(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'client_id' => ['required', 'string', 'max:255'],
            'client_secret' => ['nullable', 'string', 'max:2000'],
            'webhook_signing_key' => ['nullable', 'string', 'max:2000'],
            'scopes' => ['required', 'string', 'max:500'],
        ]);

        $values = [
            'UBER_EATS_CLIENT_ID' => trim((string) $data['client_id']),
            'UBER_EATS_SCOPES' => trim((string) $data['scopes']),
        ];

        if (trim((string) ($data['client_secret'] ?? '')) !== '') {
            $values['UBER_EATS_CLIENT_SECRET'] = trim((string) $data['client_secret']);
        }

        if (trim((string) ($data['webhook_signing_key'] ?? '')) !== '') {
            $values['UBER_EATS_WEBHOOK_SIGNING_KEY'] = trim((string) $data['webhook_signing_key']);
        }

        $this->writeEnvValues($values);
        Artisan::call('config:clear');

        return redirect()
            ->route('super-admin.integrations.uber-eats.index')
            ->with('success', __('uber_eats.platform_credentials_saved'));
    }

    public function test(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
        ]);

        $store = Store::query()->findOrFail((int) $data['store_id']);
        $result = $this->runConnectionTest($store);

        return redirect()
            ->route('super-admin.integrations.uber-eats.index', ['store_id' => $store->id])
            ->with('uber_eats_test_result', $result);
    }

    public function redirectForActivation(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
        ]);

        $store = Store::query()->findOrFail((int) $data['store_id']);
        if (trim((string) $store->uber_eats_store_id) === '') {
            return redirect()
                ->route('super-admin.integrations.uber-eats.index', ['store_id' => $store->id])
                ->withErrors(['store_id' => __('uber_eats.activation_store_id_required')]);
        }

        if (! $this->hasPlatformOAuthCredentials()) {
            return redirect()
                ->route('super-admin.integrations.uber-eats.index', ['store_id' => $store->id])
                ->withErrors(['credentials' => __('uber_eats.activation_credentials_required')]);
        }

        $state = Str::random(48);
        $request->session()->put('uber_eats_activation_state', [
            'state' => $state,
            'store_id' => $store->id,
        ]);

        $authorizationUrl = $this->authorizationUrl([
            'client_id' => (string) config('services.uber_eats.client_id'),
            'response_type' => 'code',
            'redirect_uri' => route('super-admin.integrations.uber-eats.oauth.callback'),
            'scope' => self::POS_PROVISIONING_SCOPE,
            'state' => $state,
        ]);

        return redirect()->away($authorizationUrl);
    }

    public function handleActivationCallback(Request $request): RedirectResponse
    {
        $sessionState = $request->session()->pull('uber_eats_activation_state');
        $storeId = (int) ($sessionState['store_id'] ?? 0);
        $redirect = redirect()->route('super-admin.integrations.uber-eats.index', ['store_id' => $storeId]);

        if (! is_array($sessionState) || ! hash_equals((string) ($sessionState['state'] ?? ''), (string) $request->query('state', ''))) {
            return $redirect->withErrors(['oauth' => __('uber_eats.activation_state_invalid')]);
        }

        if ($request->query('error')) {
            return $redirect->withErrors([
                'oauth' => trim((string) $request->query('error_description', $request->query('error'))),
            ]);
        }

        $code = trim((string) $request->query('code', ''));
        if ($code === '') {
            return $redirect->withErrors(['oauth' => __('uber_eats.activation_code_missing')]);
        }

        $store = Store::query()->findOrFail($storeId);
        $result = $this->activateStoreIntegration($store, $code);

        return redirect()
            ->route('super-admin.integrations.uber-eats.index', ['store_id' => $store->id])
            ->with('uber_eats_activation_result', $result)
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    private function runConnectionTest(Store $store): array
    {
        $startedAt = now();
        $steps = [];
        $token = null;

        $steps[] = [
            'name' => __('uber_eats.local_credentials'),
            'ok' => $store->hasUberEatsIntegration(),
            'message' => $store->hasUberEatsIntegration()
                ? __('uber_eats.local_credentials_ok')
                : __('uber_eats.local_credentials_missing'),
        ];

        if (! $store->hasUberEatsIntegration()) {
            return $this->testSummary($store, $startedAt, $steps);
        }

        try {
            $response = Http::asForm()
                ->timeout(max((int) config('services.uber_eats.timeout', 15), 1))
                ->acceptJson()
                ->post((string) config('services.uber_eats.auth_url'), [
                    'client_id' => (string) config('services.uber_eats.client_id'),
                    'client_secret' => (string) config('services.uber_eats.client_secret'),
                    'grant_type' => 'client_credentials',
                    'scope' => (string) config('services.uber_eats.scopes'),
                ]);

            $token = trim((string) ($response->json('access_token') ?? ''));
            $steps[] = [
                'name' => __('uber_eats.oauth_token'),
                'ok' => $response->successful() && $token !== '',
                'message' => $response->successful()
                    ? __('uber_eats.token_received_scope', ['scope' => (string) ($response->json('scope') ?? '')])
                    : 'HTTP '.$response->status().' '.$response->body(),
            ];
        } catch (\Throwable $e) {
            $steps[] = [
                'name' => __('uber_eats.oauth_token'),
                'ok' => false,
                'message' => $e->getMessage(),
            ];
        }

        if ($token === '') {
            return $this->testSummary($store, $startedAt, $steps);
        }

        $apiBaseUrl = rtrim((string) config('services.uber_eats.api_base_url'), '/');
        $storeId = (string) $store->uber_eats_store_id;

        foreach ([
            __('uber_eats.accessible_stores') => $apiBaseUrl.'/v1/eats/stores',
            __('uber_eats.fetch_store') => $apiBaseUrl.'/v1/eats/stores/'.rawurlencode($storeId),
            __('uber_eats.fetch_store_menu') => $apiBaseUrl.'/v2/eats/stores/'.rawurlencode($storeId).'/menus',
        ] as $name => $url) {
            try {
                $response = Http::timeout(max((int) config('services.uber_eats.timeout', 15), 1))
                    ->acceptJson()
                    ->withToken($token)
                    ->get($url);

                $body = $response->body();
                $steps[] = [
                    'name' => $name,
                    'ok' => $response->successful(),
                    'message' => $response->successful()
                        ? $this->summarizeApiBody($body)
                        : 'HTTP '.$response->status().' '.$body,
                ];
            } catch (\Throwable $e) {
                $steps[] = [
                    'name' => $name,
                    'ok' => false,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $this->testSummary($store, $startedAt, $steps);
    }

    private function activateStoreIntegration(Store $store, string $authorizationCode): array
    {
        $steps = [];
        $token = '';

        try {
            $response = Http::asForm()
                ->timeout(max((int) config('services.uber_eats.timeout', 15), 1))
                ->acceptJson()
                ->post((string) config('services.uber_eats.auth_url'), [
                    'client_id' => (string) config('services.uber_eats.client_id'),
                    'client_secret' => (string) config('services.uber_eats.client_secret'),
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => route('super-admin.integrations.uber-eats.oauth.callback'),
                    'code' => $authorizationCode,
                ]);

            $token = trim((string) ($response->json('access_token') ?? ''));
            $steps[] = [
                'name' => __('uber_eats.activation_exchange_code'),
                'ok' => $response->successful() && $token !== '',
                'message' => $response->successful()
                    ? __('uber_eats.token_received_scope', ['scope' => (string) ($response->json('scope') ?? '')])
                    : 'HTTP '.$response->status().' '.$response->body(),
            ];
        } catch (\Throwable $e) {
            $steps[] = [
                'name' => __('uber_eats.activation_exchange_code'),
                'ok' => false,
                'message' => $e->getMessage(),
            ];
        }

        if ($token === '') {
            return $this->activationSummary($store, false, __('uber_eats.activation_failed'), $steps);
        }

        $apiBaseUrl = rtrim((string) config('services.uber_eats.api_base_url'), '/');
        $storeUuid = (string) $store->uber_eats_store_id;

        try {
            $response = Http::timeout(max((int) config('services.uber_eats.timeout', 15), 1))
                ->acceptJson()
                ->withToken($token)
                ->get($apiBaseUrl.'/v1/eats/stores');

            $steps[] = [
                'name' => __('uber_eats.activation_fetch_authorized_stores'),
                'ok' => $response->successful(),
                'message' => $response->successful()
                    ? $this->summarizeApiBody($response->body())
                    : 'HTTP '.$response->status().' '.$response->body(),
            ];
        } catch (\Throwable $e) {
            $steps[] = [
                'name' => __('uber_eats.activation_fetch_authorized_stores'),
                'ok' => false,
                'message' => $e->getMessage(),
            ];
        }

        try {
            $payload = [
                'integration_enabled' => true,
                'integrator_store_id' => 'dineflow-store-'.$store->id,
                'store_configuration_data' => json_encode([
                    'dineflow_store_id' => $store->id,
                    'environment' => $this->currentMode(),
                ], JSON_THROW_ON_ERROR),
            ];

            $response = Http::timeout(max((int) config('services.uber_eats.timeout', 15), 1))
                ->acceptJson()
                ->asJson()
                ->withToken($token)
                ->post($apiBaseUrl.'/v1/eats/stores/'.rawurlencode($storeUuid).'/pos_data', $payload);

            $ok = $response->successful();
            $steps[] = [
                'name' => __('uber_eats.activation_post_pos_data'),
                'ok' => $ok,
                'message' => $ok
                    ? __('uber_eats.activation_post_pos_data_ok')
                    : 'HTTP '.$response->status().' '.$response->body(),
            ];

            if ($ok) {
                $store->forceFill(['uber_eats_enabled' => true])->save();
            }

            return $this->activationSummary(
                $store,
                $ok,
                $ok ? __('uber_eats.activation_success') : __('uber_eats.activation_failed'),
                $steps
            );
        } catch (\Throwable $e) {
            $steps[] = [
                'name' => __('uber_eats.activation_post_pos_data'),
                'ok' => false,
                'message' => $e->getMessage(),
            ];
        }

        return $this->activationSummary($store, false, __('uber_eats.activation_failed'), $steps);
    }

    private function activationSummary(Store $store, bool $ok, string $message, array $steps): array
    {
        return [
            'ok' => $ok,
            'message' => $message,
            'store' => $store->name.' (#'.$store->id.')',
            'store_uuid' => (string) $store->uber_eats_store_id,
            'mode' => $this->currentMode(),
            'finished_at' => now()->toDateTimeString(),
            'steps' => $steps,
        ];
    }

    private function testSummary(Store $store, \Illuminate\Support\Carbon $startedAt, array $steps): array
    {
        return [
            'store' => $store->name.' (#'.$store->id.')',
            'mode' => $this->currentMode(),
            'api_base_url' => (string) config('services.uber_eats.api_base_url'),
            'auth_url' => (string) config('services.uber_eats.auth_url'),
            'started_at' => $startedAt->toDateTimeString(),
            'finished_at' => now()->toDateTimeString(),
            'ok' => collect($steps)->every(fn (array $step): bool => (bool) ($step['ok'] ?? false)),
            'steps' => $steps,
        ];
    }

    private function currentMode(): string
    {
        $apiBaseUrl = (string) config('services.uber_eats.api_base_url');
        $authUrl = (string) config('services.uber_eats.auth_url');

        return str_contains($apiBaseUrl, 'test-api.uber.com') || str_contains($authUrl, 'sandbox-login.uber.com')
            ? 'sandbox'
            : 'production';
    }

    private function platformCredentialsStatus(): array
    {
        return [
            'client_id' => (string) config('services.uber_eats.client_id', ''),
            'has_client_secret' => trim((string) config('services.uber_eats.client_secret', '')) !== '',
            'has_webhook_signing_key' => trim((string) config('services.uber_eats.webhook_signing_key', '')) !== '',
        ];
    }

    private function hasPlatformOAuthCredentials(): bool
    {
        return trim((string) config('services.uber_eats.client_id', '')) !== ''
            && trim((string) config('services.uber_eats.client_secret', '')) !== '';
    }

    private function authorizationUrl(array $query): string
    {
        $authUrl = (string) config('services.uber_eats.auth_url');
        $authorizeUrl = preg_replace('#/token$#', '/authorize', $authUrl) ?: $authUrl;

        return $authorizeUrl.'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    private function summarizeApiBody(string $body): string
    {
        $payload = json_decode($body, true);
        if (is_array($payload)) {
            if (isset($payload['stores']) && is_array($payload['stores'])) {
                return __('uber_eats.accessible_stores_count', ['count' => count($payload['stores'])]);
            }

            return __('uber_eats.response_keys', ['keys' => implode(', ', array_slice(array_keys($payload), 0, 8))]);
        }

        return mb_substr($body, 0, 500);
    }

    /**
     * @param array<string, string> $values
     */
    private function writeEnvValues(array $values): void
    {
        $path = base_path('.env');
        $contents = is_file($path) ? (string) file_get_contents($path) : '';

        foreach ($values as $key => $value) {
            $line = $key.'='.$this->formatEnvValue($value);

            if (preg_match('/^'.preg_quote($key, '/').'=.*/m', $contents) === 1) {
                $contents = preg_replace('/^'.preg_quote($key, '/').'=.*/m', $line, $contents) ?? $contents;
            } else {
                $contents = rtrim($contents).PHP_EOL.$line.PHP_EOL;
            }
        }

        file_put_contents($path, $contents);
    }

    private function formatEnvValue(string $value): string
    {
        if ($value !== '' && preg_match('/^[A-Za-z0-9_.:\/@+-]+$/', $value) === 1) {
            return $value;
        }

        $escaped = str_replace(
            ["\\", '"', '$', "\r", "\n"],
            ["\\\\", '\"', '\$', '\r', '\n'],
            $value
        );

        return '"'.$escaped.'"';
    }
}
