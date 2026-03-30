<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique()->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('type');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('RUB');
            $table->string('payment_method')->nullable();
            $table->string('payment_gateway')->nullable();
            $table->string('status')->default('pending');
            $table->json('gateway_response')->nullable();
            $table->string('description')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
