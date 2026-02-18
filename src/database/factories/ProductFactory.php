<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory для генерации товаров
 * 
 * Создает реалистичные товары для магазина кофе и чая:
 * - Кофе различных сортов (Арабика, Робуста)
 * - Чай (зеленый, черный, травяной)
 * - Аксессуары (турки, чашки)
 * 
 * Каждый товар имеет характеристики: цену, вес, рейтинг, 
 * уровень горчинки и кислинки (для кофе)
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Модель, для которой создается фабрика
     * 
     * @var string
     */
    protected $model = Product::class;

    /**
     * Список названий кофе для генерации реалистичных товаров
     * 
     * @var array<string>
     */
    private static array $coffeeNames = [
        'Эфиопия Иргачиф',
        'Колумбия Супремо',
        'Кения АА',
        'Бразилия Сантос',
        'Гватемала Антигуа',
        'Коста-Рика Тарразу',
        'Индонезия Суматра',
        'Ямайка Блю Маунтин',
        'Танзания Пиберри',
        'Вьетнам Далат',
        'Мексика Альтура',
        'Перу Чанчамайо',
        'Руанда Мисаго',
        'Гондурас Марагоджип',
        'Панама Гейша',
    ];

    /**
     * Список названий чая
     * 
     * @var array<string>
     */
    private static array $teaNames = [
        'Зеленый Сенча',
        'Черный Эрл Грей',
        'Улун Те Гуань Инь',
        'Белый Бай Хао Инь Чжень',
        'Пуэр Шу',
        'Жасминовый Мао Фэн',
        'Матча премиум',
        'Ройбуш ванильный',
        'Ассам Крепкий',
        'Дарджилинг первый сбор',
    ];

    /**
     * Список названий аксессуаров
     * 
     * @var array<string>
     */
    private static array $accessoryNames = [
        'Турка медная 500мл',
        'Кофемолка ручная',
        'Чашка керамическая 250мл',
        'Френч-пресс 1л',
        'Темпер для кофе 58мм',
        'Питчер для молока 600мл',
        'Весы электронные',
        'Гейзерная кофеварка',
    ];

    /**
     * Определение состояния по умолчанию для модели
     * 
     * Генерирует случайный товар с базовыми параметрами
     * 
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Случайно выбираем тип товара
        $type = fake()->randomElement(['coffee', 'tea', 'accessory']);
        
        // Выбираем название в зависимости от типа
        $name = match($type) {
            'coffee' => fake()->randomElement(self::$coffeeNames),
            'tea' => fake()->randomElement(self::$teaNames),
            'accessory' => fake()->randomElement(self::$accessoryNames),
        };

        // Генерируем цену в зависимости от типа товара
        $price = match($type) {
            'coffee' => fake()->randomFloat(2, 250, 800),
            'tea' => fake()->randomFloat(2, 150, 500),
            'accessory' => fake()->randomFloat(2, 300, 2000),
        };

        // Иногда добавляем старую цену (скидка)
        $hasDiscount = fake()->boolean(30); // 30% товаров со скидкой
        $oldPrice = $hasDiscount ? round($price * fake()->randomFloat(2, 1.1, 1.5), 2) : null;

        // Генерируем SKU (артикул): CF-XXXX для кофе, TE-XXXX для чая
        $sku = match($type) {
            'coffee' => 'CF-' . fake()->unique()->numberBetween(1000, 9999),
            'tea' => 'TE-' . fake()->unique()->numberBetween(1000, 9999),
            'accessory' => 'AC-' . fake()->unique()->numberBetween(1000, 9999),
        };

        return [
            // Категория будет установлена в seeder в зависимости от типа товара
            'category_id' => Category::factory(),
            
            // Название товара
            'name' => $name,
            
            // Slug генерируется из названия
            'slug' => Str::slug($name) . '-' . fake()->numberBetween(1, 999),
            
            // Краткое описание (1-2 предложения)
            'description' => fake()->sentence(15),
            
            // Подробное описание (2-3 абзаца)
            'long_description' => fake()->paragraphs(3, true),
            
            // Текущая цена
            'price' => $price,
            
            // Старая цена (если есть скидка)
            'old_price' => $oldPrice,
            
            // Вес в граммах (типичные значения для кофе/чая)
            'weight' => fake()->randomElement([250, 500, 1000]),
            
            // Уникальный артикул
            'sku' => $sku,
            
            // Количество на складе
            'stock' => fake()->numberBetween(0, 100),
            
            // Средний рейтинг (3.5 - 5.0)
            // Будет обновлен после добавления отзывов
            'rating' => fake()->randomFloat(2, 3.5, 5.0),
            
            // Количество отзывов
            // Будет обновлено в seeder после создания отзывов
            'reviews_count' => 0,
            
            // Процент горчинки (только для кофе, для остальных - 0)
            'bitterness_percent' => $type === 'coffee' ? fake()->randomElement([0, 2, 4, 6, 8, 10]) : 0,
            
            // Процент кислинки (только для кофе, для остальных - 0)
            'acidity_percent' => $type === 'coffee' ? fake()->randomElement([0, 2, 4, 6, 8, 10]) : 0,
            
            // Рекомендуемый товар (показывается на главной)
            'is_featured' => fake()->boolean(20), // 20% товаров рекомендуемые
            
            // Доступен для заказа (зависит от наличия на складе)
            'is_available' => fake()->boolean(90), // 90% товаров доступны
            
            // SEO заголовок
            'meta_title' => $name . ' - купить в интернет-магазине',
            
            // SEO описание
            'meta_description' => 'Купить ' . $name . ' по выгодной цене. ' . fake()->sentence(10),
        ];
    }

    /**
     * Состояние для создания товара-кофе
     * 
     * Использование: Product::factory()->coffee()->create()
     * 
     * @return static
     */
    public function coffee(): static
    {
        $name = fake()->randomElement(self::$coffeeNames);
        
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->numberBetween(1, 999),
            'sku' => 'CF-' . fake()->unique()->numberBetween(1000, 9999),
            'price' => fake()->randomFloat(2, 250, 800),
            'weight' => fake()->randomElement([250, 500, 1000]),
            'bitterness_percent' => fake()->randomElement([2, 4, 6, 8, 10]), // Убрали 0
            'acidity_percent' => fake()->randomElement([2, 4, 6, 8, 10]), // Убрали 0
            'description' => 'Премиальный кофе ' . $name . '. ' . fake()->sentence(12),
        ]);
    }

    /**
     * Состояние для создания товара-чая
     * 
     * Использование: Product::factory()->tea()->create()
     * 
     * @return static
     */
    public function tea(): static
    {
        $name = fake()->randomElement(self::$teaNames);
        
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->numberBetween(1, 999),
            'sku' => 'TE-' . fake()->unique()->numberBetween(1000, 9999),
            'price' => fake()->randomFloat(2, 150, 500),
            'weight' => fake()->randomElement([50, 100, 250]),
            'bitterness_percent' => 0,
            'acidity_percent' => 0,
            'description' => 'Качественный чай ' . $name . '. ' . fake()->sentence(12),
        ]);
    }

    /**
     * Состояние для создания аксессуара
     * 
     * Использование: Product::factory()->accessory()->create()
     * 
     * @return static
     */
    public function accessory(): static
    {
        $name = fake()->randomElement(self::$accessoryNames);
        
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->numberBetween(1, 999),
            'sku' => 'AC-' . fake()->unique()->numberBetween(1000, 9999),
            'price' => fake()->randomFloat(2, 300, 2000),
            'weight' => fake()->randomElement([100, 200, 500, 1000]),
            'bitterness_percent' => 0,
            'acidity_percent' => 0,
            'description' => $name . ' для приготовления кофе. ' . fake()->sentence(12),
        ]);
    }

    /**
     * Состояние для товара со скидкой
     * 
     * Использование: Product::factory()->discounted()->create()
     * 
     * @return static
     */
    public function discounted(): static
    {
        return $this->state(function (array $attributes) {
            $price = $attributes['price'];
            $discount = fake()->randomFloat(2, 1.15, 1.5); // Скидка 15-50%
            
            return [
                'old_price' => round($price * $discount, 2),
            ];
        });
    }

    /**
     * Состояние для рекомендуемого товара
     * 
     * Использование: Product::factory()->featured()->create()
     * 
     * @return static
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'rating' => fake()->randomFloat(2, 4.5, 5.0), // Высокий рейтинг
        ]);
    }

    /**
     * Состояние для товара, которого нет в наличии
     * 
     * Использование: Product::factory()->outOfStock()->create()
     * 
     * @return static
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
            'is_available' => false,
        ]);
    }
}
