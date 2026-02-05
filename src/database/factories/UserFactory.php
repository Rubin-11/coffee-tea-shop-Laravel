<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Factory для генерации тестовых пользователей
 * 
 * Создает пользователей с реалистичными данными для тестирования.
 * По умолчанию генерирует обычных пользователей (не администраторов).
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Текущий пароль, используемый фабрикой
     * Кешируется для оптимизации (хеширование - дорогая операция)
     */
    protected static ?string $password;

    /**
     * Определение состояния модели по умолчанию
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Имя пользователя (только имя, без фамилии)
            'first_name' => fake()->firstName(),
            
            // Фамилия пользователя
            'last_name' => fake()->lastName(),
            
            // Уникальный email адрес
            'email' => fake()->unique()->safeEmail(),
            
            // Телефон в формате +7 (XXX) XXX-XX-XX
            'phone' => fake()->optional(0.7)->numerify('+7 (###) ###-##-##'),
            
            // Хешированный пароль (по умолчанию 'password')
            'password' => static::$password ??= Hash::make('password'),
            
            // Обычный пользователь (не администратор)
            'is_admin' => false,
            
            // Активный аккаунт
            'is_active' => true,
        ];
    }

    /**
     * Создать пользователя-администратора
     * 
     * Использование: User::factory()->admin()->create()
     *
     * @return static
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_admin' => true,
        ]);
    }

    /**
     * Создать неактивного пользователя (заблокированного)
     * 
     * Использование: User::factory()->inactive()->create()
     *
     * @return static
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
