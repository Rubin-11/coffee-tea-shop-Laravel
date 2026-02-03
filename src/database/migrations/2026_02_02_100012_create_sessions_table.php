<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Запуск миграции - создание таблицы sessions
     * Эта таблица хранит данные сессий пользователей в базе данных
     */
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            // Первичный ключ - уникальный идентификатор сессии
            $table->string('id')->primary();
            
            // ID пользователя (если пользователь авторизован)
            $table->foreignId('user_id')->nullable()->index();
            
            // IP-адрес пользователя
            $table->string('ip_address', 45)->nullable();
            
            // User Agent браузера
            $table->text('user_agent')->nullable();
            
            // Данные сессии (сериализованные)
            $table->longText('payload');
            
            // Время последней активности (Unix timestamp)
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Откат миграции - удаление таблицы sessions
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
