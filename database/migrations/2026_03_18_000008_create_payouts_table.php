<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->string('payout_number')->unique();
            $table->unsignedBigInteger('driver_id');
            $table->decimal('amount', 10, 2);
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('total_rides')->nullable();
            $table->decimal('total_earnings', 10, 2)->nullable();
            $table->decimal('commission_deducted', 10, 2)->nullable();
            $table->string('status')->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('bank_name')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
