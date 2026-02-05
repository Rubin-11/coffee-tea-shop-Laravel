<?php

namespace Database\Factories;

use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory для генерации подписчиков на рассылку
 * 
 * Создает подписчиков на email-рассылку новостей магазина.
 * Каждый подписчик имеет уникальный email и токен для отписки.
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscriber>
 */
class SubscriberFactory extends Factory
{
    /**
     * Модель, для которой создается фабрика
     * 
     * @var string
     */
    protected $model = Subscriber::class;

    /**
     * Определение состояния по умолчанию для модели
     * 
     * Генерирует случайного подписчика
     * 
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Большинство подписчиков активны
        $isActive = fake()->boolean(90);
        
        // Дата подписки (от 6 месяцев назад до сегодня)
        $subscribedAt = fake()->dateTimeBetween('-6 months', 'now');
        
        // Если подписчик неактивен, устанавливаем дату отписки
        $unsubscribedAt = !$isActive 
            ? fake()->dateTimeBetween($subscribedAt, 'now')
            : null;

        return [
            // Уникальный email подписчика
            'email' => fake()->unique()->safeEmail(),
            
            // Уникальный токен для отписки (генерируется автоматически в модели)
            // Но можем установить здесь для тестирования
            'token' => Str::random(64),
            
            // Активна ли подписка
            'is_active' => $isActive,
            
            // Дата подписки
            'subscribed_at' => $subscribedAt,
            
            // Дата отписки (если отписался)
            'unsubscribed_at' => $unsubscribedAt,
        ];
    }

    /**
     * Состояние для активного подписчика
     * 
     * Использование: Subscriber::factory()->active()->create()
     * 
     * @return static
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'unsubscribed_at' => null,
        ]);
    }

    /**
     * Состояние для неактивного подписчика (отписался)
     * 
     * Использование: Subscriber::factory()->inactive()->create()
     * 
     * @return static
     */
    public function inactive(): static
    {
        return $this->state(function (array $attributes) {
            $subscribedAt = $attributes['subscribed_at'];
            
            return [
                'is_active' => false,
                'unsubscribed_at' => fake()->dateTimeBetween($subscribedAt, 'now'),
            ];
        });
    }

    /**
     * Состояние для недавно подписавшегося
     * 
     * Использование: Subscriber::factory()->recent()->create()
     * 
     * @return static
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'subscribed_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'unsubscribed_at' => null,
        ]);
    }

    /**
     * Состояние для давнего подписчика
     * 
     * Использование: Subscriber::factory()->longtime()->create()
     * 
     * @return static
     */
    public function longtime(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'subscribed_at' => fake()->dateTimeBetween('-2 years', '-6 months'),
            'unsubscribed_at' => null,
        ]);
    }
}
