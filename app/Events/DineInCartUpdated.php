<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DineInCartUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use SerializesModels;

    public bool $afterCommit = true;
    public int $tries = 1;
    public int $timeout = 5;

    public function __construct(
        public int $storeId,
        public string $tableToken,
        public ?string $sourceClientId = null,
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('dinein-cart.' . $this->storeId . '.' . $this->tableToken),
        ];
    }

    public function broadcastAs(): string
    {
        return 'dinein.cart.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'source_client_id' => $this->sourceClientId,
            'updated_at' => now()->toISOString(),
        ];
    }
}
