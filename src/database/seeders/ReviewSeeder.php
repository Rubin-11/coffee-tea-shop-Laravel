<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Seeder для генерации отзывов на товары
 *
 * Создает реалистичное распределение отзывов:
 * - Популярные товары: 8-12 отзывов
 * - Средние товары: 3-5 отзывов
 * - Новые товары: 0-2 отзыва
 *
 * После создания отзывов обновляет рейтинг и количество отзывов для каждого товара
 */
class ReviewSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Запуск seeder'а для заполнения отзывов
     *
     * Процесс:
     * 1. Получить все товары и пользователей из БД
     * 2. Разделить товары на группы по популярности
     * 3. Создать отзывы для каждого товара
     * 4. Обновить рейтинги и счетчики товаров
     */
    public function run(): void
    {
        $this->command->info('📝 Создание отзывов на товары...');

        // Получаем все товары и пользователей
        $products = Product::all();
        $users = User::all();

        // Проверяем наличие необходимых данных
        if ($products->isEmpty()) {
            $this->command->error('❌ Товары не найдены! Сначала запустите ProductSeeder.');

            return;
        }

        if ($users->isEmpty()) {
            $this->command->error('❌ Пользователи не найдены! Сначала запустите UserSeeder.');

            return;
        }

        // Разделяем товары на группы по популярности
        $productsCount = $products->count();

        // Популярные товары (первые 25%) - получат много отзывов
        $popularProducts = $products->take((int) ceil($productsCount * 0.25));

        // Средние товары (следующие 50%) - получат среднее количество отзывов
        $mediumProducts = $products->skip($popularProducts->count())->take((int) ceil($productsCount * 0.5));

        // Новые товары (оставшиеся 25%) - получат мало отзывов или не получат вовсе
        $newProducts = $products->skip($popularProducts->count() + $mediumProducts->count());

        $totalReviews = 0;

        // Создаем отзывы для популярных товаров (8-12 отзывов)
        $this->command->info("   Создание отзывов для популярных товаров ({$popularProducts->count()} шт.)...");
        $totalReviews += $this->createReviewsForProducts($popularProducts, $users, 8, 12);

        // Создаем отзывы для средних товаров (3-5 отзывов)
        $this->command->info("   Создание отзывов для средних товаров ({$mediumProducts->count()} шт.)...");
        $totalReviews += $this->createReviewsForProducts($mediumProducts, $users, 3, 5);

        // Создаем отзывы для новых товаров (0-2 отзыва)
        $this->command->info("   Создание отзывов для новых товаров ({$newProducts->count()} шт.)...");
        $totalReviews += $this->createReviewsForProducts($newProducts, $users, 0, 2);

        // Обновляем рейтинги и счетчики для всех товаров
        $this->command->info('   Обновление рейтингов товаров...');
        $this->updateProductRatings($products);

        // Выводим итоговую статистику
        $this->command->newLine();
        $this->command->info("✅ Создано {$totalReviews} отзывов");
        $this->command->info("   Популярные товары: {$popularProducts->count()} (8-12 отзывов каждый)");
        $this->command->info("   Средние товары: {$mediumProducts->count()} (3-5 отзывов каждый)");
        $this->command->info("   Новые товары: {$newProducts->count()} (0-2 отзыва каждый)");
    }

    /**
     * Создание отзывов для группы товаров
     *
     * Для каждого товара создается случайное количество отзывов в заданном диапазоне.
     * Отзывы привязываются к случайным пользователям.
     * Один пользователь может оставить только один отзыв на товар.
     *
     * @param  Collection  $products  Коллекция товаров
     * @param  Collection  $users  Коллекция пользователей
     * @param  int  $minReviews  Минимальное количество отзывов
     * @param  int  $maxReviews  Максимальное количество отзывов
     * @return int Количество созданных отзывов
     */
    private function createReviewsForProducts($products, $users, int $minReviews, int $maxReviews): int
    {
        $createdCount = 0;

        foreach ($products as $product) {
            // Определяем случайное количество отзывов для этого товара
            $reviewsCount = rand($minReviews, $maxReviews);

            // Если 0 отзывов - пропускаем товар
            if ($reviewsCount === 0) {
                continue;
            }

            // Проверяем, что отзывов не больше, чем пользователей
            $reviewsCount = min($reviewsCount, $users->count());

            // Получаем случайных пользователей для отзывов (без повторений)
            $selectedUsers = $users->random($reviewsCount);

            // Создаем отзывы от выбранных пользователей
            foreach ($selectedUsers as $user) {
                Review::factory()->create([
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                ]);

                $createdCount++;
            }
        }

        return $createdCount;
    }

    /**
     * Обновление рейтингов и счетчиков отзывов для товаров
     *
     * Для каждого товара вычисляется:
     * - rating: средний рейтинг из всех одобренных отзывов (от 0.00 до 5.00)
     * - reviews_count: количество одобренных отзывов
     *
     * Используются только одобренные отзывы (is_approved = true),
     * так как неодобренные не должны влиять на рейтинг.
     *
     * @param  Collection  $products  Коллекция товаров
     */
    private function updateProductRatings($products): void
    {
        foreach ($products as $product) {
            // Получаем все одобренные отзывы для товара
            $approvedReviews = Review::where('product_id', $product->id)
                ->where('is_approved', true)
                ->get();

            // Если нет одобренных отзывов - устанавливаем значения по умолчанию
            if ($approvedReviews->isEmpty()) {
                $product->update([
                    'rating' => 0,
                    'reviews_count' => 0,
                ]);

                continue;
            }

            // Вычисляем средний рейтинг (с двумя знаками после запятой)
            $averageRating = round($approvedReviews->avg('rating'), 2);

            // Обновляем товар
            $product->update([
                'rating' => $averageRating,
                'reviews_count' => $approvedReviews->count(),
            ]);
        }
    }
}
