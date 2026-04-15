<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use App\Support\GooglePlaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoreManagementController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->get('keyword');
        $countryCode = strtolower((string) $request->query('country_code', ''));
        $countryOptions = $this->countryOptions();
        if (! array_key_exists($countryCode, $countryOptions)) {
            $countryCode = '';
        }
        $user = $request->user();

        $maxStores = null;
        $usedStores = null;
        $remainingStores = null;
        $canCreateStore = true;

        if ($user && $user->isMerchant()) {
            $maxStores = $user->maxAllowedStores();
            $usedStores = Store::query()
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->count();
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
            ->when($countryCode !== '', function ($query) use ($countryCode) {
                $query->where('country_code', $countryCode);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.stores.index', compact(
            'stores',
            'keyword',
            'countryCode',
            'countryOptions',
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
        $data = $this->fillCoordinatesFromAddress($data);

        if ($user && $user->isMerchant()) {
            $data['user_id'] = $user->id;

            if ($data['is_active'] && ! $this->canActivateStore($user)) {
                $data['is_active'] = false;
            }
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
                'message' => $this->storeCreatedMessage($user, $data['is_active']),
                'store' => $this->storePayload($store->fresh()),
            ]);
        }

        return redirect()
            ->route('admin.stores.index')
            ->with('success', $this->storeCreatedMessage($user, $data['is_active']));
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
        $updateData = $this->fillCoordinatesFromAddress($updateData, $store);

        $user = $request->user();
        if ($user && $user->isMerchant() && $updateData['is_active']) {
            $canActivate = $this->canActivateStore($user, $store->id);
            if (! $canActivate) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'ok' => false,
                        'message' => $this->activationBlockedMessage($user),
                    ], 422);
                }

                return redirect()
                    ->route('admin.stores.index')
                    ->with('error', $this->activationBlockedMessage($user));
            }
        }

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
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'phone' => ['nullable', 'regex:/^(09\d{2}-\d{3}-\d{3}|09\d{8})$/'],
            'currency' => ['nullable', 'in:twd,vnd,cny,usd'],
            'country_code' => ['nullable', 'in:tw,vn,cn,us'],
            'banner_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp'],
            'checkout_timing' => ['nullable', 'in:prepay,postpay'],
            'opening_time' => ['nullable', 'date_format:H:i', 'required_with:closing_time'],
            'closing_time' => ['nullable', 'date_format:H:i', 'required_with:opening_time'],
        ], [
            'phone.regex' => '電話格式需為 0922333444 或 0922-333-444。',
        ]);

        $data['phone'] = $this->normalizeTaiwanMobilePhone($data['phone'] ?? null);
        $data['latitude'] = $this->normalizeCoordinate($data['latitude'] ?? null);
        $data['longitude'] = $this->normalizeCoordinate($data['longitude'] ?? null);
        $data['slug'] = $data['slug'] ?: (Str::slug($data['name']) ?: 'store');
        $data['is_active'] = $request->boolean('is_active');
        $data['currency'] = strtolower($data['currency'] ?? 'twd');
        $data['country_code'] = strtolower($data['country_code'] ?? $this->inferCountryCodeFromCurrency($data['currency']));
        $data['checkout_timing'] = $data['checkout_timing'] ?? 'postpay';
        $data['takeout_qr_enabled'] = true;

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

    protected function normalizeCoordinate(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return round((float) $value, 7);
    }

    protected function fillCoordinatesFromAddress(array $data, ?Store $existingStore = null): array
    {
        $address = trim((string) ($data['address'] ?? ''));
        if ($address === '') {
            $data['latitude'] = null;
            $data['longitude'] = null;
            return $data;
        }

        $geocoded = app(GooglePlaceService::class)->geocodeAddress($address);
        if ($geocoded === null) {
            if ($existingStore) {
                $data['latitude'] = $existingStore->latitude;
                $data['longitude'] = $existingStore->longitude;
            }
            return $data;
        }

        $data['latitude'] = $geocoded['latitude'];
        $data['longitude'] = $geocoded['longitude'];

        return $data;
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

        // Inactive stores do not consume plan quota. Quota is enforced on activation.
        return true;
    }

    protected function canActivateStore(User $user, ?int $excludingStoreId = null): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->hasActiveSubscription()) {
            return false;
        }

        if ($user->subscriptionPlan === null) {
            return false;
        }

        $maxStores = $user->maxAllowedStores();
        if ($maxStores === null) {
            return true;
        }

        $activeStoresQuery = Store::query()
            ->where('user_id', $user->id)
            ->where('is_active', true);

        if ($excludingStoreId !== null) {
            $activeStoresQuery->where('id', '!=', $excludingStoreId);
        }

        $activeCount = $activeStoresQuery->count();

        return $activeCount < $maxStores;
    }

    protected function storeCreatedMessage(?User $user, bool $isActive): string
    {
        if (! $user || ! $user->isMerchant()) {
            return '店家已建立。';
        }

        if ($isActive) {
            return '店家已建立。';
        }

        if (! $user->hasActiveSubscription()) {
            return '店家已建立，但因訂閱已到期，目前為關閉狀態。';
        }

        return '店家已建立，但因已達可開啟店家上限，目前為關閉狀態。';
    }

    protected function activationBlockedMessage(User $user): string
    {
        if (! $user->hasActiveSubscription()) {
            return '訂閱已到期，店家無法開啟。請先續訂方案。';
        }

        return '目前已達可開啟店家上限。請先關閉其他店家，再開啟此店家。';
    }

    protected function storePayload(Store $store): array
    {
        return [
            'id' => $store->id,
            'name' => $store->name,
            'slug' => $store->slug,
            'description' => $store->description,
            'address' => $store->address,
            'latitude' => $store->latitude,
            'longitude' => $store->longitude,
            'phone' => $store->phone,
            'currency' => strtolower($store->currency ?? 'twd'),
            'country_code' => strtolower($store->country_code ?? $this->inferCountryCodeFromCurrency($store->currency ?? 'twd')),
            'checkout_timing' => $store->checkout_timing ?? 'postpay',
            'is_active' => (bool) $store->is_active,
            'opening_time' => $this->formatTimeForInput($store->opening_time),
            'closing_time' => $this->formatTimeForInput($store->closing_time),
            'banner_image' => $store->banner_image,
            'banner_image_url' => $store->banner_image
                ? asset('storage/' . ltrim($store->banner_image, '/'))
                : null,
        ];
    }

    protected function countryOptions(): array
    {
        return [
            'tw' => 'admin.country_tw',
            'vn' => 'admin.country_vn',
            'cn' => 'admin.country_cn',
            'us' => 'admin.country_us',
        ];
    }

    protected function inferCountryCodeFromCurrency(string $currency): string
    {
        return match (strtolower($currency)) {
            'vnd' => 'vn',
            'cny' => 'cn',
            'usd' => 'us',
            default => 'tw',
        };
    }

    protected function formatTimeForInput(?string $time): ?string
    {
        if (! is_string($time) || $time === '') {
            return null;
        }

        return substr($time, 0, 5);
    }
}
