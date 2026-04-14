<?php

namespace App\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class EcpayEcpgService
{
    public function createToken(array $orderData): array
    {
        return $this->callApi((string) config('services.ecpay.ecpg_create_token_url'), $orderData);
    }

    public function createTrade(array $tradeData): array
    {
        return $this->callApi((string) config('services.ecpay.ecpg_create_payment_url'), $tradeData);
    }

    private function callApi(string $url, array $data): array
    {
        $merchantId = (string) config('services.ecpay.merchant_id');
        $hashKey = (string) config('services.ecpay.hash_key');
        $hashIv = (string) config('services.ecpay.hash_iv');

        if ($url === '' || $merchantId === '' || $hashKey === '' || $hashIv === '') {
            return [
                'ok' => false,
                'message' => 'ECPay SDK 2.0 API 參數未設定完整。',
                'raw' => [],
                'data' => [],
            ];
        }

        $requestBody = [
            'MerchantID' => $merchantId,
            'RqHeader' => [
                'Timestamp' => time(),
            ],
            'Data' => $this->encryptData($data, $hashKey, $hashIv),
        ];

        try {
            $response = Http::timeout(20)
                ->acceptJson()
                ->asJson()
                ->post($url, $requestBody);
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'ECPay API 連線失敗：' . $e->getMessage(),
                'raw' => [],
                'data' => [],
            ];
        }

        $json = $response->json();
        if (! is_array($json)) {
            return [
                'ok' => false,
                'message' => 'ECPay API 回應格式非 JSON。',
                'raw' => ['status' => $response->status(), 'body' => $response->body()],
                'data' => [],
            ];
        }

        $decodedData = [];
        $rawData = Arr::get($json, 'Data');

        if (is_array($rawData)) {
            // Some endpoints return Data as plain JSON object when already decoded.
            $decodedData = $rawData;
        } elseif (is_string($rawData) && $rawData !== '') {
            $decodedData = $this->decryptData($rawData, $hashKey, $hashIv);

            // Fallback: if Data is actually plain JSON string instead of encrypted blob.
            if ($decodedData === []) {
                $jsonData = json_decode($rawData, true);
                if (is_array($jsonData)) {
                    $decodedData = $jsonData;
                }
            }
        }

        $transCode = (int) Arr::get($json, 'TransCode', 0);
        $rtnCode = (string) Arr::get($decodedData, 'RtnCode', '');
        $ok = $transCode === 1 && ($rtnCode === '' || $rtnCode === '1');

        return [
            'ok' => $ok,
            'message' => (string) (Arr::get($decodedData, 'RtnMsg') ?: Arr::get($json, 'TransMsg', '')), 
            'raw' => $json,
            'data' => $decodedData,
        ];
    }

    private function encryptData(array $data, string $hashKey, string $hashIv): string
    {
        $json = json_encode($data);
        if ($json === false) {
            return '';
        }

        $urlEncoded = urlencode($json);

        $encrypted = openssl_encrypt($urlEncoded, 'aes-128-cbc', $hashKey, OPENSSL_RAW_DATA, $hashIv);
        if ($encrypted === false) {
            return '';
        }

        return base64_encode($encrypted);
    }

    private function decryptData(string $encryptedData, string $hashKey, string $hashIv): array
    {
        $binary = base64_decode($encryptedData, true);
        if ($binary === false) {
            return [];
        }

        $decrypted = openssl_decrypt($binary, 'aes-128-cbc', $hashKey, OPENSSL_RAW_DATA, $hashIv);
        if ($decrypted === false) {
            return [];
        }

        $urlDecoded = urldecode($decrypted);
        $decoded = json_decode($urlDecoded, true);
        return is_array($decoded) ? $decoded : [];
    }
}
