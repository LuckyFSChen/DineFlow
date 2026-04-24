<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\FoodpandaWebhookEvent;
use App\Services\FoodpandaOrderSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class FoodpandaWebhookController extends Controller
{
    public function __invoke(Request $request, FoodpandaOrderSyncService $syncService)
    {
        $payload = json_decode($request->getContent(), true);

        if (! is_array($payload)) {
            return response('Invalid JSON payload.', 422);
        }

        $eventId = $syncService->buildWebhookEventId($payload);

        $event = FoodpandaWebhookEvent::query()->firstOrNew([
            'event_id' => $eventId,
        ]);

        if ($event->exists && $event->processed_at !== null && in_array($event->status, ['processed', 'ignored'], true)) {
            return response('', 200);
        }

        $event->fill([
            'event_type' => $syncService->extractWebhookEventType($payload),
            'foodpanda_store_id' => $syncService->extractWebhookStoreId($payload),
            'foodpanda_order_id' => $syncService->extractOrderId($payload),
            'status' => 'received',
            'error_message' => null,
            'payload' => $payload,
        ]);
        $event->save();

        try {
            $result = $syncService->processWebhook($payload, $request->header('Authorization'));

            $event->fill([
                'status' => (string) ($result['status'] ?? 'processed'),
                'local_store_id' => $result['local_store_id'] ?? $event->local_store_id,
                'error_message' => $result['message'] ?? null,
                'processed_at' => Carbon::now(),
            ]);
            $event->save();

            if (($result['status'] ?? null) === 'unauthorized') {
                return response('Invalid Foodpanda authorization header.', 401);
            }

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
}
