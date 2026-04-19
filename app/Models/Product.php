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
        'sort',
        'name',
        'description',
        'price',
        'cost',
        'is_active',
        'is_sold_out',
        'image',
        'option_groups',
        'allow_item_note',
    ];

    protected $casts = [
        'sort' => 'integer',
        'price' => 'integer',
        'cost' => 'integer',
        'is_active' => 'boolean',
        'is_sold_out' => 'boolean',
        'option_groups' => 'array',
        'allow_item_note' => 'boolean',
    ];

    public function store() {
        return $this->belongsTo(Store::class);
    }

    public function category() {
        return $this->belongsTo(Category::class);
    }
}
