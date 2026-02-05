<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory для генерации отзывов на товары
 * 
 * Создает реалистичные отзывы покупателей:
 * - Рейтинг от 1 до 5 звезд (с перевесом в 4-5)
 * - Текст отзыва о качестве, вкусе, аромате
 * - Достоинства и недостатки товара
 * - Отметка о проверенной покупке
 * - Статус модерации (одобрен/не одобрен)
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Модель, для которой создается фабрика
     * 
     * @var string
     */
    protected $model = Review::class;

    /**
     * Шаблоны положительных отзывов для кофе
     * 
     * @var array<string>
     */
    private static array $positiveComments = [
        'Отличный кофе! Насыщенный вкус и приятный аромат. Рекомендую!',
        'Очень доволен покупкой. Кофе свежий, зерна качественные.',
        'Прекрасный кофе на каждый день. Хорошо сбалансированный вкус.',
        'Один из лучших сортов, что я пробовал. Буду заказывать еще.',
        'Отличное соотношение цены и качества. Вкус насыщенный, без горечи.',
        'Замечательный кофе! Готовлю в турке - получается очень вкусно.',
        'Кофе превосходный, аромат потрясающий. Всем рекомендую!',
        'Давно искал такой кофе. Идеально подходит для эспрессо.',
        'Качество на высоте! Зерна равномерной обжарки, вкус богатый.',
        'Беру уже не первый раз. Стабильно хорошее качество.',
    ];

    /**
     * Шаблоны нейтральных отзывов
     * 
     * @var array<string>
     */
    private static array $neutralComments = [
        'Кофе неплохой, но ожидал большего. На троечку.',
        'Обычный кофе, ничего особенного. Для повседневного употребления подойдет.',
        'Вкус средний. Есть варианты и получше за эту цену.',
        'Кофе нормальный, но не впечатлил. Попробую другой сорт.',
        'Качество приемлемое. Но аромат не такой яркий, как описано.',
    ];

    /**
     * Шаблоны отрицательных отзывов
     * 
     * @var array<string>
     */
    private static array $negativeComments = [
        'Разочарован. Кофе горчит, аромата почти нет.',
        'Не понравился вкус. Слишком кислый для меня.',
        'Качество оставляет желать лучшего. Зерна неравномерной обжарки.',
        'Не рекомендую. За эти деньги можно найти лучше.',
        'Ожидал совсем другого вкуса. Не буду заказывать повторно.',
    ];

    /**
     * Достоинства товаров
     * 
     * @var array<string>
     */
    private static array $pros = [
        'Отличный вкус',
        'Насыщенный аромат',
        'Свежая обжарка',
        'Хорошая упаковка',
        'Быстрая доставка',
        'Приятная цена',
        'Качественные зерна',
        'Без горечи',
        'Идеально для эспрессо',
        'Подходит для турки',
    ];

    /**
     * Недостатки товаров
     * 
     * @var array<string>
     */
    private static array $cons = [
        'Немного дороговато',
        'Маленькая упаковка',
        'Слишком крепкий',
        'Есть легкая горчинка',
        'Кисловатый привкус',
        'Не хватает аромата',
        'Быстро заканчивается',
        'Могла быть свежее обжарка',
    ];

    /**
     * Определение состояния по умолчанию для модели
     * 
     * Генерирует случайный отзыв с реалистичными данными
     * 
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Генерируем рейтинг с перевесом в 4-5 звезд (положительные отзывы)
        // 60% - 5 звезд, 25% - 4 звезды, 10% - 3 звезды, 5% - 1-2 звезды
        $rating = fake()->randomElement([
            5, 5, 5, 5, 5, 5,  // 60%
            4, 4, 4,           // 30%
            3,                 // 10%
            2, 1,              // 10%
        ]);

        // Выбираем текст отзыва в зависимости от рейтинга
        $comment = match(true) {
            $rating >= 4 => fake()->randomElement(self::$positiveComments),
            $rating == 3 => fake()->randomElement(self::$neutralComments),
            default => fake()->randomElement(self::$negativeComments),
        };

        // Добавляем достоинства и недостатки (опционально)
        $pros = fake()->optional(0.7)->randomElement(self::$pros);
        if ($pros && fake()->boolean(50)) {
            $pros .= ', ' . fake()->randomElement(self::$pros);
        }

        $cons = fake()->optional(0.5)->randomElement(self::$cons);

        return [
            // Связь с товаром и пользователем будет установлена в seeder
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            
            // Рейтинг от 1 до 5
            'rating' => $rating,
            
            // Текст отзыва
            'comment' => $comment,
            
            // Достоинства (опционально)
            'pros' => $pros,
            
            // Недостатки (опционально)
            'cons' => $cons,
            
            // 70% отзывов - проверенная покупка
            'is_verified_purchase' => fake()->boolean(70),
            
            // 90% отзывов одобрены модератором
            'is_approved' => fake()->boolean(90),
        ];
    }

    /**
     * Состояние для положительного отзыва (4-5 звезд)
     * 
     * Использование: Review::factory()->positive()->create()
     * 
     * @return static
     */
    public function positive(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => fake()->randomElement([4, 5]),
            'comment' => fake()->randomElement(self::$positiveComments),
        ]);
    }

    /**
     * Состояние для отрицательного отзыва (1-2 звезды)
     * 
     * Использование: Review::factory()->negative()->create()
     * 
     * @return static
     */
    public function negative(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => fake()->randomElement([1, 2]),
            'comment' => fake()->randomElement(self::$negativeComments),
        ]);
    }

    /**
     * Состояние для отзыва с проверенной покупкой
     * 
     * Использование: Review::factory()->verified()->create()
     * 
     * @return static
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified_purchase' => true,
            'is_approved' => true, // Проверенные покупки обычно одобряются автоматически
        ]);
    }

    /**
     * Состояние для неодобренного отзыва (на модерации)
     * 
     * Использование: Review::factory()->pending()->create()
     * 
     * @return static
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approved' => false,
        ]);
    }

    /**
     * Состояние для отзыва с максимальным рейтингом
     * 
     * Использование: Review::factory()->excellent()->create()
     * 
     * @return static
     */
    public function excellent(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => 5,
            'comment' => fake()->randomElement(array_slice(self::$positiveComments, 0, 5)),
            'is_verified_purchase' => true,
            'is_approved' => true,
        ]);
    }
}
