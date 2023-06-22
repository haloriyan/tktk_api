<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'user_id', 'code', 'total_weight', 'has_withdrawn', 'total_pay',
        'payment_status'
    ];

    public function customer() {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function items() {
        return $this->hasMany(CustomerOrderItem::class, 'order_id');
    }
}
