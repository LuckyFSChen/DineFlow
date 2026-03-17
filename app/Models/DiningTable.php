<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiningTable extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'table_no',
        'qr_token',
        'status',
    ];

    public function store() {
        return $this->belongsTo(Store::class);
    }

    public function orders() {
        return $this->hasMany(Order::class);
    }
}
