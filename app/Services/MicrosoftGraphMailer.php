<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MicrosoftGraphMailer
{
    public function mode(): string
    {
        if (! $this->isEnabled()) {
            return 'disabled';
        }

        $authMode = $this->authMode();

        if ($authMode === 'app-only') {
            return $this->isAppOnlyConfigured() ? 'app-only' : 'invalid';
        }

        if ($authMode === 'device-code') {
            return $this->isDeviceCodeConfigured() ? 'device-code' : 'invalid';
        }

        if ($this->isAppOnlyConfigured()) {
            return 'app-only';
        }

        if ($this->isDeviceCodeConfigured()) {
            return 'device-code';
        }

        return 'invalid';
    }

    public function isConfigured(): bool
    {
        return in_array($this->mode(), ['app-only', 'device-code'], true);
    }

    /**
     * @param callable(string):void|null $notify
     */
    public function sendText(string $to, string $subject, string $body, bool $saveToSentItems = true, ?callable $notify = null): void
    {
        $this->sendMessage([$to], $subject, $body, false, [], [], $saveToSentItems, $notify);
    }

    /**
     * @param array<int, string> $to
     * @param array<int, string> $cc
     * @param array<int, string> $bcc
     * @param callable(string):void|null $notify
     */
    public function sendMessage(
        array $to,
        string $subject,
        string $body,
        bool $isHtml = false,
        array $cc = [],
        array $bcc = [],
        bool $saveToSentItems = true,
        ?callable $notify = null,
    ): void {
        if ($to === []) {
            throw new RuntimeException('Graph message requires at least one recipient.');
        }

        $message = [
            'subject' => $subject,
            'body' => [
                'contentType' => $isHtml ? 'HTML' : 'Text',
                'content' => $body,
            ],
            'toRecipients' => $this->toRecipients($to),
        ];

        if ($cc !== []) {
            $message['ccRecipients'] = $this->toRecipients($cc);
        }

        if ($bcc !== []) {
            $message['bccRecipients'] = $this->toRecipients($bcc);
        }

        $payload = [
            'message' => $message,
            'saveToSentItems' => $saveToSentItems,
        ];

        $mode = $this->mode();
        if ($mode === 'app-only') {
            $response = Http::withToken($this->appOnlyAccessToken())
                ->acceptJson()
                ->post($this->graphBaseUrl().'/v1.0/users/'.rawurlencode($this->sender()).'/sendMail', $payload);

            if (! $response->successful()) {
                throw new RuntimeException('Graph sendMail failed (app-only): HTTP '.$response->status().' '.$response->body());
            }

            return;
        }

        if ($mode === 'device-code') {
            $response = Http::withToken($this->deviceCodeAccessToken($notify))
                ->acceptJson()
                ->post($this->graphBaseUrl().'/v1.0/me/sendMail', $payload);

            if (! $response->successful()) {
                throw new RuntimeException('Graph sendMail failed (device-code): HTTP '.$response->status().' '.$response->body());
            }

            return;
        }

        throw new RuntimeException($this->configurationErrorMessage());
    }

    /**
     * @param array<int, string> $emails
     * @return array<int, array<string, array<string, string>>>
     */
    private function toRecipients(array $emails): array
    {
        return array_values(array_map(static function (string $email): array {
            return ['emailAddress' => ['address' => $email]];
        }, array_filter(array_map('trim', $emails), static fn (string $email): bool => $email !== '')));
    }

    private function isEnabled(): bool
    {
        return (bool) config('services.microsoft_graph.enabled');
    }

    private function authMode(): string
    {
        $mode = strtolower(trim((string) config('services.microsoft_graph.auth_mode', 'auto')));

        return in_array($mode, ['auto', 'app-only', 'device-code'], true)
            ? $mode
            : 'auto';
    }

    private function configurationErrorMessage(): string
    {
        $mode = $this->authMode();

        if ($mode === 'app-only') {
            return 'Microsoft Graph app-only 未設定完成。請確認 MS_GRAPH_TENANT_ID / MS_GRAPH_CLIENT_ID / MS_GRAPH_CLIENT_SECRET / MS_GRAPH_SENDER。';
        }

        if ($mode === 'device-code') {
            return 'Microsoft Graph device-code 未設定完成。請確認 MS_GRAPH_TENANT_ID / MS_GRAPH_CLIENT_ID / GRAPH_USER_SCOPES。';
        }

        return 'Microsoft Graph mail 未設定完成。請確認 app-only 或 device-code 的必要參數。';
    }

    private function isAppOnlyConfigured(): bool
    {
        return $this->tenantId() !== ''
            && $this->clientId() !== ''
            && $this->clientSecret() !== ''
            && $this->sender() !== '';
    }

    private function isDeviceCodeConfigured(): bool
    {
        return $this->tenantId() !== ''
            && $this->clientId() !== ''
            && $this->graphUserScopes() !== '';
    }

    private function appOnlyAccessToken(): string
    {
        $cacheKey = 'microsoft_graph_mail_access_token_app_'.md5($this->tenantId().'|'.$this->clientId());

        return Cache::remember($cacheKey, now()->addMinutes(50), function (): string {
            $response = Http::asForm()
                ->acceptJson()
                ->post('https://login.microsoftonline.com/'.$this->tenantId().'/oauth2/v2.0/token', [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->clientId(),
                    'client_secret' => $this->clientSecret(),
                    'scope' => 'https://graph.microsoft.com/.default',
                ]);

            if (! $response->successful()) {
                throw new RuntimeException('Graph token request failed (app-only): HTTP '.$response->status().' '.$response->body());
            }

            $token = (string) ($response->json('access_token') ?? '');
            if ($token === '') {
                throw new RuntimeException('Graph token response missing access_token (app-only).');
            }

            return $token;
        });
    }

    /**
     * @param callable(string):void|null $notify
     */
    private function deviceCodeAccessToken(?callable $notify = null): string
    {
        $cacheKey = 'microsoft_graph_mail_access_token_device_'.md5($this->tenantId().'|'.$this->clientId().'|'.$this->graphUserScopes());
        $cachedToken = Cache::get($cacheKey);
        if (is_string($cachedToken) && $cachedToken !== '') {
            return $cachedToken;
        }

        $deviceCodeResponse = Http::asForm()
            ->acceptJson()
            ->post('https://login.microsoftonline.com/'.$this->tenantId().'/oauth2/v2.0/devicecode', [
                'client_id' => $this->clientId(),
                'scope' => $this->graphUserScopes(),
            ]);

        if (! $deviceCodeResponse->successful()) {
            $codes = $deviceCodeResponse->json('error_codes');
            if (is_array($codes) && in_array(50059, $codes, true)) {
                throw new RuntimeException('Graph device code request failed: tenant_id 不能用 common。請改為實際 Tenant ID (GUID) 或網域名稱。');
            }

            throw new RuntimeException('Graph device code request failed: HTTP '.$deviceCodeResponse->status().' '.$deviceCodeResponse->body());
        }

        $message = (string) ($deviceCodeResponse->json('message') ?? 'Open browser and complete device login.');
        if ($notify) {
            $notify($message);
        }

        $deviceCode = (string) ($deviceCodeResponse->json('device_code') ?? '');
        $interval = max(2, (int) ($deviceCodeResponse->json('interval') ?? 5));
        $expiresIn = max(60, (int) ($deviceCodeResponse->json('expires_in') ?? 900));

        if ($deviceCode === '') {
            throw new RuntimeException('Graph device code response missing device_code.');
        }

        $deadline = now()->addSeconds($expiresIn)->getTimestamp();
        while (now()->getTimestamp() < $deadline) {
            sleep($interval);

            $tokenResponse = Http::asForm()
                ->acceptJson()
                ->post('https://login.microsoftonline.com/'.$this->tenantId().'/oauth2/v2.0/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:device_code',
                    'client_id' => $this->clientId(),
                    'device_code' => $deviceCode,
                ]);

            if ($tokenResponse->successful()) {
                $token = (string) ($tokenResponse->json('access_token') ?? '');
                if ($token === '') {
                    throw new RuntimeException('Graph token response missing access_token (device-code).');
                }

                $expires = (int) ($tokenResponse->json('expires_in') ?? 3600);
                Cache::put($cacheKey, $token, now()->addSeconds(max(60, $expires - 120)));

                return $token;
            }

            $error = (string) ($tokenResponse->json('error') ?? '');
            if ($error === 'authorization_pending') {
                continue;
            }

            if (in_array($error, ['slow_down', 'authorization_declined', 'bad_verification_code', 'expired_token'], true)) {
                throw new RuntimeException('Graph device token failed: '.$error.' '.$tokenResponse->body());
            }

            throw new RuntimeException('Graph token polling failed: HTTP '.$tokenResponse->status().' '.$tokenResponse->body());
        }

        throw new RuntimeException('Graph device code login timed out.');
    }

    private function graphBaseUrl(): string
    {
        return rtrim((string) config('services.microsoft_graph.base_url', 'https://graph.microsoft.com'), '/');
    }

    private function tenantId(): string
    {
        return trim((string) config('services.microsoft_graph.tenant_id', ''));
    }

    private function clientId(): string
    {
        return trim((string) config('services.microsoft_graph.client_id', ''));
    }

    private function clientSecret(): string
    {
        return trim((string) config('services.microsoft_graph.client_secret', ''));
    }

    private function sender(): string
    {
        return trim((string) config('services.microsoft_graph.sender', ''));
    }

    private function graphUserScopes(): string
    {
        return trim((string) config('services.microsoft_graph.user_scopes', ''));
    }
}
