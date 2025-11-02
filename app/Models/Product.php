<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'sku',
        'name',
        'price',
    ];

    public function orderInfo()
    {
        return $this->hasMany(OrderInfo::class);
    }
}
