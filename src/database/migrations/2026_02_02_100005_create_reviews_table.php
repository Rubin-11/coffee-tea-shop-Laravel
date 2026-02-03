<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Таблица отзывов на товары
     * Содержит рейтинг, комментарий и дополнительную информацию
     */
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            
            // Связи
            $table->foreignId('product_id')
                  ->constrained()
                  ->cascadeOnDelete();
                  
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();
            
            // Оценка и отзыв
            $table->tinyInteger('rating');                     // Рейтинг 1-5 звёзд
            $table->text('comment');                           // Текст отзыва
            $table->text('pros')->nullable();                  // Достоинства
            $table->text('cons')->nullable();                  // Недостатки
            
            // Статусы
            $table->boolean('is_verified_purchase')            // Проверенная покупка
                  ->default(false);
            $table->boolean('is_approved')                     // Одобрен модератором
                  ->default(false);
            
            $table->timestamps();
            
            // Индексы
            $table->index('product_id');
            $table->index('user_id');
            $table->index('is_approved');
            $table->index(['product_id', 'is_approved']);
            
            // Один пользователь - один отзыв на товар
            $table->unique(['product_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
