<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory для генерации изображений товаров
 * 
 * Создает изображения для товаров (галерея).
 * Каждый товар может иметь 2-4 изображения.
 * Одно из изображений помечается как главное (is_primary).
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductImage>
 */
class ProductImageFactory extends Factory
{
    /**
     * Модель, для которой создается фабрика
     * 
     * @var string
     */
    protected $model = ProductImage::class;

    /**
     * Определение состояния по умолчанию для модели
     * 
     * Генерирует изображение товара с placeholder
     * 
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Используем placeholder-сервис для тестовых изображений
        // Размер изображения: 800x800 пикселей
        $imageNumber = fake()->numberBetween(1, 20);
        
        return [
            // ID товара будет установлен при создании
            'product_id' => Product::factory(),
            
            // Путь к изображению (можно использовать placeholder или реальные файлы)
            'image_path' => "products/product-{$imageNumber}.jpg",
            
            // Альтернативный текст для SEO и доступности
            'alt_text' => fake()->words(3, true),
            
            // Порядок отображения в галерее (начинается с 0)
            'sort_order' => 0,
            
            // По умолчанию не главное изображение
            'is_primary' => false,
        ];
    }

    /**
     * Состояние для создания главного изображения товара
     * 
     * Использование: ProductImage::factory()->primary()->create()
     * 
     * @return static
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
            'sort_order' => 0, // Главное изображение всегда первое
        ]);
    }

    /**
     * Состояние для создания изображения кофе
     * 
     * Использование: ProductImage::factory()->coffee()->create()
     * 
     * @return static
     */
    public function coffee(): static
    {
        $imageNumber = fake()->numberBetween(1, 15);
        
        return $this->state(fn (array $attributes) => [
            'image_path' => "products/coffee-{$imageNumber}.jpg",
            'alt_text' => 'Кофе ' . fake()->words(2, true),
        ]);
    }

    /**
     * Состояние для создания изображения чая
     * 
     * Использование: ProductImage::factory()->tea()->create()
     * 
     * @return static
     */
    public function tea(): static
    {
        $imageNumber = fake()->numberBetween(1, 10);
        
        return $this->state(fn (array $attributes) => [
            'image_path' => "products/tea-{$imageNumber}.jpg",
            'alt_text' => 'Чай ' . fake()->words(2, true),
        ]);
    }

    /**
     * Состояние для создания изображения аксессуара
     * 
     * Использование: ProductImage::factory()->accessory()->create()
     * 
     * @return static
     */
    public function accessory(): static
    {
        $imageNumber = fake()->numberBetween(1, 8);
        
        return $this->state(fn (array $attributes) => [
            'image_path' => "products/accessory-{$imageNumber}.jpg",
            'alt_text' => 'Аксессуар ' . fake()->words(2, true),
        ]);
    }
}
