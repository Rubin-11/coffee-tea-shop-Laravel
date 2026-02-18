<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit тесты для CartService
 * 
 * Тестируем всю бизнес-логику работы с корзиной покупок:
 * - Добавление/обновление/удаление товаров
 * - Расчеты (общая сумма, количество)
 * - Синхронизацию цен
 * - Проверку доступности товаров
 * - Работу с гостевой корзиной
 * - Слияние корзин при авторизации
 */
class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Сервис корзины для тестов
     */
    private CartService $cartService;

    /**
     * Подготовка перед каждым тестом
     * 
     * Выполняется автоматически перед каждым тестовым методом
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Создаем экземпляр сервиса для использования в тестах
        $this->cartService = app(CartService::class);
        
        // Очищаем данные сессии перед каждым тестом
        Session::flush();
    }

    // ==========================================
    // ТЕСТЫ: ДОБАВЛЕНИЕ ТОВАРА В КОРЗИНУ
    // ==========================================

    /**
     * Тест: Можно успешно добавить товар в корзину
     * 
     * Проверяем базовый сценарий добавления товара в корзину авторизованного пользователя
     */
    #[Test]
    public function test_can_add_product_to_cart(): void
    {
        // Arrange (Подготовка)
        // Создаем пользователя и авторизуем его
        $user = $this->createUser();
        Auth::login($user);
        
        // Создаем товар с достаточным количеством на складе
        $product = $this->createProduct([
            'price' => 500.00,
            'stock' => 10,
            'is_available' => true,
        ]);

        // Act (Действие)
        // Добавляем товар в корзину
        $cartItem = $this->cartService->addItem($product->id, 2);

        // Assert (Проверка)
        // Проверяем, что позиция корзины была создана
        $this->assertInstanceOf(CartItem::class, $cartItem);
        
        // Проверяем, что товар добавлен с правильным количеством
        $this->assertEquals(2, $cartItem->quantity);
        
        // Проверяем, что цена зафиксирована на момент добавления
        $this->assertEquals(500.00, (float) $cartItem->price);
        
        // Проверяем, что позиция привязана к правильному пользователю
        $this->assertEquals($user->id, $cartItem->user_id);
        
        // Проверяем, что позиция привязана к правильному товару
        $this->assertEquals($product->id, $cartItem->product_id);
        
        // Проверяем, что позиция сохранена в БД
        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 500.00,
        ]);
    }

    /**
     * Тест: Увеличивается количество при повторном добавлении существующего товара
     * 
     * Если товар уже есть в корзине, количество должно увеличиваться,
     * а не создаваться новая позиция
     */
    #[Test]
    public function test_increases_quantity_when_adding_existing_product(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        $product = $this->createProduct([
            'price' => 300.00,
            'stock' => 20,
            'is_available' => true,
        ]);

        // Добавляем товар первый раз (3 единицы)
        $this->cartService->addItem($product->id, 3);

        // Act (Действие)
        // Добавляем тот же товар еще раз (2 единицы)
        $cartItem = $this->cartService->addItem($product->id, 2);

        // Assert (Проверка)
        // Проверяем, что количество увеличилось до 5 (3 + 2)
        $this->assertEquals(5, $cartItem->quantity);
        
        // Проверяем, что в корзине только одна позиция этого товара
        $this->assertEquals(1, CartItem::byUser($user->id)
            ->where('product_id', $product->id)
            ->count()
        );
    }

    /**
     * Тест: Выбрасывается исключение при добавлении несуществующего товара
     * 
     * Если товар не найден в БД, должно выброситься исключение
     */
    #[Test]
    public function test_throws_exception_when_product_not_found(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        // Используем несуществующий ID товара
        $nonExistentProductId = 99999;

        // Assert & Act (Проверка и Действие)
        // Ожидаем, что будет выброшено исключение ModelNotFoundException
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        
        // Пытаемся добавить несуществующий товар
        $this->cartService->addItem($nonExistentProductId);
    }

    /**
     * Тест: Выбрасывается исключение при недостаточном количестве на складе
     * 
     * Если на складе меньше товара, чем пытаемся добавить, должна быть ошибка
     */
    #[Test]
    public function test_throws_exception_when_insufficient_stock(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        // Создаем товар с ограниченным количеством на складе
        $product = $this->createProduct([
            'price' => 400.00,
            'stock' => 3, // Только 3 единицы на складе
            'is_available' => true,
        ]);

        // Assert & Act (Проверка и Действие)
        // Ожидаем исключение с сообщением о недостатке товара
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Недостаточно товара на складе');
        
        // Пытаемся добавить больше, чем есть на складе (5 > 3)
        $this->cartService->addItem($product->id, 5);
    }

    /**
     * Тест: Выбрасывается исключение при попытке добавить недоступный товар
     * 
     * Если товар недоступен (is_available = false), его нельзя добавить в корзину
     */
    #[Test]
    public function test_throws_exception_when_product_unavailable(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        // Создаем недоступный товар
        $product = $this->createProduct([
            'price' => 350.00,
            'stock' => 10,
            'is_available' => false, // Товар выключен
        ]);

        // Assert & Act (Проверка и Действие)
        // Ожидаем исключение ModelNotFoundException, потому что scope available() фильтрует недоступные товары
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        
        // Пытаемся добавить недоступный товар
        $this->cartService->addItem($product->id, 1);
    }

    /**
     * Тест: Цена фиксируется на момент добавления в корзину
     * 
     * Цена в корзине должна быть зафиксирована и не меняться при изменении цены товара
     */
    #[Test]
    public function test_fixes_price_when_adding_to_cart(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        $product = $this->createProduct([
            'price' => 600.00,
            'stock' => 10,
            'is_available' => true,
        ]);

        // Act (Действие)
        // Добавляем товар в корзину по цене 600.00
        $cartItem = $this->cartService->addItem($product->id, 1);
        
        // Изменяем цену товара в БД
        $product->update(['price' => 700.00]);

        // Assert (Проверка)
        // Проверяем, что в корзине осталась старая цена
        $cartItem->refresh(); // Перезагружаем данные из БД
        $this->assertEquals(600.00, (float) $cartItem->price);
        
        // Проверяем, что цена товара действительно изменилась
        $this->assertEquals(700.00, (float) $product->fresh()->price);
    }

    /**
     * Тест: Выбрасывается исключение при превышении остатка при повторном добавлении
     * 
     * Если товар уже в корзине, и при добавлении дополнительного количества
     * превышается остаток, должна быть ошибка
     */
    #[Test]
    public function test_throws_exception_when_exceeding_stock_on_repeated_add(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        $product = $this->createProduct([
            'price' => 450.00,
            'stock' => 10, // Всего 10 единиц на складе
            'is_available' => true,
        ]);

        // Добавляем 7 единиц в корзину
        $this->cartService->addItem($product->id, 7);

        // Assert & Act (Проверка и Действие)
        // Ожидаем исключение, потому что 7 + 5 = 12 > 10
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Недостаточно товара на складе');
        
        // Пытаемся добавить еще 5 единиц (итого будет 12, но на складе только 10)
        $this->cartService->addItem($product->id, 5);
    }

    // ==========================================
    // ТЕСТЫ: ОБНОВЛЕНИЕ КОЛИЧЕСТВА
    // ==========================================

    /**
     * Тест: Можно успешно обновить количество товара в корзине
     * 
     * Базовый сценарий изменения количества позиции корзины
     */
    #[Test]
    public function test_can_update_cart_item_quantity(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        $product = $this->createProduct([
            'price' => 500.00,
            'stock' => 15,
            'is_available' => true,
        ]);
        
        // Добавляем товар в корзину
        $cartItem = $this->cartService->addItem($product->id, 3);

        // Act (Действие)
        // Обновляем количество на 7
        $updatedCartItem = $this->cartService->updateItem($cartItem->id, 7);

        // Assert (Проверка)
        // Проверяем, что количество обновилось
        $this->assertEquals(7, $updatedCartItem->quantity);
        
        // Проверяем, что в БД обновилось
        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 7,
        ]);
    }

    /**
     * Тест: Выбрасывается исключение при обновлении с недостаточным остатком
     * 
     * Если пытаемся обновить количество больше, чем есть на складе
     */
    #[Test]
    public function test_throws_exception_when_updating_with_insufficient_stock(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        $product = $this->createProduct([
            'price' => 400.00,
            'stock' => 5, // Только 5 единиц на складе
            'is_available' => true,
        ]);
        
        $cartItem = $this->cartService->addItem($product->id, 2);

        // Assert & Act (Проверка и Действие)
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Недостаточно товара на складе');
        
        // Пытаемся обновить количество до 10 (больше чем 5 на складе)
        $this->cartService->updateItem($cartItem->id, 10);
    }

    /**
     * Тест: Выбрасывается исключение при обновлении несуществующей позиции
     * 
     * Если позиция не найдена в корзине пользователя
     */
    #[Test]
    public function test_throws_exception_when_cart_item_not_found(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        $nonExistentCartItemId = 99999;

        // Assert & Act (Проверка и Действие)
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Позиция не найдена в корзине');
        
        // Пытаемся обновить несуществующую позицию
        $this->cartService->updateItem($nonExistentCartItemId, 5);
    }

    /**
     * Тест: Нельзя обновить позицию корзины другого пользователя
     * 
     * Проверка безопасности - пользователь может изменять только свою корзину
     */
    #[Test]
    public function test_cannot_update_another_users_cart_item(): void
    {
        // Arrange (Подготовка)
        // Создаем первого пользователя и добавляем товар в его корзину
        $user1 = $this->createUser();
        Auth::login($user1);
        
        $product = $this->createProduct(['stock' => 20, 'is_available' => true]);
        $cartItem = $this->cartService->addItem($product->id, 2);
        
        // Выходим и логинимся как другой пользователь
        Auth::logout();
        $user2 = $this->createUser();
        Auth::login($user2);

        // Assert & Act (Проверка и Действие)
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Позиция не найдена в корзине');
        
        // Пытаемся обновить корзину первого пользователя
        $this->cartService->updateItem($cartItem->id, 5);
    }

    // ==========================================
    // ТЕСТЫ: УДАЛЕНИЕ ТОВАРА
    // ==========================================

    /**
     * Тест: Можно успешно удалить товар из корзины
     * 
     * Базовый сценарий удаления позиции корзины
     */
    #[Test]
    public function test_can_remove_cart_item(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        $product = $this->createProduct(['stock' => 10, 'is_available' => true]);
        $cartItem = $this->cartService->addItem($product->id, 3);

        // Act (Действие)
        $result = $this->cartService->removeItem($cartItem->id);

        // Assert (Проверка)
        // Проверяем, что метод вернул true (успех)
        $this->assertTrue($result);
        
        // Проверяем, что позиция удалена из БД
        $this->assertDatabaseMissing('cart_items', [
            'id' => $cartItem->id,
        ]);
    }

    /**
     * Тест: Выбрасывается исключение при удалении несуществующей позиции
     * 
     * Если позиция не найдена, должна быть ошибка
     */
    #[Test]
    public function test_throws_exception_when_removing_non_existent_item(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        $nonExistentCartItemId = 99999;

        // Assert & Act (Проверка и Действие)
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Позиция не найдена в корзине');
        
        // Пытаемся удалить несуществующую позицию
        $this->cartService->removeItem($nonExistentCartItemId);
    }

    /**
     * Тест: Нельзя удалить позицию корзины другого пользователя
     * 
     * Проверка безопасности - пользователь может удалять только из своей корзины
     */
    #[Test]
    public function test_cannot_remove_another_users_cart_item(): void
    {
        // Arrange (Подготовка)
        $user1 = $this->createUser();
        Auth::login($user1);
        
        $product = $this->createProduct(['stock' => 10, 'is_available' => true]);
        $cartItem = $this->cartService->addItem($product->id, 2);
        
        Auth::logout();
        $user2 = $this->createUser();
        Auth::login($user2);

        // Assert & Act (Проверка и Действие)
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Позиция не найдена в корзине');
        
        $this->cartService->removeItem($cartItem->id);
    }

    // ==========================================
    // ТЕСТЫ: РАСЧЕТЫ
    // ==========================================

    /**
     * Тест: Правильно рассчитывается общая сумма корзины
     * 
     * Проверяем, что total = сумма всех (price * quantity)
     */
    #[Test]
    public function test_calculates_cart_total_correctly(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        $product1 = $this->createProduct(['price' => 100.00, 'stock' => 20, 'is_available' => true]);
        $product2 = $this->createProduct(['price' => 250.50, 'stock' => 20, 'is_available' => true]);
        $product3 = $this->createProduct(['price' => 75.75, 'stock' => 20, 'is_available' => true]);
        
        // Добавляем товары в корзину
        $this->cartService->addItem($product1->id, 2);  // 100 * 2 = 200.00
        $this->cartService->addItem($product2->id, 3);  // 250.50 * 3 = 751.50
        $this->cartService->addItem($product3->id, 4);  // 75.75 * 4 = 303.00
        
        // Ожидаемая сумма: 200.00 + 751.50 + 303.00 = 1254.50

        // Act (Действие)
        $total = $this->cartService->getTotal();

        // Assert (Проверка)
        $this->assertEquals(1254.50, $total);
    }

    /**
     * Тест: Правильно подсчитывается количество позиций в корзине
     * 
     * Количество позиций = количество уникальных товаров (не сумма quantity!)
     */
    #[Test]
    public function test_counts_cart_items_correctly(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        $product1 = $this->createProduct(['stock' => 20, 'is_available' => true]);
        $product2 = $this->createProduct(['stock' => 20, 'is_available' => true]);
        $product3 = $this->createProduct(['stock' => 20, 'is_available' => true]);
        
        // Добавляем 3 разных товара (даже если у них разное quantity)
        $this->cartService->addItem($product1->id, 5);
        $this->cartService->addItem($product2->id, 1);
        $this->cartService->addItem($product3->id, 10);

        // Act (Действие)
        $count = $this->cartService->getItemsCount();

        // Assert (Проверка)
        // Должно быть 3 позиции (3 уникальных товара)
        $this->assertEquals(3, $count);
    }

    /**
     * Тест: Правильно подсчитывается общее количество единиц товаров
     * 
     * Общее количество = сумма всех quantity
     */
    #[Test]
    public function test_counts_cart_items_quantity_correctly(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        $product1 = $this->createProduct(['stock' => 20, 'is_available' => true]);
        $product2 = $this->createProduct(['stock' => 20, 'is_available' => true]);
        $product3 = $this->createProduct(['stock' => 20, 'is_available' => true]);
        
        $this->cartService->addItem($product1->id, 5);  // 5 единиц
        $this->cartService->addItem($product2->id, 3);  // 3 единицы
        $this->cartService->addItem($product3->id, 2);  // 2 единицы
        
        // Ожидаемое количество: 5 + 3 + 2 = 10 единиц

        // Act (Действие)
        $quantity = $this->cartService->getItemsQuantity();

        // Assert (Проверка)
        $this->assertEquals(10, $quantity);
    }

    /**
     * Тест: Проверка пустоты корзины
     * 
     * Проверяем метод isEmpty() для пустой и непустой корзины
     */
    #[Test]
    public function test_cart_is_empty_check(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);

        // Act & Assert (Действие и Проверка)
        // Пустая корзина
        $this->assertTrue($this->cartService->isEmpty());
        
        // Добавляем товар
        $product = $this->createProduct(['stock' => 10, 'is_available' => true]);
        $this->cartService->addItem($product->id, 1);
        
        // Корзина больше не пустая
        $this->assertFalse($this->cartService->isEmpty());
    }

    /**
     * Тест: Правильный расчет общей суммы с десятичными числами
     * 
     * Проверяем округление до 2 знаков после запятой
     */
    #[Test]
    public function test_total_rounds_to_two_decimals(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        // Создаем товар с ценой, которая даст много знаков после запятой
        $product = $this->createProduct([
            'price' => 33.33,
            'stock' => 20,
            'is_available' => true,
        ]);
        
        // 33.33 * 3 = 99.99
        $this->cartService->addItem($product->id, 3);

        // Act (Действие)
        $total = $this->cartService->getTotal();

        // Assert (Проверка)
        // Проверяем точное значение
        $this->assertEquals(99.99, $total);
        
        // Проверяем, что результат - float
        $this->assertIsFloat($total);
    }

    // ==========================================
    // ТЕСТЫ: СИНХРОНИЗАЦИЯ ЦЕН
    // ==========================================

    /**
     * Тест: Синхронизирует цены когда цена товара изменилась
     * 
     * Если цена товара в БД изменилась, метод syncPrices() должен обновить цену в корзине
     */
    #[Test]
    public function test_syncs_prices_when_product_price_changed(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        $product = $this->createProduct([
            'price' => 500.00,
            'stock' => 10,
            'is_available' => true,
        ]);
        
        // Добавляем товар в корзину по цене 500.00
        $cartItem = $this->cartService->addItem($product->id, 2);
        
        // Изменяем цену товара
        $product->update(['price' => 600.00]);

        // Act (Действие)
        $updatedCount = $this->cartService->syncPrices();

        // Assert (Проверка)
        // Проверяем, что одна позиция была обновлена
        $this->assertEquals(1, $updatedCount);
        
        // Проверяем, что цена в корзине обновилась
        $cartItem->refresh();
        $this->assertEquals(600.00, (float) $cartItem->price);
    }

    /**
     * Тест: Не синхронизирует когда цены не изменились
     * 
     * Если цены одинаковые, метод не должен делать обновлений
     */
    #[Test]
    public function test_does_not_sync_when_prices_unchanged(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        $product = $this->createProduct([
            'price' => 400.00,
            'stock' => 10,
            'is_available' => true,
        ]);
        
        // Добавляем товар в корзину
        $this->cartService->addItem($product->id, 1);
        
        // Цену НЕ меняем

        // Act (Действие)
        $updatedCount = $this->cartService->syncPrices();

        // Assert (Проверка)
        // Ни одна позиция не должна быть обновлена
        $this->assertEquals(0, $updatedCount);
    }

    /**
     * Тест: Синхронизирует несколько позиций с разными изменениями цен
     * 
     * Если в корзине несколько товаров с измененными ценами
     */
    #[Test]
    public function test_syncs_multiple_items_with_price_changes(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        // Создаем 3 товара
        $product1 = $this->createProduct(['price' => 100.00, 'stock' => 20, 'is_available' => true]);
        $product2 = $this->createProduct(['price' => 200.00, 'stock' => 20, 'is_available' => true]);
        $product3 = $this->createProduct(['price' => 300.00, 'stock' => 20, 'is_available' => true]);
        
        // Добавляем их в корзину
        $this->cartService->addItem($product1->id, 1);
        $this->cartService->addItem($product2->id, 1);
        $this->cartService->addItem($product3->id, 1);
        
        // Изменяем цены первых двух товаров
        $product1->update(['price' => 150.00]);
        $product2->update(['price' => 250.00]);
        // product3 не меняем

        // Act (Действие)
        $updatedCount = $this->cartService->syncPrices();

        // Assert (Проверка)
        // Должны обновиться 2 позиции
        $this->assertEquals(2, $updatedCount);
    }

    // ==========================================
    // ТЕСТЫ: ПРОВЕРКА ДОСТУПНОСТИ
    // ==========================================

    /**
     * Тест: Проверяет доступность всех товаров в корзине
     * 
     * Все товары доступны, если хватает на складе и они включены
     */
    #[Test]
    public function test_checks_availability_of_all_items(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        $product1 = $this->createProduct([
            'stock' => 10,
            'is_available' => true,
        ]);
        $product2 = $this->createProduct([
            'stock' => 20,
            'is_available' => true,
        ]);
        
        $this->cartService->addItem($product1->id, 3);
        $this->cartService->addItem($product2->id, 5);

        // Act (Действие)
        $result = $this->cartService->checkAvailability();

        // Assert (Проверка)
        // Все товары доступны
        $this->assertTrue($result['available']);
        $this->assertEmpty($result['unavailable_items']);
    }

    /**
     * Тест: Определяет недоступные товары
     * 
     * Если товар выключен (is_available = false), он недоступен
     */
    #[Test]
    public function test_identifies_unavailable_items(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        $product = $this->createProduct([
            'name' => 'Кофе Арабика',
            'stock' => 10,
            'is_available' => true,
        ]);
        
        // Добавляем товар в корзину
        $cartItem = $this->cartService->addItem($product->id, 2);
        
        // Потом отключаем товар
        $product->update(['is_available' => false]);

        // Act (Действие)
        $result = $this->cartService->checkAvailability();

        // Assert (Проверка)
        // Корзина недоступна для оформления
        $this->assertFalse($result['available']);
        
        // Есть недоступные товары
        $this->assertCount(1, $result['unavailable_items']);
        
        // Проверяем информацию о недоступном товаре
        $unavailableItem = $result['unavailable_items'][0];
        $this->assertEquals($cartItem->id, $unavailableItem['cart_item_id']);
        $this->assertEquals('Кофе Арабика', $unavailableItem['product_name']);
        $this->assertFalse($unavailableItem['is_available']);
    }

    /**
     * Тест: Определяет товары с недостаточным количеством на складе
     * 
     * Если на складе меньше, чем в корзине
     */
    #[Test]
    public function test_identifies_insufficient_stock_items(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        $product = $this->createProduct([
            'name' => 'Чай Зеленый',
            'stock' => 10,
            'is_available' => true,
        ]);
        
        // Добавляем 5 единиц в корзину
        $cartItem = $this->cartService->addItem($product->id, 5);
        
        // Потом уменьшаем остаток на складе
        $product->update(['stock' => 3]);

        // Act (Действие)
        $result = $this->cartService->checkAvailability();

        // Assert (Проверка)
        $this->assertFalse($result['available']);
        $this->assertCount(1, $result['unavailable_items']);
        
        $unavailableItem = $result['unavailable_items'][0];
        $this->assertEquals(5, $unavailableItem['requested_quantity']);
        $this->assertEquals(3, $unavailableItem['available_quantity']);
    }

    /**
     * Тест: Проверяет несколько недоступных товаров одновременно
     * 
     * Если в корзине несколько проблемных товаров
     */
    #[Test]
    public function test_identifies_multiple_unavailable_items(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        // Товар 1: недоступен (is_available = false)
        $product1 = $this->createProduct([
            'name' => 'Товар 1',
            'stock' => 10,
            'is_available' => false,
        ]);
        
        // Товар 2: недостаточно на складе
        $product2 = $this->createProduct([
            'name' => 'Товар 2',
            'stock' => 2,
            'is_available' => true,
        ]);
        
        // Товар 3: доступен
        $product3 = $this->createProduct([
            'name' => 'Товар 3',
            'stock' => 20,
            'is_available' => true,
        ]);
        
        // Добавляем в корзину
        CartItem::create([
            'user_id' => $user->id,
            'product_id' => $product1->id,
            'quantity' => 2,
            'price' => $product1->price,
        ]);
        
        CartItem::create([
            'user_id' => $user->id,
            'product_id' => $product2->id,
            'quantity' => 5, // Больше чем 2 на складе
            'price' => $product2->price,
        ]);
        
        CartItem::create([
            'user_id' => $user->id,
            'product_id' => $product3->id,
            'quantity' => 3,
            'price' => $product3->price,
        ]);

        // Act (Действие)
        $result = $this->cartService->checkAvailability();

        // Assert (Проверка)
        $this->assertFalse($result['available']);
        $this->assertCount(2, $result['unavailable_items']);
    }

    // ==========================================
    // ТЕСТЫ: ГОСТЕВАЯ КОРЗИНА
    // ==========================================

    /**
     * Тест: Гость может добавить товар в корзину по session_id
     * 
     * Неавторизованный пользователь использует session_id вместо user_id
     */
    #[Test]
    public function test_guest_can_add_to_cart_by_session(): void
    {
        // Arrange (Подготовка)
        // Убеждаемся, что пользователь НЕ авторизован
        Auth::logout();
        
        // Стартуем сессию
        Session::start();
        $sessionId = Session::getId();
        
        $product = $this->createProduct([
            'price' => 350.00,
            'stock' => 10,
            'is_available' => true,
        ]);

        // Act (Действие)
        $cartItem = $this->cartService->addItem($product->id, 2);

        // Assert (Проверка)
        // user_id должен быть null
        $this->assertNull($cartItem->user_id);
        
        // session_id должен быть заполнен
        $this->assertNotNull($cartItem->session_id);
        
        // Проверяем в БД
        $this->assertDatabaseHas('cart_items', [
            'user_id' => null,
            'session_id' => $sessionId,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    /**
     * Тест: Сливает гостевую корзину с корзиной пользователя при авторизации
     * 
     * После авторизации товары из гостевой корзины переносятся в корзину пользователя
     */
    #[Test]
    public function test_merges_guest_cart_with_user_cart_on_login(): void
    {
        // Arrange (Подготовка)
        // Шаг 1: Гость добавляет товары в корзину
        Auth::logout();
        Session::start();
        $sessionId = Session::getId();
        
        $product1 = $this->createProduct(['stock' => 20, 'is_available' => true]);
        $product2 = $this->createProduct(['stock' => 20, 'is_available' => true]);
        
        // Гостевая корзина: 2 товара
        $this->cartService->addItem($product1->id, 3);
        $this->cartService->addItem($product2->id, 2);
        
        // Проверяем, что товары в гостевой корзине
        $this->assertEquals(2, CartItem::bySession($sessionId)->count());
        
        // Шаг 2: Создаем пользователя (имитация регистрации)
        $user = $this->createUser();

        // Act (Действие)
        // Вызываем слияние корзин
        $this->cartService->mergeGuestCart($user->id);

        // Assert (Проверка)
        // Товары должны быть в корзине пользователя
        $this->assertEquals(2, CartItem::byUser($user->id)->count());
        
        // Гостевая корзина должна быть пуста
        $this->assertEquals(0, CartItem::bySession($sessionId)->count());
        
        // Проверяем конкретные товары
        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product1->id,
            'quantity' => 3,
        ]);
        
        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product2->id,
            'quantity' => 2,
        ]);
    }

    /**
     * Тест: Объединяет одинаковые товары при слиянии корзин
     * 
     * Если товар есть и в гостевой, и в пользовательской корзине,
     * количество должно суммироваться
     */
    #[Test]
    public function test_combines_same_products_when_merging(): void
    {
        // Arrange (Подготовка)
        $product = $this->createProduct(['stock' => 50, 'is_available' => true]);
        
        // Шаг 1: Пользователь уже имеет товар в корзине
        $user = $this->createUser();
        Auth::login($user);
        $this->cartService->addItem($product->id, 5); // 5 единиц
        Auth::logout();
        
        // Шаг 2: Тот же пользователь как гость добавляет тот же товар
        Session::start();
        $sessionId = Session::getId();
        
        // Создаем гостевую позицию напрямую (имитация добавления до авторизации)
        CartItem::create([
            'session_id' => $sessionId,
            'user_id' => null,
            'product_id' => $product->id,
            'quantity' => 3, // 3 единицы
            'price' => $product->price,
        ]);

        // Act (Действие)
        $this->cartService->mergeGuestCart($user->id);

        // Assert (Проверка)
        // Должна быть одна позиция с суммой количества: 5 + 3 = 8
        $this->assertEquals(1, CartItem::byUser($user->id)
            ->where('product_id', $product->id)
            ->count()
        );
        
        $cartItem = CartItem::byUser($user->id)
            ->where('product_id', $product->id)
            ->first();
        
        $this->assertEquals(8, $cartItem->quantity);
    }

    /**
     * Тест: Очищает гостевую корзину после слияния
     * 
     * После переноса товаров гостевая корзина должна быть пуста
     */
    #[Test]
    public function test_clears_guest_cart_after_merging(): void
    {
        // Arrange (Подготовка)
        Auth::logout();
        Session::start();
        $sessionId = Session::getId();
        
        $product1 = $this->createProduct(['stock' => 20, 'is_available' => true]);
        $product2 = $this->createProduct(['stock' => 20, 'is_available' => true]);
        $product3 = $this->createProduct(['stock' => 20, 'is_available' => true]);
        
        // Создаем гостевую корзину с 3 товарами
        CartItem::create([
            'session_id' => $sessionId,
            'product_id' => $product1->id,
            'quantity' => 2,
            'price' => $product1->price,
        ]);
        CartItem::create([
            'session_id' => $sessionId,
            'product_id' => $product2->id,
            'quantity' => 1,
            'price' => $product2->price,
        ]);
        CartItem::create([
            'session_id' => $sessionId,
            'product_id' => $product3->id,
            'quantity' => 4,
            'price' => $product3->price,
        ]);
        
        $user = $this->createUser();

        // Act (Действие)
        $this->cartService->mergeGuestCart($user->id);

        // Assert (Проверка)
        // Гостевая корзина должна быть полностью пуста
        $this->assertEquals(0, CartItem::bySession($sessionId)->count());
        
        // Все товары теперь в корзине пользователя
        $this->assertEquals(3, CartItem::byUser($user->id)->count());
    }

    /**
     * Тест: Не выполняет слияние если гостевая корзина пуста
     * 
     * Если у гостя нет товаров в корзине, слияние не нужно
     */
    #[Test]
    public function test_does_not_merge_when_guest_cart_is_empty(): void
    {
        // Arrange (Подготовка)
        Auth::logout();
        Session::start();
        
        $user = $this->createUser();
        
        // У пользователя уже есть товар в корзине
        Auth::login($user);
        $product = $this->createProduct(['stock' => 10, 'is_available' => true]);
        $this->cartService->addItem($product->id, 3);
        Auth::logout();

        // Act (Действие)
        // Пытаемся слить пустую гостевую корзину
        $this->cartService->mergeGuestCart($user->id);

        // Assert (Проверка)
        // В корзине пользователя должна остаться только его позиция
        $this->assertEquals(1, CartItem::byUser($user->id)->count());
        
        $cartItem = CartItem::byUser($user->id)->first();
        $this->assertEquals(3, $cartItem->quantity);
    }

    // ==========================================
    // ТЕСТЫ: ОЧИСТКА КОРЗИНЫ
    // ==========================================

    /**
     * Тест: Можно полностью очистить корзину
     * 
     * Метод clearCart() удаляет все позиции корзины пользователя
     */
    #[Test]
    public function test_can_clear_entire_cart(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        // Добавляем несколько товаров в корзину
        $product1 = $this->createProduct(['stock' => 20, 'is_available' => true]);
        $product2 = $this->createProduct(['stock' => 20, 'is_available' => true]);
        $product3 = $this->createProduct(['stock' => 20, 'is_available' => true]);
        
        $this->cartService->addItem($product1->id, 2);
        $this->cartService->addItem($product2->id, 3);
        $this->cartService->addItem($product3->id, 1);
        
        // Проверяем, что корзина не пуста
        $this->assertEquals(3, $this->cartService->getItemsCount());

        // Act (Действие)
        $deletedCount = $this->cartService->clearCart();

        // Assert (Проверка)
        // Проверяем, что удалено 3 позиции
        $this->assertEquals(3, $deletedCount);
        
        // Корзина теперь пуста
        $this->assertTrue($this->cartService->isEmpty());
        $this->assertEquals(0, $this->cartService->getItemsCount());
        
        // В БД нет позиций для этого пользователя
        $this->assertEquals(0, CartItem::byUser($user->id)->count());
    }

    /**
     * Тест: Возвращает количество удаленных позиций
     * 
     * Метод clearCart() должен вернуть количество удаленных записей
     */
    #[Test]
    public function test_returns_deleted_items_count(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        // Добавляем 5 товаров
        $products = $this->createProducts(5, ['stock' => 20, 'is_available' => true]);
        
        foreach ($products as $product) {
            $this->cartService->addItem($product->id, 1);
        }

        // Act (Действие)
        $deletedCount = $this->cartService->clearCart();

        // Assert (Проверка)
        $this->assertEquals(5, $deletedCount);
    }

    /**
     * Тест: Очищает только корзину текущего пользователя
     * 
     * Проверка безопасности - не удаляет корзины других пользователей
     */
    #[Test]
    public function test_clears_only_current_users_cart(): void
    {
        // Arrange (Подготовка)
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        
        $product = $this->createProduct(['stock' => 20, 'is_available' => true]);
        
        // Добавляем товар в корзину первого пользователя
        Auth::login($user1);
        $this->cartService->addItem($product->id, 2);
        
        // Добавляем товар в корзину второго пользователя
        Auth::logout();
        Auth::login($user2);
        $this->cartService->addItem($product->id, 3);

        // Act (Действие)
        // Очищаем корзину второго пользователя
        $this->cartService->clearCart();

        // Assert (Проверка)
        // Корзина второго пользователя пуста
        $this->assertEquals(0, CartItem::byUser($user2->id)->count());
        
        // Корзина первого пользователя НЕ ТРОНУТА
        $this->assertEquals(1, CartItem::byUser($user1->id)->count());
    }

    /**
     * Тест: Гость может очистить свою корзину по session_id
     * 
     * Неавторизованный пользователь может очистить гостевую корзину
     */
    #[Test]
    public function test_guest_can_clear_cart_by_session(): void
    {
        // Arrange (Подготовка)
        Auth::logout();
        Session::start();
        $sessionId = Session::getId();
        
        $product1 = $this->createProduct(['stock' => 20, 'is_available' => true]);
        $product2 = $this->createProduct(['stock' => 20, 'is_available' => true]);
        
        // Добавляем товары в гостевую корзину
        $this->cartService->addItem($product1->id, 2);
        $this->cartService->addItem($product2->id, 1);
        
        // Проверяем, что корзина не пуста
        $this->assertEquals(2, CartItem::bySession($sessionId)->count());

        // Act (Действие)
        $deletedCount = $this->cartService->clearCart();

        // Assert (Проверка)
        $this->assertEquals(2, $deletedCount);
        $this->assertEquals(0, CartItem::bySession($sessionId)->count());
    }

    // ==========================================
    // ТЕСТЫ: ПОЛУЧЕНИЕ ТОВАРОВ КОРЗИНЫ
    // ==========================================

    /**
     * Тест: Получает все товары из корзины пользователя
     * 
     * Метод getCartItems() возвращает коллекцию позиций корзины
     */
    #[Test]
    public function test_gets_all_cart_items_for_user(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        $product1 = $this->createProduct(['stock' => 20, 'is_available' => true]);
        $product2 = $this->createProduct(['stock' => 20, 'is_available' => true]);
        
        $this->cartService->addItem($product1->id, 2);
        $this->cartService->addItem($product2->id, 3);

        // Act (Действие)
        $items = $this->cartService->getCartItems();

        // Assert (Проверка)
        $this->assertCount(2, $items);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $items);
        
        // Проверяем, что связи загружены
        $firstItem = $items->first();
        $this->assertTrue($firstItem->relationLoaded('product'));
    }

    /**
     * Тест: Возвращает пустую коллекцию для пустой корзины
     * 
     * Если корзина пуста, getCartItems() возвращает пустую коллекцию (не null)
     */
    #[Test]
    public function test_returns_empty_collection_for_empty_cart(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);

        // Act (Действие)
        $items = $this->cartService->getCartItems();

        // Assert (Проверка)
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $items);
        $this->assertTrue($items->isEmpty());
        $this->assertCount(0, $items);
    }

    /**
     * Тест: Возвращает только товары текущего пользователя
     * 
     * Проверка безопасности - не возвращает товары других пользователей
     */
    #[Test]
    public function test_returns_only_current_users_cart_items(): void
    {
        // Arrange (Подготовка)
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        
        $product = $this->createProduct(['stock' => 20, 'is_available' => true]);
        
        // Пользователь 1 добавляет товар
        Auth::login($user1);
        $this->cartService->addItem($product->id, 2);
        
        // Пользователь 2 добавляет товар
        Auth::logout();
        Auth::login($user2);
        $this->cartService->addItem($product->id, 5);

        // Act (Действие)
        $items = $this->cartService->getCartItems();

        // Assert (Проверка)
        // Должна быть только одна позиция (второго пользователя)
        $this->assertCount(1, $items);
        
        $item = $items->first();
        $this->assertEquals($user2->id, $item->user_id);
        $this->assertEquals(5, $item->quantity);
    }

    // ==========================================
    // ДОПОЛНИТЕЛЬНЫЕ EDGE CASE ТЕСТЫ
    // ==========================================

    /**
     * Тест: Обработка транзакции при ошибке добавления в корзину
     * 
     * Если происходит ошибка в середине транзакции, изменения откатываются
     */
    #[Test]
    public function test_rolls_back_transaction_on_error(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        // Создаем товар с недостаточным остатком
        $product = $this->createProduct([
            'stock' => 5,
            'is_available' => true,
        ]);

        // Act & Assert (Действие и Проверка)
        try {
            // Пытаемся добавить больше, чем есть на складе
            $this->cartService->addItem($product->id, 10);
        } catch (\Exception $e) {
            // Ожидаем исключение
        }

        // Проверяем, что в корзине ничего не добавилось
        $this->assertEquals(0, CartItem::byUser($user->id)->count());
    }

    /**
     * Тест: Корректно работает с товаром нулевой ценой (бесплатный товар)
     * 
     * Проверяем edge case с нулевой ценой
     */
    #[Test]
    public function test_handles_zero_price_product(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        $product = $this->createProduct([
            'price' => 0.00,
            'stock' => 10,
            'is_available' => true,
        ]);

        // Act (Действие)
        $cartItem = $this->cartService->addItem($product->id, 2);

        // Assert (Проверка)
        $this->assertEquals(0.00, (float) $cartItem->price);
        $this->assertEquals(0.00, $this->cartService->getTotal());
    }

    /**
     * Тест: Корректно работает с очень большими числами
     * 
     * Проверяем edge case с большой ценой и количеством
     */
    #[Test]
    public function test_handles_large_numbers(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        $product = $this->createProduct([
            'price' => 9999.99,
            'stock' => 100,
            'is_available' => true,
        ]);

        // Act (Действие)
        $this->cartService->addItem($product->id, 50);

        // Assert (Проверка)
        // 9999.99 * 50 = 499999.50
        $this->assertEquals(499999.50, $this->cartService->getTotal());
    }

    /**
     * Тест: Правильно работает с десятичными ценами
     * 
     * Проверяем точность расчетов с копейками
     */
    #[Test]
    public function test_handles_decimal_prices_accurately(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        // Товары с копейками
        $product1 = $this->createProduct(['price' => 123.45, 'stock' => 20, 'is_available' => true]);
        $product2 = $this->createProduct(['price' => 67.89, 'stock' => 20, 'is_available' => true]);
        
        $this->cartService->addItem($product1->id, 3); // 123.45 * 3 = 370.35
        $this->cartService->addItem($product2->id, 2); // 67.89 * 2 = 135.78

        // Act (Действие)
        $total = $this->cartService->getTotal();

        // Assert (Проверка)
        // 370.35 + 135.78 = 506.13
        $this->assertEquals(506.13, $total);
    }

    /**
     * Тест: Сессия создается автоматически для гостя
     * 
     * Если у гостя нет сессии, она должна быть создана автоматически
     */
    #[Test]
    public function test_creates_session_automatically_for_guest(): void
    {
        // Arrange (Подготовка)
        Auth::logout();
        
        // НЕ стартуем сессию вручную, пусть сервис это сделает
        $product = $this->createProduct(['stock' => 10, 'is_available' => true]);

        // Act (Действие)
        $cartItem = $this->cartService->addItem($product->id, 1);

        // Assert (Проверка)
        // session_id должен быть заполнен
        $this->assertNotNull($cartItem->session_id);
        
        // И сессия должна существовать
        $this->assertTrue(Session::has('cart_session_id'));
    }
}
