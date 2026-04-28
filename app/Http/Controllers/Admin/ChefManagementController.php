<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ChefManagementController extends Controller
{
    public function index(Request $request, Store $store): View
    {
        $this->authorize('manageChefs', $store);

        $chefs = $store->chefs()->orderBy('created_at', 'desc')->get();

        return view('admin.chefs.index', compact('store', 'chefs'));
    }

    public function store(Request $request, Store $store): RedirectResponse
    {
        $this->authorize('manageChefs', $store);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->where(fn ($query) => $query->where('role', 'chef')),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (User::emailIsReservedForLogin((string) $value)) {
                        $fail(__('validation.unique', ['attribute' => __('validation.attributes.email')]));
                    }
                },
            ],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'chef',
            'store_id' => $store->id,
        ]);

        return redirect()->route('admin.stores.chefs.index', $store)
            ->with('success', __('chef.created_success'));
    }

    public function destroy(Request $request, Store $store, User $chef): RedirectResponse
    {
        $this->authorize('manageChefs', $store);

        abort_unless($chef->isChef() && (int) $chef->store_id === (int) $store->id, 404);

        $chef->delete();

        return redirect()->route('admin.stores.chefs.index', $store)
            ->with('success', __('chef.deleted_success'));
    }

}
