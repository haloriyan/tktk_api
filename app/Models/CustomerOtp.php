<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerOtp extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'code', 'has_used', 'expiry'
    ];
}
