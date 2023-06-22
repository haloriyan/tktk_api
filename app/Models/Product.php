<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'description', 'price', 'slug', 'is_service', 'type',
        'dimension', 'weight', 'quantity', 'is_visible',
        'filename', 'download_limit', 'download_expiration',
        'quantity'
    ];

    public function images() {
        return $this->hasMany(ProductImage::class, 'product_id');
    }
    public function image() {
        return $this->hasOne(ProductImage::class, 'product_id');
    }
    public function additionals() {
        return $this->hasMany(ProductAdditional::class, 'product_id');
    }
    public function schedules() {
        return $this->hasMany(ProductSchedule::class, 'product_id');
    }
    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }
}
