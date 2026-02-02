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
            $table->string('first_name', 100);  // Имя
            $table->string('last_name', 100);   // Фамилия
            $table->string('email', 250)->unique(); // Уникальный email
            $table->string('phone', 20)->nullable();    // Номер телефона
            $table->text('password');   // Хеш пароля
            $table->boolean('is_admin')->default(false);    // Флаг администратора
            $table->boolean('is_active')->default(true);    // Активен ли аккаунт
            $table->timestamps();   // create_at и updated_at
        });

        // Индекс по email - ускоряет вход и проверку
        Schema::table('users', function (Blueprint $table) {
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
