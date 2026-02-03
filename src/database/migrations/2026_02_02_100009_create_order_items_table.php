<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Таблица товаров в заказе
     * Содержит снапшот информации о товарах на момент заказа
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            
            // Связь с заказом
            $table->foreignId('order_id')
                  ->constrained()
                  ->cascadeOnDelete();
            
            // Связь с товаром
            $table->foreignId('product_id')
                  ->constrained()
                  ->cascadeOnDelete();
            
            // Снапшот информации о товаре (на момент заказа)
            $table->string('product_name');                    // Фиксируем название
            $table->integer('quantity');                       // Количество
            $table->decimal('price', 10, 2);                  // Цена за единицу
            $table->decimal('total', 10, 2);                  // Итого (quantity * price)
            
            $table->timestamps();
            
            // Индексы
            $table->index('order_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
