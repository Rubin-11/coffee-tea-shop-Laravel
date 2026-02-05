<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory для генерации тегов товаров
 * 
 * Создает теги для маркировки товаров:
 * "Новинка", "Хит продаж", "Акция", "Органический", "Премиум"
 * 
 * Теги используются для быстрой фильтрации и привлечения внимания к товарам
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tag>
 */
class TagFactory extends Factory
{
    /**
     * Модель, для которой создается фабрика
     * 
     * @var string
     */
    protected $model = Tag::class;

    /**
     * Определение состояния по умолчанию для модели
     * 
     * Генерирует случайный тег из предопределенного списка
     * 
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Список популярных тегов для магазина кофе и чая
        $tagNames = [
            'Новинка',
            'Хит продаж',
            'Акция',
            'Органический',
            'Премиум',
            'Скидка',
            'Ограниченная серия',
            'Best Seller',
            'Эксклюзив',
            'Рекомендуем',
        ];

        $name = fake()->unique()->randomElement($tagNames);

        return [
            // Название тега
            'name' => $name,
            
            // Slug генерируется из названия
            // Например: "Хит продаж" -> "hit-prodazh"
            'slug' => Str::slug($name),
        ];
    }
}
