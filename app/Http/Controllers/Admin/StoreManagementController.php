<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use App\Support\GooglePlaceService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreManagementController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Store::class);

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
            ->select([
                'id',
                'user_id',
                'name',
                'slug',
                'description',
                'address',
                'phone',
                'currency',
                'country_code',
                'is_active',
                'banner_image',
                'created_at',
            ])
            ->with(['owner:id,name,email'])
            ->when($user && $user->isMerchant(), function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->when($keyword, function ($query) use ($keyword) {
                $operator = $this->caseInsensitiveLikeOperator();

                $query->where(function ($q) use ($keyword, $operator) {
                    $q->where('name', $operator, "%{$keyword}%")
                        ->orWhere('slug', $operator, "%{$keyword}%")
                        ->orWhere('description', $operator, "%{$keyword}%")
                        ->orWhere('address', $operator, "%{$keyword}%")
                        ->orWhere('phone', $operator, "%{$keyword}%");
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
        $this->authorize('create', Store::class);

        $user = $request->user();
        if ($user && $user->isMerchant() && ! $this->canCreateStore($user)) {
            return redirect()
                ->route('admin.stores.index')
                ->with('error', __('admin.error_store_quota_reached'));
        }

        $store = new Store();

        return view('admin.stores.create', compact('store'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Store::class);

        $user = $request->user();
        if ($user && $user->isMerchant() && ! $this->canCreateStore($user)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => __('admin.error_store_quota_reached'),
                ], 422);
            }

            return redirect()
                ->route('admin.stores.index')
                ->with('error', __('admin.error_store_quota_reached'));
        }

        $data = $this->validatedData($request);
        $data = $this->fillCoordinatesFromAddress($data);

        if ($user && $user->isMerchant()) {
            $data['user_id'] = $user->id;

            if ($data['is_active'] && ! $this->canActivateStore($user)) {
                $data['is_active'] = false;
            }
        }

        $maxAttempts = 10;
        $baseSlug = Str::slug((string) $data['name']) ?: 'store';
        $data['slug'] = $this->nextAvailableStoreSlug($baseSlug);
        $store = null;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            try {
                $store = Store::create($data);
                break;
            } catch (QueryException $e) {
                if ($this->isStoreSlugUniqueViolation($e) && $attempt < $maxAttempts - 1) {
                    $data['slug'] = $this->nextAvailableStoreSlug($baseSlug);
                    continue;
                }

                throw $e;
            }
        }

        if (! $store) {
            throw new \RuntimeException(__('admin.error_store_slug_collision_repeated'));
        }

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
        $this->authorize('update', $store);

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
        $this->authorize('update', $store);

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
                'message' => __('admin.store_saved'),
                'store' => $this->storePayload($store->fresh()),
            ]);
        }

        return redirect()
            ->route('admin.stores.index')
            ->with('success', __('admin.store_saved'));
    }

    public function destroy(Store $store)
    {
        $this->authorize('delete', $store);

        $store->delete();

        return redirect()
            ->route('admin.stores.index')
            ->with('success', __('admin.store_deleted'));
    }

    public function activate(Request $request, Store $store)
    {
        $this->authorize('update', $store);

        if ((bool) $store->is_active) {
            return back()->with('success', __('admin.store_already_active'));
        }

        $user = $request->user();
        if ($user && $user->isMerchant() && ! $this->canActivateStore($user, $store->id)) {
            return back()->with('error', $this->activationBlockedMessage($user));
        }

        $store->update(['is_active' => true]);

        return back()->with('success', __('admin.store_activate_success'));
    }

    public function deactivate(Request $request, Store $store)
    {
        $this->authorize('update', $store);

        if (! (bool) $store->is_active) {
            return back()->with('success', __('admin.store_already_inactive'));
        }

        $store->update(['is_active' => false]);

        return back()->with('success', __('admin.store_deactivate_success'));
    }

    protected function validatedData(Request $request, ?int $storeId = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'phone' => [
                'nullable',
                'string',
                'max:32',
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    $raw = trim((string) ($value ?? ''));
                    if ($raw === '') {
                        return;
                    }

                    $countryCode = strtolower((string) $request->input('country_code', 'tw'));
                    $expectedLength = $this->expectedPhoneLengthByCountry($countryCode);
                    $digits = preg_replace('/\D+/', '', $raw);
                    if (! is_string($digits) || strlen($digits) !== $expectedLength) {
                        $fail(__('admin.phone_length_error', ['digits' => $expectedLength]));
                    }
                },
            ],
            'currency' => ['nullable', 'in:twd,vnd,cny,usd'],
            'country_code' => ['nullable', 'in:tw,vn,cn,us'],
            'timezone' => ['nullable', 'timezone'],
            'banner_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp'],
            'checkout_timing' => ['nullable', 'in:prepay,postpay'],
            'opening_time' => ['nullable', 'date_format:H:i', 'required_with:closing_time'],
            'closing_time' => ['nullable', 'date_format:H:i', 'required_with:opening_time'],
            'prep_time_minutes' => ['nullable', 'integer', 'min:1', 'max:300'],
            'uber_eats_enabled' => ['nullable', 'boolean'],
            'uber_eats_store_id' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('stores', 'uber_eats_store_id')->ignore($storeId),
            ],
            'uber_eats_store_url' => ['nullable', 'url', 'max:2048'],
            'uber_eats_client_id' => ['nullable', 'string', 'max:255'],
            'uber_eats_client_secret' => ['nullable', 'string', 'max:2000'],
            'uber_eats_webhook_signing_key' => ['nullable', 'string', 'max:2000'],
            'foodpanda_enabled' => ['nullable', 'boolean'],
            'foodpanda_chain_id' => [
                Rule::requiredIf(fn () => $request->boolean('foodpanda_enabled')),
                'nullable',
                'string',
                'max:255',
            ],
            'foodpanda_store_id' => [
                Rule::requiredIf(fn () => $request->boolean('foodpanda_enabled')
                    && blank($request->input('foodpanda_external_partner_config_id'))),
                'nullable',
                'string',
                'max:255',
            ],
            'foodpanda_external_partner_config_id' => [
                Rule::requiredIf(fn () => $request->boolean('foodpanda_enabled')
                    && blank($request->input('foodpanda_store_id'))),
                'nullable',
                'string',
                'max:255',
            ],
            'foodpanda_client_id' => [
                Rule::requiredIf(fn () => $request->boolean('foodpanda_enabled')),
                'nullable',
                'string',
                'max:255',
            ],
            'foodpanda_client_secret' => [
                Rule::requiredIf(fn () => $request->boolean('foodpanda_enabled')),
                'nullable',
                'string',
                'max:2000',
            ],
            'foodpanda_webhook_secret' => [
                Rule::requiredIf(fn () => $request->boolean('foodpanda_enabled')),
                'nullable',
                'string',
                'max:2000',
            ],
        ];

        foreach (array_keys($this->weekdayFormToStorageMap()) as $weekday) {
            $rules["business_hours.{$weekday}.start"] = ['nullable', 'date_format:H:i', "required_with:business_hours.{$weekday}.end"];
            $rules["business_hours.{$weekday}.end"] = ['nullable', 'date_format:H:i', "required_with:business_hours.{$weekday}.start"];
            $rules["break_hours.{$weekday}.start"] = ['nullable', 'date_format:H:i', "required_with:break_hours.{$weekday}.end"];
            $rules["break_hours.{$weekday}.end"] = ['nullable', 'date_format:H:i', "required_with:break_hours.{$weekday}.start"];
        }

        $data = $request->validate($rules);
        $existingStore = $storeId !== null ? Store::query()->find($storeId) : null;

        $countryCode = strtolower((string) ($data['country_code'] ?? 'tw'));
        $data['country_code'] = $countryCode;
        $data['phone'] = $this->normalizePhoneByCountry($data['phone'] ?? null, $countryCode);
        $data['latitude'] = $this->normalizeCoordinate($data['latitude'] ?? null);
        $data['longitude'] = $this->normalizeCoordinate($data['longitude'] ?? null);
        $data['is_active'] = $request->boolean('is_active');
        $data['currency'] = strtolower($data['currency'] ?? 'twd');
        $data['country_code'] = $countryCode !== '' ? $countryCode : strtolower($this->inferCountryCodeFromCurrency($data['currency']));
        $timezone = trim((string) ($data['timezone'] ?? ''));
        $data['timezone'] = $timezone !== '' ? $timezone : $this->inferTimezoneFromCountryCode($data['country_code']);
        $data['checkout_timing'] = $data['checkout_timing'] ?? 'postpay';
        $data['prep_time_minutes'] = isset($data['prep_time_minutes']) ? (int) $data['prep_time_minutes'] : null;
        $data['uber_eats_enabled'] = $request->boolean('uber_eats_enabled');
        $data['uber_eats_store_id'] = $this->normalizeNullableString($data['uber_eats_store_id'] ?? null);
        $data['uber_eats_store_url'] = $this->normalizeNullableString($data['uber_eats_store_url'] ?? null);
        $data['uber_eats_client_id'] = $this->normalizeNullableString($data['uber_eats_client_id'] ?? null);
        $submittedUberSecret = $this->normalizeNullableString($data['uber_eats_client_secret'] ?? null);
        if ($submittedUberSecret !== null) {
            $data['uber_eats_client_secret'] = $submittedUberSecret;
        } else {
            unset($data['uber_eats_client_secret']);
        }
        $submittedUberSigningKey = $this->normalizeNullableString($data['uber_eats_webhook_signing_key'] ?? null);
        if ($submittedUberSigningKey !== null) {
            $data['uber_eats_webhook_signing_key'] = $submittedUberSigningKey;
        } else {
            unset($data['uber_eats_webhook_signing_key']);
        }
        $data['foodpanda_enabled'] = $request->boolean('foodpanda_enabled');
        $data['foodpanda_chain_id'] = $this->normalizeNullableString($data['foodpanda_chain_id'] ?? null);
        $data['foodpanda_store_id'] = $this->normalizeNullableString($data['foodpanda_store_id'] ?? null);
        $data['foodpanda_external_partner_config_id'] = $this->normalizeNullableString($data['foodpanda_external_partner_config_id'] ?? null);
        $data['foodpanda_client_id'] = $this->normalizeNullableString($data['foodpanda_client_id'] ?? null);
        $data['foodpanda_client_secret'] = $this->normalizeNullableString($data['foodpanda_client_secret'] ?? null);
        $data['foodpanda_webhook_secret'] = $this->normalizeNullableString($data['foodpanda_webhook_secret'] ?? null);
        $data['takeout_qr_enabled'] = true;
        $data['weekly_business_hours'] = $this->normalizeWeeklyBusinessHours($data['business_hours'] ?? []);
        $data['weekly_break_hours'] = $this->normalizeWeeklyBreakHours($data['break_hours'] ?? []);
        unset($data['business_hours']);
        unset($data['break_hours']);

        $uberSecretForValidation = array_key_exists('uber_eats_client_secret', $data)
            ? $data['uber_eats_client_secret']
            : $this->normalizeNullableString($existingStore?->uber_eats_client_secret);
        $uberSigningKeyForValidation = array_key_exists('uber_eats_webhook_signing_key', $data)
            ? $data['uber_eats_webhook_signing_key']
            : $this->normalizeNullableString($existingStore?->uber_eats_webhook_signing_key);

        $uberValidationErrors = [];
        if ($data['uber_eats_enabled']) {
            if ($data['uber_eats_store_id'] === null) {
                $uberValidationErrors['uber_eats_store_id'] = 'Uber Eats Store ID is required when the integration is enabled.';
            }

            if ($data['uber_eats_client_id'] === null) {
                $uberValidationErrors['uber_eats_client_id'] = 'Uber Eats Client ID is required when the integration is enabled.';
            }

            if ($uberSecretForValidation === null) {
                $uberValidationErrors['uber_eats_client_secret'] = 'Uber Eats Client Secret is required when the integration is enabled.';
            }

            if ($uberSigningKeyForValidation === null) {
                $uberValidationErrors['uber_eats_webhook_signing_key'] = 'Uber Eats Webhook Signing Key is required when the integration is enabled.';
            }
        }

        if ($uberValidationErrors !== []) {
            throw ValidationException::withMessages($uberValidationErrors);
        }

        return $data;
    }

    protected function isStoreSlugUniqueViolation(QueryException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? '');
        $message = $e->getMessage();

        if ($sqlState === '23505' && str_contains($message, 'stores_slug_unique')) {
            return true;
        }

        return str_contains($message, 'stores_slug_unique')
            || str_contains($message, 'store_slug_unique');
    }

    protected function nextAvailableStoreSlug(string $baseSlug, ?int $excludeStoreId = null): string
    {
        $baseSlug = Str::slug($baseSlug) ?: 'store';

        $existingSlugs = Store::withTrashed()
            ->select('slug')
            ->when($excludeStoreId !== null, function ($query) use ($excludeStoreId) {
                $query->where('id', '!=', $excludeStoreId);
            })
            ->where(function ($query) use ($baseSlug) {
                $query->where('slug', $baseSlug)
                    ->orWhere('slug', 'like', $baseSlug . '-%');
            })
            ->pluck('slug');

        if (! $existingSlugs->contains($baseSlug)) {
            return $baseSlug;
        }

        $maxSuffix = 0;
        $pattern = '/^' . preg_quote($baseSlug, '/') . '-(\d+)$/';

        foreach ($existingSlugs as $slug) {
            if (preg_match($pattern, (string) $slug, $matches) === 1) {
                $maxSuffix = max($maxSuffix, (int) $matches[1]);
            }
        }

        return $baseSlug . '-' . ($maxSuffix + 1);
    }

    protected function expectedPhoneLengthByCountry(string $countryCode): int
    {
        return match (strtolower($countryCode)) {
            'cn' => 11,
            default => 10,
        };
    }

    protected function normalizePhoneByCountry(?string $phone, string $countryCode): ?string
    {
        if ($phone === null) {
            return null;
        }

        $phone = trim($phone);
        if ($phone === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);
        $expectedLength = $this->expectedPhoneLengthByCountry($countryCode);

        if (! is_string($digits) || strlen($digits) !== $expectedLength) {
            return null;
        }

        return $digits;
    }

    protected function normalizeCoordinate(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return round((float) $value, 7);
    }

    protected function normalizeNullableString(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));

        return $normalized !== '' ? $normalized : null;
    }

    protected function fillCoordinatesFromAddress(array $data, ?Store $existingStore = null): array
    {
        $hasManualCoordinates = ($data['latitude'] ?? null) !== null && ($data['longitude'] ?? null) !== null;
        if ($hasManualCoordinates) {
            return $data;
        }

        $address = trim((string) ($data['address'] ?? ''));
        if ($address === '') {
            $data['latitude'] = null;
            $data['longitude'] = null;

            return $data;
        }

        if ($existingStore && trim((string) $existingStore->address) === $address) {
            $data['latitude'] = $existingStore->latitude;
            $data['longitude'] = $existingStore->longitude;

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
            return '店家建立成功。';
        }

        if ($isActive) {
            return '店家建立成功。';
        }

        if (! $user->hasActiveSubscription()) {
            return '店家建立成功，但因訂閱已到期，目前為停用狀態。';
        }

        return '店家建立成功，但因已達啟用上限，目前為停用狀態。';
    }

    protected function activationBlockedMessage(User $user): string
    {
        if (! $user->hasActiveSubscription()) {
            return __('admin.error_store_activation_subscription_expired');
        }

        return __('admin.error_store_activation_limit_reached');
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
            'timezone' => $store->timezone ?: $this->inferTimezoneFromCountryCode(strtolower($store->country_code ?? 'tw')),
            'checkout_timing' => $store->checkout_timing ?? 'postpay',
            'is_active' => (bool) $store->is_active,
            'uber_eats_enabled' => (bool) $store->uber_eats_enabled,
            'uber_eats_store_id' => $store->uber_eats_store_id,
            'uber_eats_store_url' => $store->uber_eats_store_url,
            'uber_eats_client_id' => $store->uber_eats_client_id,
            'uber_eats_has_client_secret' => trim((string) ($store->uber_eats_client_secret ?? '')) !== '',
            'uber_eats_has_webhook_signing_key' => trim((string) ($store->uber_eats_webhook_signing_key ?? '')) !== '',
            'foodpanda_enabled' => (bool) $store->foodpanda_enabled,
            'foodpanda_chain_id' => $store->foodpanda_chain_id,
            'foodpanda_store_id' => $store->foodpanda_store_id,
            'foodpanda_external_partner_config_id' => $store->foodpanda_external_partner_config_id,
            'foodpanda_client_id' => $store->foodpanda_client_id,
            'opening_time' => $this->formatTimeForInput($store->opening_time),
            'closing_time' => $this->formatTimeForInput($store->closing_time),
            'weekly_business_hours' => $this->weeklyBusinessHoursForForm($store),
            'prep_time_minutes' => $store->prep_time_minutes,
            'weekly_break_hours' => $this->weeklyBreakHoursForForm($store),
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

    protected function inferTimezoneFromCountryCode(string $countryCode): string
    {
        return match (strtolower($countryCode)) {
            'vn' => 'Asia/Ho_Chi_Minh',
            'cn' => 'Asia/Shanghai',
            'us' => 'America/New_York',
            default => 'Asia/Taipei',
        };
    }

    protected function weekdayFormToStorageMap(): array
    {
        return [
            'monday' => 'mon',
            'tuesday' => 'tue',
            'wednesday' => 'wed',
            'thursday' => 'thu',
            'friday' => 'fri',
            'saturday' => 'sat',
            'sunday' => 'sun',
        ];
    }

    protected function normalizeWeeklyBreakHours(mixed $input): ?array
    {
        if (! is_array($input)) {
            return null;
        }

        $normalized = [];
        foreach ($this->weekdayFormToStorageMap() as $formWeekday => $storageWeekday) {
            $slot = $input[$formWeekday] ?? null;
            if (! is_array($slot)) {
                continue;
            }

            $start = $this->formatTimeForInput((string) ($slot['start'] ?? ''));
            $end = $this->formatTimeForInput((string) ($slot['end'] ?? ''));

            if ($start === null || $end === null) {
                continue;
            }

            $normalized[$storageWeekday] = [
                'start' => $start,
                'end' => $end,
            ];
        }

        return $normalized === [] ? null : $normalized;
    }

    protected function normalizeWeeklyBusinessHours(mixed $input): ?array
    {
        if (! is_array($input)) {
            return null;
        }

        $normalized = [];
        foreach ($this->weekdayFormToStorageMap() as $formWeekday => $storageWeekday) {
            $slot = $input[$formWeekday] ?? null;
            if (! is_array($slot)) {
                continue;
            }

            $start = $this->formatTimeForInput((string) ($slot['start'] ?? ''));
            $end = $this->formatTimeForInput((string) ($slot['end'] ?? ''));

            if ($start === null || $end === null) {
                continue;
            }

            $normalized[$storageWeekday] = [
                'start' => $start,
                'end' => $end,
            ];
        }

        return $normalized === [] ? null : $normalized;
    }

    protected function weeklyBusinessHoursForForm(Store $store): array
    {
        $raw = is_array($store->weekly_business_hours) ? $store->weekly_business_hours : [];
        $output = [];

        foreach ($this->weekdayFormToStorageMap() as $formWeekday => $storageWeekday) {
            $slot = $raw[$storageWeekday] ?? null;
            $start = is_array($slot) ? $this->formatTimeForInput((string) ($slot['start'] ?? '')) : null;
            $end = is_array($slot) ? $this->formatTimeForInput((string) ($slot['end'] ?? '')) : null;

            $output[$formWeekday] = [
                'start' => $start,
                'end' => $end,
            ];
        }

        return $output;
    }

    protected function weeklyBreakHoursForForm(Store $store): array
    {
        $raw = is_array($store->weekly_break_hours) ? $store->weekly_break_hours : [];
        $output = [];

        foreach ($this->weekdayFormToStorageMap() as $formWeekday => $storageWeekday) {
            $slot = $raw[$storageWeekday] ?? null;
            $start = is_array($slot) ? $this->formatTimeForInput((string) ($slot['start'] ?? '')) : null;
            $end = is_array($slot) ? $this->formatTimeForInput((string) ($slot['end'] ?? '')) : null;

            $output[$formWeekday] = [
                'start' => $start,
                'end' => $end,
            ];
        }

        return $output;
    }

    protected function formatTimeForInput(?string $time): ?string
    {
        if (! is_string($time) || $time === '') {
            return null;
        }

        return substr($time, 0, 5);
    }

    protected function caseInsensitiveLikeOperator(): string
    {
        return DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'like';
    }
}

