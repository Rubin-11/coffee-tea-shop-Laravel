<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Таблица подписчиков email рассылки
     * Форма "Ваш email" из макета для подписки на новости
     */
    public function up(): void
    {
        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();
            
            // Email подписчика
            $table->string('email')->unique();                 // Уникальный email
            
            // Токен для отписки
            $table->string('token')->unique();                 // Уникальный токен
            
            // Статус подписки
            $table->boolean('is_active')->default(true);       // Активна ли подписка
            
            // Временные метки
            $table->timestamp('subscribed_at');                // Дата подписки
            $table->timestamp('unsubscribed_at')->nullable();  // Дата отписки
            
            $table->timestamps();
            
            // Индексы
            $table->index('email');
            $table->index('is_active');
            $table->index('token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscribers');
    }
};
