<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'name',
        'sort',
        'is_active',
    ];

    public function store() {
        return $this->belongsTo(Store::class);
    }

    public function products() {
        return $this->hasMany(Product::class);
    }
}
