<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Support\LoginCaptcha;
use App\Support\TakeoutCartSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request): View
    {
        LoginCaptcha::refresh($request);

        $requestedType = (string) $request->query('account_type', 'customer');
        $defaultAccountType = in_array($requestedType, ['merchant', 'backend_staff'], true)
            ? $requestedType
            : 'customer';

        return view('auth.login', [
            'defaultAccountType' => $defaultAccountType,
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();
        if ($user instanceof User) {
            TakeoutCartSession::inheritGuestCarts($request, $user);
        }

        if ($user instanceof User && (bool) $user->must_change_password) {
            return redirect()
                ->route('profile.edit')
                ->with('warning', __('auth.force_password_change_notice'));
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
