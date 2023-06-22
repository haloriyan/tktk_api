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
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned()->index()->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->bigInteger('customer_id')->unsigned()->index()->nullable();
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->bigInteger('interest_product_id')->unsigned()->index()->nullable();
            $table->foreign('interest_product_id')->references('id')->on('products')->onDelete('set null');

            $table->string('sender');
            $table->text('body');
            $table->text('stemmed_body');
            $table->text('actions')->nullable();
            $table->string('context')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
