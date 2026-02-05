<?php

namespace Database\Factories;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory для генерации статей блога
 * 
 * Создает реалистичные статьи для блога магазина:
 * - Статьи о здоровом питании
 * - Рецепты с кофе и чаем
 * - Гиды по выбору кофе
 * - История происхождения кофейных сортов
 * - Новости компании
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BlogPost>
 */
class BlogPostFactory extends Factory
{
    /**
     * Модель, для которой создается фабрика
     * 
     * @var string
     */
    protected $model = BlogPost::class;

    /**
     * Категории статей блога
     * 
     * @var array<string>
     */
    private static array $categories = [
        'Здоровое питание',
        'Рецепты с кофе',
        'Гид по выбору кофе',
        'История происхождения',
        'Новости компании',
        'Советы бариста',
        'Культура чаепития',
        'Обзоры оборудования',
    ];

    /**
     * Заголовки статей о кофе
     * 
     * @var array<string>
     */
    private static array $coffeeTitles = [
        '5 способов заварить идеальный кофе дома',
        'Как выбрать кофе для эспрессо: гид для начинающих',
        'Арабика vs Робуста: в чем разница?',
        'История происхождения кофе: от Эфиопии до наших дней',
        'Топ-10 сортов кофе, которые стоит попробовать',
        'Как правильно хранить кофе в зернах',
        '7 ошибок при приготовлении кофе в турке',
        'Альтернативные методы заваривания кофе',
        'Что такое specialty coffee и почему он особенный',
        'Кофе и здоровье: мифы и реальность',
    ];

    /**
     * Заголовки статей о чае
     * 
     * @var array<string>
     */
    private static array $teaTitles = [
        'Церемония чаепития: традиции разных стран',
        'Зеленый чай: польза и правила заваривания',
        'Как выбрать качественный чай',
        'Матча: японский порошковый чай',
        'Пуэр: чай с многолетней выдержкой',
        'Травяные чаи для здоровья и красоты',
        'Улун: между зеленым и черным чаем',
        'Холодное заваривание чая: летний тренд',
    ];

    /**
     * Определение состояния по умолчанию для модели
     * 
     * Генерирует случайную статью блога
     * 
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Случайно выбираем тему статьи
        $isCoffee = fake()->boolean(60); // 60% статей о кофе, 40% о чае
        $title = $isCoffee 
            ? fake()->randomElement(self::$coffeeTitles)
            : fake()->randomElement(self::$teaTitles);

        // Генерируем содержимое статьи
        $paragraphs = fake()->paragraphs(fake()->numberBetween(5, 10));
        $content = '<p>' . implode('</p><p>', $paragraphs) . '</p>';

        // Определяем, опубликована ли статья
        $isPublished = fake()->boolean(85); // 85% статей опубликованы
        $publishedAt = $isPublished ? fake()->dateTimeBetween('-6 months', 'now') : null;

        return [
            // Заголовок статьи
            'title' => $title,
            
            // Slug генерируется из заголовка
            'slug' => Str::slug($title) . '-' . fake()->numberBetween(1, 999),
            
            // Краткое описание (анонс)
            'excerpt' => fake()->sentence(20),
            
            // Полный текст статьи (HTML)
            'content' => $content,
            
            // Главное изображение статьи
            'featured_image' => 'blog/article-' . fake()->numberBetween(1, 20) . '.jpg',
            
            // Автор статьи (будет установлен в seeder)
            'author_id' => User::factory(),
            
            // Категория статьи
            'category' => fake()->randomElement(self::$categories),
            
            // Количество просмотров
            'views_count' => fake()->numberBetween(50, 500),
            
            // Опубликована ли статья
            'is_published' => $isPublished,
            
            // Дата публикации
            'published_at' => $publishedAt,
            
            // SEO заголовок
            'meta_title' => $title . ' | Блог Coffee-Tea Shop',
            
            // SEO описание
            'meta_description' => fake()->sentence(20),
        ];
    }

    /**
     * Состояние для опубликованной статьи
     * 
     * Использование: BlogPost::factory()->published()->create()
     * 
     * @return static
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
            'published_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    /**
     * Состояние для черновика статьи
     * 
     * Использование: BlogPost::factory()->draft()->create()
     * 
     * @return static
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }

    /**
     * Состояние для популярной статьи
     * 
     * Использование: BlogPost::factory()->popular()->create()
     * 
     * @return static
     */
    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
            'published_at' => fake()->dateTimeBetween('-3 months', '-1 month'),
            'views_count' => fake()->numberBetween(500, 2000),
        ]);
    }

    /**
     * Состояние для статьи о кофе
     * 
     * Использование: BlogPost::factory()->coffee()->create()
     * 
     * @return static
     */
    public function coffee(): static
    {
        $title = fake()->randomElement(self::$coffeeTitles);
        
        return $this->state(fn (array $attributes) => [
            'title' => $title,
            'slug' => Str::slug($title) . '-' . fake()->numberBetween(1, 999),
            'category' => fake()->randomElement([
                'Рецепты с кофе',
                'Гид по выбору кофе',
                'История происхождения',
                'Советы бариста',
            ]),
            'featured_image' => 'blog/coffee-' . fake()->numberBetween(1, 15) . '.jpg',
        ]);
    }

    /**
     * Состояние для статьи о чае
     * 
     * Использование: BlogPost::factory()->tea()->create()
     * 
     * @return static
     */
    public function tea(): static
    {
        $title = fake()->randomElement(self::$teaTitles);
        
        return $this->state(fn (array $attributes) => [
            'title' => $title,
            'slug' => Str::slug($title) . '-' . fake()->numberBetween(1, 999),
            'category' => fake()->randomElement([
                'Культура чаепития',
                'Здоровое питание',
            ]),
            'featured_image' => 'blog/tea-' . fake()->numberBetween(1, 10) . '.jpg',
        ]);
    }

    /**
     * Состояние для недавней статьи
     * 
     * Использование: BlogPost::factory()->recent()->create()
     * 
     * @return static
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
            'published_at' => fake()->dateTimeBetween('-2 weeks', 'now'),
            'views_count' => fake()->numberBetween(50, 200),
        ]);
    }
}
