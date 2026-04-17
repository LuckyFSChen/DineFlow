<?php

namespace App\Providers;

use App\Mail\Transport\MicrosoftGraphTransport;
use App\Services\MicrosoftGraphMailer;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Mail::extend('graph', function (array $config): MicrosoftGraphTransport {
            return new MicrosoftGraphTransport($this->app->make(MicrosoftGraphMailer::class));
        });

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
