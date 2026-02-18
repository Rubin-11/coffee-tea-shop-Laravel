<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory для генерации позиций заказа
 * 
 * Создает реалистичные позиции (товары) в заказе:
 * - Связь с заказом и товаром
 * - "Снапшот" данных товара на момент заказа
 * - Количество товара (обычно 1-5 единиц)
 * - Зафиксированная цена на момент заказа
 * - Рассчитанная итоговая стоимость позиции
 * 
 * ВАЖНО: Позиция заказа хранит исторические данные (snapshot).
 * Даже если цена товара изменится, в заказе останется цена на момент покупки.
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Модель, для которой создается фабрика
     * 
     * @var string
     */
    protected $model = OrderItem::class;

    /**
     * Определение состояния по умолчанию для модели
     * 
     * Генерирует случайную позицию заказа с реалистичными данными
     * 
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Генерируем цену товара (200-1500 рублей)
        // Это цена на момент заказа (snapshot), может отличаться от текущей
        $price = fake()->randomFloat(2, 200, 1500);

        // Количество товара в заказе (обычно 1-3, редко до 10)
        // 70% заказов - 1 единица, 20% - 2 единицы, 10% - 3-10 единиц
        $quantity = fake()->randomElement([
            1, 1, 1, 1, 1, 1, 1,  // 70% - 1 шт
            2, 2,                  // 20% - 2 шт
            3, 4, 5,              // 10% - 3-5 шт
        ]);

        // Общая стоимость позиции = цена × количество
        $total = round($price * $quantity, 2);

        // Генерируем название товара на момент заказа
        // Это тоже snapshot - даже если название товара изменится, в заказе останется старое
        $productNames = [
            'Кофе Эфиопия Иргачиф 250г',
            'Кофе Колумбия Супремо 500г',
            'Чай Зеленый Сенча 100г',
            'Чай Черный Эрл Грей 50г',
            'Турка медная 500мл',
            'Кофе Бразилия Сантос 1кг',
            'Чай Улун Те Гуань Инь 250г',
            'Френч-пресс 1л',
            'Кофе Кения АА 250г',
            'Чай Матча премиум 100г',
        ];

        return [
            // ID заказа (будет установлен при создании или передан явно)
            'order_id' => Order::factory(),

            // ID товара (для отображения актуальной информации: изображение, наличие)
            'product_id' => Product::factory(),

            // Название товара на момент заказа (snapshot)
            // Даже если название товара изменится в БД, в заказе останется старое
            'product_name' => fake()->randomElement($productNames),

            // Количество единиц товара
            'quantity' => $quantity,

            // Цена за единицу товара на момент заказа (snapshot)
            'price' => $price,

            // Общая стоимость позиции (price × quantity)
            'total' => $total,
        ];
    }

    /**
     * Состояние для позиции заказа с конкретным заказом
     * 
     * Использование: OrderItem::factory()->forOrder($order)->create()
     * 
     * @param \App\Models\Order|int $order Заказ или его ID
     * @return static
     */
    public function forOrder($order): static
    {
        $orderId = is_object($order) ? $order->id : $order;

        return $this->state(fn (array $attributes) => [
            'order_id' => $orderId,
        ]);
    }

    /**
     * Состояние для позиции заказа с конкретным товаром
     * 
     * При использовании этого состояния:
     * - Берется реальная цена товара из БД
     * - Берется реальное название товара
     * - Гарантируется соответствие данных
     * 
     * Использование: OrderItem::factory()->forProduct($product)->create()
     * 
     * @param \App\Models\Product $product Товар
     * @return static
     */
    public function forProduct(Product $product): static
    {
        return $this->state(function (array $attributes) use ($product) {
            // Используем реальную цену и название товара
            $price = (float) $product->price;
            $quantity = $attributes['quantity'];
            
            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'price' => $price,
                'total' => round($price * $quantity, 2),
            ];
        });
    }

    /**
     * Состояние для позиции с большим количеством товара
     * 
     * Полезно для тестирования обработки крупных заказов
     * 
     * Использование: OrderItem::factory()->bulk()->create()
     * 
     * @return static
     */
    public function bulk(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = fake()->numberBetween(5, 20);
            $price = $attributes['price'];
            
            return [
                'quantity' => $quantity,
                'total' => round($price * $quantity, 2),
            ];
        });
    }

    /**
     * Состояние для позиции с одной единицей товара
     * 
     * Самый распространенный случай - покупка одной упаковки кофе/чая
     * 
     * Использование: OrderItem::factory()->single()->create()
     * 
     * @return static
     */
    public function single(): static
    {
        return $this->state(function (array $attributes) {
            $price = $attributes['price'];
            
            return [
                'quantity' => 1,
                'total' => $price, // При количестве = 1, total = price
            ];
        });
    }

    /**
     * Состояние для дорогой позиции (премиум товар)
     * 
     * Полезно для тестирования заказов с дорогими товарами
     * 
     * Использование: OrderItem::factory()->premium()->create()
     * 
     * @return static
     */
    public function premium(): static
    {
        return $this->state(function (array $attributes) {
            $price = fake()->randomFloat(2, 1500, 5000);
            $quantity = $attributes['quantity'];
            
            return [
                'price' => $price,
                'total' => round($price * $quantity, 2),
                'product_name' => fake()->randomElement([
                    'Кофе Панама Гейша 250г',
                    'Кофе Ямайка Блю Маунтин 250г',
                    'Чай Белый Бай Хао Инь Чжень 100г',
                    'Кофемолка премиум класса',
                    'Эспрессо-машина профессиональная',
                ]),
            ];
        });
    }

    /**
     * Состояние для позиции со скидкой
     * 
     * Имитирует ситуацию, когда товар был куплен по старой цене (со скидкой).
     * В реальном приложении это будет обрабатываться через поле old_price у Product,
     * но для тестов полезно иметь отдельное состояние.
     * 
     * Использование: OrderItem::factory()->discounted()->create()
     * 
     * @return static
     */
    public function discounted(): static
    {
        return $this->state(function (array $attributes) {
            // Уменьшаем цену на 15-40%
            $originalPrice = $attributes['price'];
            $discountPercent = fake()->numberBetween(15, 40);
            $discountedPrice = round($originalPrice * (1 - $discountPercent / 100), 2);
            $quantity = $attributes['quantity'];
            
            return [
                'price' => $discountedPrice,
                'total' => round($discountedPrice * $quantity, 2),
            ];
        });
    }

    /**
     * Состояние для позиции кофе
     * 
     * Использование: OrderItem::factory()->coffee()->create()
     * 
     * @return static
     */
    public function coffee(): static
    {
        return $this->state(function (array $attributes) {
            $coffeeNames = [
                'Кофе Эфиопия Иргачиф 250г',
                'Кофе Колумбия Супремо 500г',
                'Кофе Бразилия Сантос 1кг',
                'Кофе Кения АА 250г',
                'Кофе Гватемала Антигуа 500г',
            ];

            $price = fake()->randomFloat(2, 300, 800);
            $quantity = $attributes['quantity'];
            
            return [
                'product_name' => fake()->randomElement($coffeeNames),
                'price' => $price,
                'total' => round($price * $quantity, 2),
            ];
        });
    }

    /**
     * Состояние для позиции чая
     * 
     * Использование: OrderItem::factory()->tea()->create()
     * 
     * @return static
     */
    public function tea(): static
    {
        return $this->state(function (array $attributes) {
            $teaNames = [
                'Чай Зеленый Сенча 100г',
                'Чай Черный Эрл Грей 50г',
                'Чай Улун Те Гуань Инь 250г',
                'Чай Белый Бай Хао Инь Чжень 100г',
                'Чай Матча премиум 100г',
            ];

            $price = fake()->randomFloat(2, 200, 600);
            $quantity = $attributes['quantity'];
            
            return [
                'product_name' => fake()->randomElement($teaNames),
                'price' => $price,
                'total' => round($price * $quantity, 2),
            ];
        });
    }
}
