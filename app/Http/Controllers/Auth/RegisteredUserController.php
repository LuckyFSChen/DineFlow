<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PhoneFormatter;
use App\Support\TakeoutCartSession;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $accountType = $request->string('account_type')->toString();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                $accountType === 'customer' ? 'required' : 'nullable',
                'string',
                'max:32',
                'unique:'.User::class,
            ],
            'email' => [
                $accountType === 'merchant' ? 'required' : 'nullable',
                'string',
                'lowercase',
                'email',
                'max:255',
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'account_type' => ['required', 'in:customer,merchant'],
            'merchant_region' => ['nullable', 'in:tw,cn,vn', 'required_if:account_type,merchant'],
        ]);

        $normalizedPhone = PhoneFormatter::digitsOnly($request->input('phone'), 32);

        $user = User::create([
            'name' => $request->name,
            'phone' => $normalizedPhone,
            'email' => $request->input('email') ?: null,
            'password' => Hash::make($request->password),
            'role' => $accountType,
            'merchant_region' => $accountType === 'merchant'
                ? $request->string('merchant_region')->toString()
                : null,
        ]);

        event(new Registered($user));

        Auth::login($user);
        TakeoutCartSession::inheritGuestCarts($request, $user);

        return redirect(route('dashboard', absolute: false));
    }
}
