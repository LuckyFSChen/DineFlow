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
        'prep_time_minutes',
        'is_active',
    ];

    protected $casts = [
        'prep_time_minutes' => 'integer',
        'is_active' => 'boolean',
    ];

    public function store() {
        return $this->belongsTo(Store::class);
    }

    public function products() {
        return $this->hasMany(Product::class);
    }
}
