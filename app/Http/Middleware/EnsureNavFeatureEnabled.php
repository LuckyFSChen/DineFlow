<?php

namespace App\Http\Middleware;

use App\Support\NavFeature;
use App\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNavFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $routeStore = $request->route('store');
        $store = $routeStore instanceof Store ? $routeStore : null;

        if (NavFeature::enabledForUser($request->user(), $feature, $store)) {
            return $next($request);
        }

        $message = __('admin.nav_feature_disabled', [
            'feature' => __(NavFeature::labelKey($feature)),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => false,
                'message' => $message,
            ], 403);
        }

        $user = $request->user();

        if ($user?->isAdmin()) {
            return redirect()
                ->route('super-admin.subscriptions.index', ['tab' => 'features'])
                ->with('error', $message);
        }

        return redirect()
            ->route('dashboard')
            ->with('error', $message);
    }
}
