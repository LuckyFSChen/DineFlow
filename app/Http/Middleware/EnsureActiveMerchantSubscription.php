<?php

namespace App\Http\Middleware;

use App\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveMerchantSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403, __('admin.error_login_required'));
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        if (!$user->hasActiveSubscription()) {
            Store::query()
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            return redirect()
                ->route('dashboard')
                ->with('error', __('admin.need_subscription'));
        }

        return $next($request);
    }
}
