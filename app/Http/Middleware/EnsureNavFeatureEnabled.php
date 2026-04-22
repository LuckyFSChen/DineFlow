<?php

namespace App\Http\Middleware;

use App\Support\NavFeature;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNavFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (NavFeature::enabled($feature)) {
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
