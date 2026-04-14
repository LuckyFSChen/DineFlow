<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoreManagementController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->get('keyword');
        $user = $request->user();

        $maxStores = null;
        $usedStores = null;
        $remainingStores = null;
        $canCreateStore = true;

        if ($user && $user->isMerchant()) {
            $maxStores = $user->maxAllowedStores();
            $usedStores = Store::query()->where('user_id', $user->id)->count();
            $remainingStores = $maxStores === null ? null : max($maxStores - $usedStores, 0);
            $canCreateStore = $this->canCreateStore($user);
        }

        $stores = Store::query()
            ->when($user && $user->isMerchant(), function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->when($keyword, function ($query) use ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhere('slug', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%")
                        ->orWhere('address', 'like', "%{$keyword}%")
                        ->orWhere('phone', 'like', "%{$keyword}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.stores.index', compact(
            'stores',
            'keyword',
            'maxStores',
            'usedStores',
            'remainingStores',
            'canCreateStore'
        ));
    }

    public function create(Request $request)
    {
        $user = $request->user();
        if ($user && $user->isMerchant() && ! $this->canCreateStore($user)) {
            return redirect()
                ->route('admin.stores.index')
                ->with('error', '你目前方案可建立的店家數已達上限，請升級方案。');
        }

        $store = new Store();

        return view('admin.stores.create', compact('store'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if ($user && $user->isMerchant() && ! $this->canCreateStore($user)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => '你目前方案可建立的店家數已達上限，請升級方案。',
                ], 422);
            }

            return redirect()
                ->route('admin.stores.index')
                ->with('error', '你目前方案可建立的店家數已達上限，請升級方案。');
        }

        $data = $this->validatedData($request);

        if ($user && $user->isMerchant()) {
            $data['user_id'] = $user->id;
        }

        $store = Store::create($data);

        if ($request->hasFile('banner_image')) {
            $file = $request->file('banner_image');
            $extension = strtolower($file->getClientOriginalExtension()) ?: 'jpg';

            $path = $file->storeAs(
                "stores/{$store->id}/banner",
                "banner.{$extension}",
                'public'
            );

            $store->update([
                'banner_image' => $path,
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => '店家已建立。',
                'store' => $this->storePayload($store->fresh()),
            ]);
        }

        return redirect()
            ->route('admin.stores.index')
            ->with('success', '店家已建立。');
    }

    public function edit(Request $request, Store $store)
    {
        $this->authorizeStoreAccess($request, $store);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'store' => $this->storePayload($store),
            ]);
        }

        return view('admin.stores.edit', compact('store'));
    }

    public function update(Request $request, Store $store)
    {
        $this->authorizeStoreAccess($request, $store);

        $updateData = $this->validatedData($request, $store->id);

        if ($request->hasFile('banner_image')) {
            $file = $request->file('banner_image');
            $extension = strtolower($file->getClientOriginalExtension()) ?: 'jpg';

            if (! empty($store->banner_image)) {
                Storage::disk('public')->delete($store->banner_image);
            }

            Storage::disk('public')->deleteDirectory("stores/{$store->id}/banner");

            $path = $file->storeAs(
                "stores/{$store->id}/banner",
                "banner.{$extension}",
                'public'
            );

            $updateData['banner_image'] = $path;
        }

        $store->update($updateData);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => '店家已更新。',
                'store' => $this->storePayload($store->fresh()),
            ]);
        }

        return redirect()
            ->route('admin.stores.index')
            ->with('success', '店家已更新。');
    }

    public function destroy(Store $store)
    {
        $this->authorizeStoreAccess(request(), $store);

        $store->delete();

        return redirect()
            ->route('admin.stores.index')
            ->with('success', '店家已刪除。');
    }

    protected function validatedData(Request $request, ?int $storeId = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:stores,slug,' . $storeId],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'regex:/^(09\d{2}-\d{3}-\d{3}|09\d{8})$/'],
            'banner_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'opening_time' => ['nullable', 'date_format:H:i', 'required_with:closing_time'],
            'closing_time' => ['nullable', 'date_format:H:i', 'required_with:opening_time'],
        ], [
            'phone.regex' => '電話格式需為 0922333444 或 0922-333-444。',
        ]);

        $data['phone'] = $this->normalizeTaiwanMobilePhone($data['phone'] ?? null);
        $data['slug'] = $data['slug'] ?: (Str::slug($data['name']) ?: 'store');
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    protected function normalizeTaiwanMobilePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $phone = trim($phone);
        if ($phone === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);
        if (preg_match('/^09\d{8}$/', $digits) !== 1) {
            return $phone;
        }

        return substr($digits, 0, 4) . '-' . substr($digits, 4, 3) . '-' . substr($digits, 7, 3);
    }

    protected function authorizeStoreAccess(Request $request, Store $store): void
    {
        $user = $request->user();
        if (! $user) {
            abort(403, '請先登入。');
        }

        if ($user->isAdmin()) {
            return;
        }

        if ($user->isMerchant() && (int) $store->user_id === (int) $user->id) {
            return;
        }

        abort(403, '你無法管理此店家。');
    }

    protected function canCreateStore(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->subscriptionPlan === null) {
            return false;
        }

        $maxStores = $user->maxAllowedStores();
        if ($maxStores === null) {
            return true;
        }

        $currentCount = Store::query()
            ->where('user_id', $user->id)
            ->count();

        return $currentCount < $maxStores;
    }

    protected function storePayload(Store $store): array
    {
        return [
            'id' => $store->id,
            'name' => $store->name,
            'slug' => $store->slug,
            'description' => $store->description,
            'address' => $store->address,
            'phone' => $store->phone,
            'is_active' => (bool) $store->is_active,
            'opening_time' => $store->opening_time,
            'closing_time' => $store->closing_time,
            'banner_image' => $store->banner_image,
            'banner_image_url' => $store->banner_image
                ? asset('storage/' . ltrim($store->banner_image, '/'))
                : null,
        ];
    }
}
