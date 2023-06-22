<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'price_monthly', 'price_yearly',
        'accent_color', 'custom_username', 'remove_logo', 'personalize_virtual_assistant',
        'product_count', 'manual_invoice', 'product_mass_import',
        'google_analytics', 'google_ads', 'facebook_pixel', 'facebook_ads',
        'reporting_period', 'download_report',
        'transaction_fee', 'custom_virtual_account'
    ];
}
