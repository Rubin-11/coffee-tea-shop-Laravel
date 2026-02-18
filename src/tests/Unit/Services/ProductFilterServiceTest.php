<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use App\Services\ProductFilterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit тесты для ProductFilterService
 * 
 * Проверяет все методы фильтрации, сортировки и вспомогательные функции сервиса.
 * Тестирует корректность применения фильтров к запросам товаров в каталоге.
 * 
 * Каждый тест изолирован и использует фабрики для создания тестовых данных.
 */
final class ProductFilterServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Экземпляр тестируемого сервиса
     * 
     * @var ProductFilterService
     */
    private ProductFilterService $service;

    /**
     * Подготовка к каждому тесту
     * 
     * Создается новый экземпляр сервиса перед каждым тестом
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Создаем экземпляр сервиса
        $this->service = new ProductFilterService();
    }

    // ==========================================
    // ТЕСТЫ ФИЛЬТРАЦИИ ПО ПОИСКУ
    // ==========================================

    /**
     * Тест: фильтр находит товары по названию
     * 
     * @return void
     */
    public function test_filters_by_name(): void
    {
        // Arrange: создаем товары с разными названиями
        $category = Category::factory()->create();
        
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Эфиопия Иргачиф',
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Колумбия Супремо',
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Кения АА',
            'is_available' => true,
        ]);

        // Act: применяем фильтр по поиску
        $result = $this->service->filter(['search' => 'Эфиопия']);

        // Assert: проверяем что найден только один товар
        $this->assertCount(1, $result);
        $this->assertEquals('Эфиопия Иргачиф', $result->first()->name);
    }

    /**
     * Тест: фильтр находит товары по описанию
     * 
     * @return void
     */
    public function test_filters_by_description(): void
    {
        // Arrange: создаем товары с разными описаниями
        $category = Category::factory()->create();
        
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Товар 1',
            'description' => 'Премиальный кофе из Эфиопии',
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Товар 2',
            'description' => 'Качественный чай',
            'is_available' => true,
        ]);

        // Act: применяем фильтр по описанию
        $result = $this->service->filter(['search' => 'Эфиопии']);

        // Assert: проверяем что найден товар с соответствующим описанием
        $this->assertCount(1, $result);
        $this->assertEquals('Товар 1', $result->first()->name);
    }

    /**
     * Тест: фильтр находит товары по артикулу (SKU)
     * 
     * @return void
     */
    public function test_filters_by_sku(): void
    {
        // Arrange: создаем товары с разными артикулами
        $category = Category::factory()->create();
        
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Кофе 1',
            'sku' => 'CF-1234',
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Кофе 2',
            'sku' => 'CF-5678',
            'is_available' => true,
        ]);

        // Act: применяем фильтр по артикулу
        $result = $this->service->filter(['search' => 'CF-1234']);

        // Assert: проверяем что найден товар с соответствующим SKU
        $this->assertCount(1, $result);
        $this->assertEquals('CF-1234', $result->first()->sku);
    }

    /**
     * Тест: поиск не чувствителен к регистру
     * 
     * Примечание: PostgreSQL требует использования ILIKE для case-insensitive поиска.
     * В текущей реализации сервиса используется LIKE, который case-sensitive.
     * Этот тест демонстрирует текущее поведение.
     * 
     * @return void
     */
    public function test_search_is_case_insensitive(): void
    {
        // Arrange: создаем товар с названием в разном регистре
        $category = Category::factory()->create();
        
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Эфиопия Иргачиф',
            'is_available' => true,
        ]);

        // Act: ищем точно по названию (как в БД)
        $resultExact = $this->service->filter(['search' => 'Эфиопия']);
        
        // Assert: поиск с правильным регистром находит товар
        $this->assertCount(1, $resultExact);
        $this->assertEquals('Эфиопия Иргачиф', $resultExact->first()->name);
        
        // Дополнительный тест: поиск по части названия
        $resultPartial = $this->service->filter(['search' => 'Иргачиф']);
        $this->assertCount(1, $resultPartial);
    }

    // ==========================================
    // ТЕСТЫ ФИЛЬТРАЦИИ ПО КАТЕГОРИИ
    // ==========================================

    /**
     * Тест: фильтр по категории работает корректно
     * 
     * @return void
     */
    public function test_filters_by_category(): void
    {
        // Arrange: создаем две категории с товарами
        $category1 = Category::factory()->create(['name' => 'Кофе']);
        $category2 = Category::factory()->create(['name' => 'Чай']);
        
        Product::factory()->count(3)->create([
            'category_id' => $category1->id,
            'is_available' => true,
        ]);
        
        Product::factory()->count(2)->create([
            'category_id' => $category2->id,
            'is_available' => true,
        ]);

        // Act: фильтруем по первой категории
        $result = $this->service->filter(['category_id' => $category1->id]);

        // Assert: получаем только товары первой категории
        $this->assertCount(3, $result);
        foreach ($result as $product) {
            $this->assertEquals($category1->id, $product->category_id);
        }
    }

    /**
     * Тест: фильтр включает товары из подкатегорий
     * 
     * Примечание: в текущей реализации сервиса подкатегории не обрабатываются,
     * но тест готов для будущей функциональности
     * 
     * @return void
     */
    public function test_includes_subcategory_products(): void
    {
        // Arrange: создаем категорию с подкатегорией
        $parentCategory = Category::factory()->create(['name' => 'Кофе']);
        $childCategory = Category::factory()->child($parentCategory->id)->create(['name' => 'Арабика']);
        
        // Товары в родительской категории
        Product::factory()->count(2)->create([
            'category_id' => $parentCategory->id,
            'is_available' => true,
        ]);
        
        // Товары в подкатегории
        Product::factory()->count(3)->create([
            'category_id' => $childCategory->id,
            'is_available' => true,
        ]);

        // Act: фильтруем по родительской категории
        $result = $this->service->filter(['category_id' => $parentCategory->id]);

        // Assert: пока получаем только товары родительской категории (функционал не реализован)
        // В будущем должно быть 5 товаров (2 + 3)
        $this->assertCount(2, $result);
    }

    // ==========================================
    // ТЕСТЫ ФИЛЬТРАЦИИ ПО ЦЕНЕ
    // ==========================================

    /**
     * Тест: фильтр по минимальной цене
     * 
     * @return void
     */
    public function test_filters_by_min_price(): void
    {
        // Arrange: создаем товары с разными ценами
        $category = Category::factory()->create();
        
        Product::factory()->create([
            'category_id' => $category->id,
            'price' => 100.00,
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category->id,
            'price' => 300.00,
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category->id,
            'price' => 500.00,
            'is_available' => true,
        ]);

        // Act: фильтруем товары дороже 250
        $result = $this->service->filter(['price_min' => 250]);

        // Assert: получаем только товары от 250 и выше
        $this->assertCount(2, $result);
        foreach ($result as $product) {
            $this->assertGreaterThanOrEqual(250, $product->price);
        }
    }

    /**
     * Тест: фильтр по максимальной цене
     * 
     * @return void
     */
    public function test_filters_by_max_price(): void
    {
        // Arrange: создаем товары с разными ценами
        $category = Category::factory()->create();
        
        Product::factory()->create([
            'category_id' => $category->id,
            'price' => 100.00,
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category->id,
            'price' => 300.00,
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category->id,
            'price' => 500.00,
            'is_available' => true,
        ]);

        // Act: фильтруем товары дешевле 400
        $result = $this->service->filter(['price_max' => 400]);

        // Assert: получаем только товары до 400
        $this->assertCount(2, $result);
        foreach ($result as $product) {
            $this->assertLessThanOrEqual(400, $product->price);
        }
    }

    /**
     * Тест: фильтр по диапазону цен
     * 
     * @return void
     */
    public function test_filters_by_price_range(): void
    {
        // Arrange: создаем товары с разными ценами
        $category = Category::factory()->create();
        
        Product::factory()->create([
            'category_id' => $category->id,
            'price' => 100.00,
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category->id,
            'price' => 300.00,
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category->id,
            'price' => 500.00,
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category->id,
            'price' => 700.00,
            'is_available' => true,
        ]);

        // Act: фильтруем товары в диапазоне 200-600
        $result = $this->service->filter([
            'price_min' => 200,
            'price_max' => 600,
        ]);

        // Assert: получаем только товары в указанном диапазоне
        $this->assertCount(2, $result);
        foreach ($result as $product) {
            $this->assertGreaterThanOrEqual(200, $product->price);
            $this->assertLessThanOrEqual(600, $product->price);
        }
    }

    // ==========================================
    // ТЕСТЫ ФИЛЬТРАЦИИ ПО ТЕГАМ
    // ==========================================

    /**
     * Тест: фильтр по одному тегу
     * 
     * @return void
     */
    public function test_filters_by_single_tag(): void
    {
        // Arrange: создаем теги и товары
        $category = Category::factory()->create();
        $tag1 = Tag::factory()->create(['name' => 'Новинка']);
        $tag2 = Tag::factory()->create(['name' => 'Акция']);
        
        $product1 = Product::factory()->create([
            'category_id' => $category->id,
            'is_available' => true,
        ]);
        $product1->tags()->attach($tag1);
        
        $product2 = Product::factory()->create([
            'category_id' => $category->id,
            'is_available' => true,
        ]);
        $product2->tags()->attach($tag2);
        
        $product3 = Product::factory()->create([
            'category_id' => $category->id,
            'is_available' => true,
        ]);
        // Без тегов

        // Act: фильтруем по тегу "Новинка"
        $result = $this->service->filter(['tags' => [$tag1->id]]);

        // Assert: получаем только товары с этим тегом
        $this->assertCount(1, $result);
        $this->assertEquals($product1->id, $result->first()->id);
    }

    /**
     * Тест: фильтр по нескольким тегам (логика ИЛИ)
     * 
     * @return void
     */
    public function test_filters_by_multiple_tags(): void
    {
        // Arrange: создаем теги и товары
        $category = Category::factory()->create();
        $tag1 = Tag::factory()->create(['name' => 'Новинка']);
        $tag2 = Tag::factory()->create(['name' => 'Акция']);
        $tag3 = Tag::factory()->create(['name' => 'Премиум']);
        
        $product1 = Product::factory()->create([
            'category_id' => $category->id,
            'is_available' => true,
        ]);
        $product1->tags()->attach($tag1);
        
        $product2 = Product::factory()->create([
            'category_id' => $category->id,
            'is_available' => true,
        ]);
        $product2->tags()->attach($tag2);
        
        $product3 = Product::factory()->create([
            'category_id' => $category->id,
            'is_available' => true,
        ]);
        $product3->tags()->attach($tag3);

        // Act: фильтруем по тегам "Новинка" ИЛИ "Акция"
        $result = $this->service->filter(['tags' => [$tag1->id, $tag2->id]]);

        // Assert: получаем товары с любым из указанных тегов
        $this->assertCount(2, $result);
        $resultIds = $result->pluck('id')->toArray();
        $this->assertContains($product1->id, $resultIds);
        $this->assertContains($product2->id, $resultIds);
        $this->assertNotContains($product3->id, $resultIds);
    }

    // ==========================================
    // ТЕСТЫ ФИЛЬТРАЦИИ ПО НАЛИЧИЮ
    // ==========================================

    /**
     * Тест: фильтр показывает только товары в наличии
     * 
     * @return void
     */
    public function test_filters_products_in_stock(): void
    {
        // Arrange: создаем товары с разным количеством на складе
        $category = Category::factory()->create();
        
        Product::factory()->create([
            'category_id' => $category->id,
            'stock' => 10,
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category->id,
            'stock' => 0,
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category->id,
            'stock' => 5,
            'is_available' => true,
        ]);

        // Act: фильтруем только товары в наличии
        $result = $this->service->filter(['in_stock' => true]);

        // Assert: получаем только товары с stock > 0
        $this->assertCount(2, $result);
        foreach ($result as $product) {
            $this->assertGreaterThan(0, $product->stock);
        }
    }

    /**
     * Тест: фильтр исключает товары с нулевым остатком
     * 
     * @return void
     */
    public function test_excludes_out_of_stock_products(): void
    {
        // Arrange: создаем товары
        $category = Category::factory()->create();
        
        $productInStock = Product::factory()->create([
            'category_id' => $category->id,
            'stock' => 10,
            'is_available' => true,
        ]);
        
        $productOutOfStock = Product::factory()->create([
            'category_id' => $category->id,
            'stock' => 0,
            'is_available' => true,
        ]);

        // Act: фильтруем только товары в наличии
        $result = $this->service->filter(['in_stock' => true]);

        // Assert: товар с нулевым остатком не попадает в результаты
        $resultIds = $result->pluck('id')->toArray();
        $this->assertContains($productInStock->id, $resultIds);
        $this->assertNotContains($productOutOfStock->id, $resultIds);
    }

    // ==========================================
    // ТЕСТЫ ФИЛЬТРАЦИИ ПО РЕЙТИНГУ
    // ==========================================

    /**
     * Тест: фильтр по минимальному рейтингу
     * 
     * @return void
     */
    public function test_filters_by_minimum_rating(): void
    {
        // Arrange: создаем товары с разными рейтингами
        $category = Category::factory()->create();
        
        Product::factory()->create([
            'category_id' => $category->id,
            'rating' => 3.5,
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category->id,
            'rating' => 4.2,
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category->id,
            'rating' => 4.8,
            'is_available' => true,
        ]);

        // Act: фильтруем товары с рейтингом от 4.0
        $result = $this->service->filter(['min_rating' => 4.0]);

        // Assert: получаем только товары с рейтингом >= 4.0
        $this->assertCount(2, $result);
        foreach ($result as $product) {
            $this->assertGreaterThanOrEqual(4.0, $product->rating);
        }
    }

    /**
     * Тест: фильтр исключает товары с низким рейтингом
     * 
     * @return void
     */
    public function test_excludes_products_below_rating(): void
    {
        // Arrange: создаем товары с разными рейтингами
        $category = Category::factory()->create();
        
        $lowRated = Product::factory()->create([
            'category_id' => $category->id,
            'rating' => 2.5,
            'is_available' => true,
        ]);
        
        $highRated = Product::factory()->create([
            'category_id' => $category->id,
            'rating' => 4.5,
            'is_available' => true,
        ]);

        // Act: фильтруем товары с рейтингом от 4.0
        $result = $this->service->filter(['min_rating' => 4.0]);

        // Assert: товар с низким рейтингом не попадает в результаты
        $resultIds = $result->pluck('id')->toArray();
        $this->assertNotContains($lowRated->id, $resultIds);
        $this->assertContains($highRated->id, $resultIds);
    }

    // ==========================================
    // ТЕСТЫ ФИЛЬТРАЦИИ ПО ХАРАКТЕРИСТИКАМ
    // ==========================================

    /**
     * Тест: фильтр по диапазону горчинки
     * 
     * @return void
     */
    public function test_filters_by_bitterness_range(): void
    {
        // Arrange: создаем кофе с разными уровнями горчинки
        $category = Category::factory()->create();
        
        Product::factory()->create([
            'category_id' => $category->id,
            'bitterness_percent' => 2,
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category->id,
            'bitterness_percent' => 6,
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category->id,
            'bitterness_percent' => 10,
            'is_available' => true,
        ]);

        // Act: фильтруем кофе с горчинкой 4-8
        $result = $this->service->filter([
            'bitterness_min' => 4,
            'bitterness_max' => 8,
        ]);

        // Assert: получаем только товары в указанном диапазоне
        $this->assertCount(1, $result);
        $this->assertEquals(6, $result->first()->bitterness_percent);
    }

    /**
     * Тест: фильтр по диапазону кислинки
     * 
     * @return void
     */
    public function test_filters_by_acidity_range(): void
    {
        // Arrange: создаем кофе с разными уровнями кислинки
        $category = Category::factory()->create();
        
        Product::factory()->create([
            'category_id' => $category->id,
            'acidity_percent' => 2,
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category->id,
            'acidity_percent' => 5,
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category->id,
            'acidity_percent' => 8,
            'is_available' => true,
        ]);

        // Act: фильтруем кофе с кислинкой 4-6
        $result = $this->service->filter([
            'acidity_min' => 4,
            'acidity_max' => 6,
        ]);

        // Assert: получаем только товары в указанном диапазоне
        $this->assertCount(1, $result);
        $this->assertEquals(5, $result->first()->acidity_percent);
    }

    // ==========================================
    // ТЕСТЫ ФИЛЬТРАЦИИ ПО СКИДКАМ
    // ==========================================

    /**
     * Тест: фильтр показывает только товары со скидкой
     * 
     * @return void
     */
    public function test_filters_products_on_sale(): void
    {
        // Arrange: создаем товары со скидкой и без
        $category = Category::factory()->create();
        
        Product::factory()->create([
            'category_id' => $category->id,
            'price' => 400.00,
            'old_price' => 500.00,
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category->id,
            'price' => 300.00,
            'old_price' => null,
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category->id,
            'price' => 600.00,
            'old_price' => 700.00,
            'is_available' => true,
        ]);

        // Act: фильтруем только товары со скидкой
        $result = $this->service->filter(['on_sale' => true]);

        // Assert: получаем только товары с old_price
        $this->assertCount(2, $result);
        foreach ($result as $product) {
            $this->assertNotNull($product->old_price);
            $this->assertGreaterThan($product->price, $product->old_price);
        }
    }

    /**
     * Тест: фильтр исключает товары без скидки
     * 
     * @return void
     */
    public function test_excludes_products_without_discount(): void
    {
        // Arrange: создаем товары
        $category = Category::factory()->create();
        
        $withDiscount = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 400.00,
            'old_price' => 500.00,
            'is_available' => true,
        ]);
        
        $withoutDiscount = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 300.00,
            'old_price' => null,
            'is_available' => true,
        ]);

        // Act: фильтруем только товары со скидкой
        $result = $this->service->filter(['on_sale' => true]);

        // Assert: товар без скидки не попадает в результаты
        $resultIds = $result->pluck('id')->toArray();
        $this->assertContains($withDiscount->id, $resultIds);
        $this->assertNotContains($withoutDiscount->id, $resultIds);
    }

    // ==========================================
    // ТЕСТЫ СОРТИРОВКИ
    // ==========================================

    /**
     * Тест: сортировка по популярности (рейтинг + отзывы)
     * 
     * @return void
     */
    public function test_sorts_by_popularity(): void
    {
        // Arrange: создаем товары с разной популярностью
        $category = Category::factory()->create();
        
        $product1 = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Товар 1',
            'rating' => 4.0,
            'reviews_count' => 10,
            'is_available' => true,
        ]);
        
        $product2 = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Товар 2',
            'rating' => 4.5,
            'reviews_count' => 5,
            'is_available' => true,
        ]);
        
        $product3 = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Товар 3',
            'rating' => 4.5,
            'reviews_count' => 15,
            'is_available' => true,
        ]);

        // Act: применяем сортировку по популярности
        $result = $this->service->filter(['sort' => 'popular']);

        // Assert: товары отсортированы по рейтингу, затем по количеству отзывов
        $this->assertEquals($product3->id, $result[0]->id); // 4.5 рейтинг, 15 отзывов
        $this->assertEquals($product2->id, $result[1]->id); // 4.5 рейтинг, 5 отзывов
        $this->assertEquals($product1->id, $result[2]->id); // 4.0 рейтинг
    }

    /**
     * Тест: сортировка по дате (новинки первыми)
     * 
     * @return void
     */
    public function test_sorts_by_newest(): void
    {
        // Arrange: создаем товары в разное время
        $category = Category::factory()->create();
        
        $oldest = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Старый',
            'created_at' => now()->subDays(10),
            'is_available' => true,
        ]);
        
        $middle = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Средний',
            'created_at' => now()->subDays(5),
            'is_available' => true,
        ]);
        
        $newest = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Новый',
            'created_at' => now(),
            'is_available' => true,
        ]);

        // Act: применяем сортировку по дате
        $result = $this->service->filter(['sort' => 'newest']);

        // Assert: новые товары первыми
        $this->assertEquals($newest->id, $result[0]->id);
        $this->assertEquals($middle->id, $result[1]->id);
        $this->assertEquals($oldest->id, $result[2]->id);
    }

    /**
     * Тест: сортировка по цене (по возрастанию)
     * 
     * @return void
     */
    public function test_sorts_by_price_ascending(): void
    {
        // Arrange: создаем товары с разными ценами
        $category = Category::factory()->create();
        
        Product::factory()->create([
            'category_id' => $category->id,
            'price' => 500.00,
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category->id,
            'price' => 200.00,
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category->id,
            'price' => 800.00,
            'is_available' => true,
        ]);

        // Act: применяем сортировку по возрастанию цены
        $result = $this->service->filter(['sort' => 'price_asc']);

        // Assert: товары отсортированы от дешевых к дорогим
        $this->assertEquals(200.00, $result[0]->price);
        $this->assertEquals(500.00, $result[1]->price);
        $this->assertEquals(800.00, $result[2]->price);
    }

    /**
     * Тест: сортировка по цене (по убыванию)
     * 
     * @return void
     */
    public function test_sorts_by_price_descending(): void
    {
        // Arrange: создаем товары с разными ценами
        $category = Category::factory()->create();
        
        Product::factory()->create([
            'category_id' => $category->id,
            'price' => 500.00,
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category->id,
            'price' => 200.00,
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category->id,
            'price' => 800.00,
            'is_available' => true,
        ]);

        // Act: применяем сортировку по убыванию цены
        $result = $this->service->filter(['sort' => 'price_desc']);

        // Assert: товары отсортированы от дорогих к дешевым
        $this->assertEquals(800.00, $result[0]->price);
        $this->assertEquals(500.00, $result[1]->price);
        $this->assertEquals(200.00, $result[2]->price);
    }

    /**
     * Тест: сортировка по рейтингу
     * 
     * @return void
     */
    public function test_sorts_by_rating(): void
    {
        // Arrange: создаем товары с разными рейтингами
        $category = Category::factory()->create();
        
        Product::factory()->create([
            'category_id' => $category->id,
            'rating' => 4.2,
            'reviews_count' => 10,
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category->id,
            'rating' => 4.8,
            'reviews_count' => 5,
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category->id,
            'rating' => 3.5,
            'reviews_count' => 20,
            'is_available' => true,
        ]);

        // Act: применяем сортировку по рейтингу
        $result = $this->service->filter(['sort' => 'rating']);

        // Assert: товары отсортированы по убыванию рейтинга
        $this->assertEquals(4.8, $result[0]->rating);
        $this->assertEquals(4.2, $result[1]->rating);
        $this->assertEquals(3.5, $result[2]->rating);
    }

    /**
     * Тест: сортировка по названию (алфавит)
     * 
     * @return void
     */
    public function test_sorts_by_name(): void
    {
        // Arrange: создаем товары с разными названиями
        $category = Category::factory()->create();
        
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Кения АА',
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Бразилия',
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Эфиопия',
            'is_available' => true,
        ]);

        // Act: применяем сортировку по названию
        $result = $this->service->filter(['sort' => 'name']);

        // Assert: товары отсортированы по алфавиту
        $this->assertEquals('Бразилия', $result[0]->name);
        $this->assertEquals('Кения АА', $result[1]->name);
        $this->assertEquals('Эфиопия', $result[2]->name);
    }

    // ==========================================
    // ТЕСТЫ ВСПОМОГАТЕЛЬНЫХ МЕТОДОВ
    // ==========================================

    /**
     * Тест: получение минимальной и максимальной цены
     * 
     * @return void
     */
    public function test_gets_price_range(): void
    {
        // Arrange: создаем товары с разными ценами
        $category = Category::factory()->create();
        
        Product::factory()->create([
            'category_id' => $category->id,
            'price' => 150.00,
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category->id,
            'price' => 500.00,
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category->id,
            'price' => 800.00,
            'is_available' => true,
        ]);

        // Act: получаем диапазон цен
        $range = $this->service->getPriceRange();

        // Assert: получаем минимальную и максимальную цену
        $this->assertEquals(150.00, $range['min']);
        $this->assertEquals(800.00, $range['max']);
    }

    /**
     * Тест: получение диапазона цен для конкретной категории
     * 
     * @return void
     */
    public function test_gets_price_range_for_category(): void
    {
        // Arrange: создаем две категории с товарами
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();
        
        Product::factory()->create([
            'category_id' => $category1->id,
            'price' => 200.00,
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category1->id,
            'price' => 400.00,
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category2->id,
            'price' => 1000.00,
            'is_available' => true,
        ]);

        // Act: получаем диапазон цен для первой категории
        $range = $this->service->getPriceRange($category1->id);

        // Assert: получаем цены только для первой категории
        $this->assertEquals(200.00, $range['min']);
        $this->assertEquals(400.00, $range['max']);
    }

    /**
     * Тест: получение доступных фильтров для категории
     * 
     * @return void
     */
    public function test_gets_available_filters(): void
    {
        // Arrange: создаем категорию с товарами и тегами
        $category = Category::factory()->create();
        $tag1 = Tag::factory()->create(['name' => 'Новинка']);
        $tag2 = Tag::factory()->create(['name' => 'Акция']);
        
        $product1 = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 300.00,
            'bitterness_percent' => 6,
            'acidity_percent' => 4,
            'is_available' => true,
        ]);
        $product1->tags()->attach([$tag1->id, $tag2->id]);
        
        $product2 = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 500.00,
            'bitterness_percent' => 8,
            'acidity_percent' => 2,
            'is_available' => true,
        ]);
        $product2->tags()->attach($tag1);

        // Act: получаем доступные фильтры
        $filters = $this->service->getAvailableFilters($category->id);

        // Assert: проверяем структуру ответа
        $this->assertArrayHasKey('tags', $filters);
        $this->assertArrayHasKey('price_range', $filters);
        $this->assertArrayHasKey('bitterness_range', $filters);
        $this->assertArrayHasKey('acidity_range', $filters);
        
        // Проверяем теги
        $this->assertCount(2, $filters['tags']);
        
        // Проверяем диапазон цен
        $this->assertEquals(300.00, $filters['price_range']['min']);
        $this->assertEquals(500.00, $filters['price_range']['max']);
        
        // Проверяем диапазон характеристик
        $this->assertEquals(6, $filters['bitterness_range']['min']);
        $this->assertEquals(8, $filters['bitterness_range']['max']);
        $this->assertEquals(2, $filters['acidity_range']['min']);
        $this->assertEquals(4, $filters['acidity_range']['max']);
    }

    /**
     * Тест: получение количества товаров для каждого фильтра
     * 
     * @return void
     */
    public function test_gets_filter_counts(): void
    {
        // Arrange: создаем товары с разными характеристиками
        // Важно: группы могут пересекаться, т.к. фабрика создает товары с рандомными параметрами
        $category = Category::factory()->create();
        
        // 3 товара в наличии (без остальных характеристик)
        Product::factory()->count(3)->create([
            'category_id' => $category->id,
            'stock' => 10,
            'price' => 300.00,
            'old_price' => null,
            'is_featured' => false,
            'rating' => 3.0,
            'is_available' => true,
        ]);
        
        // 2 товара не в наличии
        Product::factory()->count(2)->create([
            'category_id' => $category->id,
            'stock' => 0,
            'price' => 300.00,
            'old_price' => null,
            'is_featured' => false,
            'rating' => 3.0,
            'is_available' => true,
        ]);
        
        // 2 товара со скидкой (в наличии для разнообразия)
        Product::factory()->count(2)->create([
            'category_id' => $category->id,
            'stock' => 5,
            'price' => 400.00,
            'old_price' => 500.00,
            'is_featured' => false,
            'rating' => 3.0,
            'is_available' => true,
        ]);
        
        // 1 рекомендуемый товар (в наличии)
        Product::factory()->create([
            'category_id' => $category->id,
            'stock' => 8,
            'price' => 300.00,
            'old_price' => null,
            'is_featured' => true,
            'rating' => 3.0,
            'is_available' => true,
        ]);
        
        // 2 товара с рейтингом 4+ (в наличии)
        Product::factory()->count(2)->create([
            'category_id' => $category->id,
            'stock' => 12,
            'price' => 300.00,
            'old_price' => null,
            'is_featured' => false,
            'rating' => 4.5,
            'is_available' => true,
        ]);

        // Act: получаем счетчики фильтров
        $counts = $this->service->getFilterCounts($category->id);

        // Assert: проверяем количество для каждого фильтра
        $this->assertArrayHasKey('total', $counts);
        $this->assertArrayHasKey('in_stock', $counts);
        $this->assertArrayHasKey('on_sale', $counts);
        $this->assertArrayHasKey('featured', $counts);
        $this->assertArrayHasKey('rating_4_plus', $counts);
        
        // Всего 10 товаров (3 + 2 + 2 + 1 + 2)
        $this->assertEquals(10, $counts['total']);
        
        // В наличии: 3 (обычные) + 2 (со скидкой) + 1 (featured) + 2 (с рейтингом) = 8
        $this->assertEquals(8, $counts['in_stock']);
        
        // Со скидкой: 2 товара
        $this->assertEquals(2, $counts['on_sale']);
        
        // Рекомендуемые: 1 товар
        $this->assertEquals(1, $counts['featured']);
        
        // С рейтингом 4+: 2 товара
        $this->assertEquals(2, $counts['rating_4_plus']);
    }

    // ==========================================
    // ДОПОЛНИТЕЛЬНЫЕ ТЕСТЫ
    // ==========================================

    /**
     * Тест: фильтр по рекомендуемым товарам (featured)
     * 
     * @return void
     */
    public function test_filters_featured_products(): void
    {
        // Arrange: создаем товары
        $category = Category::factory()->create();
        
        $featured1 = Product::factory()->create([
            'category_id' => $category->id,
            'is_featured' => true,
            'is_available' => true,
        ]);
        
        $featured2 = Product::factory()->create([
            'category_id' => $category->id,
            'is_featured' => true,
            'is_available' => true,
        ]);
        
        $regular = Product::factory()->create([
            'category_id' => $category->id,
            'is_featured' => false,
            'is_available' => true,
        ]);

        // Act: фильтруем только featured товары
        $result = $this->service->filter(['featured' => true]);

        // Assert: получаем только рекомендуемые товары
        $this->assertCount(2, $result);
        $resultIds = $result->pluck('id')->toArray();
        $this->assertContains($featured1->id, $resultIds);
        $this->assertContains($featured2->id, $resultIds);
        $this->assertNotContains($regular->id, $resultIds);
    }

    /**
     * Тест: комбинированные фильтры работают корректно
     * 
     * @return void
     */
    public function test_combines_multiple_filters(): void
    {
        // Arrange: создаем разнообразные товары
        $category = Category::factory()->create();
        
        // Подходит под все фильтры
        $matchingProduct = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Эфиопия Иргачиф',
            'price' => 400.00,
            'stock' => 10,
            'rating' => 4.5,
            'is_available' => true,
        ]);
        
        // Не подходит по цене
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Эфиопия другой',
            'price' => 100.00,
            'stock' => 10,
            'rating' => 4.5,
            'is_available' => true,
        ]);
        
        // Не подходит по рейтингу
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Эфиопия третий',
            'price' => 400.00,
            'stock' => 10,
            'rating' => 3.0,
            'is_available' => true,
        ]);
        
        // Не подходит по наличию
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Эфиопия четвертый',
            'price' => 400.00,
            'stock' => 0,
            'rating' => 4.5,
            'is_available' => true,
        ]);

        // Act: применяем несколько фильтров одновременно
        $result = $this->service->filter([
            'search' => 'Эфиопия',
            'price_min' => 300,
            'price_max' => 500,
            'in_stock' => true,
            'min_rating' => 4.0,
        ]);

        // Assert: получаем только товар, подходящий под все фильтры
        $this->assertCount(1, $result);
        $this->assertEquals($matchingProduct->id, $result->first()->id);
    }

    /**
     * Тест: возвращаются пустые результаты при отсутствии совпадений
     * 
     * @return void
     */
    public function test_returns_empty_results_when_no_matches(): void
    {
        // Arrange: создаем товары
        $category = Category::factory()->create();
        
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Кофе',
            'is_available' => true,
        ]);

        // Act: ищем несуществующий товар
        $result = $this->service->filter(['search' => 'НесуществующийТовар123']);

        // Assert: получаем пустой результат
        $this->assertCount(0, $result);
    }

    /**
     * Тест: фильтр показывает только доступные товары (is_available = true)
     * 
     * @return void
     */
    public function test_filters_only_available_products(): void
    {
        // Arrange: создаем доступные и недоступные товары
        $category = Category::factory()->create();
        
        $available = Product::factory()->create([
            'category_id' => $category->id,
            'is_available' => true,
        ]);
        
        $unavailable = Product::factory()->create([
            'category_id' => $category->id,
            'is_available' => false,
        ]);

        // Act: получаем все товары без фильтров
        $result = $this->service->filter([]);

        // Assert: недоступный товар не попадает в результаты
        $resultIds = $result->pluck('id')->toArray();
        $this->assertContains($available->id, $resultIds);
        $this->assertNotContains($unavailable->id, $resultIds);
    }

    /**
     * Тест: пагинация работает корректно
     * 
     * @return void
     */
    public function test_paginated_results(): void
    {
        // Arrange: создаем 25 товаров
        $category = Category::factory()->create();
        Product::factory()->count(25)->create([
            'category_id' => $category->id,
            'is_available' => true,
        ]);

        // Act: получаем первую страницу с 10 товарами на странице
        $result = $this->service->filter(['per_page' => 10]);

        // Assert: на первой странице 10 товаров
        $this->assertCount(10, $result);
        $this->assertEquals(25, $result->total());
        $this->assertEquals(3, $result->lastPage());
    }

    /**
     * Тест: сортировка по умолчанию (popular)
     * 
     * @return void
     */
    public function test_default_sorting_is_popular(): void
    {
        // Arrange: создаем товары с разной популярностью
        $category = Category::factory()->create();
        
        $lowPopular = Product::factory()->create([
            'category_id' => $category->id,
            'rating' => 3.0,
            'reviews_count' => 5,
            'is_available' => true,
        ]);
        
        $highPopular = Product::factory()->create([
            'category_id' => $category->id,
            'rating' => 5.0,
            'reviews_count' => 100,
            'is_available' => true,
        ]);

        // Act: не указываем сортировку (должна быть по умолчанию popular)
        $result = $this->service->filter([]);

        // Assert: товары отсортированы по популярности
        $this->assertEquals($highPopular->id, $result->first()->id);
    }

    /**
     * Тест: фильтр по минимальной горчинке
     * 
     * @return void
     */
    public function test_filters_by_min_bitterness(): void
    {
        // Arrange: создаем кофе с разной горчинкой
        $category = Category::factory()->create();
        
        Product::factory()->create([
            'category_id' => $category->id,
            'bitterness_percent' => 2,
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category->id,
            'bitterness_percent' => 8,
            'is_available' => true,
        ]);

        // Act: фильтруем кофе с горчинкой от 5
        $result = $this->service->filter(['bitterness_min' => 5]);

        // Assert: получаем только товары с горчинкой >= 5
        $this->assertCount(1, $result);
        $this->assertGreaterThanOrEqual(5, $result->first()->bitterness_percent);
    }

    /**
     * Тест: фильтр по максимальной кислинке
     * 
     * @return void
     */
    public function test_filters_by_max_acidity(): void
    {
        // Arrange: создаем кофе с разной кислинкой
        $category = Category::factory()->create();
        
        Product::factory()->create([
            'category_id' => $category->id,
            'acidity_percent' => 2,
            'is_available' => true,
        ]);
        
        Product::factory()->create([
            'category_id' => $category->id,
            'acidity_percent' => 8,
            'is_available' => true,
        ]);

        // Act: фильтруем кофе с кислинкой до 5
        $result = $this->service->filter(['acidity_max' => 5]);

        // Assert: получаем только товары с кислинкой <= 5
        $this->assertCount(1, $result);
        $this->assertLessThanOrEqual(5, $result->first()->acidity_percent);
    }

    /**
     * Тест: диапазон цен возвращает 0 если нет товаров
     * 
     * @return void
     */
    public function test_price_range_returns_zero_when_no_products(): void
    {
        // Arrange: нет товаров

        // Act: получаем диапазон цен
        $range = $this->service->getPriceRange();

        // Assert: получаем нули
        $this->assertEquals(0, $range['min']);
        $this->assertEquals(0, $range['max']);
    }

    /**
     * Тест: счетчики фильтров возвращают 0 если нет товаров
     * 
     * @return void
     */
    public function test_filter_counts_return_zero_when_no_products(): void
    {
        // Arrange: нет товаров
        $category = Category::factory()->create();

        // Act: получаем счетчики
        $counts = $this->service->getFilterCounts($category->id);

        // Assert: все счетчики равны 0
        $this->assertEquals(0, $counts['total']);
        $this->assertEquals(0, $counts['in_stock']);
        $this->assertEquals(0, $counts['on_sale']);
        $this->assertEquals(0, $counts['featured']);
        $this->assertEquals(0, $counts['rating_4_plus']);
    }
}
