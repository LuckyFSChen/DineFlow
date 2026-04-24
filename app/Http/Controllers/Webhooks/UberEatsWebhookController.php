<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\UberEatsWebhookEvent;
use App\Services\UberEatsOrderSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class UberEatsWebhookController extends Controller
{
    public function __invoke(Request $request, UberEatsOrderSyncService $syncService)
    {
        $rawBody = $request->getContent();

        $payload = json_decode($rawBody, true);
        if (! is_array($payload)) {
            return response('Invalid JSON payload.', 422);
        }

        $store = $this->resolveLocalStore($payload);
        if (! $store || ! $store->hasUberEatsApiCredentials()) {
            return response('Invalid Uber Eats signature.', 401);
        }

        if (! $syncService->verifySignature($store, $rawBody, $request->header('X-Uber-Signature'))) {
            return response('Invalid Uber Eats signature.', 401);
        }

        $eventId = $this->resolveEventId($payload);
        if ($eventId === null) {
            return response('Missing Uber Eats event id.', 422);
        }

        $event = UberEatsWebhookEvent::query()->firstOrNew([
            'event_id' => $eventId,
        ]);

        if ($event->exists && $event->processed_at !== null && in_array($event->status, ['processed', 'ignored'], true)) {
            return response('', 200);
        }

        $event->fill([
            'event_type' => (string) ($this->resolveEventType($payload) ?? 'unknown'),
            'uber_store_id' => $this->resolveStoreId($payload),
            'uber_order_id' => $this->resolveOrderId($payload),
            'local_store_id' => $store->id,
            'status' => 'received',
            'error_message' => null,
            'payload' => $payload,
        ]);
        $event->save();

        try {
            $result = $syncService->process($event, $store);

            $event->fill([
                'status' => (string) ($result['status'] ?? 'processed'),
                'local_store_id' => $result['local_store_id'] ?? $event->local_store_id,
                'error_message' => $result['message'] ?? null,
                'processed_at' => Carbon::now(),
            ]);
            $event->save();

            return response('', 200);
        } catch (\Throwable $e) {
            $event->fill([
                'status' => 'failed',
                'error_message' => Str::limit($e->getMessage(), 1000),
            ]);
            $event->save();

            report($e);

            return response('Webhook processing failed.', 500);
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveEventId(array $payload): ?string
    {
        $eventId = trim((string) (
            Arr::get($payload, 'event_id')
            ?? Arr::get($payload, 'webhook_meta.webhook_msg_uuid')
            ?? ''
        ));

        return $eventId !== '' ? $eventId : null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveEventType(array $payload): ?string
    {
        $eventType = trim((string) Arr::get($payload, 'event_type', ''));

        return $eventType !== '' ? $eventType : null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveStoreId(array $payload): ?string
    {
        $storeId = trim((string) (
            Arr::get($payload, 'meta.user_id')
            ?? Arr::get($payload, 'store_id')
            ?? ''
        ));

        return $storeId !== '' ? $storeId : null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveOrderId(array $payload): ?string
    {
        $orderId = trim((string) (
            Arr::get($payload, 'meta.resource_id')
            ?? Arr::get($payload, 'resource_id')
            ?? ''
        ));

        return $orderId !== '' ? $orderId : null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveLocalStore(array $payload): ?Store
    {
        $uberStoreId = $this->resolveStoreId($payload);
        if ($uberStoreId === null) {
            return null;
        }

        return Store::query()
            ->where('uber_eats_store_id', $uberStoreId)
            ->first();
    }
}
