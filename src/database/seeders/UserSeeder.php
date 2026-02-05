<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder для пользователей
 * 
 * Создает тестовых пользователей для разработки и демонстрации:
 * - 2 администратора (включая тестовый аккаунт с известным паролем)
 * - 18-23 обычных пользователей (включая тестовый аккаунт)
 * 
 * Тестовые аккаунты для входа:
 * - admin@coffee-shop.ru / password (администратор)
 * - user@coffee-shop.ru / password (обычный пользователь)
 */
class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Запуск seeder'а
     */
    public function run(): void
    {
        // ============================================
        // АДМИНИСТРАТОРЫ (2 пользователя)
        // ============================================
        
        // 1. Главный администратор (для тестирования)
        User::create([
            'first_name' => 'Администратор',
            'last_name' => 'Главный',
            'email' => 'admin@coffee-shop.ru',
            'phone' => '+7 (999) 123-45-67',
            'password' => Hash::make('password'), // Явно указываем пароль
            'is_admin' => true,
            'is_active' => true,
        ]);

        // 2. Второй администратор (автор блога)
        User::create([
            'first_name' => 'Мария',
            'last_name' => 'Кофейникова',
            'email' => 'maria@coffee-shop.ru',
            'phone' => '+7 (999) 234-56-78',
            'password' => Hash::make('password'),
            'is_admin' => true,
            'is_active' => true,
        ]);

        // ============================================
        // ОБЫЧНЫЕ ПОЛЬЗОВАТЕЛИ (18-23 пользователя)
        // ============================================
        
        // 1. Тестовый обычный пользователь (для тестирования)
        User::create([
            'first_name' => 'Тестовый',
            'last_name' => 'Пользователь',
            'email' => 'user@coffee-shop.ru',
            'phone' => '+7 (999) 345-67-89',
            'password' => Hash::make('password'), // Явно указываем пароль
            'is_admin' => false,
            'is_active' => true,
        ]);

        // 2-3. Дополнительные именованные пользователи (для примера)
        User::create([
            'first_name' => 'Иван',
            'last_name' => 'Петров',
            'email' => 'ivan.petrov@example.com',
            'phone' => '+7 (916) 123-45-67',
            'password' => Hash::make('password'),
            'is_admin' => false,
            'is_active' => true,
        ]);

        User::create([
            'first_name' => 'Анна',
            'last_name' => 'Смирнова',
            'email' => 'anna.smirnova@example.com',
            'phone' => '+7 (925) 234-56-78',
            'password' => Hash::make('password'),
            'is_admin' => false,
            'is_active' => true,
        ]);

        // 3. Генерируем случайных пользователей (16-20 штук)
        // Итого будет: 2 админа + 1 тестовый + 2 именованных + 16-20 случайных = 21-25 пользователей
        $randomUsersCount = rand(16, 20);
        
        User::factory()
            ->count($randomUsersCount)
            ->create();

        // 4. Создаем несколько неактивных пользователей (для демонстрации блокировки)
        User::factory()
            ->count(2)
            ->inactive()
            ->create();

        $this->command->info('✅ Создано пользователей:');
        $this->command->info('   - Администраторов: 2');
        $this->command->info('   - Обычных пользователей: ' . ($randomUsersCount + 3));
        $this->command->info('   - Неактивных пользователей: 2');
        $this->command->info('   - Всего: ' . User::count());
        $this->command->newLine();
        $this->command->info('🔐 Тестовые аккаунты:');
        $this->command->info('   📧 admin@coffee-shop.ru / password (администратор)');
        $this->command->info('   📧 user@coffee-shop.ru / password (пользователь)');
    }
}
