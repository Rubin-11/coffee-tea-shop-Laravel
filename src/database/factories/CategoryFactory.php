<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory для генерации категорий товаров
 * 
 * Создает реалистичные категории для магазина кофе и чая:
 * - Главные категории: "Кофе в зернах", "Молотый кофе", "Чай", "Аксессуары"
 * - Подкатегории: "Арабика", "Робуста", "Эспрессо-смеси" и т.д.
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Модель, для которой создается фабрика
     * 
     * @var string
     */
    protected $model = Category::class;

    /**
     * Определение состояния по умолчанию для модели
     * 
     * Генерирует случайную категорию с базовыми параметрами
     * 
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Массив возможных названий категорий
        $categoryNames = [
            'Кофе в зернах',
            'Молотый кофе',
            'Чай',
            'Аксессуары',
            'Подарочные наборы',
            'Арабика',
            'Робуста',
            'Эспрессо-смеси',
            'Зеленый чай',
            'Черный чай',
            'Турки и кофеварки',
            'Чашки и кружки',
        ];

        $name = fake()->unique()->randomElement($categoryNames);

        return [
            // Название категории
            'name' => $name,
            
            // Slug генерируется автоматически из названия
            // Например: "Кофе в зернах" -> "kofe-v-zernah"
            'slug' => Str::slug($name),
            
            // Описание категории (1-2 предложения)
            'description' => fake()->optional(0.7)->sentence(12),
            
            // Путь к изображению категории (placeholder)
            'image' => fake()->optional(0.5)->randomElement([
                'categories/coffee-beans.jpg',
                'categories/ground-coffee.jpg',
                'categories/tea.jpg',
                'categories/accessories.jpg',
            ]),
            
            // parent_id будет установлен отдельно для подкатегорий
            'parent_id' => null,
            
            // Порядок сортировки (0-100)
            'sort_order' => fake()->numberBetween(0, 100),
            
            // Большинство категорий активны
            'is_active' => fake()->boolean(90),
        ];
    }

    /**
     * Состояние для создания главной категории (без родителя)
     * 
     * Использование: Category::factory()->parent()->create()
     * 
     * @return static
     */
    public function parent(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => null,
            'sort_order' => fake()->numberBetween(0, 10),
        ]);
    }

    /**
     * Состояние для создания подкатегории (с родителем)
     * 
     * Использование: Category::factory()->child($parentId)->create()
     * 
     * @param int $parentId ID родительской категории
     * @return static
     */
    public function child(int $parentId): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parentId,
            'sort_order' => fake()->numberBetween(0, 50),
        ]);
    }

    /**
     * Состояние для неактивной категории
     * 
     * Использование: Category::factory()->inactive()->create()
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
