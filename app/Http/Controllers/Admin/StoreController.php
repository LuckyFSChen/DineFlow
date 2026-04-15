<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
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

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $data['is_active'] = $request->boolean('is_active');

        // 先建立店家，拿到 id 後才能決定圖片路徑
        $store = Store::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'is_active' => $data['is_active'],
        ]);

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

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $data['is_active'] = $request->boolean('is_active');

        $updateData = [
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'address' => $data['address'] ?? null,
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
            'phone' => ['nullable', 'regex:/^(09\d{2}-\d{3}-\d{3}|09\d{8})$/'],
            'banner_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp'],
        ], [
            'phone.regex' => '電話格式需為 0922333444 或 0922-333-444。',
        ]);

        $data['phone'] = $this->normalizeTaiwanMobilePhone($data['phone'] ?? null);

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
}