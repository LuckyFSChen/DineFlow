<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::updated(function (User $user): void {
            if (! $user->isMerchant()) {
                return;
            }

            if (! ($user->wasChanged('subscription_plan_id') || $user->wasChanged('subscription_ends_at'))) {
                return;
            }

            $admin = Auth::user();
            if (! ($admin instanceof User) || ! $admin->isAdmin()) {
                return;
            }

            $oldPlanId = $user->getOriginal('subscription_plan_id');
            $oldEndsAtRaw = $user->getOriginal('subscription_ends_at');
            $oldEndsAt = $oldEndsAtRaw ? Carbon::parse($oldEndsAtRaw) : null;
            $oldStatus = $oldEndsAt && $oldEndsAt->greaterThanOrEqualTo(now()->startOfDay())
                ? 'active'
                : 'inactive';

            $oldPlanName = null;
            if ($oldPlanId !== null) {
                $oldPlanName = SubscriptionPlan::query()->whereKey($oldPlanId)->value('name');
            }

            $action = request()?->input('action');
            if (! in_array($action, ['activate', 'expire'], true)) {
                $action = $user->hasActiveSubscription() ? 'activate' : 'expire';
            }

            SubscriptionChangeLog::query()->create([
                'admin_user_id' => $admin->id,
                'merchant_user_id' => $user->id,
                'store_names_snapshot' => $user->stores()->orderBy('id')->pluck('name')->implode(', ') ?: null,
                'old_plan_id' => $oldPlanId,
                'old_plan_name' => $oldPlanName,
                'old_status' => $oldStatus,
                'old_subscription_ends_at' => $oldEndsAt,
                'new_plan_id' => $user->subscription_plan_id,
                'new_plan_name' => $user->subscriptionPlan?->name,
                'new_status' => $user->hasActiveSubscription() ? 'active' : 'inactive',
                'new_subscription_ends_at' => $user->subscription_ends_at,
                'action' => $action,
            ]);
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'merchant_region',
        'subscription_ends_at',
        'subscription_plan_id',
        'trial_started_at',
        'trial_ends_at',
        'trial_used_at',
        'store_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'subscription_ends_at' => 'datetime',
            'trial_started_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'trial_used_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isMerchant(): bool
    {
        return $this->role === 'merchant';
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    public function isChef(): bool
    {
        return $this->role === 'chef';
    }

    public function isCashier(): bool
    {
        return $this->role === 'cashier';
    }

    public function hasActiveSubscription(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->isMerchant()
            && $this->subscription_ends_at !== null
            && $this->subscription_ends_at->greaterThanOrEqualTo(now()->startOfDay());
    }

    public function canStartTrial(): bool
    {
        return $this->isMerchant() && $this->trial_used_at === null;
    }

    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function stores()
    {
        return $this->hasMany(Store::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function maxAllowedStores(): ?int
    {
        if ($this->isAdmin()) {
            return null;
        }

        return $this->subscriptionPlan?->max_stores;
    }

    public function subscriptionCurrencyCode(): string
    {
        return match (strtolower((string) $this->merchant_region)) {
            'cn' => 'cny',
            'vn' => 'vnd',
            default => 'twd',
        };
    }

    public function merchantRegionCode(): string
    {
        return match (strtolower((string) $this->merchant_region)) {
            'cn' => 'cn',
            'vn' => 'vn',
            default => 'tw',
        };
    }
}
