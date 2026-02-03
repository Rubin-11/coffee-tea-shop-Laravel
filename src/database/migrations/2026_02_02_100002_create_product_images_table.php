<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Таблица изображений товаров
     * Один товар может иметь несколько изображений
     */
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            
            // Связь с товаром
            $table->foreignId('product_id')
                  ->constrained()
                  ->cascadeOnDelete();
            
            // Информация об изображении
            $table->string('image_path');                      // Путь к файлу изображения
            $table->string('alt_text')->nullable();            // Alt текст для SEO
            $table->integer('sort_order')->default(0);         // Порядок отображения
            $table->boolean('is_primary')->default(false);     // Главное изображение
            
            $table->timestamps();
            
            // Индексы
            $table->index('product_id');
            $table->index(['product_id', 'is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
