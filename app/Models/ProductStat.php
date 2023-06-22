<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'customer_id', 'hit', 'user_id'
    ];

    public function customer() {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
    public function product() {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
