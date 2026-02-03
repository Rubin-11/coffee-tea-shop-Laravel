<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Заполнение базы данных тестовыми данными
     * 
     * Здесь можно создать сидеры для:
     * - Категорий товаров
     * - Товаров (кофе, чай)
     * - Тегов
     * - Блог постов
     * - Тестовых пользователей
     */
    public function run(): void
    {
        // Пример создания тестового пользователя
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        
        // В будущем здесь можно добавить вызовы сидеров:
        // $this->call([
        //     CategorySeeder::class,
        //     ProductSeeder::class,
        //     TagSeeder::class,
        // ]);
    }
}
