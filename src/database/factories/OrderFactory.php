<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory для генерации заказов
 * 
 * Создает реалистичные заказы для тестирования:
 * - Заказы авторизованных пользователей и гостей
 * - Различные способы доставки (курьер, самовывоз, почта)
 * - Различные способы оплаты (наличные, карта, онлайн)
 * - Разные статусы заказа (ожидает, оплачен, доставлен и т.д.)
 * - Реалистичные суммы: товары, доставка, скидки
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Модель, для которой создается фабрика
     * 
     * @var string
     */
    protected $model = Order::class;

    /**
     * Счетчик для генерации уникальных номеров заказов
     * Используется для формата ORD-2026-00001
     * 
     * @var int
     */
    private static int $orderCounter = 1;

    /**
     * Возможные способы доставки
     * 
     * @var array<string>
     */
    private const DELIVERY_METHODS = ['courier', 'pickup', 'post'];

    /**
     * Возможные способы оплаты
     * 
     * @var array<string>
     */
    private const PAYMENT_METHODS = ['cash', 'card', 'online'];

    /**
     * Возможные статусы заказа
     * 
     * @var array<string>
     */
    private const ORDER_STATUSES = ['pending', 'processing', 'paid', 'shipped', 'delivered', 'cancelled'];

    /**
     * Возможные статусы оплаты
     * 
     * @var array<string>
     */
    private const PAYMENT_STATUSES = ['pending', 'paid', 'failed'];

    /**
     * Определение состояния по умолчанию для модели
     * 
     * Генерирует случайный заказ с реалистичными данными
     * 
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Генерируем уникальный номер заказа в формате ORD-YYYY-XXXXX
        $year = now()->year;
        $orderNumber = sprintf('ORD-%d-%05d', $year, self::$orderCounter++);

        // Случайно выбираем способ доставки
        $deliveryMethod = fake()->randomElement(self::DELIVERY_METHODS);

        // Генерируем сумму товаров (500-5000 рублей)
        $subtotal = fake()->randomFloat(2, 500, 5000);

        // Рассчитываем стоимость доставки в зависимости от метода
        $deliveryCost = match($deliveryMethod) {
            'pickup' => 0.00,                                    // Самовывоз - бесплатно
            'courier' => $subtotal >= 2000 ? 0.00 : 300.00,    // Курьер - бесплатно от 2000 руб
            'post' => 400.00,                                   // Почта - 400 руб
            default => 0.00,
        };

        // Скидка для заказов от 3000 рублей (5%)
        $discount = $subtotal >= 3000 ? round($subtotal * 0.05, 2) : 0.00;

        // Итоговая сумма: товары + доставка - скидка
        $total = round($subtotal + $deliveryCost - $discount, 2);

        // Случайно выбираем статусы
        $status = fake()->randomElement(self::ORDER_STATUSES);
        $paymentStatus = fake()->randomElement(self::PAYMENT_STATUSES);

        // Генерируем временные метки в зависимости от статуса
        $createdAt = fake()->dateTimeBetween('-3 months', 'now');
        $paidAt = in_array($status, ['paid', 'shipped', 'delivered']) ? fake()->dateTimeBetween($createdAt, 'now') : null;
        $shippedAt = in_array($status, ['shipped', 'delivered']) ? fake()->dateTimeBetween($paidAt ?? $createdAt, 'now') : null;
        $deliveredAt = $status === 'delivered' ? fake()->dateTimeBetween($shippedAt ?? $createdAt, 'now') : null;
        $cancelledAt = $status === 'cancelled' ? fake()->dateTimeBetween($createdAt, 'now') : null;

        // Если заказ оплачен, устанавливаем статус оплаты
        if ($paidAt) {
            $paymentStatus = 'paid';
        }

        return [
            // ID пользователя (null для гостевых заказов в 30% случаев)
            'user_id' => fake()->boolean(70) ? User::factory() : null,

            // Уникальный номер заказа
            'order_number' => $orderNumber,

            // Имя покупателя (реальное русское имя)
            'customer_name' => fake()->firstName() . ' ' . fake()->lastName(),

            // Email покупателя
            'customer_email' => fake()->safeEmail(),

            // Телефон покупателя в российском формате
            'customer_phone' => fake()->numerify('+7 (###) ###-##-##'),

            // Полный адрес доставки (реалистичный российский адрес)
            'delivery_address' => sprintf(
                'г. %s, ул. %s, д. %d%s',
                fake()->randomElement(['Калининград', 'Москва', 'Санкт-Петербург', 'Нижний Новгород']),
                fake()->randomElement(['Ленина', 'Мира', 'Советская', 'Победы', 'Гагарина']),
                fake()->numberBetween(1, 150),
                fake()->boolean(70) ? ', кв. ' . fake()->numberBetween(1, 200) : ''
            ),

            // Способ доставки
            'delivery_method' => $deliveryMethod,

            // Способ оплаты
            'payment_method' => fake()->randomElement(self::PAYMENT_METHODS),

            // Сумма товаров
            'subtotal' => $subtotal,

            // Стоимость доставки
            'delivery_cost' => $deliveryCost,

            // Скидка
            'discount' => $discount,

            // Итоговая сумма
            'total' => $total,

            // Статус заказа
            'status' => $status,

            // Статус оплаты
            'payment_status' => $paymentStatus,

            // Комментарий клиента (опционально, в 40% случаев)
            'notes' => fake()->optional(0.4)->sentence(10),

            // Внутренние заметки администратора (опционально, в 20% случаев)
            'admin_notes' => fake()->optional(0.2)->sentence(8),

            // Временные метки
            'paid_at' => $paidAt,
            'shipped_at' => $shippedAt,
            'delivered_at' => $deliveredAt,
            'cancelled_at' => $cancelledAt,
            'created_at' => $createdAt,
            'updated_at' => fake()->dateTimeBetween($createdAt, 'now'),
        ];
    }

    /**
     * Состояние для гостевого заказа (без авторизации)
     * 
     * Использование: Order::factory()->guest()->create()
     * 
     * @return static
     */
    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
        ]);
    }

    /**
     * Состояние для заказа авторизованного пользователя
     * 
     * Использование: Order::factory()->forUser($user)->create()
     * 
     * @param \App\Models\User|int $user Пользователь или его ID
     * @return static
     */
    public function forUser($user): static
    {
        $userId = is_object($user) ? $user->id : $user;

        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }

    /**
     * Состояние для заказа в статусе "ожидает обработки"
     * 
     * Использование: Order::factory()->pending()->create()
     * 
     * @return static
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'payment_status' => 'pending',
            'paid_at' => null,
            'shipped_at' => null,
            'delivered_at' => null,
            'cancelled_at' => null,
        ]);
    }

    /**
     * Состояние для оплаченного заказа
     * 
     * Использование: Order::factory()->paid()->create()
     * 
     * @return static
     */
    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            $createdAt = $attributes['created_at'] ?? now();
            
            return [
                'status' => 'paid',
                'payment_status' => 'paid',
                'paid_at' => fake()->dateTimeBetween($createdAt, 'now'),
                'shipped_at' => null,
                'delivered_at' => null,
                'cancelled_at' => null,
            ];
        });
    }

    /**
     * Состояние для доставленного заказа
     * 
     * Использование: Order::factory()->delivered()->create()
     * 
     * @return static
     */
    public function delivered(): static
    {
        return $this->state(function (array $attributes) {
            $createdAt = $attributes['created_at'] ?? now();
            $paidAt = fake()->dateTimeBetween($createdAt, 'now');
            $shippedAt = fake()->dateTimeBetween($paidAt, 'now');
            $deliveredAt = fake()->dateTimeBetween($shippedAt, 'now');
            
            return [
                'status' => 'delivered',
                'payment_status' => 'paid',
                'paid_at' => $paidAt,
                'shipped_at' => $shippedAt,
                'delivered_at' => $deliveredAt,
                'cancelled_at' => null,
            ];
        });
    }

    /**
     * Состояние для отмененного заказа
     * 
     * Использование: Order::factory()->cancelled()->create()
     * 
     * @return static
     */
    public function cancelled(): static
    {
        return $this->state(function (array $attributes) {
            $createdAt = $attributes['created_at'] ?? now();
            
            return [
                'status' => 'cancelled',
                'payment_status' => 'pending',
                'paid_at' => null,
                'shipped_at' => null,
                'delivered_at' => null,
                'cancelled_at' => fake()->dateTimeBetween($createdAt, 'now'),
            ];
        });
    }

    /**
     * Состояние для заказа с курьерской доставкой
     * 
     * Использование: Order::factory()->courier()->create()
     * 
     * @return static
     */
    public function courier(): static
    {
        return $this->state(function (array $attributes) {
            // Если subtotal передан явно через create(), используем его
            // Иначе берем из атрибутов фабрики
            $subtotal = $attributes['subtotal'] ?? fake()->randomFloat(2, 500, 5000);
            $discount = $attributes['discount'] ?? ($subtotal >= 3000 ? round($subtotal * 0.05, 2) : 0.00);
            $deliveryCost = $subtotal >= 2000 ? 0.00 : 300.00;
            
            return [
                'delivery_method' => 'courier',
                'subtotal' => $subtotal,
                'discount' => $discount,
                'delivery_cost' => $deliveryCost,
                'total' => round($subtotal + $deliveryCost - $discount, 2),
            ];
        });
    }

    /**
     * Состояние для заказа с самовывозом
     * 
     * Использование: Order::factory()->pickup()->create()
     * 
     * @return static
     */
    public function pickup(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = $attributes['subtotal'];
            
            return [
                'delivery_method' => 'pickup',
                'delivery_cost' => 0.00,
                'total' => round($subtotal - $attributes['discount'], 2),
            ];
        });
    }

    /**
     * Состояние для заказа с доставкой почтой
     * 
     * Использование: Order::factory()->post()->create()
     * 
     * @return static
     */
    public function post(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = $attributes['subtotal'];
            $deliveryCost = 400.00;
            
            return [
                'delivery_method' => 'post',
                'delivery_cost' => $deliveryCost,
                'total' => round($subtotal + $deliveryCost - $attributes['discount'], 2),
            ];
        });
    }

    /**
     * Состояние для крупного заказа (более 3000 рублей со скидкой)
     * 
     * Использование: Order::factory()->large()->create()
     * 
     * @return static
     */
    public function large(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = fake()->randomFloat(2, 3000, 8000);
            $discount = round($subtotal * 0.05, 2); // 5% скидка
            $deliveryCost = $attributes['delivery_method'] === 'courier' ? 0.00 : $attributes['delivery_cost'];
            
            return [
                'subtotal' => $subtotal,
                'discount' => $discount,
                'delivery_cost' => $deliveryCost,
                'total' => round($subtotal + $deliveryCost - $discount, 2),
            ];
        });
    }

    /**
     * Сброс счетчика номеров заказов (полезно для тестов)
     * 
     * @return void
     */
    public static function resetOrderCounter(): void
    {
        self::$orderCounter = 1;
    }
}
