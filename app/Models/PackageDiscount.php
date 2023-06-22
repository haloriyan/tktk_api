<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageDiscount extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id', 'affiliator_id',
        'promo_code', 'discount_amount', 'expiration_date'
    ];

    public function package() {
        return $this->belongsTo(Package::class, 'package_id');
    }
    public function affilator() {
        return $this->belongsTo(Affilator::class, 'affiliator_id');
    }
}
