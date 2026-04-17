<?php

use App\Models\Store;
use App\Models\User;
use App\Services\MicrosoftGraphMailer;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('mail:test {to? : Recipient email address (defaults to MAIL_FROM_ADDRESS)} {--subject= : Custom email subject} {--graph : Send via Microsoft Graph OAuth2 instead of default mailer}', function (?string $to = null) {
    $recipient = $to ?: (string) config('mail.from.address');
    $subject = (string) ($this->option('subject') ?: 'DineFlow Mail Test');
    $useGraph = (bool) $this->option('graph');
    $mailer = (string) config('mail.default');

    if ($recipient === '') {
        $this->error('No recipient provided and MAIL_FROM_ADDRESS is empty.');

        return self::FAILURE;
    }

    $shouldUseGraph = $useGraph || $mailer === 'graph';

    $this->line('channel: '.($shouldUseGraph ? 'microsoft-graph' : $mailer));
    $this->line("to: {$recipient}");

    try {
        if ($shouldUseGraph) {
            /** @var MicrosoftGraphMailer $graphMailer */
            $graphMailer = app(MicrosoftGraphMailer::class);
            $this->info('Graph 模式: '.$graphMailer->mode());
            $graphMailer->sendText(
                $recipient,
                $subject,
                'This is a Microsoft Graph mail test from DineFlow. Sent at '.now()->toDateTimeString(),
                true,
                function (string $message): void {
                    $this->line($message);
                }
            );
        } else {
            Mail::raw('This is a test email from DineFlow. Sent at '.now()->toDateTimeString(), function ($message) use ($recipient, $subject): void {
                $message->to($recipient)->subject($subject);
            });
        }

        $this->info('Mail test sent successfully.');

        return self::SUCCESS;
    } catch (\Throwable $e) {
        $this->error('Mail test failed: '.$e->getMessage());

        return self::FAILURE;
    }
})->purpose('Send a test email using current mail configuration.');

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
