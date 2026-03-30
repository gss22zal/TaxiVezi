<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('driver_license_number')->nullable();
            $table->date('driver_license_expiry')->nullable();
            $table->integer('total_rides')->default(0);
            $table->decimal('total_earnings', 10, 2)->default(0);
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('total_ratings')->default(0);
            $table->string('status')->default('offline');
            $table->decimal('commission_rate', 5, 2)->default(20);
            $table->decimal('current_lat', 10, 8)->nullable();
            $table->decimal('current_lng', 11, 8)->nullable();
            $table->timestamp('last_location_update')->nullable();
            $table->boolean('is_online')->default(false);
            $table->boolean('can_accept_orders')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
