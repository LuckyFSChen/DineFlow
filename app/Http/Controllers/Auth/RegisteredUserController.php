<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\MerchantRegisteredNotificationMail;
use App\Models\User;
use App\Support\PhoneFormatter;
use App\Support\TakeoutCartSession;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

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
        if ($normalizedPhone !== null && User::query()->where('phone', $normalizedPhone)->exists()) {
            throw ValidationException::withMessages([
                'phone' => __('validation.unique', ['attribute' => __('auth.Phone')]),
            ]);
        }

        if ($accountType === 'merchant' && User::emailIsReservedForLogin((string) $request->input('email'))) {
            throw ValidationException::withMessages([
                'email' => __('validation.unique', ['attribute' => __('auth.Email')]),
            ]);
        }

        try {
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
        } catch (QueryException $e) {
            $message = (string) $e->getMessage();

            if (str_contains($message, 'users_phone_unique')) {
                throw ValidationException::withMessages([
                    'phone' => __('validation.unique', ['attribute' => __('auth.Phone')]),
                ]);
            }

            if (str_contains($message, 'users_email_unique')) {
                throw ValidationException::withMessages([
                    'email' => __('validation.unique', ['attribute' => __('auth.Email')]),
                ]);
            }

            throw $e;
        }

        if ($accountType === 'merchant') {
            try {
                $notifyEmail = trim((string) config('mail.merchant_registration_notify_to', ''));
                if ($notifyEmail !== '') {
                    Mail::to($notifyEmail)->send(new MerchantRegisteredNotificationMail($user));
                }
            } catch (Throwable $e) {
                report($e);
            }
        }

        event(new Registered($user));

        Auth::login($user);
        TakeoutCartSession::inheritGuestCarts($request, $user);

        return redirect(route('dashboard', absolute: false));
    }
}
