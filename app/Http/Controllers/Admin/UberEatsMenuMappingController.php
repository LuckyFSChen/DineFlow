<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\UberEatsApiException;
use App\Http\Controllers\Controller;
use App\Models\ExternalProductMapping;
use App\Models\Product;
use App\Models\Store;
use App\Services\UberEatsApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UberEatsMenuMappingController extends Controller
{
    private const PLATFORM = 'uber_eats';

    public function index(Request $request, Store $store): View
    {
        $this->authorize('update', $store);

        $products = Product::query()
            ->where('store_id', $store->id)
            ->with('category:id,name')
            ->orderBy('name')
            ->get(['id', 'category_id', 'name', 'price', 'is_active', 'is_sold_out']);

        $mappings = ExternalProductMapping::query()
            ->where('store_id', $store->id)
            ->where('platform', self::PLATFORM)
            ->with('product:id,name,price,category_id')
            ->orderByRaw('product_id is null desc')
            ->orderBy('external_category_name')
            ->orderBy('external_item_name')
            ->get();

        $mappedCount = $mappings->whereNotNull('product_id')->count();
        $unmappedCount = $mappings->whereNull('product_id')->count();

        return view('admin.integrations.uber-eats-menu-mapping', compact(
            'store',
            'products',
            'mappings',
            'mappedCount',
            'unmappedCount'
        ));
    }

    public function sync(Request $request, Store $store, UberEatsApiClient $client): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $store);

        if (! $store->hasUberEatsApiCredentials()) {
            return $this->respond($request, false, __('uber_eats.credentials_required'));
        }

        try {
            $payload = $client->fetchMenu($store, (string) $store->uber_eats_store_id);
            $items = $this->extractMenuItems($payload);
        } catch (UberEatsApiException $e) {
            if ($e->isUnauthorized()) {
                return $this->respond($request, false, __('uber_eats.sync_unauthorized', [
                    'status' => $e->status(),
                    'store_id' => (string) $store->uber_eats_store_id,
                ]));
            }

            return $this->respond($request, false, __('uber_eats.sync_failed', ['message' => $e->getMessage()]));
        } catch (\Throwable $e) {
            return $this->respond($request, false, __('uber_eats.sync_failed', ['message' => $e->getMessage()]));
        }

        if ($items->isEmpty()) {
            return $this->respond($request, false, __('uber_eats.sync_empty'));
        }

        $now = now();
        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($store, $items, $now, &$created, &$updated): void {
            foreach ($items as $item) {
                $mapping = ExternalProductMapping::query()->firstOrNew([
                    'store_id' => $store->id,
                    'platform' => self::PLATFORM,
                    'external_item_id' => $item['external_item_id'],
                ]);

                if (! $mapping->exists) {
                    $mapping->product_id = $this->guessProductId($store, (string) $item['external_item_name']);
                    $created++;
                } else {
                    $updated++;
                }

                $mapping->fill([
                    'external_item_name' => $item['external_item_name'],
                    'external_category_id' => $item['external_category_id'],
                    'external_category_name' => $item['external_category_name'],
                    'external_price' => $item['external_price'],
                    'external_currency' => $item['external_currency'],
                    'external_payload' => $item['external_payload'],
                    'last_seen_at' => $now,
                ]);
                $mapping->save();
            }
        });

        return $this->respond($request, true, __('uber_eats.sync_success', [
            'created' => $created,
            'updated' => $updated,
        ]), true);
    }

    public function update(Request $request, Store $store): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $store);

        if (is_array($request->input('mappings'))) {
            $request->merge([
                'mappings' => collect($request->input('mappings'))
                    ->map(fn ($value) => $value === '' ? null : $value)
                    ->all(),
            ]);
        }

        $data = $request->validate([
            'mappings' => ['nullable', 'array'],
            'mappings.*' => ['nullable', 'integer'],
        ]);

        $productIds = Product::query()
            ->where('store_id', $store->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $allowedProductIds = array_flip($productIds);
        $updates = $data['mappings'] ?? [];
        $changed = 0;

        DB::transaction(function () use ($store, $updates, $allowedProductIds, &$changed): void {
            foreach ($updates as $mappingId => $productId) {
                $mapping = ExternalProductMapping::query()
                    ->where('store_id', $store->id)
                    ->where('platform', self::PLATFORM)
                    ->whereKey((int) $mappingId)
                    ->first();

                if (! $mapping) {
                    continue;
                }

                $normalizedProductId = $productId !== null && $productId !== '' ? (int) $productId : null;
                if ($normalizedProductId !== null && ! isset($allowedProductIds[$normalizedProductId])) {
                    continue;
                }

                if ((int) ($mapping->product_id ?? 0) !== (int) ($normalizedProductId ?? 0)) {
                    $mapping->update(['product_id' => $normalizedProductId]);
                    $changed++;
                }
            }
        });

        return $this->respond($request, true, __('uber_eats.save_success', ['changed' => $changed]));
    }

    private function respond(Request $request, bool $ok, string $message, bool $reload = false): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => $ok,
                'message' => $message,
                'reload' => $reload,
            ]);
        }

        return back()->with($ok ? 'success' : 'error', $message);
    }

    /**
     * @param array<string, mixed> $payload
     * @return Collection<int, array<string, mixed>>
     */
    private function extractMenuItems(array $payload): Collection
    {
        $categoriesById = $this->collectCategories($payload);
        $items = collect();

        foreach ($this->walkArrays($payload) as $node) {
            $id = $this->extractItemId($node);
            $name = $this->extractItemName($node);

            if ($id === null || $name === null || ! $this->looksLikeMenuItem($node)) {
                continue;
            }

            $categoryId = $this->extractCategoryId($node);
            $categoryName = $categoryId !== null ? ($categoriesById[$categoryId] ?? null) : null;

            $items->put($id, [
                'external_item_id' => $id,
                'external_item_name' => $name,
                'external_category_id' => $categoryId,
                'external_category_name' => $categoryName,
                'external_price' => $this->extractPrice($node),
                'external_currency' => $this->extractCurrency($node),
                'external_payload' => $node,
            ]);
        }

        return $items->values();
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, string>
     */
    private function collectCategories(array $payload): array
    {
        $categories = [];

        foreach ($this->walkArrays($payload) as $node) {
            $id = trim((string) (Arr::get($node, 'id') ?? Arr::get($node, 'category_id') ?? ''));
            $name = $this->extractLocalizedText(Arr::get($node, 'title'))
                ?? $this->extractLocalizedText(Arr::get($node, 'name'));

            if ($id !== '' && $name !== null && $this->nodeHasAnyKey($node, ['entities', 'items', 'item_ids', 'category'])) {
                $categories[$id] = $name;
            }
        }

        return $categories;
    }

    /**
     * @param array<string, mixed> $node
     */
    private function looksLikeMenuItem(array $node): bool
    {
        return $this->nodeHasAnyKey($node, ['price_info', 'price', 'tax_info', 'modifier_group_ids', 'modifier_groups', 'suspension_info', 'quantity_info'])
            && ! $this->nodeHasAnyKey($node, ['items', 'item_ids']);
    }

    /**
     * @param array<string, mixed> $node
     * @param array<int, string> $keys
     */
    private function nodeHasAnyKey(array $node, array $keys): bool
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $node)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $node
     */
    private function extractItemId(array $node): ?string
    {
        $id = trim((string) (Arr::get($node, 'id') ?? Arr::get($node, 'item_id') ?? Arr::get($node, 'entity_id') ?? ''));

        return $id !== '' ? $id : null;
    }

    /**
     * @param array<string, mixed> $node
     */
    private function extractItemName(array $node): ?string
    {
        return $this->extractLocalizedText(Arr::get($node, 'title'))
            ?? $this->extractLocalizedText(Arr::get($node, 'name'));
    }

    /**
     * @param array<string, mixed> $node
     */
    private function extractCategoryId(array $node): ?string
    {
        $id = trim((string) (Arr::get($node, 'category_id') ?? Arr::get($node, 'parent_category_id') ?? ''));

        return $id !== '' ? $id : null;
    }

    /**
     * @param array<string, mixed> $node
     */
    private function extractPrice(array $node): ?int
    {
        foreach ([
            'price_info.price',
            'price_info.price.amount',
            'price.amount',
            'price',
        ] as $path) {
            $value = Arr::get($node, $path);
            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $node
     */
    private function extractCurrency(array $node): ?string
    {
        $currency = trim((string) (Arr::get($node, 'price_info.currency_code') ?? Arr::get($node, 'price.currency_code') ?? ''));

        return $currency !== '' ? strtoupper($currency) : null;
    }

    private function extractLocalizedText(mixed $value): ?string
    {
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        if (! is_array($value)) {
            return null;
        }

        foreach (['translations.en_us', 'translations.en', 'translations.zh_tw', 'text', 'default_text', 'value'] as $path) {
            $candidate = Arr::get($value, $path);
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        foreach ($value as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<int, array<string, mixed>>
     */
    private function walkArrays(array $payload): array
    {
        $nodes = [];
        $stack = [$payload];

        while ($stack !== []) {
            $node = array_pop($stack);
            if (! is_array($node)) {
                continue;
            }

            if (array_is_list($node)) {
                foreach ($node as $child) {
                    if (is_array($child)) {
                        $stack[] = $child;
                    }
                }

                continue;
            }

            $nodes[] = $node;

            foreach ($node as $child) {
                if (is_array($child)) {
                    $stack[] = $child;
                }
            }
        }

        return $nodes;
    }

    private function guessProductId(Store $store, string $externalName): ?int
    {
        $targetKeys = $this->lookupKeys($externalName);

        if ($targetKeys === []) {
            return null;
        }

        $products = Product::query()
            ->where('store_id', $store->id)
            ->get(['id', 'name']);

        foreach ($products as $product) {
            if (array_intersect($targetKeys, $this->lookupKeys((string) $product->name)) !== []) {
                return (int) $product->id;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function lookupKeys(string $value): array
    {
        $lower = mb_strtolower(trim($value), 'UTF-8');
        if ($lower === '') {
            return [];
        }

        return array_values(array_unique(array_filter([
            preg_replace('/\s+/u', ' ', $lower) ?? $lower,
            preg_replace('/[\s\-_]+/u', '', $lower) ?? $lower,
            Str::ascii($lower),
        ])));
    }
}
