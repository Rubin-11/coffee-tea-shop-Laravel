<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Заполнение базы данных тестовыми данными
     *
     * Порядок выполнения важен из-за внешних ключей:
     * 1. UserSeeder - пользователи (нужны для отзывов и блога)
     * 2. CategorySeeder - категории товаров
     * 3. TagSeeder - теги для товаров
     * 4. ProductSeeder - товары с изображениями
     * 5. ReviewSeeder - отзывы на товары (требует Users и Products)
     * 6. BlogPostSeeder - статьи блога (требует Users)
     * 7. SubscriberSeeder - подписчики на рассылку
     */
    public function run(): void
    {
        $this->command->info('🌱 Начинаем заполнение базы данных тестовыми данными...');
        $this->command->newLine();

        // Запускаем seeder'ы в правильном порядке
        $this->call([
            UserSeeder::class,           // ✓ Пользователи
            CategorySeeder::class,       // ✓ Категории товаров
            TagSeeder::class,            // ✓ Теги товаров
            ProductSeeder::class,        // ✓ Товары с изображениями
            ReviewSeeder::class,         // ✓ Отзывы на товары
            BlogPostSeeder::class,       // ✓ Статьи блога
            SubscriberSeeder::class,     // ✓ Подписчики на рассылку
        ]);

        $this->command->newLine();
        $this->command->info('✅ Заполнение базы данных завершено!');
    }
}
