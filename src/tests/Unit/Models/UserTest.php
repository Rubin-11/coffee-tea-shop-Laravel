<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Address;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Unit тесты для модели User
 *
 * Тестируем:
 * - Роли пользователя (isAdmin)
 * - Связи с заказами, отзывами, адресами, корзиной
 * - Получение адреса по умолчанию
 * - Атрибут full_name
 */
#[Group('unit')]
#[Group('models')]
#[Group('user')]
final class UserTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // ТЕСТЫ: Роли пользователя
    // ==========================================

    /**
     * Тест: isAdmin() возвращает true для администратора
     *
     * Проверяем, что пользователь с is_admin = true определяется как администратор.
     */
    public function test_is_admin_returns_true_for_admin(): void
    {
        // Arrange: Создаем администратора
        $admin = $this->createUser(['is_admin' => true]);

        // Act: Проверяем статус администратора
        $isAdmin = $admin->isAdmin();

        // Assert: Пользователь является администратором
        $this->assertTrue($isAdmin);
        $this->assertTrue($admin->is_admin);
    }

    /**
     * Тест: isAdmin() возвращает false для обычного пользователя
     *
     * Проверяем, что обычный покупатель не определяется как администратор.
     */
    public function test_is_admin_returns_false_for_regular_user(): void
    {
        // Arrange: Создаем обычного пользователя
        $user = $this->createUser(['is_admin' => false]);

        // Act: Проверяем статус администратора
        $isAdmin = $user->isAdmin();

        // Assert: Пользователь не является администратором
        $this->assertFalse($isAdmin);
        $this->assertFalse($user->is_admin);
    }

    // ==========================================
    // ТЕСТЫ: Связи с заказами
    // ==========================================

    /**
     * Тест: Пользователь имеет связь с заказами
     *
     * Проверяем, что User::orders() возвращает заказы пользователя.
     */
    public function test_has_orders(): void
    {
        // Arrange: Создаем пользователя и его заказы
        $user = $this->createUser();
        Order::factory()->count(3)->create(['user_id' => $user->id]);

        // Act: Загружаем связь заказов
        $user->load('orders');

        // Assert: У пользователя 3 заказа
        $this->assertCount(3, $user->orders);
        foreach ($user->orders as $order) {
            $this->assertEquals($user->id, $order->user_id);
        }
    }

    /**
     * Тест: Правильный подсчет заказов пользователя
     */
    public function test_counts_orders_correctly(): void
    {
        $user = $this->createUser();
        Order::factory()->count(5)->create(['user_id' => $user->id]);

        $count = $user->orders()->count();

        $this->assertEquals(5, $count);
    }

    // ==========================================
    // ТЕСТЫ: Связи с отзывами
    // ==========================================

    /**
     * Тест: Пользователь имеет отзывы
     *
     * Один пользователь может оставить только один отзыв на товар (unique constraint),
     * поэтому создаем отзывы на разные товары.
     */
    public function test_has_reviews(): void
    {
        $user = $this->createUser();
        $product1 = $this->createProduct();
        $product2 = $this->createProduct();

        Review::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product1->id,
        ]);
        Review::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product2->id,
        ]);

        $user->load('reviews');

        $this->assertCount(2, $user->reviews);
        foreach ($user->reviews as $review) {
            $this->assertEquals($user->id, $review->user_id);
        }
    }

    /**
     * Тест: Правильный подсчет отзывов пользователя
     *
     * Создаем отзывы на разные товары (unique: product_id + user_id).
     */
    public function test_counts_reviews_correctly(): void
    {
        $user = $this->createUser();
        $products = $this->createProducts(4);

        foreach ($products as $product) {
            Review::factory()->create([
                'user_id' => $user->id,
                'product_id' => $product->id,
            ]);
        }

        $count = $user->reviews()->count();

        $this->assertEquals(4, $count);
    }

    // ==========================================
    // ТЕСТЫ: Связи с адресами
    // ==========================================

    /**
     * Тест: Пользователь имеет адреса доставки
     */
    public function test_has_addresses(): void
    {
        $user = $this->createUser();
        Address::factory()->count(3)->create(['user_id' => $user->id]);

        $user->load('addresses');

        $this->assertCount(3, $user->addresses);
        foreach ($user->addresses as $address) {
            $this->assertEquals($user->id, $address->user_id);
        }
    }

    /**
     * Тест: Получение адреса по умолчанию
     *
     * Проверяем метод getDefaultAddress(), который возвращает адрес с is_default = true.
     */
    public function test_gets_default_address(): void
    {
        // Arrange: Создаем пользователя с адресами
        $user = $this->createUser();
        Address::factory()->create([
            'user_id' => $user->id,
            'is_default' => false,
        ]);
        $defaultAddress = Address::factory()->default()->create([
            'user_id' => $user->id,
        ]);

        // Act: Получаем адрес по умолчанию
        $result = $user->getDefaultAddress();

        // Assert: Возвращается адрес с is_default = true
        $this->assertNotNull($result);
        $this->assertEquals($defaultAddress->id, $result->id);
        $this->assertTrue($result->is_default);
        $this->assertInstanceOf(Address::class, $result);
    }

    /**
     * Тест: getDefaultAddress() возвращает null при отсутствии основного адреса
     */
    public function test_gets_null_when_no_default_address(): void
    {
        $user = $this->createUser();
        Address::factory()->count(2)->create([
            'user_id' => $user->id,
            'is_default' => false,
        ]);

        $result = $user->getDefaultAddress();

        $this->assertNull($result);
    }

    // ==========================================
    // ТЕСТЫ: Связи с корзиной
    // ==========================================

    /**
     * Тест: Пользователь имеет позиции корзины
     */
    public function test_has_cart_items(): void
    {
        $user = $this->createUser();
        $product = $this->createProduct();

        CartItem::factory()->forUser($user)->forProduct($product)->count(2)->create();

        $user->load('cartItems');

        $this->assertCount(2, $user->cartItems);
        foreach ($user->cartItems as $cartItem) {
            $this->assertEquals($user->id, $cartItem->user_id);
        }
    }

    /**
     * Тест: Правильный подсчет позиций корзины
     */
    public function test_counts_cart_items_correctly(): void
    {
        $user = $this->createUser();
        $product = $this->createProduct();

        CartItem::factory()->forUser($user)->forProduct($product)->count(3)->create();

        $count = $user->cartItems()->count();

        $this->assertEquals(3, $count);
    }

    // ==========================================
    // ТЕСТЫ: Атрибуты
    // ==========================================

    /**
     * Тест: Атрибут full_name возвращает имя и фамилию
     */
    public function test_full_name_attribute_returns_first_and_last_name(): void
    {
        $user = $this->createUser([
            'first_name' => 'Иван',
            'last_name' => 'Петров',
        ]);

        $this->assertEquals('Иван Петров', $user->full_name);
    }
}
