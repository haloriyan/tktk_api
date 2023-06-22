<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Package::create([
            'name' => "Starter",
            'price_yearly' => 0,
            'price_monthly' => 0,
            'accent_color' => false,
            'custom_username' => false,
            'remove_logo' => false,
            'personalize_virtual_assistant' => false,
            'product_count' => 10,
            'manual_invoice' => false,
            'product_mass_import' => false,
            'google_analytics' => false,
            'google_ads' => false,
            'facebook_pixel' => false,
            'reporting_period' => 1,
            'download_report' => false,
            'transaction_fee' => 6,
            'custom_virtual_account' => false,
        ]);
        Package::create([
            'name' => "Growth",
            'price_yearly' => 69000,
            'price_monthly' => 790000,
            'accent_color' => true,
            'custom_username' => true,
            'remove_logo' => true,
            'personalize_virtual_assistant' => true,
            'product_count' => 25,
            'manual_invoice' => true,
            'product_mass_import' => false,
            'google_analytics' => true,
            'google_ads' => false,
            'facebook_pixel' => true,
            'reporting_period' => 3,
            'download_report' => false,
            'transaction_fee' => 5,
            'custom_virtual_account' => false,
        ]);
        Package::create([
            'name' => "Enterprise",
            'price_yearly' => 129000,
            'price_monthly' => 1390000,
            'accent_color' => true,
            'custom_username' => true,
            'remove_logo' => true,
            'personalize_virtual_assistant' => true,
            'product_count' => 999,
            'manual_invoice' => true,
            'product_mass_import' => true,
            'google_analytics' => true,
            'google_ads' => true,
            'facebook_pixel' => true,
            'reporting_period' => 12,
            'download_report' => true,
            'transaction_fee' => 6,
            'custom_virtual_account' => true,
        ]);
    }
}
