<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passengers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('balance', 10, 2)->default(0);
            $table->decimal('bonus_balance', 10, 2)->default(0);
            $table->integer('total_rides')->default(0);
            $table->decimal('total_spent', 10, 2)->default(0);
            $table->string('default_payment_method')->nullable();
            $table->string('home_address')->nullable();
            $table->string('work_address')->nullable();
            $table->string('preferred_car_class')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passengers');
    }
};
