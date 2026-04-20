<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\ForceHttps::class);

        $middleware->redirectGuestsTo(function (Request $request): string {
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.login');
            }

            return route('login');
        });

        // Set locale after session is started by the web middleware group.
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\EnforcePasswordChange::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'ecpay/subscription/notify',
            'ecpay/subscription/result',
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserRole::class,
            'merchant.subscription' => \App\Http\Middleware\EnsureActiveMerchantSubscription::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->expectsJson() || $e instanceof ValidationException) {
                return null;
            }

            if ($e instanceof HttpExceptionInterface && $e->getStatusCode() < 500) {
                return null;
            }

            return response()->json([
                'ok' => false,
                'message' => __('errors.server_error'),
            ], 500);
        });

        $exceptions->respond(function ($response, Throwable $e, Request $request) {
            if ($request->expectsJson()) {
                return $response;
            }

            if (in_array($response->getStatusCode(), [419, 500], true)) {
                return redirect()->route('home');
            }

            return $response;
        });
    })->create();
