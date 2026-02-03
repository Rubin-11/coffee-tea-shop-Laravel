<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Таблица заказов
     * Содержит всю информацию о заказе клиента
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            
            // Связь с пользователем (nullable для гостевых заказов)
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();
            
            // Номер заказа для клиента
            $table->string('order_number')->unique();          // ORD-2026-00001
            
            // Информация о покупателе
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone');
            
            // Доставка
            $table->text('delivery_address');                  // Полный адрес доставки
            $table->enum('delivery_method', ['courier', 'pickup', 'post'])
                  ->default('courier');                        // Способ доставки
            
            // Оплата
            $table->enum('payment_method', ['cash', 'card', 'online'])
                  ->default('cash');                           // Способ оплаты
            
            // Суммы
            $table->decimal('subtotal', 10, 2);               // Сумма товаров
            $table->decimal('delivery_cost', 10, 2)
                  ->default(0);                                // Стоимость доставки
            $table->decimal('discount', 10, 2)
                  ->default(0);                                // Скидка
            $table->decimal('total', 10, 2);                  // Итоговая сумма
            
            // Статусы
            $table->enum('status', [
                'pending',      // Ожидает обработки
                'processing',   // В обработке
                'paid',         // Оплачен
                'shipped',      // Отправлен
                'delivered',    // Доставлен
                'cancelled'     // Отменён
            ])->default('pending');
            
            $table->enum('payment_status', ['pending', 'paid', 'failed'])
                  ->default('pending');
            
            // Примечания
            $table->text('notes')->nullable();                 // Комментарий клиента
            $table->text('admin_notes')->nullable();           // Заметки администратора
            
            // Временные метки статусов
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            
            $table->timestamps();
            
            // Индексы
            $table->index('user_id');
            $table->index('order_number');
            $table->index('status');
            $table->index('payment_status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
