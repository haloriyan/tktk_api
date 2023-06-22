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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('package_id')->unsigned()->index()->nullable();
            $table->foreign('package_id')->references('id')->on('packages')->onDelete('set null');
            $table->string('name');
            $table->string('email')->unique()->nullable();
            $table->string('phone')->unique()->nullable();
            $table->string('username')->unique();
            $table->string('password');
            $table->text('bio');
            $table->text('address')->nullable();
            $table->string('photo')->nullable();
            $table->string('cover')->nullable();
            $table->string('accent_color');
            $table->string('font_family');
            $table->boolean('use_custom_virtual_account_payment');
            $table->bigInteger('flat_shipping_fee')->nullable();
            $table->tinyInteger('is_active');
            $table->string('token')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
