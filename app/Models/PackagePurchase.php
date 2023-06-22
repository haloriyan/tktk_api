<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackagePurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'package_id', 'discount_id',
        'payment_url', 'payment_code', 'payment_status', 'price', 'discount_amount', 'total_pay',
        'expiry', 
    ];

    public function package() {
        return $this->belongsTo(Package::class, 'package_id');
    }
    public function coupon() {
        return $this->belongsTo(PackageDiscount::class, 'discount_id');
    }
    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }
}
