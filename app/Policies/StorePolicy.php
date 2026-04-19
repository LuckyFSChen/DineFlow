<?php

namespace App\Policies;

use App\Models\Store;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StorePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isMerchant();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isMerchant();
    }

    public function view(User $user, Store $store): Response
    {
        if ($this->canManageStore($user, $store)) {
            return Response::allow();
        }

        return $this->denyManageStore();
    }

    public function update(User $user, Store $store): Response
    {
        return $this->view($user, $store);
    }

    public function delete(User $user, Store $store): Response
    {
        return $this->view($user, $store);
    }

    public function manageChefs(User $user, Store $store): Response
    {
        return $this->view($user, $store);
    }

    public function viewKitchen(User $user, Store $store): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($user->isMerchant() && (int) $store->user_id === (int) $user->id) {
            return Response::allow();
        }

        return $this->denyManageStore();
    }

    public function viewCashier(User $user, Store $store): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($user->isMerchant() && (int) $store->user_id === (int) $user->id) {
            return Response::allow();
        }

        if ($user->isCashier() && (int) $user->store_id === (int) $store->id) {
            return Response::allow();
        }

        return $this->denyManageStore();
    }

    public function viewBoards(User $user, Store $store): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($user->isMerchant() && (int) $store->user_id === (int) $user->id) {
            return Response::allow();
        }

        if (($user->isChef() || $user->isCashier()) && (int) $user->store_id === (int) $store->id) {
            return Response::allow();
        }

        return $this->denyManageStore();
    }

    private function canManageStore(User $user, Store $store): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isMerchant() && (int) $store->user_id === (int) $user->id;
    }

    private function denyManageStore(): Response
    {
        return Response::deny(__('admin.error_cannot_manage_store'));
    }
}
