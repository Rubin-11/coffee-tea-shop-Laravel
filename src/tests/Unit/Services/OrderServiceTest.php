<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Database\Factories\OrderFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Unit тесты для OrderService
 * 
 * Этот класс тестирует всю бизнес-логику сервиса заказов:
 * - Создание заказов из корзины
 * - Расчет стоимости (товары, доставка, скидки)
 * - Генерацию уникальных номеров заказов
 * - Отмену заказов и возврат товаров на склад
 * - Обработку оплаты
 * - Уменьшение остатков товаров
 */
class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Экземпляр тестируемого сервиса заказов
     * 
     * @var OrderService
     */
    private OrderService $orderService;

    /**
     * Экземпляр сервиса корзины (используется для создания заказов)
     * 
     * @var CartService
     */
    private CartService $cartService;

    /**
     * Настройка перед каждым тестом
     * 
     * Этот метод автоматически вызывается перед каждым тестом.
     * Здесь мы создаем чистые экземпляры сервисов для тестирования.
     * 
     * @return void
     */
    protected function setUp(): void
    {
        // Вызываем родительский setUp для инициализации Laravel окружения
        parent::setUp();

        // Создаем экземпляры сервисов для тестирования
        $this->cartService = app(CartService::class);
        $this->orderService = app(OrderService::class);

        // Сбрасываем счетчик номеров заказов для предсказуемости тестов
        OrderFactory::resetOrderCounter();
    }

    // ========================================================================
    // 2.1 СОЗДАНИЕ ЗАКАЗА
    // ========================================================================

    /**
     * Тест: Успешное создание заказа из корзины
     * 
     * Проверяет базовый сценарий создания заказа:
     * 1. Пользователь добавил товар в корзину
     * 2. Оформляет заказ с указанием данных доставки
     * 3. Создается заказ с правильными данными
     * 4. Корзина очищается после создания заказа
     * 
     * @return void
     */
    public function test_can_create_order_from_cart(): void
    {
        // Arrange (Подготовка)
        // Создаем авторизованного пользователя
        $user = $this->createUser();
        Auth::login($user);

        // Создаем товар с известными параметрами для проверки
        $product = $this->createProduct([
            'price' => 500.00,
            'stock' => 10,
            'is_available' => true,
        ]);

        // Добавляем товар в корзину (2 единицы)
        $this->cartService->addItem($product->id, 2);

        // Подготавливаем данные заказа (как будто пользователь заполнил форму checkout)
        $orderData = [
            'name' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'phone' => '+7 (999) 123-45-67',
            'delivery_method' => 'courier',
            'payment_method' => 'cash',
            'comment' => 'Позвоните за час до доставки',
        ];

        // Act (Действие)
        // Создаем заказ через сервис
        $order = $this->orderService->createOrder($orderData);

        // Assert (Проверка)
        // Проверяем, что заказ был создан в базе данных
        $this->assertInstanceOf(Order::class, $order);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => $user->id,
            'customer_name' => 'Иван Иванов',
            'customer_email' => 'ivan@example.com',
            'customer_phone' => '+7 (999) 123-45-67',
            'delivery_method' => 'courier',
            'payment_method' => 'cash',
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        // Проверяем, что создана позиция заказа
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 500.00,
        ]);

        // Проверяем, что корзина была очищена после создания заказа
        $this->assertTrue($this->cartService->isEmpty());
    }

    /**
     * Тест: Ошибка при попытке создать заказ из пустой корзины
     * 
     * Пользователь не должен иметь возможность оформить заказ,
     * если его корзина пуста.
     * 
     * @return void
     */
    public function test_throws_exception_when_cart_is_empty(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);

        // Корзина пуста (ничего не добавляем)
        
        $orderData = [
            'name' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'phone' => '+7 (999) 123-45-67',
            'delivery_method' => 'courier',
            'payment_method' => 'cash',
        ];

        // Assert & Act
        // Ожидаем, что будет выброшено исключение с конкретным сообщением
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Корзина пуста');

        // Пытаемся создать заказ из пустой корзины
        $this->orderService->createOrder($orderData);
    }

    /**
     * Тест: Ошибка при наличии недоступных товаров в корзине
     * 
     * Если в корзине есть товары, которых нет в наличии или они недоступны,
     * заказ не должен быть создан.
     * 
     * @return void
     */
    public function test_throws_exception_when_items_unavailable(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);

        // Создаем товар, которого НЕТ в наличии
        $product = $this->createProduct([
            'price' => 500.00,
            'stock' => 0, // Нет на складе!
            'is_available' => false, // Недоступен для заказа
        ]);

        // Добавляем товар в корзину напрямую через модель (обходим проверку в CartService)
        CartItem::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $product->price,
        ]);

        $orderData = [
            'name' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'phone' => '+7 (999) 123-45-67',
            'delivery_method' => 'courier',
            'payment_method' => 'cash',
        ];

        // Assert & Act
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('недоступны');

        $this->orderService->createOrder($orderData);
    }

    /**
     * Тест: Правильное создание позиций заказа
     * 
     * Проверяет, что для каждого товара в корзине создается
     * соответствующая позиция в заказе с правильными данными.
     * 
     * @return void
     */
    public function test_creates_order_items_correctly(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);

        // Создаем несколько товаров
        $product1 = $this->createProduct(['price' => 300.00, 'stock' => 10, 'is_available' => true]);
        $product2 = $this->createProduct(['price' => 500.00, 'stock' => 5, 'is_available' => true]);

        // Добавляем товары в корзину
        $this->cartService->addItem($product1->id, 2);
        $this->cartService->addItem($product2->id, 1);

        $orderData = [
            'name' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'phone' => '+7 (999) 123-45-67',
            'delivery_method' => 'pickup',
            'payment_method' => 'cash',
        ];

        // Act (Действие)
        $order = $this->orderService->createOrder($orderData);

        // Assert (Проверка)
        // Проверяем, что создано 2 позиции заказа
        $this->assertCount(2, $order->items);

        // Проверяем первую позицию
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'quantity' => 2,
            'price' => 300.00,
            'total' => 600.00, // 300 * 2
        ]);

        // Проверяем вторую позицию
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product2->id,
            'quantity' => 1,
            'price' => 500.00,
            'total' => 500.00, // 500 * 1
        ]);
    }

    /**
     * Тест: Фиксация названия товара в заказе
     * 
     * Название товара должно быть сохранено в позиции заказа
     * на момент покупки (snapshot). Даже если потом название товара изменится,
     * в заказе должно остаться старое название.
     * 
     * @return void
     */
    public function test_fixes_product_details_in_order(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);

        $product = $this->createProduct([
            'name' => 'Кофе Эфиопия 250г',
            'price' => 400.00,
            'stock' => 10,
            'is_available' => true,
        ]);

        $this->cartService->addItem($product->id, 1);

        $orderData = [
            'name' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'phone' => '+7 (999) 123-45-67',
            'delivery_method' => 'pickup',
            'payment_method' => 'cash',
        ];

        // Act (Действие)
        $order = $this->orderService->createOrder($orderData);

        // Меняем название товара после создания заказа
        $product->name = 'Кофе Эфиопия 500г';
        $product->save();

        // Assert (Проверка)
        // В позиции заказа должно остаться старое название
        $orderItem = OrderItem::where('order_id', $order->id)->first();
        $this->assertEquals('Кофе Эфиопия 250г', $orderItem->product_name);
        
        // А в базе товаров - новое
        $this->assertEquals('Кофе Эфиопия 500г', $product->fresh()->name);
    }

    /**
     * Тест: Очистка корзины после создания заказа
     * 
     * После успешного создания заказа корзина должна быть автоматически очищена.
     * 
     * @return void
     */
    public function test_clears_cart_after_order_creation(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);

        $product = $this->createProduct(['price' => 500.00, 'stock' => 10, 'is_available' => true]);
        $this->cartService->addItem($product->id, 2);

        // Проверяем, что корзина не пуста
        $this->assertFalse($this->cartService->isEmpty());

        $orderData = [
            'name' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'phone' => '+7 (999) 123-45-67',
            'delivery_method' => 'courier',
            'payment_method' => 'cash',
        ];

        // Act (Действие)
        $this->orderService->createOrder($orderData);

        // Assert (Проверка)
        // Корзина должна быть пуста после создания заказа
        $this->assertTrue($this->cartService->isEmpty());
        $this->assertEquals(0, $this->cartService->getItemsCount());
    }

    // ========================================================================
    // 2.2 ГЕНЕРАЦИЯ НОМЕРА ЗАКАЗА
    // ========================================================================

    /**
     * Тест: Генерация уникального номера заказа
     * 
     * Каждый заказ должен иметь уникальный номер.
     * Два разных заказа не могут иметь одинаковый номер.
     * 
     * @return void
     */
    public function test_generates_unique_order_number(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);

        $product = $this->createProduct(['price' => 500.00, 'stock' => 10, 'is_available' => true]);

        $orderData = [
            'name' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'phone' => '+7 (999) 123-45-67',
            'delivery_method' => 'courier',
            'payment_method' => 'cash',
        ];

        // Act (Действие)
        // Создаем первый заказ
        $this->cartService->addItem($product->id, 1);
        $order1 = $this->orderService->createOrder($orderData);

        // Создаем второй заказ
        $this->cartService->addItem($product->id, 1);
        $order2 = $this->orderService->createOrder($orderData);

        // Assert (Проверка)
        // Номера заказов должны быть разными
        $this->assertNotEquals($order1->order_number, $order2->order_number);
    }

    /**
     * Тест: Правильный формат номера заказа
     * 
     * Номер заказа должен соответствовать формату: ORD-YYYY-XXXXX
     * Где YYYY - текущий год, XXXXX - порядковый номер с ведущими нулями
     * 
     * Например: ORD-2026-00001, ORD-2026-00002 и т.д.
     * 
     * @return void
     */
    public function test_order_number_format_is_correct(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);

        $product = $this->createProduct(['price' => 500.00, 'stock' => 10, 'is_available' => true]);
        $this->cartService->addItem($product->id, 1);

        $orderData = [
            'name' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'phone' => '+7 (999) 123-45-67',
            'delivery_method' => 'courier',
            'payment_method' => 'cash',
        ];

        // Act (Действие)
        $order = $this->orderService->createOrder($orderData);

        // Assert (Проверка)
        // Проверяем формат номера заказа с помощью регулярного выражения
        $year = date('Y');
        $expectedPattern = "/^ORD-{$year}-\d{5}$/"; // ORD-2026-00001
        
        $this->assertMatchesRegularExpression(
            $expectedPattern,
            $order->order_number,
            "Номер заказа должен соответствовать формату ORD-YYYY-XXXXX"
        );
    }

    /**
     * Тест: Корректное увеличение номера заказа
     * 
     * Номера заказов должны увеличиваться последовательно:
     * ORD-2026-00001, ORD-2026-00002, ORD-2026-00003 и т.д.
     * 
     * @return void
     */
    public function test_order_number_increments_correctly(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);

        $product = $this->createProduct(['price' => 500.00, 'stock' => 10, 'is_available' => true]);

        $orderData = [
            'name' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'phone' => '+7 (999) 123-45-67',
            'delivery_method' => 'courier',
            'payment_method' => 'cash',
        ];

        // Act (Действие)
        // Создаем три заказа подряд
        $this->cartService->addItem($product->id, 1);
        $order1 = $this->orderService->createOrder($orderData);

        $this->cartService->addItem($product->id, 1);
        $order2 = $this->orderService->createOrder($orderData);

        $this->cartService->addItem($product->id, 1);
        $order3 = $this->orderService->createOrder($orderData);

        // Assert (Проверка)
        $year = date('Y');
        
        // Извлекаем порядковые номера из номеров заказов
        $number1 = (int) substr($order1->order_number, -5);
        $number2 = (int) substr($order2->order_number, -5);
        $number3 = (int) substr($order3->order_number, -5);

        // Проверяем, что номера увеличиваются последовательно
        $this->assertEquals($number1 + 1, $number2);
        $this->assertEquals($number2 + 1, $number3);
    }

    /**
     * Тест: Сброс нумерации заказов каждый год
     * 
     * Нумерация заказов должна начинаться заново каждый год.
     * Например:
     * - В 2026 году: ORD-2026-00001, ORD-2026-00002...
     * - В 2027 году: ORD-2027-00001, ORD-2027-00002... (нумерация сбрасывается)
     * 
     * Этот тест проверяет, что если есть заказы из прошлого года,
     * новый заказ все равно начнется с 00001.
     * 
     * @return void
     */
    public function test_order_number_resets_yearly(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);

        // Создаем заказ из прошлого года вручную
        $lastYear = date('Y') - 1;
        Order::create([
            'user_id' => $user->id,
            'order_number' => "ORD-{$lastYear}-00999", // Последний заказ прошлого года
            'customer_name' => 'Тест',
            'customer_email' => 'test@example.com',
            'customer_phone' => '+7 (999) 123-45-67',
            'delivery_address' => 'Адрес',
            'delivery_method' => 'courier',
            'payment_method' => 'cash',
            'subtotal' => 1000,
            'delivery_cost' => 0,
            'discount' => 0,
            'total' => 1000,
            'status' => 'delivered',
            'payment_status' => 'paid',
        ]);

        $product = $this->createProduct(['price' => 500.00, 'stock' => 10, 'is_available' => true]);
        $this->cartService->addItem($product->id, 1);

        $orderData = [
            'name' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'phone' => '+7 (999) 123-45-67',
            'delivery_method' => 'courier',
            'payment_method' => 'cash',
        ];

        // Act (Действие)
        $order = $this->orderService->createOrder($orderData);

        // Assert (Проверка)
        $currentYear = date('Y');
        
        // Новый заказ должен начинаться с 00001, а не с 01000
        $this->assertEquals("ORD-{$currentYear}-00001", $order->order_number);
    }

    // ========================================================================
    // 2.3 РАСЧЕТ СТОИМОСТИ
    // ========================================================================

    /**
     * Тест: Правильный расчет промежуточной суммы заказа
     * 
     * Промежуточная сумма (subtotal) = сумма стоимости всех товаров
     * без учета доставки и скидок.
     * 
     * @return void
     */
    public function test_calculates_subtotal_correctly(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);

        // Создаем несколько товаров с разными ценами
        $product1 = $this->createProduct(['price' => 350.50, 'stock' => 10, 'is_available' => true]);
        $product2 = $this->createProduct(['price' => 199.99, 'stock' => 10, 'is_available' => true]);
        $product3 = $this->createProduct(['price' => 500.00, 'stock' => 10, 'is_available' => true]);

        // Добавляем товары в корзину с разным количеством
        $this->cartService->addItem($product1->id, 2); // 350.50 * 2 = 701.00
        $this->cartService->addItem($product2->id, 3); // 199.99 * 3 = 599.97
        $this->cartService->addItem($product3->id, 1); // 500.00 * 1 = 500.00

        $orderData = [
            'name' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'phone' => '+7 (999) 123-45-67',
            'delivery_method' => 'pickup', // Самовывоз, чтобы не было доставки
            'payment_method' => 'cash',
        ];

        // Act (Действие)
        $order = $this->orderService->createOrder($orderData);

        // Assert (Проверка)
        // Ожидаемая промежуточная сумма: 701.00 + 599.97 + 500.00 = 1800.97
        $expectedSubtotal = 1800.97;
        
        $this->assertEquals($expectedSubtotal, (float) $order->subtotal);
    }

    /**
     * Тест: Бесплатная доставка при самовывозе
     * 
     * При выборе самовывоза (pickup) стоимость доставки должна быть 0.
     * 
     * @return void
     */
    public function test_calculates_delivery_cost_for_pickup(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);

        $product = $this->createProduct(['price' => 1000.00, 'stock' => 10, 'is_available' => true]);
        $this->cartService->addItem($product->id, 1);

        $orderData = [
            'name' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'phone' => '+7 (999) 123-45-67',
            'delivery_method' => 'pickup', // Самовывоз
            'payment_method' => 'cash',
        ];

        // Act (Действие)
        $order = $this->orderService->createOrder($orderData);

        // Assert (Проверка)
        $this->assertEquals(0.00, (float) $order->delivery_cost);
    }

    /**
     * Тест: Расчет стоимости курьерской доставки (300 руб)
     * 
     * При заказе менее 2000 руб курьерская доставка стоит 300 руб.
     * 
     * @return void
     */
    public function test_calculates_delivery_cost_for_courier(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);

        // Создаем товар на сумму меньше 2000 руб (порог бесплатной доставки)
        $product = $this->createProduct(['price' => 800.00, 'stock' => 10, 'is_available' => true]);
        $this->cartService->addItem($product->id, 1);

        $orderData = [
            'name' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'phone' => '+7 (999) 123-45-67',
            'delivery_method' => 'courier', // Курьерская доставка
            'payment_method' => 'cash',
        ];

        // Act (Действие)
        $order = $this->orderService->createOrder($orderData);

        // Assert (Проверка)
        $this->assertEquals(300.00, (float) $order->delivery_cost);
    }

    /**
     * Тест: Бесплатная курьерская доставка при заказе от 2000 руб
     * 
     * Если сумма заказа >= 2000 руб, курьерская доставка бесплатная.
     * 
     * @return void
     */
    public function test_free_courier_delivery_over_threshold(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);

        // Создаем товар на сумму >= 2000 руб
        $product = $this->createProduct(['price' => 2500.00, 'stock' => 10, 'is_available' => true]);
        $this->cartService->addItem($product->id, 1);

        $orderData = [
            'name' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'phone' => '+7 (999) 123-45-67',
            'delivery_method' => 'courier', // Курьерская доставка
            'payment_method' => 'cash',
        ];

        // Act (Действие)
        $order = $this->orderService->createOrder($orderData);

        // Assert (Проверка)
        // Доставка должна быть бесплатной, т.к. заказ >= 2000 руб
        $this->assertEquals(0.00, (float) $order->delivery_cost);
    }

    /**
     * Тест: Расчет стоимости доставки почтой (400 руб)
     * 
     * Доставка почтой России всегда стоит 400 руб, независимо от суммы заказа.
     * 
     * @return void
     */
    public function test_calculates_delivery_cost_for_post(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);

        $product = $this->createProduct(['price' => 1000.00, 'stock' => 10, 'is_available' => true]);
        $this->cartService->addItem($product->id, 1);

        $orderData = [
            'name' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'phone' => '+7 (999) 123-45-67',
            'delivery_method' => 'post', // Почта России
            'payment_method' => 'cash',
        ];

        // Act (Действие)
        $order = $this->orderService->createOrder($orderData);

        // Assert (Проверка)
        $this->assertEquals(400.00, (float) $order->delivery_cost);
    }

    /**
     * Тест: Скидка 5% для заказов от 3000 руб
     * 
     * При сумме заказа >= 3000 руб автоматически применяется скидка 5%.
     * 
     * @return void
     */
    public function test_calculates_discount_for_large_orders(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);

        // Создаем товар на сумму >= 3000 руб
        $product = $this->createProduct(['price' => 3500.00, 'stock' => 10, 'is_available' => true]);
        $this->cartService->addItem($product->id, 1);

        $orderData = [
            'name' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'phone' => '+7 (999) 123-45-67',
            'delivery_method' => 'pickup',
            'payment_method' => 'cash',
        ];

        // Act (Действие)
        $order = $this->orderService->createOrder($orderData);

        // Assert (Проверка)
        // Ожидаемая скидка: 3500 * 0.05 = 175.00
        $expectedDiscount = 175.00;
        
        $this->assertEquals($expectedDiscount, (float) $order->discount);
    }

    /**
     * Тест: Правильный расчет итоговой суммы заказа
     * 
     * Итоговая сумма (total) = subtotal + delivery_cost - discount
     * 
     * @return void
     */
    public function test_calculates_total_correctly(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);

        // Создаем товар на сумму 3500 руб (будет скидка 5%)
        $product = $this->createProduct(['price' => 3500.00, 'stock' => 10, 'is_available' => true]);
        $this->cartService->addItem($product->id, 1);

        $orderData = [
            'name' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'phone' => '+7 (999) 123-45-67',
            'delivery_method' => 'courier', // 0 руб (т.к. сумма >= 2000)
            'payment_method' => 'cash',
        ];

        // Act (Действие)
        $order = $this->orderService->createOrder($orderData);

        // Assert (Проверка)
        // Расчет:
        // Subtotal: 3500.00
        // Delivery: 0.00 (бесплатно, т.к. >= 2000)
        // Discount: 175.00 (5% от 3500)
        // Total: 3500.00 + 0.00 - 175.00 = 3325.00
        
        $this->assertEquals(3500.00, (float) $order->subtotal);
        $this->assertEquals(0.00, (float) $order->delivery_cost);
        $this->assertEquals(175.00, (float) $order->discount);
        $this->assertEquals(3325.00, (float) $order->total);
    }

    /**
     * Тест: Сложный расчет с доставкой и скидкой
     * 
     * Проверяет правильность всех расчетов в комплексе:
     * - Несколько товаров с разными ценами
     * - Платная доставка
     - Скидка для крупного заказа
     * 
     * @return void
     */
    public function test_calculates_total_with_delivery_and_discount(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);

        // Создаем товары на общую сумму 1800 руб (меньше 2000, но меньше 3000)
        $product1 = $this->createProduct(['price' => 900.00, 'stock' => 10, 'is_available' => true]);
        $product2 = $this->createProduct(['price' => 900.00, 'stock' => 10, 'is_available' => true]);
        
        $this->cartService->addItem($product1->id, 1);
        $this->cartService->addItem($product2->id, 1);

        $orderData = [
            'name' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'phone' => '+7 (999) 123-45-67',
            'delivery_method' => 'courier', // 300 руб (т.к. < 2000)
            'payment_method' => 'cash',
        ];

        // Act (Действие)
        $order = $this->orderService->createOrder($orderData);

        // Assert (Проверка)
        // Расчет:
        // Subtotal: 1800.00 (900 + 900)
        // Delivery: 300.00 (курьер, т.к. < 2000)
        // Discount: 0.00 (нет скидки, т.к. < 3000)
        // Total: 1800.00 + 300.00 - 0.00 = 2100.00
        
        $this->assertEquals(1800.00, (float) $order->subtotal);
        $this->assertEquals(300.00, (float) $order->delivery_cost);
        $this->assertEquals(0.00, (float) $order->discount);
        $this->assertEquals(2100.00, (float) $order->total);
    }

    // ========================================================================
    // 2.4 УМЕНЬШЕНИЕ ОСТАТКОВ
    // ========================================================================

    /**
     * Тест: Уменьшение остатков товаров после создания заказа
     * 
     * После успешного создания заказа количество товаров на складе
     * должно автоматически уменьшиться на заказанное количество.
     * 
     * @return void
     */
    public function test_decreases_product_stock_after_order(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);

        // Создаем товар с известным количеством на складе
        $product = $this->createProduct([
            'price' => 500.00,
            'stock' => 20, // Начальный остаток: 20 шт
            'is_available' => true,
        ]);

        // Добавляем в корзину 5 единиц товара
        $this->cartService->addItem($product->id, 5);

        $orderData = [
            'name' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'phone' => '+7 (999) 123-45-67',
            'delivery_method' => 'courier',
            'payment_method' => 'cash',
        ];

        // Act (Действие)
        $this->orderService->createOrder($orderData);

        // Assert (Проверка)
        // Остаток должен уменьшиться: 20 - 5 = 15
        $product->refresh(); // Обновляем данные из БД
        $this->assertEquals(15, $product->stock);
    }

    /**
     * Тест: Правильное уменьшение остатков для нескольких товаров
     * 
     * Если в заказе несколько разных товаров, остатки должны уменьшиться
     * для каждого товара на соответствующее количество.
     * 
     * @return void
     */
    public function test_stock_decreased_by_correct_quantity(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);

        // Создаем три товара с разными начальными остатками
        $product1 = $this->createProduct(['price' => 300.00, 'stock' => 50, 'is_available' => true]);
        $product2 = $this->createProduct(['price' => 400.00, 'stock' => 30, 'is_available' => true]);
        $product3 = $this->createProduct(['price' => 500.00, 'stock' => 10, 'is_available' => true]);

        // Добавляем товары в корзину с разным количеством
        $this->cartService->addItem($product1->id, 3); // Заказываем 3 шт
        $this->cartService->addItem($product2->id, 5); // Заказываем 5 шт
        $this->cartService->addItem($product3->id, 2); // Заказываем 2 шт

        $orderData = [
            'name' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'phone' => '+7 (999) 123-45-67',
            'delivery_method' => 'pickup',
            'payment_method' => 'cash',
        ];

        // Act (Действие)
        $this->orderService->createOrder($orderData);

        // Assert (Проверка)
        // Проверяем, что остатки уменьшились правильно для каждого товара
        $product1->refresh();
        $product2->refresh();
        $product3->refresh();

        $this->assertEquals(47, $product1->stock); // 50 - 3 = 47
        $this->assertEquals(25, $product2->stock); // 30 - 5 = 25
        $this->assertEquals(8, $product3->stock);  // 10 - 2 = 8
    }

    // ========================================================================
    // 2.5 ОТМЕНА ЗАКАЗА
    // ========================================================================

    /**
     * Тест: Успешная отмена заказа со статусом pending
     * 
     * Заказ со статусом "ожидает обработки" может быть отменен.
     * 
     * @return void
     */
    public function test_can_cancel_pending_order(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);

        $product = $this->createProduct(['price' => 500.00, 'stock' => 10, 'is_available' => true]);
        $this->cartService->addItem($product->id, 2);

        $orderData = [
            'name' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'phone' => '+7 (999) 123-45-67',
            'delivery_method' => 'courier',
            'payment_method' => 'cash',
        ];

        $order = $this->orderService->createOrder($orderData);
        
        // Убеждаемся, что заказ имеет статус pending
        $this->assertEquals('pending', $order->status);

        // Act (Действие)
        $cancelledOrder = $this->orderService->cancelOrder($order, 'Передумал');

        // Assert (Проверка)
        $this->assertEquals('cancelled', $cancelledOrder->status);
        $this->assertNotNull($cancelledOrder->cancelled_at);
    }

    /**
     * Тест: Нельзя отменить доставленный заказ
     * 
     * Заказ, который уже доставлен, не может быть отменен.
     * 
     * @return void
     */
    public function test_cannot_cancel_delivered_order(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        
        // Создаем заказ со статусом "delivered" напрямую через фабрику
        $order = Order::factory()->delivered()->forUser($user)->create();

        // Assert & Act
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('не может быть отменен');

        // Пытаемся отменить доставленный заказ
        $this->orderService->cancelOrder($order);
    }

    /**
     * Тест: Нельзя отменить отправленный заказ
     * 
     * Заказ, который уже отправлен, не может быть отменен.
     * 
     * @return void
     */
    public function test_cannot_cancel_shipped_order(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        
        // Создаем заказ со статусом "shipped"
        $order = Order::factory()->forUser($user)->create([
            'status' => 'shipped',
            'shipped_at' => now(),
        ]);

        // Assert & Act
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('не может быть отменен');

        $this->orderService->cancelOrder($order);
    }

    /**
     * Тест: Возврат товаров на склад при отмене заказа
     * 
     * При отмене заказа товары должны быть возвращены на склад
     * (остатки увеличиваются на количество из заказа).
     * 
     * @return void
     */
    public function test_returns_stock_when_order_cancelled(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);

        $product = $this->createProduct([
            'price' => 500.00,
            'stock' => 20, // Начальный остаток
        ]);

        $this->cartService->addItem($product->id, 5); // Заказываем 5 шт

        $orderData = [
            'name' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'phone' => '+7 (999) 123-45-67',
            'delivery_method' => 'courier',
            'payment_method' => 'cash',
        ];

        $order = $this->orderService->createOrder($orderData);
        
        // После создания заказа остаток: 20 - 5 = 15
        $product->refresh();
        $this->assertEquals(15, $product->stock);

        // Act (Действие)
        $this->orderService->cancelOrder($order, 'Передумал');

        // Assert (Проверка)
        // После отмены заказа остаток должен вернуться: 15 + 5 = 20
        $product->refresh();
        $this->assertEquals(20, $product->stock);
    }

    /**
     * Тест: Изменение статуса заказа на cancelled при отмене
     * 
     * После отмены статус заказа должен измениться на 'cancelled',
     * и должна быть установлена дата отмены.
     * 
     * @return void
     */
    public function test_updates_order_status_to_cancelled(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);

        $product = $this->createProduct(['price' => 500.00, 'stock' => 10, 'is_available' => true]);
        $this->cartService->addItem($product->id, 1);

        $orderData = [
            'name' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'phone' => '+7 (999) 123-45-67',
            'delivery_method' => 'courier',
            'payment_method' => 'cash',
        ];

        $order = $this->orderService->createOrder($orderData);

        // Act (Действие)
        $cancelledOrder = $this->orderService->cancelOrder($order, 'Нашел дешевле');

        // Assert (Проверка)
        $this->assertEquals('cancelled', $cancelledOrder->status);
        $this->assertNotNull($cancelledOrder->cancelled_at);
        $this->assertStringContainsString('Нашел дешевле', $cancelledOrder->admin_notes);
    }

    // ========================================================================
    // 2.6 ОПЛАТА ЗАКАЗА
    // ========================================================================

    /**
     * Тест: Отметка заказа как оплаченного
     * 
     * После подтверждения оплаты заказ должен быть отмечен как оплаченный.
     * 
     * @return void
     */
    public function test_marks_order_as_paid(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);

        $product = $this->createProduct(['price' => 500.00, 'stock' => 10, 'is_available' => true]);
        $this->cartService->addItem($product->id, 1);

        $orderData = [
            'name' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'phone' => '+7 (999) 123-45-67',
            'delivery_method' => 'courier',
            'payment_method' => 'online',
        ];

        $order = $this->orderService->createOrder($orderData);
        
        // Проверяем начальные статусы
        $this->assertEquals('pending', $order->status);
        $this->assertEquals('pending', $order->payment_status);
        $this->assertNull($order->paid_at);

        // Act (Действие)
        $paidOrder = $this->orderService->markAsPaid($order);

        // Assert (Проверка)
        $this->assertEquals('paid', $paidOrder->status);
        $this->assertEquals('paid', $paidOrder->payment_status);
        $this->assertNotNull($paidOrder->paid_at);
    }

    /**
     * Тест: Установка времени оплаты
     * 
     * При отметке заказа как оплаченного должна быть установлена
     * текущая дата и время оплаты (paid_at).
     * 
     * @return void
     */
    public function test_sets_paid_at_timestamp(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);

        $product = $this->createProduct(['price' => 500.00, 'stock' => 10, 'is_available' => true]);
        $this->cartService->addItem($product->id, 1);

        $orderData = [
            'name' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'phone' => '+7 (999) 123-45-67',
            'delivery_method' => 'courier',
            'payment_method' => 'online',
        ];

        $order = $this->orderService->createOrder($orderData);

        // Act (Действие)
        $paidOrder = $this->orderService->markAsPaid($order);

        // Assert (Проверка)
        $this->assertNotNull($paidOrder->paid_at);
        
        // Проверяем, что время оплаты было установлено только что (в течение последних 2 секунд)
        // Используем diffInSeconds() для проверки разницы во времени
        $this->assertTrue(
            $paidOrder->paid_at->diffInSeconds(now()) <= 2,
            'Время оплаты должно быть установлено в текущий момент (в пределах 2 секунд)'
        );
    }

    /**
     * Тест: Изменение статуса оплаты на paid
     * 
     * После оплаты payment_status должен измениться с 'pending' на 'paid'.
     * 
     * @return void
     */
    public function test_payment_status_changes_to_paid(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);

        $product = $this->createProduct(['price' => 500.00, 'stock' => 10, 'is_available' => true]);
        $this->cartService->addItem($product->id, 1);

        $orderData = [
            'name' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'phone' => '+7 (999) 123-45-67',
            'delivery_method' => 'courier',
            'payment_method' => 'online',
        ];

        $order = $this->orderService->createOrder($orderData);

        // Act (Действие)
        $paidOrder = $this->orderService->markAsPaid($order);

        // Assert (Проверка)
        $this->assertDatabaseHas('orders', [
            'id' => $paidOrder->id,
            'payment_status' => 'paid',
            'status' => 'paid',
        ]);
    }

    // ========================================================================
    // ДОПОЛНИТЕЛЬНЫЕ ТЕСТЫ
    // ========================================================================

    /**
     * Тест: Метод calculateOrderTotal возвращает правильные данные
     * 
     * Этот вспомогательный метод используется для предпросмотра стоимости
     * заказа до его создания (например, на странице checkout).
     * 
     * @return void
     */
    public function test_calculate_order_total_returns_correct_data(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);

        $product = $this->createProduct(['price' => 3500.00, 'stock' => 10, 'is_available' => true]);
        $this->cartService->addItem($product->id, 1);

        $cartItems = $this->cartService->getCartItems();

        // Act (Действие)
        $totals = $this->orderService->calculateOrderTotal(
            $cartItems,
            'courier',
            $user
        );

        // Assert (Проверка)
        $this->assertIsArray($totals);
        $this->assertArrayHasKey('subtotal', $totals);
        $this->assertArrayHasKey('delivery_cost', $totals);
        $this->assertArrayHasKey('discount', $totals);
        $this->assertArrayHasKey('total', $totals);

        // Проверяем значения
        $this->assertEquals(3500.00, $totals['subtotal']);
        $this->assertEquals(0.00, $totals['delivery_cost']); // Бесплатно, т.к. >= 2000
        $this->assertEquals(175.00, $totals['discount']); // 5% от 3500
        $this->assertEquals(3325.00, $totals['total']); // 3500 + 0 - 175
    }

    /**
     * Тест: Гостевой заказ (без авторизации)
     * 
     * Система должна позволять создавать заказы для неавторизованных пользователей.
     * В этом случае user_id будет null, но все остальные данные должны сохраниться.
     * 
     * @return void
     */
    public function test_guest_user_can_create_order(): void
    {
        // Arrange (Подготовка)
        // НЕ авторизуем пользователя, создаем гостевую корзину
        $sessionId = 'test_guest_session_' . time();
        
        $product = $this->createProduct(['price' => 500.00, 'stock' => 10, 'is_available' => true]);
        
        // Создаем гостевую корзину напрямую
        CartItem::create([
            'session_id' => $sessionId,
            'user_id' => null,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => $product->price,
        ]);

        // Имитируем сессию для CartService
        session(['cart_session_id' => $sessionId]);

        $orderData = [
            'name' => 'Гость Гостев',
            'email' => 'guest@example.com',
            'phone' => '+7 (999) 111-22-33',
            'delivery_method' => 'courier',
            'payment_method' => 'cash',
        ];

        // Act (Действие)
        $order = $this->orderService->createOrder($orderData);

        // Assert (Проверка)
        $this->assertNull($order->user_id); // Для гостя user_id = null
        $this->assertEquals('Гость Гостев', $order->customer_name);
        $this->assertEquals('guest@example.com', $order->customer_email);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => null,
        ]);
    }

    /**
     * Тест: Можно отменить оплаченный заказ (статус paid)
     * 
     * Даже если заказ оплачен, но еще не отправлен, его можно отменить
     * (например, для возврата денег клиенту).
     * 
     * @return void
     */
    public function test_can_cancel_paid_order(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        
        // Создаем оплаченный заказ через фабрику
        $order = Order::factory()->paid()->forUser($user)->create();
        
        // Создаем позицию заказа с товаром
        $product = $this->createProduct(['price' => 500.00, 'stock' => 10, 'is_available' => true]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 3,
            'price' => $product->price,
            'total' => $product->price * 3,
        ]);

        // Уменьшаем остаток (имитируем, что заказ был создан)
        $product->stock -= 3;
        $product->save();

        $this->assertEquals(7, $product->stock); // 10 - 3 = 7

        // Act (Действие)
        $cancelledOrder = $this->orderService->cancelOrder($order, 'Возврат средств');

        // Assert (Проверка)
        $this->assertEquals('cancelled', $cancelledOrder->status);
        
        // Товары вернулись на склад
        $product->refresh();
        $this->assertEquals(10, $product->stock); // 7 + 3 = 10
    }

    /**
     * Тест: Округление сумм до 2 знаков после запятой
     * 
     * Все денежные суммы должны быть округлены до 2 знаков после запятой.
     * 
     * @return void
     */
    public function test_rounds_amounts_to_two_decimals(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);

        // Создаем товары с ценами, которые при умножении дают много знаков
        $product1 = $this->createProduct(['price' => 333.33, 'stock' => 10, 'is_available' => true]);
        $product2 = $this->createProduct(['price' => 999.99, 'stock' => 10, 'is_available' => true]);

        $this->cartService->addItem($product1->id, 3); // 333.33 * 3 = 999.99
        $this->cartService->addItem($product2->id, 2); // 999.99 * 2 = 1999.98

        $orderData = [
            'name' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'phone' => '+7 (999) 123-45-67',
            'delivery_method' => 'courier',
            'payment_method' => 'cash',
        ];

        // Act (Действие)
        $order = $this->orderService->createOrder($orderData);

        // Assert (Проверка)
        // Проверяем, что все суммы имеют ровно 2 знака после запятой
        $this->assertMatchesRegularExpression('/^\d+\.\d{2}$/', (string) $order->subtotal);
        $this->assertMatchesRegularExpression('/^\d+\.\d{2}$/', (string) $order->delivery_cost);
        $this->assertMatchesRegularExpression('/^\d+\.\d{2}$/', (string) $order->discount);
        $this->assertMatchesRegularExpression('/^\d+\.\d{2}$/', (string) $order->total);
    }
}
