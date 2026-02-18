<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Address;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Tests\TestCase;

/**
 * Тест для проверки работы всех фабрик моделей
 * 
 * Этот тест проверяет, что все фабрики могут создавать модели
 * без ошибок и с корректными данными.
 */
class FactoriesTest extends TestCase
{
    /**
     * Проверка создания пользователя через фабрику
     */
    public function test_can_create_user_with_factory(): void
    {
        // Создаем пользователя через фабрику
        $user = User::factory()->create();

        // Проверяем, что пользователь создан
        $this->assertInstanceOf(User::class, $user);
        $this->assertNotNull($user->id);
        $this->assertNotNull($user->first_name);
        $this->assertNotNull($user->last_name);
        $this->assertNotNull($user->email);
        $this->assertFalse($user->is_admin); // По умолчанию не админ
    }

    /**
     * Проверка создания категории через фабрику
     */
    public function test_can_create_category_with_factory(): void
    {
        $category = Category::factory()->create();

        $this->assertInstanceOf(Category::class, $category);
        $this->assertNotNull($category->id);
        $this->assertNotNull($category->name);
        $this->assertNotNull($category->slug);
    }

    /**
     * Проверка создания товара через фабрику
     */
    public function test_can_create_product_with_factory(): void
    {
        $product = Product::factory()->create();

        $this->assertInstanceOf(Product::class, $product);
        $this->assertNotNull($product->id);
        $this->assertNotNull($product->name);
        $this->assertNotNull($product->slug);
        $this->assertNotNull($product->price);
        $this->assertNotNull($product->sku);
        $this->assertGreaterThanOrEqual(0, $product->stock);
    }

    /**
     * Проверка создания отзыва через фабрику
     */
    public function test_can_create_review_with_factory(): void
    {
        $review = Review::factory()->create();

        $this->assertInstanceOf(Review::class, $review);
        $this->assertNotNull($review->id);
        $this->assertNotNull($review->product_id);
        $this->assertNotNull($review->user_id);
        $this->assertNotNull($review->rating);
        $this->assertGreaterThanOrEqual(1, $review->rating);
        $this->assertLessThanOrEqual(5, $review->rating);
    }

    /**
     * Проверка создания заказа через фабрику
     */
    public function test_can_create_order_with_factory(): void
    {
        $order = Order::factory()->create();

        $this->assertInstanceOf(Order::class, $order);
        $this->assertNotNull($order->id);
        $this->assertNotNull($order->order_number);
        $this->assertNotNull($order->customer_name);
        $this->assertNotNull($order->customer_email);
        $this->assertNotNull($order->customer_phone);
        $this->assertNotNull($order->delivery_method);
        $this->assertNotNull($order->payment_method);
        $this->assertNotNull($order->status);
        $this->assertGreaterThan(0, $order->total);
    }

    /**
     * Проверка создания позиции заказа через фабрику
     */
    public function test_can_create_order_item_with_factory(): void
    {
        $orderItem = OrderItem::factory()->create();

        $this->assertInstanceOf(OrderItem::class, $orderItem);
        $this->assertNotNull($orderItem->id);
        $this->assertNotNull($orderItem->order_id);
        $this->assertNotNull($orderItem->product_id);
        $this->assertNotNull($orderItem->product_name);
        $this->assertGreaterThan(0, $orderItem->quantity);
        $this->assertGreaterThan(0, $orderItem->price);
        $this->assertGreaterThan(0, $orderItem->total);
    }

    /**
     * Проверка создания позиции корзины через фабрику
     */
    public function test_can_create_cart_item_with_factory(): void
    {
        $cartItem = CartItem::factory()->create();

        $this->assertInstanceOf(CartItem::class, $cartItem);
        $this->assertNotNull($cartItem->id);
        $this->assertNotNull($cartItem->product_id);
        $this->assertGreaterThan(0, $cartItem->quantity);
        $this->assertGreaterThan(0, $cartItem->price);
        
        // Должен быть либо user_id, либо session_id
        $this->assertTrue(
            !is_null($cartItem->user_id) || !is_null($cartItem->session_id)
        );
    }

    /**
     * Проверка создания адреса через фабрику
     */
    public function test_can_create_address_with_factory(): void
    {
        $address = Address::factory()->create();

        $this->assertInstanceOf(Address::class, $address);
        $this->assertNotNull($address->id);
        $this->assertNotNull($address->user_id);
        $this->assertNotNull($address->name);
        $this->assertNotNull($address->city);
        $this->assertNotNull($address->street);
        $this->assertNotNull($address->house);
        $this->assertNotNull($address->phone);
        $this->assertNotNull($address->full_address);
    }

    /**
     * Проверка создания заказа с различными состояниями
     */
    public function test_can_create_order_with_different_states(): void
    {
        // Pending заказ
        $pendingOrder = Order::factory()->pending()->create();
        $this->assertEquals('pending', $pendingOrder->status);
        $this->assertEquals('pending', $pendingOrder->payment_status);

        // Оплаченный заказ
        $paidOrder = Order::factory()->paid()->create();
        $this->assertEquals('paid', $paidOrder->status);
        $this->assertEquals('paid', $paidOrder->payment_status);
        $this->assertNotNull($paidOrder->paid_at);

        // Доставленный заказ
        $deliveredOrder = Order::factory()->delivered()->create();
        $this->assertEquals('delivered', $deliveredOrder->status);
        $this->assertNotNull($deliveredOrder->delivered_at);

        // Отмененный заказ
        $cancelledOrder = Order::factory()->cancelled()->create();
        $this->assertEquals('cancelled', $cancelledOrder->status);
        $this->assertNotNull($cancelledOrder->cancelled_at);
    }

    /**
     * Проверка создания корзины для пользователя и гостя
     */
    public function test_can_create_cart_for_user_and_guest(): void
    {
        $user = User::factory()->create();

        // Корзина пользователя
        $userCart = CartItem::factory()->forUser($user)->create();
        $this->assertEquals($user->id, $userCart->user_id);
        $this->assertNull($userCart->session_id);

        // Гостевая корзина
        $guestCart = CartItem::factory()->guest('test-session-id')->create();
        $this->assertNull($guestCart->user_id);
        $this->assertEquals('test-session-id', $guestCart->session_id);
    }

    /**
     * Проверка создания адресов разных типов
     */
    public function test_can_create_different_address_types(): void
    {
        // Домашний адрес
        $homeAddress = Address::factory()->home()->create();
        $this->assertEquals('Дом', $homeAddress->name);

        // Рабочий адрес
        $workAddress = Address::factory()->work()->create();
        $this->assertEquals('Работа', $workAddress->name);

        // Адрес по умолчанию
        $defaultAddress = Address::factory()->default()->create();
        $this->assertTrue($defaultAddress->is_default);

        // Адрес в Калининграде
        $kgdAddress = Address::factory()->kaliningrad()->create();
        $this->assertEquals('Калининград', $kgdAddress->city);
        $this->assertStringStartsWith('2360', $kgdAddress->postal_code);
    }

    /**
     * Проверка создания товаров разных типов
     */
    public function test_can_create_different_product_types(): void
    {
        // Кофе
        $coffee = Product::factory()->coffee()->create();
        $this->assertStringStartsWith('CF-', $coffee->sku);
        $this->assertGreaterThan(0, $coffee->bitterness_percent);

        // Чай
        $tea = Product::factory()->tea()->create();
        $this->assertStringStartsWith('TE-', $tea->sku);
        $this->assertEquals(0, $tea->bitterness_percent);

        // Товар со скидкой
        $discounted = Product::factory()->discounted()->create();
        $this->assertNotNull($discounted->old_price);
        $this->assertGreaterThan($discounted->price, $discounted->old_price);

        // Товар не в наличии
        $outOfStock = Product::factory()->outOfStock()->create();
        $this->assertEquals(0, $outOfStock->stock);
        $this->assertFalse($outOfStock->is_available);
    }

    /**
     * Проверка создания позиций заказа с различными состояниями
     */
    public function test_can_create_order_items_with_different_states(): void
    {
        // Одна единица товара
        $single = OrderItem::factory()->single()->create();
        $this->assertEquals(1, $single->quantity);
        $this->assertEquals($single->price, $single->total);

        // Большое количество
        $bulk = OrderItem::factory()->bulk()->create();
        $this->assertGreaterThanOrEqual(5, $bulk->quantity);

        // Кофе
        $coffee = OrderItem::factory()->coffee()->create();
        $this->assertStringContainsString('Кофе', $coffee->product_name);

        // Чай
        $tea = OrderItem::factory()->tea()->create();
        $this->assertStringContainsString('Чай', $tea->product_name);
    }

    /**
     * Проверка создания связанных моделей
     */
    public function test_can_create_related_models(): void
    {
        // Создаем пользователя с заказами (множественное число!)
        $user = User::factory()
            ->has(Order::factory()->count(2), 'orders')
            ->create();

        $this->assertCount(2, $user->orders);

        // Создаем товар с отзывами
        $product = Product::factory()
            ->has(Review::factory()->count(3), 'reviews')
            ->create();

        $this->assertCount(3, $product->reviews);

        // Создаем заказ с позициями
        $order = Order::factory()
            ->has(OrderItem::factory()->count(5), 'items')
            ->create();

        $this->assertCount(5, $order->items);
    }

    /**
     * Проверка создания заказа для конкретного пользователя
     */
    public function test_can_create_order_for_specific_user(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->forUser($user)->create();

        $this->assertEquals($user->id, $order->user_id);
        $this->assertEquals($user->email, $order->user->email);
    }

    /**
     * Проверка создания позиции заказа для конкретного товара
     */
    public function test_can_create_order_item_for_specific_product(): void
    {
        $product = Product::factory()->create(['price' => 500.00]);
        $orderItem = OrderItem::factory()->forProduct($product)->create();

        $this->assertEquals($product->id, $orderItem->product_id);
        $this->assertEquals($product->name, $orderItem->product_name);
        $this->assertEquals(500.00, $orderItem->price);
    }

    /**
     * Проверка расчета стоимости доставки в заказах
     */
    public function test_order_delivery_cost_calculation(): void
    {
        // Самовывоз - бесплатно
        $pickup = Order::factory()->pickup()->create();
        $this->assertEquals('pickup', $pickup->delivery_method);
        $this->assertEquals(0.00, $pickup->delivery_cost);

        // Курьер с маленькой суммой - 300 руб
        $courierSmall = Order::factory()->state([
            'delivery_method' => 'courier',
            'subtotal' => 1500.00,
            'discount' => 0.00,
            'delivery_cost' => 300.00,
            'total' => 1800.00,
        ])->create();
        $this->assertEquals('courier', $courierSmall->delivery_method);
        $this->assertEquals(1500.00, (float) $courierSmall->subtotal);
        $this->assertEquals(300.00, (float) $courierSmall->delivery_cost);

        // Курьер с большой суммой - бесплатно
        $courierLarge = Order::factory()->state([
            'delivery_method' => 'courier',
            'subtotal' => 2500.00,
            'discount' => 125.00, // 5% скидка
            'delivery_cost' => 0.00,
            'total' => 2375.00,
        ])->create();
        $this->assertEquals('courier', $courierLarge->delivery_method);
        $this->assertEquals(2500.00, (float) $courierLarge->subtotal);
        $this->assertEquals(0.00, (float) $courierLarge->delivery_cost);

        // Почта - 400 руб
        $post = Order::factory()->post()->create();
        $this->assertEquals('post', $post->delivery_method);
        $this->assertEquals(400.00, (float) $post->delivery_cost);
    }

    /**
     * Проверка массового создания моделей
     */
    public function test_can_create_multiple_models(): void
    {
        // Создаем 10 пользователей
        $users = User::factory()->count(10)->create();
        $this->assertCount(10, $users);

        // Создаем 5 товаров
        $products = Product::factory()->count(5)->create();
        $this->assertCount(5, $products);

        // Создаем 3 заказа
        $orders = Order::factory()->count(3)->create();
        $this->assertCount(3, $orders);
    }
}
