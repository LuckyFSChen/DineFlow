<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class ProductManagementController extends Controller
{
    public function index(Request $request, Store $store): View
    {
        $this->authorize('update', $store);

        $categories = Category::query()
            ->where('store_id', $store->id)
            ->where('is_active', true)
            ->with(['products' => function ($query) {
                $query->orderBy('sort')->orderBy('id');
            }])
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        $inactiveCategories = Category::query()
            ->where('store_id', $store->id)
            ->where('is_active', false)
            ->withCount('products')
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        $totalProducts = $categories->sum(fn ($category) => $category->products->count());
        $activeProducts = $categories->sum(fn ($category) => $category->products->where('is_active', true)->where('is_sold_out', false)->count());
        $optionEnabledProducts = $categories->sum(fn ($category) => $category->products->filter(fn ($product) => !empty($product->option_groups))->count());

        $categoryOptions = $categories->map(fn ($category) => [
            'id' => $category->id,
            'name' => $category->name,
        ]);

        return view('admin.products.index', compact(
            'store',
            'categories',
            'inactiveCategories',
            'totalProducts',
            'activeProducts',
            'optionEnabledProducts',
            'categoryOptions'
        ));
    }

    public function create(Request $request, Store $store): View
    {
        $this->authorize('update', $store);

        $product = new Product([
            'is_active' => true,
            'is_sold_out' => false,
            'allow_item_note' => false,
            'cost' => 0,
        ]);

        $categories = Category::query()
            ->where('store_id', $store->id)
            ->where('is_active', true)
            ->orderBy('sort')
            ->get();

        return view('admin.products.create', compact('store', 'product', 'categories'));
    }

    public function store(Request $request, Store $store)
    {
        $this->authorize('update', $store);

        $data = $this->validatedData($request, $store, null);

        // New products are always appended to the end of the selected category.
        $maxSortInCategory = Product::query()
            ->where('store_id', $store->id)
            ->where('category_id', $data['category_id'])
            ->max('sort');
        $data['sort'] = ((int) $maxSortInCategory) + 1;

        $product = Product::create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => '商品已建立。',
                'product' => $this->productPayload($product->fresh('category')),
            ]);
        }

        return redirect()
            ->route('admin.stores.products.index', $store)
            ->with('success', '商品已建立。');
    }

    public function edit(Request $request, Store $store, Product $product)
    {
        $this->authorize('update', $store);
        $this->ensureProductBelongsToStore($store, $product);

        $categories = Category::query()
            ->where('store_id', $store->id)
            ->where('is_active', true)
            ->orderBy('sort')
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'product' => $this->productPayload($product->fresh('category')),
                'categories' => $categories->map(fn ($category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                ])->values(),
            ]);
        }

        return view('admin.products.edit', compact('store', 'product', 'categories'));
    }

    public function update(Request $request, Store $store, Product $product)
    {
        $this->authorize('update', $store);
        $this->ensureProductBelongsToStore($store, $product);

        $data = $this->validatedData($request, $store, $product);
        $product->update($data);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => '商品已更新。',
                'product' => $this->productPayload($product->fresh('category')),
            ]);
        }

        return redirect()
            ->route('admin.stores.products.index', $store)
            ->with('success', '商品已更新。');
    }

    public function destroy(Request $request, Store $store, Product $product)
    {
        $this->authorize('update', $store);
        $this->ensureProductBelongsToStore($store, $product);

        $product->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => '商品已刪除。',
            ]);
        }

        return redirect()
            ->route('admin.stores.products.index', $store)
            ->with('success', '商品已刪除。');
    }

    public function storeCategory(Request $request, Store $store): JsonResponse
    {
        $this->authorize('update', $store);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sort' => ['nullable', 'integer', 'min:1'],
        ]);

        $sort = $data['sort'] ?? ((int) Category::query()->where('store_id', $store->id)->max('sort') + 1);

        $category = Category::query()->create([
            'store_id' => $store->id,
            'name' => $data['name'],
            'sort' => max((int) $sort, 1),
            'is_active' => true,
        ]);

        return response()->json([
            'ok' => true,
            'message' => '分類已建立。',
            'category' => $this->categoryPayload($category),
        ]);
    }

    public function editCategory(Request $request, Store $store, Category $category): JsonResponse
    {
        $this->authorize('update', $store);
        $this->ensureCategoryBelongsToStore($store, $category);

        return response()->json([
            'ok' => true,
            'category' => $this->categoryPayload($category),
        ]);
    }

    public function updateCategory(Request $request, Store $store, Category $category): JsonResponse
    {
        $this->authorize('update', $store);
        $this->ensureCategoryBelongsToStore($store, $category);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sort' => ['nullable', 'integer', 'min:1'],
        ]);

        $category->update([
            'name' => $data['name'],
            'sort' => max((int) ($data['sort'] ?? $category->sort ?? 1), 1),
        ]);

        return response()->json([
            'ok' => true,
            'message' => '分類已更新。',
            'category' => $this->categoryPayload($category->fresh()),
        ]);
    }

    public function destroyCategory(Request $request, Store $store, Category $category): JsonResponse
    {
        $this->authorize('update', $store);
        $this->ensureCategoryBelongsToStore($store, $category);

        $hasProducts = Product::query()
            ->where('store_id', $store->id)
            ->where('category_id', $category->id)
            ->exists();

        if ($hasProducts) {
            throw ValidationException::withMessages([
                'category' => __('admin.error_category_has_products'),
            ]);
        }

        $category->delete();

        return response()->json([
            'ok' => true,
            'message' => '分類已刪除。',
        ]);
    }

    public function disableCategory(Request $request, Store $store, Category $category): JsonResponse
    {
        $this->authorize('update', $store);
        $this->ensureCategoryBelongsToStore($store, $category);

        if (! $category->is_active) {
            return response()->json([
                'ok' => true,
                'message' => '分類已是停用狀態。',
            ]);
        }

        $category->update([
            'is_active' => false,
        ]);

        return response()->json([
            'ok' => true,
            'message' => '分類已停用。',
        ]);
    }

    public function enableCategory(Request $request, Store $store, Category $category): JsonResponse
    {
        $this->authorize('update', $store);
        $this->ensureCategoryBelongsToStore($store, $category);

        if ($category->is_active) {
            return response()->json([
                'ok' => true,
                'message' => '分類已是啟用狀態。',
            ]);
        }

        $category->update([
            'is_active' => true,
        ]);

        return response()->json([
            'ok' => true,
            'message' => '分類已重新啟用。',
        ]);
    }

    public function reorder(Request $request, Store $store): JsonResponse
    {
        $this->authorize('update', $store);

        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'distinct'],
        ]);

        $categoryBelongsStore = Category::query()
            ->where('id', $data['category_id'])
            ->where('store_id', $store->id)
            ->exists();

        if (! $categoryBelongsStore) {
            throw ValidationException::withMessages([
                'category_id' => __('admin.error_category_not_belong_store'),
            ]);
        }

        $products = Product::query()
            ->where('store_id', $store->id)
            ->where('category_id', $data['category_id'])
            ->whereIn('id', $data['product_ids'])
            ->get()
            ->keyBy('id');

        if ($products->count() !== count($data['product_ids'])) {
            throw ValidationException::withMessages([
                'product_ids' => __('admin.error_sort_contains_invalid_products'),
            ]);
        }

        foreach ($data['product_ids'] as $index => $productId) {
            $product = $products->get($productId);
            if (! $product) {
                continue;
            }

            $product->sort = $index + 1;
            $product->save();
        }

        return response()->json([
            'ok' => true,
            'message' => '排序已更新。',
        ]);
    }

    public function move(Request $request, Store $store): JsonResponse
    {
        $this->authorize('update', $store);

        $data = $request->validate([
            'moved_product_id' => ['required', 'integer', 'exists:products,id'],
            'source_category_id' => ['required', 'integer', 'exists:categories,id'],
            'target_category_id' => ['required', 'integer', 'exists:categories,id'],
            'source_product_ids' => ['required', 'array'],
            'source_product_ids.*' => ['integer', 'distinct'],
            'target_product_ids' => ['required', 'array'],
            'target_product_ids.*' => ['integer', 'distinct'],
        ]);

        $categoryIds = [(int) $data['source_category_id'], (int) $data['target_category_id']];
        $validCategoriesCount = Category::query()
            ->whereIn('id', $categoryIds)
            ->where('store_id', $store->id)
            ->count();

        if ($validCategoriesCount !== count(array_unique($categoryIds))) {
            throw ValidationException::withMessages([
                'target_category_id' => __('admin.error_category_not_belong_store'),
            ]);
        }

        $movedProduct = Product::query()
            ->where('id', $data['moved_product_id'])
            ->where('store_id', $store->id)
            ->first();

        if (! $movedProduct) {
            throw ValidationException::withMessages([
                'moved_product_id' => __('admin.error_product_not_belong_store'),
            ]);
        }

        if (! in_array((int) $movedProduct->id, array_map('intval', $data['target_product_ids']), true)) {
            throw ValidationException::withMessages([
                'target_product_ids' => __('admin.error_target_sort_missing_dragged_product'),
            ]);
        }

        DB::transaction(function () use ($data, $movedProduct, $store) {
            $sourceCategoryId = (int) $data['source_category_id'];
            $targetCategoryId = (int) $data['target_category_id'];

            if ($sourceCategoryId !== $targetCategoryId) {
                $movedProduct->update([
                    'category_id' => $targetCategoryId,
                ]);
            }

            $this->applyCategorySort($store, $sourceCategoryId, array_map('intval', $data['source_product_ids']));
            if ($sourceCategoryId !== $targetCategoryId) {
                $this->applyCategorySort($store, $targetCategoryId, array_map('intval', $data['target_product_ids']));
                return;
            }

            $this->applyCategorySort($store, $targetCategoryId, array_map('intval', $data['target_product_ids']));
        });

        return response()->json([
            'ok' => true,
            'message' => '分類與排序已更新。',
        ]);
    }

    protected function applyCategorySort(Store $store, int $categoryId, array $productIds): void
    {
        if (count($productIds) === 0) {
            return;
        }

        $products = Product::query()
            ->where('store_id', $store->id)
            ->where('category_id', $categoryId)
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        if ($products->count() !== count($productIds)) {
            throw ValidationException::withMessages([
                'product_ids' => __('admin.error_sort_contains_invalid_products'),
            ]);
        }

        foreach ($productIds as $index => $productId) {
            $product = $products->get($productId);
            if (! $product) {
                continue;
            }

            $product->sort = $index + 1;
            $product->save();
        }
    }

    protected function validatedData(Request $request, Store $store, ?Product $currentProduct = null): array
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            'cost' => ['required', 'integer', 'min:0'],
            'image_upload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp'],
            'remove_image' => ['nullable', 'boolean'],
            'option_groups_json' => ['nullable', 'string'],
            'allow_item_note' => ['nullable', 'boolean'],
        ]);

        $categoryBelongsStore = Category::query()
            ->where('id', $data['category_id'])
            ->where('store_id', $store->id)
            ->exists();

        if (! $categoryBelongsStore) {
            throw ValidationException::withMessages([
                'category_id' => __('admin.error_category_not_belong_store'),
            ]);
        }

        $data['store_id'] = $store->id;
        $data['is_active'] = $request->boolean('is_active');
        $data['is_sold_out'] = $request->boolean('is_sold_out');
        $data['allow_item_note'] = $request->boolean('allow_item_note');
        $data['option_groups'] = $this->parseOptionGroupsJson($data['option_groups_json'] ?? null);

        $hasNewUpload = $request->hasFile('image_upload');
        $shouldRemoveImage = $request->boolean('remove_image');

        if ($hasNewUpload) {
            if ($currentProduct && $this->isLocalStorageImage($currentProduct->image)) {
                Storage::disk('public')->delete(ltrim((string) $currentProduct->image, '/'));
            }

            $data['image'] = $request->file('image_upload')->store('products', 'public');
        } elseif ($shouldRemoveImage) {
            if ($currentProduct && $this->isLocalStorageImage($currentProduct->image)) {
                Storage::disk('public')->delete(ltrim((string) $currentProduct->image, '/'));
            }

            $data['image'] = null;
        } elseif ($currentProduct) {
            unset($data['image']);
        } else {
            $data['image'] = null;
        }

        unset($data['image_upload'], $data['remove_image']);
        unset($data['option_groups_json']);

        return $data;
    }

    protected function isLocalStorageImage(?string $path): bool
    {
        if (! $path) {
            return false;
        }

        return ! Str::startsWith($path, ['http://', 'https://']);
    }

    protected function parseOptionGroupsJson(?string $raw): ?array
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'option_groups_json' => __('admin.error_option_groups_json_invalid'),
            ]);
        }

        return $decoded;
    }

    protected function ensureProductBelongsToStore(Store $store, Product $product): void
    {
        if ((int) $product->store_id !== (int) $store->id) {
            abort(404);
        }
    }

    protected function ensureCategoryBelongsToStore(Store $store, Category $category): void
    {
        if ((int) $category->store_id !== (int) $store->id) {
            abort(404);
        }
    }

    protected function categoryPayload(Category $category): array
    {
        return [
            'id' => $category->id,
            'store_id' => $category->store_id,
            'name' => $category->name,
            'sort' => (int) ($category->sort ?? 1),
            'is_active' => (bool) ($category->is_active ?? true),
        ];
    }

    protected function productPayload(Product $product): array
    {
        return [
            'id' => $product->id,
            'store_id' => $product->store_id,
            'category_id' => $product->category_id,
            'category_name' => $product->category?->name,
            'name' => $product->name,
            'description' => $product->description,
            'price' => (int) $product->price,
            'cost' => (int) ($product->cost ?? 0),
            'gross_margin_rate' => (int) $product->price > 0
                ? round((((int) $product->price - (int) ($product->cost ?? 0)) / (int) $product->price) * 100, 1)
                : 0,
            'sort' => (int) ($product->sort ?? 1),
            'image' => $product->image,
            'image_url' => $this->resolveImageUrl($product->image),
            'is_active' => (bool) $product->is_active,
            'is_sold_out' => (bool) $product->is_sold_out,
            'allow_item_note' => (bool) $product->allow_item_note,
            'option_groups' => $product->option_groups,
            'option_groups_json' => $product->option_groups
                ? json_encode($product->option_groups, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : '[]',
        ];
    }

    protected function resolveImageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return asset('storage/' . ltrim($path, '/'));
    }
}
