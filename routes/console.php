<?php

use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Services\FakeMenuSeeder;
use App\Services\MicrosoftGraphMailer;
use App\Support\StoreFakeOrderGenerator;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('mail:test {to? : Recipient email address (defaults to MAIL_FROM_ADDRESS)} {--subject= : Custom email subject} {--graph : Send via Microsoft Graph OAuth2 instead of default mailer}', function (?string $to = null) {
    $recipient = $to ?: (string) config('mail.from.address');
    $subject = (string) ($this->option('subject') ?: __('mail_admin.mail_test.subject'));
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
                __('mail_admin.mail_test.graph_body', ['time' => now()->toDateTimeString()]),
                true,
                function (string $message): void {
                    $this->line($message);
                }
            );
        } else {
            Mail::raw(__('mail_admin.mail_test.body', ['time' => now()->toDateTimeString()]), function ($message) use ($recipient, $subject): void {
                $message->to($recipient)->subject($subject);
            });
        }

        $this->info('Mail test sent successfully.');

        return self::SUCCESS;
    } catch (Throwable $e) {
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

Artisan::command('admin:create {email : Admin email address} {--name= : Admin display name}', function (string $email) {
    $email = Str::lower(trim($email));

    if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('Please provide a valid email address.');

        return self::FAILURE;
    }

    $providedName = trim((string) $this->option('name'));
    $defaultName = (string) Str::of(Str::before($email, '@'))
        ->replaceMatches('/[._-]+/', ' ')
        ->squish()
        ->headline();
    $defaultName = $defaultName !== '' ? $defaultName : 'Admin';

    $user = User::query()->where('email', $email)->first();
    $isNewUser = ! $user instanceof User;
    $previousRole = $user?->role;

    if (! $user instanceof User) {
        $user = new User;
    }

    $user->fill([
        'name' => $providedName !== '' ? $providedName : ($user->exists ? (string) $user->name : $defaultName),
        'email' => $email,
        'password' => 'password',
        'must_change_password' => true,
        'role' => 'admin',
    ]);

    if ($user->email_verified_at === null) {
        $user->email_verified_at = now();
    }

    $user->save();

    $this->info($isNewUser ? 'Admin user created successfully.' : 'Admin user updated successfully.');
    $this->line('email: '.$user->email);
    $this->line('name: '.$user->name);
    $this->line('role: '.$user->role);
    $this->line('password: password');
    $this->line('must_change_password: true');

    if (! $isNewUser && $previousRole !== null && $previousRole !== 'admin') {
        $this->line('previous_role: '.$previousRole);
    }

    return self::SUCCESS;
})->purpose('Create or promote a user account to admin with the default password "password".');

Artisan::command('stores:fake-menu {store : Store id, slug, exact name, or merchant email} {--replace : Delete the store\'s existing categories and products before seeding}', function (string $store) {
    /** @var FakeMenuSeeder $fakeMenuSeeder */
    $fakeMenuSeeder = app(FakeMenuSeeder::class);
    $resolved = $fakeMenuSeeder->findStore($store);
    $targetStore = $resolved['store'] ?? null;
    $error = $resolved['error'] ?? null;

    if (! $targetStore instanceof Store) {
        $this->error((string) $error);

        return self::FAILURE;
    }

    $replace = (bool) $this->option('replace');
    $summary = $fakeMenuSeeder->seed($targetStore, $replace);

    $this->info('Fake menu seeded successfully.');
    $this->line('mode: '.($replace ? 'replace' : 'upsert'));
    $this->line('store: '.$targetStore->name.' (#'.$targetStore->id.', '.$targetStore->slug.')');
    $this->line('categories_created: '.(int) ($summary['categories_created'] ?? 0));
    $this->line('categories_updated: '.(int) ($summary['categories_updated'] ?? 0));
    $this->line('products_created: '.(int) ($summary['products_created'] ?? 0));
    $this->line('products_updated: '.(int) ($summary['products_updated'] ?? 0));

    return self::SUCCESS;
})->purpose('Seed a reusable fake menu for a specific store.');

Artisan::command('stores:fake-orders
    {store : Store id, slug, or exact name}
    {--count=20 : Number of fake orders to create}
    {--days=7 : Spread the generated orders across recent days}
    {--clear : Delete the target store\'s existing orders before generating}', function (string $store) {
    $storeKey = trim($store);
    $count = max(1, (int) $this->option('count'));
    $days = max(1, (int) $this->option('days'));
    $clearExisting = (bool) $this->option('clear');

    if ($storeKey === '') {
        $this->error('Please provide a store id, slug, or exact name.');

        return self::FAILURE;
    }

    $matches = Store::query()
        ->where(function ($query) use ($storeKey): void {
            if (ctype_digit($storeKey)) {
                $query->whereKey((int) $storeKey)
                    ->orWhere('slug', $storeKey)
                    ->orWhere('name', $storeKey);

                return;
            }

            $query->where('slug', $storeKey)
                ->orWhere('name', $storeKey);
        })
        ->orderBy('id')
        ->get(['id', 'name', 'slug']);

    if ($matches->isEmpty()) {
        $this->error(sprintf('Store not found for [%s].', $storeKey));

        return self::FAILURE;
    }

    if ($matches->count() > 1) {
        $this->error('Multiple stores matched that value. Please use a unique store id or slug.');

        foreach ($matches as $candidate) {
            $this->line(sprintf('- #%d %s (%s)', $candidate->id, $candidate->name, $candidate->slug));
        }

        return self::FAILURE;
    }

    $targetStore = $matches->first();

    try {
        $summary = app(StoreFakeOrderGenerator::class)->generate(
            $targetStore,
            $count,
            $days,
            $clearExisting
        );
    } catch (\Throwable $e) {
        $this->error('Fake order generation failed: '.$e->getMessage());

        return self::FAILURE;
    }

    $latestOrder = Order::query()
        ->where('store_id', $targetStore->id)
        ->latest('id')
        ->first(['order_no']);

    $this->info('Fake orders generated successfully.');
    $this->line('store: '.$targetStore->name.' (#'.$targetStore->id.', '.$targetStore->slug.')');
    $this->line('created_orders: '.(int) ($summary['created_orders'] ?? 0));
    $this->line('cleared_orders: '.(int) ($summary['cleared_orders'] ?? 0));
    $this->line('status_counts: '.collect($summary['status_counts'] ?? [])->map(fn ($value, $key) => $key.'='.$value)->implode(', '));
    $this->line('order_type_counts: '.collect($summary['order_type_counts'] ?? [])->map(fn ($value, $key) => $key.'='.$value)->implode(', '));

    if ($latestOrder) {
        $this->line('latest_order_no: '.$latestOrder->order_no);
    }

    return self::SUCCESS;
})->purpose('Generate fake test orders for one store without triggering customer mail or invoice side effects.');

Schedule::command('stores:enforce-merchant-quota')
    ->dailyAt('00:30')
    ->withoutOverlapping();
