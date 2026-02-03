<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Таблица категорий товаров (кофе, чай, аксессуары)
     * Поддерживает вложенность (parent_id для подкатегорий)
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            
            // Основная информация
            $table->string('name');                           // Название категории
            $table->string('slug')->unique();                 // URL-friendly название
            $table->text('description')->nullable();          // Описание категории
            $table->string('image')->nullable();              // Изображение категории
            
            // Вложенность категорий
            $table->foreignId('parent_id')                    // Родительская категория
                  ->nullable()
                  ->constrained('categories')
                  ->cascadeOnDelete();
            
            // Сортировка и отображение
            $table->integer('sort_order')->default(0);        // Порядок сортировки
            $table->boolean('is_active')->default(true);      // Активна ли категория
            
            $table->timestamps();
            
            // Индексы для оптимизации
            $table->index('parent_id');
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
