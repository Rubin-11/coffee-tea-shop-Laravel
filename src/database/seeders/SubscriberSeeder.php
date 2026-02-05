<?php

namespace Database\Seeders;

use App\Models\Subscriber;
use Illuminate\Database\Seeder;

/**
 * Seeder для подписчиков на email-рассылку
 * 
 * Создает 30-40 подписчиков на новости магазина.
 * Большинство подписчиков активны, но есть и неактивные (отписавшиеся).
 * Подписчики имеют различные даты подписки для реалистичности.
 */
class SubscriberSeeder extends Seeder
{
    /**
     * Запуск seeder'а
     * 
     * Создаем различные типы подписчиков:
     * - 25 активных подписчиков (70%)
     * - 7 недавно подписавшихся (20%)
     * - 3 неактивных, которые отписались (10%)
     * 
     * Итого: 35 подписчиков (в пределах плана 30-40)
     */
    public function run(): void
    {
        // Очищаем таблицу перед заполнением
        Subscriber::query()->delete();

        // 1. Создаем 25 обычных активных подписчиков
        // Это основная масса подписчиков с разными датами подписки
        echo "Создание 25 активных подписчиков...\n";
        Subscriber::factory()
            ->count(25)
            ->active()
            ->create();

        // 2. Создаем 7 недавно подписавшихся
        // Эти подписчики присоединились в течение последнего месяца
        echo "Создание 7 недавно подписавшихся...\n";
        Subscriber::factory()
            ->count(7)
            ->recent()
            ->create();

        // 3. Создаем 3 неактивных подписчиков (отписались)
        // Для реалистичности - не все остаются подписанными
        echo "Создание 3 отписавшихся подписчиков...\n";
        Subscriber::factory()
            ->count(3)
            ->inactive()
            ->create();

        // Выводим статистику
        $totalSubscribers = Subscriber::count();
        $activeSubscribers = Subscriber::active()->count();
        $inactiveSubscribers = Subscriber::inactive()->count();

        echo "\n";
        echo "✅ Seeding подписчиков завершен!\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "Всего подписчиков: {$totalSubscribers}\n";
        echo "  • Активных: {$activeSubscribers}\n";
        echo "  • Неактивных: {$inactiveSubscribers}\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    }
}
