<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveMerchantSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403, '請先登入。');
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        if (!$user->hasActiveSubscription()) {
            return redirect()
                ->route('dashboard')
                ->with('error', '商家帳號需先啟用有效訂閱，才能使用商家後台。');
        }

        return $next($request);
    }
}
