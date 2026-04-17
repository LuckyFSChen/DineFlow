<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Support\GooglePlaceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->get('keyword');

        $stores = Store::query()
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

        return view('admin.stores.index', compact('stores', 'keyword'));
    }

    public function create()
    {
        $store = new Store();

        return view('admin.stores.create', compact('store'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data = $this->fillCoordinatesFromAddress($data);

        $maxAttempts = 10;
        $attempt = 0;
        do {
            try {
                if (empty($data['slug'])) {
                    $baseSlug = Str::slug($data['name']);
                    $slug = $baseSlug;
                    $i = 1;
                    while (Store::where('slug', $slug)->exists()) {
                        $slug = $baseSlug . '-' . $i;
                        $i++;
                    }
                    $data['slug'] = $slug;
                }
                $data['is_active'] = $request->boolean('is_active');
                $store = Store::create([
                    'name' => $data['name'],
                    'slug' => $data['slug'],
                    'description' => $data['description'] ?? null,
                    'address' => $data['address'] ?? null,
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'timezone' => $data['timezone'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'is_active' => $data['is_active'],
                    'takeout_qr_enabled' => true,
                ]);
                break;
            } catch (\Illuminate\Database\QueryException $e) {
                if (str_contains($e->getMessage(), 'store_slug_unique') && $attempt < $maxAttempts) {
                    $baseSlug = Str::slug($data['name']);
                    $i = $attempt + 1;
                    $data['slug'] = $baseSlug . '-' . $i;
                    $attempt++;
                } else {
                    throw $e;
                }
            }
        } while ($attempt < $maxAttempts);

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

        return redirect()
            ->route('admin.stores.index')
            ->with('success', '店家建立成功');
    }

    public function edit(Store $store)
    {
        return view('admin.stores.edit', compact('store'));
    }

    public function update(Request $request, Store $store)
    {
        $data = $this->validatedData($request, $store->id);
        $data = $this->fillCoordinatesFromAddress($data, $store);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $data['is_active'] = $request->boolean('is_active');

        $updateData = [
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'address' => $data['address'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'timezone' => $data['timezone'] ?? null,
            'phone' => $data['phone'] ?? null,
            'is_active' => $data['is_active'],
        ];

        if ($request->hasFile('banner_image')) {
            $file = $request->file('banner_image');
            $extension = strtolower($file->getClientOriginalExtension()) ?: 'jpg';

            // 先刪除舊 banner，避免殘留不同副檔名檔案
            if (!empty($store->banner_image)) {
                Storage::disk('public')->delete($store->banner_image);
            }

            // 再額外清一次整個 banner 資料夾，避免 jpg/png/webp 混留
            Storage::disk('public')->deleteDirectory("stores/{$store->id}/banner");

            $path = $file->storeAs(
                "stores/{$store->id}/banner",
                "banner.{$extension}",
                'public'
            );

            $updateData['banner_image'] = $path;
        }

        $store->update($updateData);

        return redirect()
            ->route('admin.stores.index')
            ->with('success', '店家更新成功');
    }

    public function destroy(Store $store)
    {
        $store->delete();

        return redirect()
            ->route('admin.stores.index')
            ->with('success', '店家已刪除');
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
            'timezone' => ['nullable', 'timezone'],
            'country_code' => ['nullable', 'in:tw,vn,cn,us'],
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
            'banner_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp'],
        ]);

        $countryCode = strtolower((string) ($data['country_code'] ?? $request->input('country_code', 'tw')));
        $data['country_code'] = $countryCode;
        $data['phone'] = $this->normalizePhoneByCountry($data['phone'] ?? null, $countryCode);
        $data['latitude'] = $this->normalizeCoordinate($data['latitude'] ?? null);
        $data['longitude'] = $this->normalizeCoordinate($data['longitude'] ?? null);
        $data['timezone'] = trim((string) ($data['timezone'] ?? '')) ?: null;

        return $data;
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
}