<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'customer_id', 'interest_product_id', 'sender',
        'body', 'stemmed_body', 'actions', 'context'
    ];

    public function product() {
        return $this->belongsTo(Product::class, 'interest_product_id');
    }
}
