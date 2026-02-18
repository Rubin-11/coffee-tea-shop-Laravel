<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Tests\TestCase;

/**
 * Unit тесты для модели Product
 * 
 * Тестируем:
 * - Наличие товара (inStock)
 * - Скидки (hasDiscount, discount_percent, savings)
 * - Изображения (primary_image_url)
 * - Query Scopes (available, featured, inStock и др.)
 * - Характеристики кофе (acidity, bitterness, saturation, roast_level)
 */
final class ProductTest extends TestCase
{
    // ==========================================
    // ТЕСТЫ НАЛИЧИЯ ТОВАРА
    // ==========================================

    /**
     * Тест: товар в наличии, если stock > 0
     */
    public function test_in_stock_returns_true_when_stock_positive(): void
    {
        // Arrange: Создаем товар с положительным остатком
        $product = Product::factory()->create(['stock' => 10]);

        // Act: Проверяем наличие товара
        $result = $product->inStock();

        // Assert: Ожидаем, что товар в наличии
        $this->assertTrue($result, 'Товар с stock > 0 должен быть в наличии');
    }

    /**
     * Тест: товар не в наличии, если stock = 0
     */
    public function test_in_stock_returns_false_when_stock_zero(): void
    {
        // Arrange: Создаем товар с нулевым остатком
        $product = Product::factory()->create(['stock' => 0]);

        // Act: Проверяем наличие товара
        $result = $product->inStock();

        // Assert: Ожидаем, что товар НЕ в наличии
        $this->assertFalse($result, 'Товар с stock = 0 НЕ должен быть в наличии');
    }

    // ==========================================
    // ТЕСТЫ СКИДОК
    // ==========================================

    /**
     * Тест: есть скидка, если указана old_price
     */
    public function test_has_discount_returns_true_when_old_price_exists(): void
    {
        // Arrange: Создаем товар со старой ценой (скидкой)
        $product = Product::factory()->create([
            'price' => 400.00,
            'old_price' => 500.00,
        ]);

        // Act: Проверяем наличие скидки
        $result = $product->hasDiscount();

        // Assert: Ожидаем, что есть скидка
        $this->assertTrue($result, 'Товар с old_price должен иметь скидку');
    }

    /**
     * Тест: нет скидки, если old_price = null
     */
    public function test_has_discount_returns_false_when_no_old_price(): void
    {
        // Arrange: Создаем товар БЕЗ старой цены
        $product = Product::factory()->create([
            'price' => 400.00,
            'old_price' => null,
        ]);

        // Act: Проверяем наличие скидки
        $result = $product->hasDiscount();

        // Assert: Ожидаем, что НЕТ скидки
        $this->assertFalse($result, 'Товар без old_price НЕ должен иметь скидку');
    }

    /**
     * Тест: правильный расчет процента скидки
     */
    public function test_calculates_discount_percent_correctly(): void
    {
        // Arrange: Создаем товар со скидкой 20%
        // old_price = 500, price = 400 -> скидка = 20%
        $product = Product::factory()->create([
            'price' => 400.00,
            'old_price' => 500.00,
        ]);

        // Act: Получаем процент скидки
        $discountPercent = $product->discount_percent;

        // Assert: Ожидаем скидку 20%
        $this->assertEquals(20, $discountPercent, 'Процент скидки должен быть 20%');
    }

    /**
     * Тест: правильный расчет суммы экономии
     */
    public function test_calculates_savings_correctly(): void
    {
        // Arrange: Создаем товар со скидкой
        // old_price = 500, price = 400 -> экономия = 100
        $product = Product::factory()->create([
            'price' => 400.00,
            'old_price' => 500.00,
        ]);

        // Act: Получаем сумму экономии
        $savings = $product->savings;

        // Assert: Ожидаем экономию 100 руб
        $this->assertEquals(100.00, $savings, 'Сумма экономии должна быть 100 руб');
    }

    /**
     * Тест: discount_percent = null если нет скидки
     */
    public function test_discount_percent_is_null_when_no_discount(): void
    {
        // Arrange: Товар без скидки
        $product = Product::factory()->create([
            'price' => 400.00,
            'old_price' => null,
        ]);

        // Act & Assert: Процент скидки должен быть null
        $this->assertNull($product->discount_percent);
    }

    /**
     * Тест: savings = null если нет скидки
     */
    public function test_savings_is_null_when_no_discount(): void
    {
        // Arrange: Товар без скидки
        $product = Product::factory()->create([
            'price' => 400.00,
            'old_price' => null,
        ]);

        // Act & Assert: Экономия должна быть null
        $this->assertNull($product->savings);
    }

    // ==========================================
    // ТЕСТЫ ИЗОБРАЖЕНИЙ
    // ==========================================

    /**
     * Тест: возвращает URL главного изображения
     */
    public function test_returns_primary_image_url(): void
    {
        // Arrange: Создаем товар с главным изображением
        $product = Product::factory()->create();
        $image = ProductImage::factory()->create([
            'product_id' => $product->id,
            'is_primary' => true,
            'image_path' => 'products/coffee-main.jpg',
        ]);

        // Act: Получаем URL главного изображения
        // Обновляем товар, чтобы загрузить связь
        $product = $product->fresh(['images']);
        $imageUrl = $product->primary_image_url;

        // Assert: Ожидаем URL главного изображения
        $this->assertEquals($image->url, $imageUrl, 'Должен вернуть URL главного изображения');
    }

    /**
     * Тест: возвращает первое изображение, если нет primary
     */
    public function test_returns_first_image_when_no_primary(): void
    {
        // Arrange: Создаем товар с несколькими изображениями, но БЕЗ primary
        $product = Product::factory()->create();
        $firstImage = ProductImage::factory()->create([
            'product_id' => $product->id,
            'is_primary' => false,
            'image_path' => 'products/first.jpg',
            'sort_order' => 1,
        ]);
        ProductImage::factory()->create([
            'product_id' => $product->id,
            'is_primary' => false,
            'image_path' => 'products/second.jpg',
            'sort_order' => 2,
        ]);

        // Act: Получаем URL изображения
        $product = $product->fresh(['images']);
        $imageUrl = $product->primary_image_url;

        // Assert: Должен вернуть первое изображение
        $this->assertEquals($firstImage->url, $imageUrl, 'Должен вернуть первое изображение');
    }

    /**
     * Тест: возвращает placeholder, если нет изображений
     */
    public function test_returns_placeholder_when_no_images(): void
    {
        // Arrange: Создаем товар БЕЗ изображений
        $product = Product::factory()->create();

        // Act: Получаем URL изображения
        $imageUrl = $product->primary_image_url;

        // Assert: Должен вернуть placeholder
        $this->assertStringContainsString('placeholder', $imageUrl, 'Должен вернуть placeholder');
    }

    // ==========================================
    // ТЕСТЫ QUERY SCOPES
    // ==========================================

    /**
     * Тест: scope available фильтрует доступные товары
     */
    public function test_available_scope_filters_available_products(): void
    {
        // Arrange: Создаем доступные и недоступные товары
        $availableProduct = Product::factory()->create(['is_available' => true]);
        $unavailableProduct = Product::factory()->create(['is_available' => false]);

        // Act: Получаем только доступные товары
        $products = Product::available()->get();

        // Assert: В списке только доступные товары
        $this->assertTrue(
            $products->contains($availableProduct),
            'Должен включать доступный товар'
        );
        $this->assertFalse(
            $products->contains($unavailableProduct),
            'НЕ должен включать недоступный товар'
        );
    }

    /**
     * Тест: scope featured фильтрует рекомендуемые товары
     */
    public function test_featured_scope_filters_featured_products(): void
    {
        // Arrange: Создаем рекомендуемые и обычные товары
        $featuredProduct = Product::factory()->create(['is_featured' => true]);
        $regularProduct = Product::factory()->create(['is_featured' => false]);

        // Act: Получаем только рекомендуемые товары
        $products = Product::featured()->get();

        // Assert: В списке только рекомендуемые товары
        $this->assertTrue(
            $products->contains($featuredProduct),
            'Должен включать рекомендуемый товар'
        );
        $this->assertFalse(
            $products->contains($regularProduct),
            'НЕ должен включать обычный товар'
        );
    }

    /**
     * Тест: scope inStockScope фильтрует товары в наличии
     */
    public function test_in_stock_scope_filters_products_in_stock(): void
    {
        // Arrange: Создаем товары с разным количеством на складе
        $inStockProduct = Product::factory()->create(['stock' => 10]);
        $outOfStockProduct = Product::factory()->create(['stock' => 0]);

        // Act: Получаем только товары в наличии
        $products = Product::inStockScope()->get();

        // Assert: В списке только товары с stock > 0
        $this->assertTrue(
            $products->contains($inStockProduct),
            'Должен включать товар в наличии'
        );
        $this->assertFalse(
            $products->contains($outOfStockProduct),
            'НЕ должен включать товар с нулевым остатком'
        );
    }

    /**
     * Тест: scope byCategory фильтрует по категории
     */
    public function test_by_category_scope_filters_by_category(): void
    {
        // Arrange: Создаем две категории и товары в них
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();
        
        $productInCategory1 = Product::factory()->create(['category_id' => $category1->id]);
        $productInCategory2 = Product::factory()->create(['category_id' => $category2->id]);

        // Act: Получаем товары только из category1
        $products = Product::byCategory($category1->id)->get();

        // Assert: В списке только товары из category1
        $this->assertTrue(
            $products->contains($productInCategory1),
            'Должен включать товар из категории 1'
        );
        $this->assertFalse(
            $products->contains($productInCategory2),
            'НЕ должен включать товар из категории 2'
        );
    }

    /**
     * Тест: scope priceRange фильтрует по цене
     */
    public function test_price_range_scope_filters_by_price(): void
    {
        // Arrange: Создаем товары с разными ценами
        $cheapProduct = Product::factory()->create(['price' => 100.00]);
        $midProduct = Product::factory()->create(['price' => 300.00]);
        $expensiveProduct = Product::factory()->create(['price' => 600.00]);

        // Act: Фильтруем товары в диапазоне 200-500
        $products = Product::priceRange(200.00, 500.00)->get();

        // Assert: Только товары в заданном диапазоне
        $this->assertFalse(
            $products->contains($cheapProduct),
            'Не должен включать дешевый товар (< 200)'
        );
        $this->assertTrue(
            $products->contains($midProduct),
            'Должен включать товар в диапазоне (200-500)'
        );
        $this->assertFalse(
            $products->contains($expensiveProduct),
            'Не должен включать дорогой товар (> 500)'
        );
    }

    /**
     * Тест: scope highestRated сортирует по рейтингу
     */
    public function test_highest_rated_scope_sorts_by_rating(): void
    {
        // Arrange: Создаем товары с разными рейтингами
        $product1 = Product::factory()->create(['rating' => 3.5, 'name' => 'Product Low']);
        $product2 = Product::factory()->create(['rating' => 5.0, 'name' => 'Product High']);
        $product3 = Product::factory()->create(['rating' => 4.2, 'name' => 'Product Mid']);

        // Act: Получаем товары отсортированные по рейтингу (высокие первыми)
        $products = Product::highestRated()->get();

        // Assert: Товары отсортированы по убыванию рейтинга
        $this->assertEquals($product2->id, $products->first()->id, 'Первый должен быть с рейтингом 5.0');
        $this->assertEquals($product3->id, $products->get(1)->id, 'Второй должен быть с рейтингом 4.2');
        $this->assertEquals($product1->id, $products->last()->id, 'Последний должен быть с рейтингом 3.5');
    }

    // ==========================================
    // ТЕСТЫ ХАРАКТЕРИСТИК КОФЕ
    // ==========================================

    /**
     * Тест: правильный расчет уровня кислинки (1-7)
     */
    public function test_calculates_acidity_level_correctly(): void
    {
        // Arrange & Act & Assert: Тестируем разные значения процента

        // 0% -> уровень 0
        $product = Product::factory()->create(['acidity_percent' => 0]);
        $this->assertEquals(0, $product->acidity, 'acidity_percent = 0 должен давать уровень 0');

        // 14% -> уровень 1 (ceil(14/100 * 7) = ceil(0.98) = 1)
        $product = Product::factory()->create(['acidity_percent' => 14]);
        $this->assertEquals(1, $product->acidity, 'acidity_percent = 14 должен давать уровень 1');

        // 50% -> уровень 4 (ceil(50/100 * 7) = ceil(3.5) = 4)
        $product = Product::factory()->create(['acidity_percent' => 50]);
        $this->assertEquals(4, $product->acidity, 'acidity_percent = 50 должен давать уровень 4');

        // 100% -> уровень 7
        $product = Product::factory()->create(['acidity_percent' => 100]);
        $this->assertEquals(7, $product->acidity, 'acidity_percent = 100 должен давать уровень 7');

        // null -> уровень 0
        $product = Product::factory()->create(['acidity_percent' => null]);
        $this->assertEquals(0, $product->acidity, 'acidity_percent = null должен давать уровень 0');
    }

    /**
     * Тест: правильный расчет уровня горчинки (1-7)
     */
    public function test_calculates_bitterness_level_correctly(): void
    {
        // Arrange & Act & Assert: Тестируем разные значения процента

        // 0% -> уровень 0
        $product = Product::factory()->create(['bitterness_percent' => 0]);
        $this->assertEquals(0, $product->bitterness, 'bitterness_percent = 0 должен давать уровень 0');

        // 14% -> уровень 1
        $product = Product::factory()->create(['bitterness_percent' => 14]);
        $this->assertEquals(1, $product->bitterness, 'bitterness_percent = 14 должен давать уровень 1');

        // 50% -> уровень 4
        $product = Product::factory()->create(['bitterness_percent' => 50]);
        $this->assertEquals(4, $product->bitterness, 'bitterness_percent = 50 должен давать уровень 4');

        // 100% -> уровень 7
        $product = Product::factory()->create(['bitterness_percent' => 100]);
        $this->assertEquals(7, $product->bitterness, 'bitterness_percent = 100 должен давать уровень 7');

        // null -> уровень 0
        $product = Product::factory()->create(['bitterness_percent' => null]);
        $this->assertEquals(0, $product->bitterness, 'bitterness_percent = null должен давать уровень 0');
    }

    /**
     * Тест: правильный расчет уровня насыщенности (1-7)
     * Насыщенность вычисляется как среднее между кислинкой и горчинкой
     */
    public function test_calculates_saturation_level_correctly(): void
    {
        // Arrange & Act & Assert: Тестируем разные комбинации

        // acidity=50, bitterness=50 -> saturation=50 -> level 4
        $product = Product::factory()->create([
            'acidity_percent' => 50,
            'bitterness_percent' => 50,
        ]);
        $this->assertEquals(4, $product->saturation, 'Среднее 50% должно давать уровень 4');

        // acidity=0, bitterness=100 -> saturation=50 -> level 4
        $product = Product::factory()->create([
            'acidity_percent' => 0,
            'bitterness_percent' => 100,
        ]);
        $this->assertEquals(4, $product->saturation, 'Среднее (0+100)/2=50 должно давать уровень 4');

        // acidity=100, bitterness=100 -> saturation=100 -> level 7
        $product = Product::factory()->create([
            'acidity_percent' => 100,
            'bitterness_percent' => 100,
        ]);
        $this->assertEquals(7, $product->saturation, 'Среднее 100% должно давать уровень 7');

        // acidity=null, bitterness=null -> использует значения по умолчанию (50)
        $product = Product::factory()->create([
            'acidity_percent' => null,
            'bitterness_percent' => null,
        ]);
        $this->assertEquals(4, $product->saturation, 'По умолчанию должен использовать 50%');
    }

    /**
     * Тест: правильный расчет уровня обжарки (1-5)
     * Обжарка вычисляется на основе горчинки
     */
    public function test_calculates_roast_level_correctly(): void
    {
        // Arrange & Act & Assert: Тестируем разные значения

        // bitterness=0 -> roast=0
        $product = Product::factory()->create(['bitterness_percent' => 0]);
        $this->assertEquals(0, $product->roast_level, 'bitterness=0 должна давать уровень обжарки 0');

        // bitterness=20 -> roast=1 (ceil(20/100 * 5) = 1)
        $product = Product::factory()->create(['bitterness_percent' => 20]);
        $this->assertEquals(1, $product->roast_level, 'bitterness=20 должна давать уровень обжарки 1');

        // bitterness=50 -> roast=3
        $product = Product::factory()->create(['bitterness_percent' => 50]);
        $this->assertEquals(3, $product->roast_level, 'bitterness=50 должна давать уровень обжарки 3');

        // bitterness=100 -> roast=5
        $product = Product::factory()->create(['bitterness_percent' => 100]);
        $this->assertEquals(5, $product->roast_level, 'bitterness=100 должна давать уровень обжарки 5');

        // bitterness=null -> roast=3 (средняя обжарка по умолчанию)
        $product = Product::factory()->create(['bitterness_percent' => null]);
        $this->assertEquals(3, $product->roast_level, 'По умолчанию должна быть средняя обжарка (3)');
    }

    // ==========================================
    // ДОПОЛНИТЕЛЬНЫЕ ТЕСТЫ
    // ==========================================

    /**
     * Тест: проверка связи с категорией
     */
    public function test_belongs_to_category(): void
    {
        // Arrange: Создаем категорию и товар
        $category = Category::factory()->create(['name' => 'Кофе']);
        $product = Product::factory()->create(['category_id' => $category->id]);

        // Act: Получаем категорию товара
        $productCategory = $product->category;

        // Assert: Товар принадлежит правильной категории
        $this->assertNotNull($productCategory, 'Товар должен иметь категорию');
        $this->assertEquals($category->id, $productCategory->id, 'ID категории должны совпадать');
        $this->assertEquals('Кофе', $productCategory->name, 'Название категории должно быть "Кофе"');
    }

    /**
     * Тест: комбинация нескольких scopes
     */
    public function test_can_combine_multiple_scopes(): void
    {
        // Arrange: Создаем различные товары
        $category = Category::factory()->create();
        
        $targetProduct = Product::factory()->create([
            'category_id' => $category->id,
            'is_available' => true,
            'is_featured' => true,
            'stock' => 10,
            'price' => 300.00,
        ]);
        
        $wrongCategory = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'is_available' => true,
            'is_featured' => true,
            'stock' => 10,
            'price' => 300.00,
        ]);
        
        $notAvailable = Product::factory()->create([
            'category_id' => $category->id,
            'is_available' => false,
            'is_featured' => true,
            'stock' => 10,
            'price' => 300.00,
        ]);

        // Act: Комбинируем несколько scopes
        $products = Product::byCategory($category->id)
            ->available()
            ->featured()
            ->inStockScope()
            ->get();

        // Assert: Только целевой товар соответствует всем условиям
        $this->assertCount(1, $products, 'Должен быть только 1 товар, соответствующий всем условиям');
        $this->assertTrue(
            $products->contains($targetProduct),
            'Результат должен содержать целевой товар'
        );
    }

    /**
     * Тест: правильная работа с мягким удалением (soft deletes)
     */
    public function test_soft_deletes_work_correctly(): void
    {
        // Arrange: Создаем товар
        $product = Product::factory()->create(['name' => 'Test Product']);

        // Act: Удаляем товар (мягкое удаление)
        $product->delete();

        // Assert: Товар не отображается в обычных запросах
        $this->assertNull(
            Product::find($product->id),
            'Удаленный товар не должен находиться обычным запросом'
        );

        // Assert: Но товар доступен через withTrashed()
        $deletedProduct = Product::withTrashed()->find($product->id);
        $this->assertNotNull($deletedProduct, 'Удаленный товар должен быть доступен через withTrashed()');
        $this->assertNotNull($deletedProduct->deleted_at, 'deleted_at должен быть заполнен');
    }

    /**
     * Тест: accessor getImageUrlAttribute возвращает правильный URL
     */
    public function test_image_url_attribute_returns_correct_url(): void
    {
        // Arrange: Создаем товар с изображением
        $product = Product::factory()->create();
        ProductImage::factory()->create([
            'product_id' => $product->id,
            'image_path' => 'products/test-image.jpg',
        ]);

        // Act: Получаем image_url через accessor
        $product = $product->fresh(['images']);
        $imageUrl = $product->image_url;

        // Assert: Должен вернуть URL изображения
        $this->assertStringContainsString('test-image.jpg', $imageUrl);
    }

    /**
     * Тест: заполнение всех обязательных полей через factory
     */
    public function test_factory_creates_valid_product(): void
    {
        // Act: Создаем товар через factory
        $product = Product::factory()->create();

        // Assert: Все обязательные поля заполнены
        $this->assertNotNull($product->id, 'ID должен быть заполнен');
        $this->assertNotNull($product->name, 'Название должно быть заполнено');
        $this->assertNotNull($product->slug, 'Slug должен быть заполнен');
        $this->assertNotNull($product->price, 'Цена должна быть заполнена');
        $this->assertNotNull($product->sku, 'SKU должен быть заполнен');
        $this->assertGreaterThanOrEqual(0, $product->stock, 'Stock должен быть >= 0');
        $this->assertIsFloat((float)$product->rating, 'Рейтинг должен быть числом');
    }
}
