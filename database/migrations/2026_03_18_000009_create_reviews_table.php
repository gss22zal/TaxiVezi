<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('passenger_id');
            $table->unsignedBigInteger('driver_id');
            $table->tinyInteger('passenger_rating')->nullable();
            $table->tinyInteger('driver_rating')->nullable();
            $table->text('passenger_comment')->nullable();
            $table->text('driver_comment')->nullable();
            $table->json('passenger_tags')->nullable();
            $table->json('driver_tags')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
