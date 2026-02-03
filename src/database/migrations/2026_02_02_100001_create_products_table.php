<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Таблица товаров (кофе и чай)
     * Содержит всю информацию о продукте: цену, вес, рейтинг, характеристики
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            
            // Связь с категорией
            $table->foreignId('category_id')
                  ->constrained()
                  ->cascadeOnDelete();
            
            // Основная информация
            $table->string('name');                                    // Название товара (Colombia Supremo, Кения АА)
            $table->string('slug')->unique();                          // URL-friendly название
            $table->text('description');                               // Краткое описание
            $table->text('long_description')->nullable();              // Полное описание товара
            
            // Цена и наличие
            $table->decimal('price', 10, 2);                          // Цена (250₽, 350₽)
            $table->decimal('old_price', 10, 2)->nullable();          // Старая цена для отображения скидки
            $table->integer('weight');                                 // Вес в граммах (250)
            $table->string('sku')->unique();                          // Артикул товара
            $table->integer('stock')->default(0);                     // Количество на складе
            
            // Рейтинг и отзывы (денормализация для производительности)
            $table->decimal('rating', 3, 2)->default(0);              // Средний рейтинг (4.0)
            $table->integer('reviews_count')->default(0);             // Количество отзывов (32)
            
            // Характеристики кофе (из макета)
            $table->integer('bitterness_percent')->nullable();        // % Горчинки (2%)
            $table->integer('acidity_percent')->nullable();           // % Кислинки
            
            // Статусы и флаги
            $table->boolean('is_featured')->default(false);           // Рекомендуемый товар
            $table->boolean('is_available')->default(true);           // Доступен для заказа
            
            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            
            $table->timestamps();
            $table->softDeletes();                                     // Мягкое удаление
            
            // Индексы для оптимизации поиска и фильтрации
            $table->index('category_id');
            $table->index('price');
            $table->index('rating');
            $table->index('is_featured');
            $table->index('is_available');
            $table->index(['category_id', 'is_available']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
