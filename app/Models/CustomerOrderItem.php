<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'customer_id', 'user_id', 'product_id', 
        'price', 'quantity', 'total_price'
    ];

    public function product() {
        return $this->belongsTo(\App\Models\Product::class, 'product_id');
    }
}
