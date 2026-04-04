<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Хранит агрегированные данные о выручке по дням
     */
    public function up(): void
    {
        Schema::create('daily_revenues', function (Blueprint $table) {
            $table->id();
            $table->date('date');                    // Дата
            $table->decimal('revenue', 12, 2);       // Выручка за день
            $table->integer('orders_count')->default(0);  // Количество заказов
            $table->integer('completed_count')->default(0); // Количество выполненных
            $table->integer('cancelled_count')->default(0); // Количество отменённых
            $table->timestamps();
            
            $table->unique('date');  // Одна запись на один день
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_revenues');
    }
};
