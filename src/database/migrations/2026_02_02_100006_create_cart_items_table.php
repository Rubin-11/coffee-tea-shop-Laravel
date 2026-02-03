<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Таблица корзины покупок
     * Поддерживает как авторизованных пользователей, так и гостей
     */
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            
            // Связь с пользователем (nullable для гостей)
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained()
                  ->cascadeOnDelete();
            
            // ID сессии для гостей
            $table->string('session_id')->nullable();
            
            // Связь с товаром
            $table->foreignId('product_id')
                  ->constrained()
                  ->cascadeOnDelete();
            
            // Информация о товаре в корзине
            $table->integer('quantity')->default(1);           // Количество
            $table->decimal('price', 10, 2);                  // Цена на момент добавления
            
            $table->timestamps();
            
            // Индексы
            $table->index('user_id');
            $table->index('session_id');
            $table->index('product_id');
            $table->index(['user_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
