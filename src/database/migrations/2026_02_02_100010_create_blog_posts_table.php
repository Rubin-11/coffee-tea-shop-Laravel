<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Таблица статей блога
     * Раздел "Блог" из макета с темами типа "Здоровое питание"
     */
    public function up(): void
    {
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            
            // Основная информация
            $table->string('title');                           // Заголовок статьи
            $table->string('slug')->unique();                  // URL-friendly название
            $table->text('excerpt');                           // Краткое описание
            $table->longText('content');                       // Полный текст статьи
            $table->string('featured_image')->nullable();      // Главное изображение
            
            // Автор
            $table->foreignId('author_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            
            // Категория
            $table->string('category')->nullable();            // "Здоровое питание", "Рецепты"
            
            // Статистика
            $table->integer('views_count')->default(0);        // Количество просмотров
            
            // Публикация
            $table->boolean('is_published')->default(false);   // Опубликована ли
            $table->timestamp('published_at')->nullable();     // Дата публикации
            
            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            
            $table->timestamps();
            
            // Индексы
            $table->index('author_id');
            $table->index('is_published');
            $table->index('published_at');
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
