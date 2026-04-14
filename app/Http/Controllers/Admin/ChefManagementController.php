<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ChefManagementController extends Controller
{
    public function index(Request $request, Store $store): View
    {
        $this->authorizeStore($request, $store);

        $chefs = $store->chefs()->orderBy('created_at', 'desc')->get();

        return view('admin.chefs.index', compact('store', 'chefs'));
    }

    public function store(Request $request, Store $store): RedirectResponse
    {
        $this->authorizeStore($request, $store);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
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
            ->with('success', '廚師帳號已建立');
    }

    public function destroy(Request $request, Store $store, User $chef): RedirectResponse
    {
        $this->authorizeStore($request, $store);

        abort_unless($chef->isChef() && (int) $chef->store_id === (int) $store->id, 404);

        $chef->delete();

        return redirect()->route('admin.stores.chefs.index', $store)
            ->with('success', '廚師帳號已刪除');
    }

    private function authorizeStore(Request $request, Store $store): void
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return;
        }

        abort_unless($user->isMerchant() && (int) $store->user_id === (int) $user->id, 403);
    }
}
