<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    //
    protected $fillable = [
        'order_id',
        'order_date',
        'customer_email',
    ];

    public function orderInfo()
    {
        return $this->hasMany(OrderInfo::class);
    }
}
