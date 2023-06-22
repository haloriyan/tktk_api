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
        Schema::create('package_purchases', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->bigInteger('package_id')->unsigned()->index();
            $table->foreign('package_id')->references('id')->on('packages')->onDelete('cascade');
            $table->bigInteger('discount_id')->unsigned()->index()->nullable();
            $table->foreign('discount_id')->references('id')->on('package_discounts')->onDelete('set null');
            $table->string('payment_url')->nullable();
            $table->string('payment_code')->nullable();
            $table->string('payment_status')->nullable();
            
            $table->bigInteger('price');
            $table->bigInteger('discount_amount')->nullable();
            $table->bigInteger('total_pay');
            $table->dateTime('expiry');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_purchases');
    }
};
