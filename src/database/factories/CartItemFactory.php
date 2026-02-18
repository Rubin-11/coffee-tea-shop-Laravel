<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory для генерации позиций корзины
 * 
 * Создает реалистичные позиции в корзине покупок:
 * - Корзины авторизованных пользователей (через user_id)
 * - Гостевые корзины (через session_id)
 * - Связь с товарами
 * - Количество товара (обычно 1-3 единицы)
 * - Зафиксированная цена на момент добавления в корзину
 * 
 * ВАЖНО: Цена в корзине может отличаться от текущей цены товара,
 * если цена изменилась после добавления товара в корзину.
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CartItem>
 */
class CartItemFactory extends Factory
{
    /**
     * Модель, для которой создается фабрика
     * 
     * @var string
     */
    protected $model = CartItem::class;

    /**
     * Определение состояния по умолчанию для модели
     * 
     * Генерирует случайную позицию корзины с реалистичными данными
     * 
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Генерируем цену товара (200-1500 рублей)
        // Это цена на момент добавления в корзину
        $price = fake()->randomFloat(2, 200, 1500);

        // Количество товара в корзине (обычно 1-3, редко больше)
        // 60% позиций - 1 единица, 30% - 2 единицы, 10% - 3-5 единиц
        $quantity = fake()->randomElement([
            1, 1, 1, 1, 1, 1,    // 60% - 1 шт
            2, 2, 2,             // 30% - 2 шт
            3, 4, 5,             // 10% - 3-5 шт
        ]);

        // В 70% случаев создаем корзину для авторизованного пользователя
        // В 30% случаев - для гостя (session_id)
        $isAuthenticated = fake()->boolean(70);

        return [
            // ID пользователя (null для гостей)
            'user_id' => $isAuthenticated ? User::factory() : null,

            // ID сессии (только для гостей)
            // Генерируем реалистичный ID сессии Laravel
            'session_id' => $isAuthenticated ? null : Str::random(40),

            // ID товара (будет установлен при создании или передан явно)
            'product_id' => Product::factory(),

            // Количество единиц товара в корзине
            'quantity' => $quantity,

            // Цена за единицу товара на момент добавления в корзину
            // Может отличаться от текущей цены товара
            'price' => $price,

            // Временные метки
            // created_at - когда товар был добавлен в корзину
            // updated_at - когда количество или цена были изменены
            'created_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'updated_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ];
    }

    /**
     * Состояние для корзины авторизованного пользователя
     * 
     * Использование: CartItem::factory()->forUser($user)->create()
     * 
     * @param \App\Models\User|int $user Пользователь или его ID
     * @return static
     */
    public function forUser($user): static
    {
        $userId = is_object($user) ? $user->id : $user;

        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
            'session_id' => null, // Для авторизованных пользователей session_id не нужен
        ]);
    }

    /**
     * Состояние для гостевой корзины
     * 
     * Использование: CartItem::factory()->guest($sessionId)->create()
     * 
     * @param string|null $sessionId ID сессии (если null, сгенерируется автоматически)
     * @return static
     */
    public function guest(?string $sessionId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null, // Для гостей user_id должен быть null
            'session_id' => $sessionId ?? Str::random(40),
        ]);
    }

    /**
     * Состояние для позиции с конкретным товаром
     * 
     * При использовании этого состояния:
     * - Берется реальная цена товара из БД
     * - Гарантируется соответствие данных
     * 
     * Использование: CartItem::factory()->forProduct($product)->create()
     * 
     * @param \App\Models\Product $product Товар
     * @return static
     */
    public function forProduct(Product $product): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
            'price' => (float) $product->price, // Используем реальную цену товара
        ]);
    }

    /**
     * Состояние для позиции с одной единицей товара
     * 
     * Самый распространенный случай - добавили один товар в корзину
     * 
     * Использование: CartItem::factory()->single()->create()
     * 
     * @return static
     */
    public function single(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 1,
        ]);
    }

    /**
     * Состояние для позиции с несколькими единицами товара
     * 
     * Использование: CartItem::factory()->multiple(5)->create()
     * 
     * @param int $quantity Количество единиц товара
     * @return static
     */
    public function multiple(int $quantity): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
        ]);
    }

    /**
     * Состояние для старой позиции корзины
     * 
     * Полезно для тестирования очистки старых корзин или синхронизации цен.
     * Товары, которые лежат в корзине больше месяца.
     * 
     * Использование: CartItem::factory()->old()->create()
     * 
     * @return static
     */
    public function old(): static
    {
        return $this->state(function (array $attributes) {
            $createdAt = fake()->dateTimeBetween('-6 months', '-1 month');
            
            return [
                'created_at' => $createdAt,
                'updated_at' => fake()->dateTimeBetween($createdAt, '-1 month'),
            ];
        });
    }

    /**
     * Состояние для свежей позиции корзины
     * 
     * Товары, только что добавленные в корзину (сегодня или вчера)
     * 
     * Использование: CartItem::factory()->fresh()->create()
     * 
     * @return static
     */
    public function fresh(): static
    {
        return $this->state(function (array $attributes) {
            $createdAt = fake()->dateTimeBetween('-1 day', 'now');
            
            return [
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];
        });
    }

    /**
     * Состояние для позиции с устаревшей ценой
     * 
     * Имитирует ситуацию, когда товар добавлен в корзину давно,
     * а цена товара с тех пор изменилась.
     * Полезно для тестирования синхронизации цен.
     * 
     * Использование: CartItem::factory()->outdatedPrice()->create()
     * 
     * @return static
     */
    public function outdatedPrice(): static
    {
        return $this->state(function (array $attributes) {
            // Устанавливаем цену, отличающуюся от текущей на 10-30%
            $currentPrice = $attributes['price'];
            $priceChange = fake()->randomElement([-1, 1]); // Цена могла вырасти или упасть
            $changePercent = fake()->numberBetween(10, 30) / 100;
            $oldPrice = round($currentPrice * (1 + ($priceChange * $changePercent)), 2);
            
            return [
                'price' => $oldPrice,
                'created_at' => fake()->dateTimeBetween('-2 months', '-1 week'),
            ];
        });
    }

    /**
     * Состояние для большого количества товара в корзине
     * 
     * Полезно для тестирования проверки наличия на складе
     * 
     * Использование: CartItem::factory()->bulk()->create()
     * 
     * @return static
     */
    public function bulk(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => fake()->numberBetween(10, 50),
        ]);
    }

    /**
     * Состояние для позиции кофе
     * 
     * Использование: CartItem::factory()->coffee()->create()
     * 
     * @return static
     */
    public function coffee(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => fake()->randomFloat(2, 300, 800),
        ]);
    }

    /**
     * Состояние для позиции чая
     * 
     * Использование: CartItem::factory()->tea()->create()
     * 
     * @return static
     */
    public function tea(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => fake()->randomFloat(2, 200, 600),
        ]);
    }

    /**
     * Состояние для позиции с одинаковым session_id
     * 
     * Полезно для создания нескольких позиций в одной гостевой корзине
     * 
     * Использование: CartItem::factory()->sameSession()->count(3)->create()
     * 
     * @return static
     */
    public function sameSession(): static
    {
        static $sessionId = null;
        
        // Генерируем session_id один раз для всех позиций
        if ($sessionId === null) {
            $sessionId = Str::random(40);
        }

        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Сброс статического session_id
     * Вызывайте этот метод в setUp() тестов для создания новых гостевых корзин
     * 
     * @return void
     */
    public static function resetSessionId(): void
    {
        // Эта функция нужна для сброса статической переменной в sameSession()
        // PHP не позволяет напрямую обращаться к static переменным внутри closure
    }
}
