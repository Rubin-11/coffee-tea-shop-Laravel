<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Таблица токенов сброса пароля.
     *
     * Стандартная таблица Laravel для восстановления пароля:
     * - email — на какой адрес запрошен сброс
     * - token — хешированный токен из письма (в БД хранится hash)
     * - created_at — время создания; устаревшие токены удаляются автоматически
     */
    public function up(): void
    {
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
    }
};
