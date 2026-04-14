<?php

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('stores:enforce-merchant-quota', function () {
    $affectedMerchants = 0;
    $closedStores = 0;

    User::query()
        ->where('role', 'merchant')
        ->with(['subscriptionPlan:id,max_stores'])
        ->chunkById(100, function ($merchants) use (&$affectedMerchants, &$closedStores) {
            foreach ($merchants as $merchant) {
                $shouldCloseAll = false;

                if (! $merchant->hasActiveSubscription()) {
                    $shouldCloseAll = true;
                } else {
                    $maxStores = $merchant->maxAllowedStores();

                    if ($maxStores !== null) {
                        $activeStoreCount = Store::query()
                            ->where('user_id', $merchant->id)
                            ->where('is_active', true)
                            ->count();

                        if ($activeStoreCount > $maxStores) {
                            $shouldCloseAll = true;
                        }
                    }
                }

                if (! $shouldCloseAll) {
                    continue;
                }

                $updated = Store::query()
                    ->where('user_id', $merchant->id)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);

                if ($updated > 0) {
                    $affectedMerchants++;
                    $closedStores += $updated;
                }
            }
        });

    $this->info("quota enforcement done: merchants={$affectedMerchants}, stores={$closedStores}");
})->purpose('Close all active stores for merchants with expired subscription or over active-store quota.');

Schedule::command('stores:enforce-merchant-quota')
    ->dailyAt('00:30')
    ->withoutOverlapping();
