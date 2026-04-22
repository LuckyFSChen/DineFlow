<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait ResolvesAccessibleStores
{
    protected function accessibleStoresQuery(User $user): Builder
    {
        $query = Store::query();

        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->isMerchant()) {
            return $query->where('user_id', $user->id);
        }

        return $query->whereRaw('1 = 0');
    }

    protected function resolveAccessibleStore(Request $request, int $storeId): Store
    {
        $store = $this->accessibleStoresQuery($request->user())
            ->whereKey($storeId)
            ->first();

        if (! $store) {
            abort(404);
        }

        return $store;
    }
}
