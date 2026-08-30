<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты личного кабинета и заказов
 *
 * Главное здесь — права доступа: пользователь видит только свои заказы,
 * чужой заказ недоступен (403). Плюс бизнес-операции: отмена и повтор.
 */
class OrderFeatureTest extends TestCase
{
    use RefreshDatabase;

    // ==================== ПРОФИЛЬ ====================

    /**
     * Профиль показывает данные пользователя и его заказы
     */
    public function test_profile_shows_user_data_and_recent_orders(): void
    {
        $user = $this->createUser();
        $order = Order::factory()->forUser($user)->create();

        $this->actingAs($user)
            ->get(route('profile.index'))
            ->assertOk()
            ->assertSee($user->full_name)
            ->assertSee($order->order_number);
    }

    /**
     * Профиль не показывает заказы других пользователей
     */
    public function test_profile_does_not_show_other_users_orders(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();
        $otherOrder = Order::factory()->forUser($otherUser)->create(['order_number' => 'ORD-2026-99999']);

        $this->actingAs($user)
            ->get(route('profile.index'))
            ->assertOk()
            ->assertDontSee($otherOrder->order_number);
    }

    // ==================== СПИСОК ЗАКАЗОВ ====================

    /**
     * В списке заказов видны только свои
     */
    public function test_orders_index_shows_only_own_orders(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();

        $ownOrder = Order::factory()->forUser($user)->create(['order_number' => 'ORD-2026-00001']);
        $otherOrder = Order::factory()->forUser($otherUser)->create(['order_number' => 'ORD-2026-00002']);

        $this->actingAs($user)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee($ownOrder->order_number)
            ->assertDontSee($otherOrder->order_number);
    }

    /**
     * Фильтр по статусу работает
     */
    public function test_orders_index_filters_by_status(): void
    {
        $user = $this->createUser();
        Order::factory()->forUser($user)->pending()->create(['order_number' => 'ORD-2026-00010']);
        Order::factory()->forUser($user)->delivered()->create(['order_number' => 'ORD-2026-00011']);

        $this->actingAs($user)
            ->get(route('orders.index', ['status' => 'delivered']))
            ->assertOk()
            ->assertSee('ORD-2026-00011')
            ->assertDontSee('ORD-2026-00010');
    }

    // ==================== ДЕТАЛИ ЗАКАЗА ====================

    /**
     * Свой заказ открывается
     */
    public function test_user_can_view_own_order(): void
    {
        $user = $this->createUser();
        $order = Order::factory()->forUser($user)->create();

        $this->actingAs($user)
            ->get(route('orders.show', $order->id))
            ->assertOk()
            ->assertSee($order->order_number);
    }

    /**
     * Чужой заказ недоступен (403)
     */
    public function test_user_cannot_view_other_users_order(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();
        $otherOrder = Order::factory()->forUser($otherUser)->create();

        $this->actingAs($user)
            ->get(route('orders.show', $otherOrder->id))
            ->assertForbidden();
    }

    /**
     * Гость без входа не видит детали заказа (редирект на логин)
     */
    public function test_guest_cannot_view_order_details(): void
    {
        $user = $this->createUser();
        $order = Order::factory()->forUser($user)->create();

        $this->get(route('orders.show', $order->id))
            ->assertRedirect(route('auth.login'));
    }

    // ==================== ОТМЕНА ЗАКАЗА ====================

    /**
     * Заказ в статусе pending можно отменить
     */
    public function test_user_can_cancel_pending_order(): void
    {
        $user = $this->createUser();
        $order = Order::factory()->forUser($user)->pending()->create();

        $this->actingAs($user)
            ->post(route('orders.cancel', $order->id))
            ->assertRedirect(route('orders.show', $order->id))
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertTrue($order->isCancelled());
    }

    /**
     * Отменить доставленный заказ нельзя
     */
    public function test_user_cannot_cancel_delivered_order(): void
    {
        $user = $this->createUser();
        $order = Order::factory()->forUser($user)->delivered()->create();

        $this->actingAs($user)
            ->post(route('orders.cancel', $order->id))
            ->assertRedirect(route('orders.show', $order->id))
            ->assertSessionHas('error');

        $order->refresh();
        $this->assertFalse($order->isCancelled());
    }

    /**
     * Чужой заказ отменить нельзя (403)
     */
    public function test_user_cannot_cancel_other_users_order(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();
        $otherOrder = Order::factory()->forUser($otherUser)->pending()->create();

        $this->actingAs($user)
            ->post(route('orders.cancel', $otherOrder->id))
            ->assertForbidden();

        $otherOrder->refresh();
        $this->assertFalse($otherOrder->isCancelled());
    }

    // ==================== ПОВТОР ЗАКАЗА ====================

    /**
     * Повтор заказа добавляет товары в корзину
     */
    public function test_user_can_reorder_order(): void
    {
        $user = $this->createUser();
        $product = $this->createProduct(['stock' => 10]);
        $order = Order::factory()->forUser($user)->create();

        OrderItem::factory()->forOrder($order)->create([
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->actingAs($user)
            ->post(route('orders.reorder', $order->id))
            ->assertRedirect(route('cart.index'));

        // В корзине появился товар из заказа
        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    /**
     * Повтор чужого заказа запрещён (403)
     */
    public function test_user_cannot_reorder_other_users_order(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();
        $otherOrder = Order::factory()->forUser($otherUser)->create();

        $this->actingAs($user)
            ->post(route('orders.reorder', $otherOrder->id))
            ->assertForbidden();

        $this->assertDatabaseCount('cart_items', 0);
    }
}
