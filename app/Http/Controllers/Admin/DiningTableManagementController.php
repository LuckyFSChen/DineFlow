<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DiningTableManagementController extends Controller
{
    public function index(Request $request, Store $store)
    {
        $this->authorizeStoreAccess($request, $store);

        $takeoutMenuUrl = route('customer.takeout.menu', ['store' => $store->slug]);
        $takeoutQrSvg = null;
        if ($store->takeout_qr_enabled) {
            $takeoutQrSvg = QrCode::format('svg')
                ->size(220)
                ->margin(1)
                ->generate($takeoutMenuUrl);
        }

        $tables = $this->dineInTablesQuery($store)
            ->orderBy('table_no')
            ->get()
            ->map(function (DiningTable $table) use ($store) {
                $menuUrl = route('customer.dinein.menu', [
                    'store' => $store->slug,
                    'table' => $table->qr_token,
                ]);

                $table->menu_url = $menuUrl;
                $table->qr_svg = QrCode::format('svg')
                    ->size(180)
                    ->margin(1)
                    ->generate($menuUrl);

                return $table;
            });

        return view('admin.stores.tables.index', [
            'store' => $store,
            'tables' => $tables,
            'takeoutMenuUrl' => $takeoutMenuUrl,
            'takeoutQrSvg' => $takeoutQrSvg,
        ]);
    }

    public function updateTakeoutQr(Request $request, Store $store): RedirectResponse
    {
        $this->authorizeStoreAccess($request, $store);

        $store->update([
            'takeout_qr_enabled' => $request->boolean('takeout_qr_enabled'),
        ]);

        return redirect()
            ->route('admin.stores.tables.index', $store)
            ->with('success', $store->takeout_qr_enabled ? '已開放外帶 QR Code。' : '已關閉外帶 QR Code。');
    }

    public function print(Request $request, Store $store)
    {
        $this->authorizeStoreAccess($request, $store);

        $validated = $request->validate([
            'table_ids' => ['nullable', 'array'],
            'table_ids.*' => ['integer'],
            'include_takeout' => ['nullable', 'boolean'],
        ]);

        $tableIds = collect($validated['table_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $tables = $this->dineInTablesQuery($store)
            ->when($tableIds->isNotEmpty(), fn ($query) => $query->whereIn('id', $tableIds->all()))
            ->orderBy('table_no')
            ->get()
            ->map(function (DiningTable $table) use ($store) {
                $menuUrl = route('customer.dinein.menu', [
                    'store' => $store->slug,
                    'table' => $table->qr_token,
                ]);

                $table->menu_url = $menuUrl;
                $table->qr_svg = QrCode::format('svg')
                    ->size(130)
                    ->margin(1)
                    ->generate($menuUrl);

                return $table;
            });

        $takeout = null;
        $includeTakeout = $store->takeout_qr_enabled && (bool) ($validated['include_takeout'] ?? false);
        if ($includeTakeout) {
            $takeoutMenuUrl = route('customer.takeout.menu', ['store' => $store->slug]);
            $takeout = [
                'table_no' => __('admin.takeout_exclusive'),
                'menu_url' => $takeoutMenuUrl,
                'qr_svg' => QrCode::format('svg')
                    ->size(130)
                    ->margin(1)
                    ->generate($takeoutMenuUrl),
            ];
        }

        return view('admin.stores.tables.print', [
            'store' => $store,
            'tables' => $tables,
            'takeout' => $takeout,
        ]);
    }

    public function store(Request $request, Store $store): RedirectResponse
    {
        $this->authorizeStoreAccess($request, $store);

        $validated = $request->validate([
            'table_no' => [
                'required',
                'string',
                'max:50',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->isReservedTableNo((string) $value)) {
                        $fail(__('admin.error_table_no_reserved'));
                    }
                },
                Rule::unique('dining_tables', 'table_no')
                    ->where(fn ($query) => $query->where('store_id', $store->id)->whereNull('deleted_at')),
            ],
        ], [
            'table_no.required' => __('admin.error_table_no_required'),
            'table_no.unique' => __('admin.error_table_no_unique'),
        ]);

        DiningTable::query()->create([
            'store_id' => $store->id,
            'table_no' => trim($validated['table_no']),
            'qr_token' => $this->generateUniqueQrToken(),
            'status' => 'available',
        ]);

        return redirect()
            ->route('admin.stores.tables.index', $store)
            ->with('success', '桌位已新增，QR Code 已建立。');
    }

    public function updateStatus(Request $request, Store $store, DiningTable $table): RedirectResponse
    {
        $this->authorizeStoreAccess($request, $store);
        $this->ensureTableBelongsToStore($store, $table);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['available', 'inactive'])],
        ]);

        $table->update([
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('admin.stores.tables.index', $store)
            ->with('success', '桌位狀態已更新。');
    }

    public function regenerateQr(Request $request, Store $store, DiningTable $table): RedirectResponse
    {
        $this->authorizeStoreAccess($request, $store);
        $this->ensureTableBelongsToStore($store, $table);

        $table->update([
            'qr_token' => $this->generateUniqueQrToken(),
        ]);

        return redirect()
            ->route('admin.stores.tables.index', $store)
            ->with('success', '已重新產生該桌位 QR Code。');
    }

    private function ensureTableBelongsToStore(Store $store, DiningTable $table): void
    {
        if ((int) $table->store_id !== (int) $store->id) {
            abort(404);
        }
    }

    private function authorizeStoreAccess(Request $request, Store $store): void
    {
        $user = $request->user();
        if (! $user) {
            abort(403, __('admin.error_login_required'));
        }

        if ($user->isAdmin()) {
            return;
        }

        if ($user->isMerchant() && (int) $store->user_id === (int) $user->id) {
            return;
        }

        abort(403, __('admin.error_cannot_manage_store'));
    }

    private function generateUniqueQrToken(): string
    {
        do {
            $token = Str::lower((string) Str::ulid());
        } while (DiningTable::query()->where('qr_token', $token)->exists());

        return $token;
    }

    private function dineInTablesQuery(Store $store)
    {
        return DiningTable::query()
            ->where('store_id', $store->id)
            ->whereNotIn(DB::raw('LOWER(table_no)'), ['takeout', '外帶', '外带']);
    }

    private function isReservedTableNo(string $tableNo): bool
    {
        return in_array(
            $this->normalizeTableNo($tableNo),
            ['takeout', '外帶', '外带'],
            true
        );
    }

    private function normalizeTableNo(string $tableNo): string
    {
        $normalized = trim(mb_strtolower($tableNo, 'UTF-8'));

        return preg_replace('/[\s\-_]+/u', '', $normalized) ?? $normalized;
    }
}
