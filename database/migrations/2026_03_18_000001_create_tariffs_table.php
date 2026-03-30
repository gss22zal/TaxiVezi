<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tariffs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->decimal('base_price', 10, 2);
            $table->decimal('price_per_km', 10, 2);
            $table->decimal('price_per_min', 10, 2);
            $table->decimal('min_price', 10, 2);
            $table->decimal('min_distance', 10, 2)->nullable();
            $table->integer('min_duration')->nullable();
            $table->decimal('commission_rate', 5, 2)->default(20);
            $table->boolean('is_active')->default(true);
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tariffs');
    }
};
