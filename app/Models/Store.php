<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'address',
        'contact_email',
        'notification_email',
        'is_active',
    ];

    protected static function booted()
    {
        static::creating(function ($store){
            $store->slug = "temp";
        });

        static::created(function ($store){
            $store->slug = 'store-' . $store->id;
            $store->saveQuietly();
        });
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }    

    public function tables() {
        return $this->hasMany(DiningTable::class);
    }

    public function categories() {
        return $this->hasMany(Category::class);
    }

    public function products() {
        return $this->hasMany(Product::class);
    }

    public function orders() {
        return $this->hasMany(Order::class);
    }
}
