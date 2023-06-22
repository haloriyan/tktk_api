<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->bigInteger('price_monthly');
            $table->bigInteger('price_yearly');

            $table->boolean('accent_color');
            $table->boolean('custom_username');
            $table->boolean('remove_logo');
            $table->boolean('personalize_virtual_assistant');

            $table->integer('product_count');
            $table->boolean('manual_invoice');
            $table->boolean('product_mass_import');

            $table->boolean('google_analytics');
            $table->boolean('google_ads');
            $table->boolean('facebook_pixel');

            $table->integer('reporting_period'); // month
            $table->boolean('download_report');

            $table->integer('transaction_fee');
            $table->boolean('custom_virtual_account');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
