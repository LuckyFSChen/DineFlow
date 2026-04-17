<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! ($user instanceof User) || ! (bool) $user->must_change_password) {
            return $next($request);
        }

        if (
            $request->routeIs('profile.edit')
            || $request->routeIs('profile.update')
            || $request->routeIs('password.update')
            || $request->routeIs('logout')
        ) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => false,
                'message' => __('auth.force_password_change_notice'),
            ], 403);
        }

        return redirect()
            ->route('profile.edit')
            ->with('warning', __('auth.force_password_change_notice'));
    }
}
