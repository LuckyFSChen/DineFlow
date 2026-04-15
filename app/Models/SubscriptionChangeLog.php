<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionChangeLog extends Model
{
    protected $fillable = [
        'admin_user_id',
        'merchant_user_id',
        'store_names_snapshot',
        'old_plan_id',
        'old_plan_name',
        'old_status',
        'old_subscription_ends_at',
        'new_plan_id',
        'new_plan_name',
        'new_status',
        'new_subscription_ends_at',
        'action',
    ];

    protected function casts(): array
    {
        return [
            'old_subscription_ends_at' => 'datetime',
            'new_subscription_ends_at' => 'datetime',
        ];
    }

    public function adminUser()
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function merchantUser()
    {
        return $this->belongsTo(User::class, 'merchant_user_id');
    }

    public function oldPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'old_plan_id');
    }

    public function newPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'new_plan_id');
    }
}
