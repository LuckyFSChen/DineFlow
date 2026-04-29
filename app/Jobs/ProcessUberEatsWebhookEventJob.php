<?php

namespace App\Jobs;

use App\Models\Store;
use App\Models\UberEatsWebhookEvent;
use App\Services\UberEatsOrderSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

class ProcessUberEatsWebhookEventJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 120;

    public function __construct(
        public readonly int $eventId,
    ) {
        $this->onQueue('uber-eats');
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [5, 15, 60, 180, 300];
    }

    public function handle(UberEatsOrderSyncService $syncService): void
    {
        $event = UberEatsWebhookEvent::query()->find($this->eventId);
        if (! $event) {
            return;
        }

        if ($event->processed_at !== null && in_array($event->status, ['processed', 'ignored'], true)) {
            return;
        }

        $event->forceFill([
            'status' => 'processing',
            'error_message' => null,
        ])->save();

        $store = $event->local_store_id
            ? Store::query()->find($event->local_store_id)
            : null;

        if (! $store || ! $store->hasUberEatsApiCredentials()) {
            throw new RuntimeException('Uber Eats credentials are not configured for the webhook store.');
        }

        try {
            $result = $syncService->process($event, $store);

            $event->forceFill([
                'status' => (string) ($result['status'] ?? 'processed'),
                'local_store_id' => $result['local_store_id'] ?? $event->local_store_id,
                'error_message' => $result['message'] ?? null,
                'processed_at' => Carbon::now(),
            ])->save();
        } catch (\Throwable $e) {
            $event->forceFill([
                'status' => 'failed',
                'error_message' => Str::limit($e->getMessage(), 1000),
            ])->save();

            throw $e;
        }
    }
}
