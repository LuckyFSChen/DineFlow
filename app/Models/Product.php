<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'category_id',
        'name',
        'description',
        'price',
        'is_active',
        'is_sold_out',
        'image',
        'option_groups',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_sold_out' => 'boolean',
        'option_groups' => 'array',
    ];

    public function store() {
        return $this->belongsTo(Store::class);
    }

    public function category() {
        return $this->belongsTo(Category::class);
    }
}
