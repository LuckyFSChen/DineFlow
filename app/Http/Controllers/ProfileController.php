<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\CustomerAccountDeletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $newEmail = $validated['email'];
        $emailChanged = strcasecmp((string) $user->email, (string) $newEmail) !== 0;

        $user->name = $validated['name'];

        if ($emailChanged && ($user->isMerchant() || $user->isAdmin())) {
            $user->pending_email = $newEmail;
            $user->save();
            $user->sendEmailVerificationNotification();

            return Redirect::route('profile.edit')->with('status', 'email-verification-link-sent');
        }

        $user->email = $newEmail;

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
            $user->pending_email = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        app(CustomerAccountDeletionService::class)->delete($user);

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
