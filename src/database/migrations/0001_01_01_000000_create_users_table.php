<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Создаёт таблицу `users`.
     *
     * Пользователи — это клиенты магазина и администраторы.
     * Все данные для входа, профиля и статуса хранятся здесь.
     *
     * Особенности:
     * - email уникален — основной идентификатор
     * - is_admin — даёт доступ к админке
     * - is_active — блокировка аккаунта без удаления
     * - Пароли хранятся в виде хеша (bcrypt)
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 250)->unique(); 
            $table->string('phone', 20)->nullable();
            $table->text('password');   
            $table->boolean('is_admin')->default(false); 
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Индексы
            $table->index('email');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
