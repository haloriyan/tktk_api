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
        Schema::create('package_discounts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('affiliator_id')->unsigned()->index()->nullable();
            $table->foreign('affiliator_id')->references('id')->on('affiliators')->onDelete('set null');
            $table->bigInteger('package_id')->unsigned()->index()->nullable();
            $table->foreign('package_id')->references('id')->on('packages')->onDelete('set null');
            $table->string('promo_code', 32);
            $table->integer('discount_amount'); // in percent (%), ~1-15
            $table->dateTime('expiration_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_discounts');
    }
};
