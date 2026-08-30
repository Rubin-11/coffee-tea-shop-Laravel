<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты оформления заказа (checkout)
 *
 * Проверяют «сайт как пользователь»:
 * - доступ к форме (пустая корзина → редирект)
 * - создание заказа авторизованным пользователем (user_id привязывается)
 * - создание заказа гостем (user_id = null)
 * - валидацию обязательных полей
 * - страницу успеха
 */
class CheckoutFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Положить товар в корзину текущего клиента (через сервис, как в реальном flow)
     */
    private function addToCart(int $productId, int $quantity = 1): void
    {
        app(CartService::class)->addItem($productId, $quantity);
    }

    /**
     * Валидные данные формы оформления
     *
     * @return array<string, mixed>
     */
    private function validCheckoutData(): array
    {
        return [
            'name' => 'Иван Петров',
            'email' => 'ivan@example.com',
            'phone' => '79991234567',
            'new_address' => [
                'city' => 'Калининград',
                'street' => 'Ленина',
                'house' => '1',
                'postal_code' => '236000',
            ],
            'delivery_method' => 'courier',
            'payment_method' => 'cash',
        ];
    }

    // ==================== ДОСТУП К ФОРМЕ ====================

    /**
     * Пустая корзина → редирект на корзину (middleware cart.not.empty)
     */
    public function test_checkout_redirects_to_cart_when_empty(): void
    {
        $this->get(route('checkout.index'))
            ->assertRedirect(route('cart.index'));
    }

    /**
     * С товаром в корзине форма открывается
     */
    public function test_checkout_form_opens_with_cart_items(): void
    {
        $product = $this->createProduct();
        $this->addToCart($product->id);

        $this->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('Оформление заказа');
    }

    // ==================== СОЗДАНИЕ ЗАКАЗА ====================

    /**
     * Авторизованный пользователь оформляет заказ — user_id привязывается
     */
    public function test_authenticated_user_checkout_creates_order_with_user_id(): void
    {
        $user = $this->createUser();
        $product = $this->createProduct(['price' => 500]);

        $this->actingAs($user);
        $this->addToCart($product->id, 2);

        $response = $this->post(route('checkout.store'), $this->validCheckoutData());

        // Заказ создан и привязан к пользователю
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'customer_email' => 'ivan@example.com',
            'status' => 'pending',
        ]);

        $order = Order::where('user_id', $user->id)->first();
        $this->assertNotNull($order);

        // Редирект на страницу успеха (оплата наличными — без payment_url)
        $response->assertRedirect(route('checkout.success', ['order' => $order->id]));

        // В корзине после оформления пусто
        $this->assertTrue(app(CartService::class)->isEmpty());
    }

    /**
     * Гость оформляет заказ — user_id = null (гостевой заказ)
     */
    public function test_guest_checkout_creates_order_without_user(): void
    {
        $product = $this->createProduct(['price' => 500]);
        $this->addToCart($product->id, 1);

        $this->post(route('checkout.store'), $this->validCheckoutData());

        $this->assertDatabaseHas('orders', [
            'user_id' => null,
            'customer_email' => 'ivan@example.com',
        ]);
    }

    /**
     * Заказ содержит позиции с товарами и правильной суммой
     */
    public function test_checkout_creates_order_items_with_correct_totals(): void
    {
        $product = $this->createProduct(['price' => 500]);
        $this->addToCart($product->id, 2);

        $this->post(route('checkout.store'), $this->validCheckoutData());

        $order = Order::where('customer_email', 'ivan@example.com')->first();
        $this->assertNotNull($order);

        // Позиция: товар × 2 = 1000 ₽
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 500.00,
            'total' => 1000.00,
        ]);

        // Сумма товаров 1000, доставка курьером бесплатна от 2000? Нет — проверяем базу
        $this->assertSame('1000.00', (string) $order->subtotal);
    }

    // ==================== ВАЛИДАЦИЯ ====================

    /**
     * Пустая форма — ошибки по всем обязательным полям
     */
    public function test_checkout_requires_required_fields(): void
    {
        $product = $this->createProduct();
        $this->addToCart($product->id);

        $this->post(route('checkout.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'phone', 'delivery_method', 'payment_method']);
    }

    /**
     * Неверный способ оплаты отклоняется
     */
    public function test_checkout_rejects_invalid_payment_method(): void
    {
        $product = $this->createProduct();
        $this->addToCart($product->id);

        $data = $this->validCheckoutData();
        $data['payment_method'] = 'bitcoin';

        $this->post(route('checkout.store'), $data)
            ->assertSessionHasErrors('payment_method');

        $this->assertDatabaseCount('orders', 0);
    }

    // ==================== СТРАНИЦА УСПЕХА ====================

    /**
     * Страница успеха открывается после оформления
     */
    public function test_success_page_opens(): void
    {
        $product = $this->createProduct();
        $this->addToCart($product->id);
        $this->post(route('checkout.store'), $this->validCheckoutData());

        $order = Order::where('customer_email', 'ivan@example.com')->first();
        $this->assertNotNull($order);

        $this->get(route('checkout.success', ['order' => $order->id]))
            ->assertOk()
            ->assertSee('Заказ оформлен');
    }
}
