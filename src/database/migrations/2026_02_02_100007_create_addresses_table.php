<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Таблица адресов доставки пользователей
     * Пользователь может иметь несколько сохранённых адресов
     */
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            
            // Связь с пользователем
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();
            
            // Название адреса (для удобства)
            $table->string('name');                            // "Дом", "Работа", "Дача"
            
            // Адрес доставки
            $table->text('full_address');                      // Полный адрес текстом
            $table->string('city');                            // Город (Калининград)
            $table->string('street');                          // Улица
            $table->string('house');                           // Номер дома
            $table->string('apartment')->nullable();           // Квартира/офис
            $table->string('postal_code')->nullable();         // Почтовый индекс
            
            // Контакты
            $table->string('phone');                           // Телефон для связи
            
            // Адрес по умолчанию
            $table->boolean('is_default')->default(false);    // Основной адрес
            
            $table->timestamps();
            
            // Индексы
            $table->index('user_id');
            $table->index(['user_id', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
