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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('name');
            $table->string('slug');
            $table->longText('description');
            $table->bigInteger('price');
            $table->boolean('is_service');
            $table->string('type')->nullable(); // physic, digital
            // --- PHYSICAL ---
            $table->string('dimension')->nullable();
            $table->integer('weight')->nullable();
            $table->integer('quantity')->nullable();
            $table->boolean('is_visible');
            // --- DIGITAL ---
            $table->string('filename')->nullable();
            $table->integer('download_limit')->nullable();
            $table->integer('download_expiration')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
