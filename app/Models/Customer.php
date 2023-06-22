<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'email', 'phone', 'address',
        'province', 'city', 'subdistrict', 'token'
    ];

    public function orders() {
        return $this->hasMany(CustomerOrder::class, 'customer_id');
    }
}
