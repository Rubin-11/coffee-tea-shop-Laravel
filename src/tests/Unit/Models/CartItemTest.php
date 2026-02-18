<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit тесты для модели CartItem
 * 
 * Тестируем:
 * - Расчеты (subtotal, округление)
 * - Проверку цен (изменение, получение, обновление)
 * - Проверку доступности товаров
 * - Query Scopes (byUser, bySession, available)
 * - Автоматическое заполнение цены
 */
final class CartItemTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ==================================================================
     * ГРУППА 1: РАСЧЕТЫ (SUBTOTAL)
     * ==================================================================
     */

    /**
     * Тест: правильный расчет промежуточной суммы (price * quantity)
     * 
     * Проверяем базовую математику: цена умножается на количество
     */
    public function test_calculates_subtotal_correctly(): void
    {
        // Arrange: Создаем позицию корзины с известными значениями
        // Цена = 500 руб, количество = 2 единицы
        $cartItem = CartItem::factory()->create([
            'price' => 500.00,
            'quantity' => 2,
        ]);

        // Act: Вычисляем промежуточную сумму
        $subtotal = $cartItem->getSubtotal();

        // Assert: Проверяем, что 500 * 2 = 1000.00
        $this->assertEquals(1000.00, $subtotal);
    }

    /**
     * Тест: округление subtotal до 2 знаков после запятой
     * 
     * Проверяем корректность округления при нестандартных ценах
     */
    public function test_subtotal_rounds_to_two_decimals(): void
    {
        // Arrange: Создаем позицию с ценой, которая даст "длинное" число
        // 333.33 * 3 = 999.99 (без проблем)
        // Но 333.333 * 3 = 999.999 (требуется округление)
        $cartItem = CartItem::factory()->create([
            'price' => 333.33,  // Laravel автоматически хранит как decimal(10,2)
            'quantity' => 3,
        ]);

        // Act: Получаем subtotal
        $subtotal = $cartItem->getSubtotal();

        // Assert: Проверяем, что результат имеет максимум 2 знака после запятой
        $this->assertEquals(999.99, $subtotal);
        
        // Дополнительная проверка: результат - это float с 2 знаками
        $this->assertIsFloat($subtotal);
        $this->assertEquals(2, strlen(substr(strrchr((string)$subtotal, '.'), 1)));
    }

    /**
     * Тест: subtotal корректно считается для единичного товара
     * 
     * Граничный случай: quantity = 1
     */
    public function test_subtotal_for_single_item(): void
    {
        // Arrange: Одна единица товара по 450 руб
        $cartItem = CartItem::factory()->single()->create([
            'price' => 450.00,
        ]);

        // Act
        $subtotal = $cartItem->getSubtotal();

        // Assert: Subtotal должен быть равен цене
        $this->assertEquals(450.00, $subtotal);
    }

    /**
     * ==================================================================
     * ГРУППА 2: ПРОВЕРКА ЦЕНЫ
     * ==================================================================
     */

    /**
     * Тест: определение изменения цены товара
     * 
     * Проверяем, что метод hasPriceChanged() правильно определяет,
     * изменилась ли цена товара с момента добавления в корзину
     */
    public function test_detects_price_change(): void
    {
        // Arrange: Создаем товар с ценой 500 руб
        $product = Product::factory()->create([
            'price' => 500.00,
        ]);

        // Добавляем товар в корзину по старой цене 450 руб
        $cartItem = CartItem::factory()->forProduct($product)->create([
            'price' => 450.00,  // Цена в корзине отличается от текущей
        ]);

        // Act & Assert: Должно определить изменение цены
        $this->assertTrue($cartItem->hasPriceChanged());
    }

    /**
     * Тест: цена не изменилась, если совпадает с текущей
     */
    public function test_price_not_changed_when_same(): void
    {
        // Arrange: Создаем товар
        $product = Product::factory()->create([
            'price' => 500.00,
        ]);

        // Добавляем в корзину по текущей цене
        $cartItem = CartItem::factory()->forProduct($product)->create([
            'price' => 500.00,  // Цена совпадает с текущей
        ]);

        // Act & Assert: Цена не изменилась
        $this->assertFalse($cartItem->hasPriceChanged());
    }

    /**
     * Тест: получение актуальной цены товара
     * 
     * Метод getCurrentPrice() должен вернуть текущую цену из БД,
     * а не цену, сохраненную в корзине
     */
    public function test_returns_current_product_price(): void
    {
        // Arrange: Товар сейчас стоит 600 руб
        $product = Product::factory()->create([
            'price' => 600.00,
        ]);

        // В корзине он был добавлен по старой цене 500 руб
        $cartItem = CartItem::factory()->forProduct($product)->create([
            'price' => 500.00,
        ]);

        // Act: Получаем ТЕКУЩУЮ цену товара
        $currentPrice = $cartItem->getCurrentPrice();

        // Assert: Должна вернуться актуальная цена 600, а не 500
        $this->assertEquals(600.00, $currentPrice);
        $this->assertNotEquals($cartItem->price, $currentPrice);
    }

    /**
     * Тест: getCurrentPrice возвращает null, если товар удален
     */
    public function test_current_price_returns_null_when_product_deleted(): void
    {
        // Arrange: Создаем позицию корзины
        $cartItem = CartItem::factory()->create();
        
        // Удаляем товар из БД
        $cartItem->product->delete();
        
        // Перезагружаем модель, чтобы обновить связи
        $cartItem = $cartItem->fresh();

        // Act
        $currentPrice = $cartItem->getCurrentPrice();

        // Assert: Должен вернуть null
        $this->assertNull($currentPrice);
    }

    /**
     * Тест: обновление цены до актуальной
     * 
     * Метод updatePrice() должен синхронизировать цену в корзине
     * с текущей ценой товара
     */
    public function test_can_update_price_to_current(): void
    {
        // Arrange: Товар стоит 700 руб
        $product = Product::factory()->create([
            'price' => 700.00,
        ]);

        // В корзине старая цена 650 руб
        $cartItem = CartItem::factory()->forProduct($product)->create([
            'price' => 650.00,
        ]);

        // Act: Обновляем цену
        $result = $cartItem->updatePrice();

        // Assert: Цена должна обновиться на 700 руб
        $this->assertTrue($result); // Метод вернул true (успех)
        $this->assertEquals(700.00, $cartItem->fresh()->price);
    }

    /**
     * Тест: updatePrice возвращает false, если товар не существует
     */
    public function test_update_price_returns_false_when_no_product(): void
    {
        // Arrange: Позиция без товара (товар удален)
        $cartItem = CartItem::factory()->create();
        $cartItem->product->delete();
        $cartItem = $cartItem->fresh();

        // Act
        $result = $cartItem->updatePrice();

        // Assert: Должен вернуть false
        $this->assertFalse($result);
    }

    /**
     * ==================================================================
     * ГРУППА 3: ПРОВЕРКА ДОСТУПНОСТИ
     * ==================================================================
     */

    /**
     * Тест: товар доступен, если есть достаточно на складе
     * 
     * Проверяем основной сценарий: товар в наличии и его хватает
     */
    public function test_is_available_when_product_in_stock(): void
    {
        // Arrange: Товар с 10 единицами на складе
        $product = Product::factory()->create([
            'stock' => 10,
            'is_available' => true,
        ]);

        // В корзине 3 единицы (меньше, чем на складе)
        $cartItem = CartItem::factory()->forProduct($product)->create([
            'quantity' => 3,
        ]);

        // Act & Assert: Товар должен быть доступен
        $this->assertTrue($cartItem->isAvailable());
    }

    /**
     * Тест: товар НЕ доступен при недостаточном количестве на складе
     * 
     * Важный граничный случай для предотвращения overselling
     */
    public function test_is_not_available_when_insufficient_stock(): void
    {
        // Arrange: Товар с только 2 единицами на складе
        $product = Product::factory()->create([
            'stock' => 2,
            'is_available' => true,
        ]);

        // В корзине пытаемся заказать 5 единиц (больше, чем есть)
        $cartItem = CartItem::factory()->forProduct($product)->create([
            'quantity' => 5,
        ]);

        // Act & Assert: Товар НЕ доступен
        $this->assertFalse($cartItem->isAvailable());
    }

    /**
     * Тест: товар НЕ доступен, если выключен (is_available = false)
     * 
     * Даже если товар есть на складе, он может быть временно недоступен
     */
    public function test_is_not_available_when_product_unavailable(): void
    {
        // Arrange: Товар есть на складе, но is_available = false
        $product = Product::factory()->create([
            'stock' => 100,
            'is_available' => false,  // Товар выключен администратором
        ]);

        // В корзине 1 единица
        $cartItem = CartItem::factory()->forProduct($product)->create([
            'quantity' => 1,
        ]);

        // Act & Assert: Товар НЕ доступен
        $this->assertFalse($cartItem->isAvailable());
    }

    /**
     * Тест: товар НЕ доступен при нулевом stock
     */
    public function test_is_not_available_when_stock_is_zero(): void
    {
        // Arrange: Товар закончился на складе
        $product = Product::factory()->create([
            'stock' => 0,
            'is_available' => true,
        ]);

        $cartItem = CartItem::factory()->forProduct($product)->create([
            'quantity' => 1,
        ]);

        // Act & Assert
        $this->assertFalse($cartItem->isAvailable());
    }

    /**
     * Тест: возвращает максимально доступное количество
     * 
     * Полезно для отображения сообщения "В наличии только X шт."
     */
    public function test_returns_max_available_quantity(): void
    {
        // Arrange: Товар с 7 единицами на складе
        $product = Product::factory()->create([
            'stock' => 7,
        ]);

        $cartItem = CartItem::factory()->forProduct($product)->create();

        // Act: Получаем максимальное доступное количество
        $maxQuantity = $cartItem->getMaxAvailableQuantity();

        // Assert: Должно вернуть 7 (весь остаток на складе)
        $this->assertEquals(7, $maxQuantity);
    }

    /**
     * Тест: максимальное количество = 0, если товар не существует
     */
    public function test_max_quantity_zero_when_no_product(): void
    {
        // Arrange: Позиция без товара
        $cartItem = CartItem::factory()->create();
        $cartItem->product->delete();
        $cartItem = $cartItem->fresh();

        // Act
        $maxQuantity = $cartItem->getMaxAvailableQuantity();

        // Assert
        $this->assertEquals(0, $maxQuantity);
    }

    /**
     * ==================================================================
     * ГРУППА 4: QUERY SCOPES
     * ==================================================================
     */

    /**
     * Тест: scope byUser фильтрует по пользователю
     * 
     * Проверяем, что запрос возвращает только корзину конкретного пользователя
     */
    public function test_by_user_scope_filters_by_user_id(): void
    {
        // Arrange: Создаем двух пользователей
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Создаем корзины для обоих пользователей
        CartItem::factory()->forUser($user1)->count(3)->create();
        CartItem::factory()->forUser($user2)->count(2)->create();

        // Act: Получаем только корзину первого пользователя
        $user1Items = CartItem::byUser($user1->id)->get();

        // Assert: Должно вернуться 3 позиции только для user1
        $this->assertCount(3, $user1Items);
        
        // Проверяем, что все позиции принадлежат user1
        $user1Items->each(function ($item) use ($user1) {
            $this->assertEquals($user1->id, $item->user_id);
        });
    }

    /**
     * Тест: scope bySession фильтрует по сессии (гостевая корзина)
     * 
     * Проверяем работу с гостевыми корзинами через session_id
     */
    public function test_by_session_scope_filters_by_session_id(): void
    {
        // Arrange: Создаем две гостевые корзины с разными session_id
        $session1 = 'session_abc123';
        $session2 = 'session_xyz789';

        CartItem::factory()->guest($session1)->count(2)->create();
        CartItem::factory()->guest($session2)->count(3)->create();
        
        // Также создаем позицию для авторизованного пользователя
        CartItem::factory()->forUser(User::factory()->create())->create();

        // Act: Получаем только первую гостевую корзину
        $sessionItems = CartItem::bySession($session1)->get();

        // Assert: Должно вернуться 2 позиции для session1
        $this->assertCount(2, $sessionItems);
        
        // Проверяем корректность данных
        $sessionItems->each(function ($item) use ($session1) {
            $this->assertEquals($session1, $item->session_id);
            $this->assertNull($item->user_id); // У гостей user_id = null
        });
    }

    /**
     * Тест: scope available фильтрует доступные позиции
     * 
     * Возвращает только те позиции, товары которых доступны для заказа
     */
    public function test_available_scope_filters_available_items(): void
    {
        // Arrange: Создаем разные сценарии
        
        // 1. Доступный товар (stock > 0, is_available = true)
        $availableProduct = Product::factory()->create([
            'stock' => 10,
            'is_available' => true,
        ]);
        CartItem::factory()->forProduct($availableProduct)->create([
            'quantity' => 2,
        ]);

        // 2. Товар закончился на складе (stock = 0)
        $outOfStockProduct = Product::factory()->create([
            'stock' => 0,
            'is_available' => true,
        ]);
        CartItem::factory()->forProduct($outOfStockProduct)->create();

        // 3. Товар выключен (is_available = false)
        $unavailableProduct = Product::factory()->create([
            'stock' => 5,
            'is_available' => false,
        ]);
        CartItem::factory()->forProduct($unavailableProduct)->create();

        // Act: Получаем только ДОСТУПНЫЕ позиции
        $availableItems = CartItem::available()->get();

        // Assert: Должна вернуться только 1 позиция (первая)
        $this->assertCount(1, $availableItems);
        $this->assertEquals($availableProduct->id, $availableItems->first()->product_id);
    }

    /**
     * ==================================================================
     * ГРУППА 5: AUTO-FILL ЦЕНЫ
     * ==================================================================
     */

    /**
     * Тест: автоматическое заполнение цены при создании
     * 
     * Если при создании CartItem не указана цена, она должна
     * автоматически заполниться из product->price
     */
    public function test_auto_fills_price_on_creation(): void
    {
        // Arrange: Создаем товар с известной ценой
        $product = Product::factory()->create([
            'price' => 555.00,
        ]);

        // Act: Создаем CartItem БЕЗ указания цены
        $cartItem = CartItem::factory()->create([
            'product_id' => $product->id,
            'price' => null,  // Явно не указываем цену
        ]);

        // Assert: Цена должна автоматически заполниться
        $this->assertNotNull($cartItem->price);
        $this->assertEquals(555.00, (float) $cartItem->price);
    }

    /**
     * Тест: не перезаписывает цену, если она указана явно
     * 
     * Если при создании указали конкретную цену, она должна сохраниться
     */
    public function test_does_not_override_explicitly_set_price(): void
    {
        // Arrange: Товар стоит 500 руб
        $product = Product::factory()->create([
            'price' => 500.00,
        ]);

        // Act: Создаем CartItem с ДРУГОЙ ценой (например, со скидкой)
        $cartItem = CartItem::factory()->create([
            'product_id' => $product->id,
            'price' => 450.00,  // Явно указываем другую цену
        ]);

        // Assert: Цена должна остаться 450, а не измениться на 500
        $this->assertEquals(450.00, (float) $cartItem->price);
    }

    /**
     * ==================================================================
     * ГРУППА 6: ГРАНИЧНЫЕ СЛУЧАИ И ДОПОЛНИТЕЛЬНЫЕ ПРОВЕРКИ
     * ==================================================================
     */

    /**
     * Тест: hasPriceChanged использует точность 0.01
     * 
     * Проверяем, что небольшие различия в округлении не считаются изменением цены
     * Метод использует порог 0.01: если разница СТРОГО БОЛЬШЕ 0.01, то это изменение
     */
    public function test_price_change_uses_correct_precision(): void
    {
        // Arrange: Создаем товар с ценой 100.00
        $product = Product::factory()->create([
            'price' => 100.00,
        ]);

        // Тест 1: Значительная разница ДОЛЖНА считаться изменением
        // Создаем CartItem с ценой, которая заметно отличается
        $cartItem = CartItem::factory()->create([
            'product_id' => $product->id,
            'price' => 100.50,  // Разница 0.50 > 0.01
        ]);

        // Act & Assert: Большая разница - это изменение
        $this->assertTrue($cartItem->hasPriceChanged());
        
        // Тест 2: Цена не изменилась - точное совпадение
        $cartItem2 = CartItem::factory()->create([
            'product_id' => $product->id,
            'price' => 100.00,  // Разница 0.00
        ]);
        $this->assertFalse($cartItem2->hasPriceChanged());
        
        // Тест 3: Небольшая разница в 2 цента ДОЛЖНА считаться изменением
        $cartItem3 = CartItem::factory()->create([
            'product_id' => $product->id,
            'price' => 100.02,  // Разница 0.02 > 0.01
        ]);
        $this->assertTrue($cartItem3->hasPriceChanged());
        
        // Тест 4: Проверяем отрицательную разницу (цена в корзине меньше)
        $cartItem4 = CartItem::factory()->create([
            'product_id' => $product->id,
            'price' => 99.50,  // Разница 0.50
        ]);
        $this->assertTrue($cartItem4->hasPriceChanged());
    }

    /**
     * Тест: связи модели работают корректно
     */
    public function test_has_correct_relationships(): void
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create();
        
        $cartItem = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        // Act & Assert: Проверяем связи
        $this->assertInstanceOf(User::class, $cartItem->user);
        $this->assertEquals($user->id, $cartItem->user->id);
        
        $this->assertInstanceOf(Product::class, $cartItem->product);
        $this->assertEquals($product->id, $cartItem->product->id);
    }

    /**
     * Тест: casts работают корректно
     */
    public function test_casts_attributes_correctly(): void
    {
        // Arrange & Act
        $cartItem = CartItem::factory()->create([
            'quantity' => '5',      // Передаем как строку
            'price' => '123.45',    // Передаем как строку
        ]);

        // Assert: Проверяем типы после cast
        $this->assertIsInt($cartItem->quantity);
        $this->assertEquals(5, $cartItem->quantity);
        
        $this->assertIsString((string)$cartItem->price); // decimal хранится как строка
        $this->assertEquals('123.45', (string)$cartItem->price);
    }
}
