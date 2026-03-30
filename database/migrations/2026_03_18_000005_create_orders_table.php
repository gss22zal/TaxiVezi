<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->unsignedBigInteger('passenger_id');
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->unsignedBigInteger('dispatcher_id')->nullable();
            $table->unsignedBigInteger('tariff_id')->nullable();
            $table->string('status')->default('new');
            $table->string('pickup_address');
            $table->decimal('pickup_lat', 10, 8);
            $table->decimal('pickup_lng', 11, 8);
            $table->string('dropoff_address');
            $table->decimal('dropoff_lat', 10, 8);
            $table->decimal('dropoff_lng', 11, 8);
            $table->decimal('distance', 10, 2)->nullable();
            $table->integer('duration')->nullable();
            $table->decimal('final_price', 10, 2)->nullable();
            $table->decimal('base_price', 10, 2)->nullable();
            $table->decimal('distance_price', 10, 2)->nullable();
            $table->decimal('time_price', 10, 2)->nullable();
            $table->decimal('surge_multiplier', 3, 2)->default(1.0);
            $table->string('payment_method')->nullable();
            $table->string('payment_status')->default('pending');
            $table->decimal('driver_earnings', 10, 2)->nullable();
            $table->decimal('commission_amount', 10, 2)->nullable();
            $table->string('passenger_name')->nullable();
            $table->string('passenger_phone')->nullable();
            $table->text('notes')->nullable();
            $table->text('driver_notes')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
