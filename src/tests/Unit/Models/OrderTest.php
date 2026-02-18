<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Unit тесты для модели Order
 *
 * Тестируем:
 * - Проверку возможности отмены заказа
 * - Проверку статусов заказа
 * - Расчет итоговой суммы заказа
 * - Query scopes для фильтрации заказов
 */
#[Group('unit')]
#[Group('models')]
#[Group('order')]
final class OrderTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // ТЕСТЫ: Проверка возможности отмены заказа
    // ==========================================

    /**
     * Тест: Заказ со статусом "pending" можно отменить
     * 
     * Проверяем, что заказ в статусе "ожидает обработки" (pending)
     * можно отменить, так как он еще не был отправлен.
     */
    public function test_can_cancel_pending_order(): void
    {
        // Arrange: Создаем заказ со статусом pending
        $order = Order::factory()->pending()->create();

        // Act: Проверяем возможность отмены
        $canBeCancelled = $order->canBeCancelled();

        // Assert: Заказ можно отменить
        $this->assertTrue($canBeCancelled, 'Заказ со статусом pending должен быть доступен для отмены');
    }

    /**
     * Тест: Заказ со статусом "paid" можно отменить
     * 
     * Проверяем, что оплаченный заказ (paid), который еще не отправлен,
     * можно отменить. В этом случае потребуется возврат средств.
     */
    public function test_can_cancel_paid_order(): void
    {
        // Arrange: Создаем оплаченный заказ
        $order = Order::factory()->paid()->create();

        // Act: Проверяем возможность отмены
        $canBeCancelled = $order->canBeCancelled();

        // Assert: Оплаченный заказ можно отменить
        $this->assertTrue($canBeCancelled, 'Оплаченный заказ должен быть доступен для отмены');
    }

    /**
     * Тест: Заказ со статусом "processing" можно отменить
     * 
     * Проверяем, что заказ в обработке (processing) можно отменить,
     * пока он не был отправлен покупателю.
     */
    public function test_can_cancel_processing_order(): void
    {
        // Arrange: Создаем заказ в обработке
        $order = Order::factory()->create([
            'status' => 'processing',
            'payment_status' => 'paid',
            'paid_at' => now()->subHour(),
        ]);

        // Act: Проверяем возможность отмены
        $canBeCancelled = $order->canBeCancelled();

        // Assert: Заказ в обработке можно отменить
        $this->assertTrue($canBeCancelled, 'Заказ со статусом processing должен быть доступен для отмены');
    }

    /**
     * Тест: Заказ со статусом "shipped" нельзя отменить
     * 
     * Проверяем, что заказ, который уже отправлен (shipped),
     * нельзя отменить, так как он находится в пути к покупателю.
     */
    public function test_cannot_cancel_shipped_order(): void
    {
        // Arrange: Создаем отправленный заказ
        $order = Order::factory()->create([
            'status' => 'shipped',
            'payment_status' => 'paid',
            'paid_at' => now()->subDay(),
            'shipped_at' => now()->subHours(12),
        ]);

        // Act: Проверяем возможность отмены
        $canBeCancelled = $order->canBeCancelled();

        // Assert: Отправленный заказ нельзя отменить
        $this->assertFalse($canBeCancelled, 'Отправленный заказ не должен быть доступен для отмены');
    }

    /**
     * Тест: Заказ со статусом "delivered" нельзя отменить
     * 
     * Проверяем, что доставленный заказ (delivered) нельзя отменить.
     * Для возврата товара нужно использовать другой механизм (возврат/обмен).
     */
    public function test_cannot_cancel_delivered_order(): void
    {
        // Arrange: Создаем доставленный заказ
        $order = Order::factory()->delivered()->create();

        // Act: Проверяем возможность отмены
        $canBeCancelled = $order->canBeCancelled();

        // Assert: Доставленный заказ нельзя отменить
        $this->assertFalse($canBeCancelled, 'Доставленный заказ не должен быть доступен для отмены');
    }

    /**
     * Тест: Уже отмененный заказ нельзя отменить повторно
     * 
     * Проверяем, что заказ со статусом "cancelled" нельзя отменить снова.
     * Это предотвращает повторную отмену и дублирование логики возврата товаров на склад.
     */
    public function test_cannot_cancel_already_cancelled_order(): void
    {
        // Arrange: Создаем отмененный заказ
        $order = Order::factory()->cancelled()->create();

        // Act: Проверяем возможность отмены
        $canBeCancelled = $order->canBeCancelled();

        // Assert: Уже отмененный заказ нельзя отменить повторно
        $this->assertFalse($canBeCancelled, 'Уже отмененный заказ не должен быть доступен для повторной отмены');
    }

    // ==========================================
    // ТЕСТЫ: Проверка статусов заказа
    // ==========================================

    /**
     * Тест: Метод isPaid() возвращает true для оплаченного заказа
     * 
     * Проверяем, что метод isPaid() правильно определяет оплаченный заказ:
     * - по статусу оплаты payment_status === 'paid'
     * - по заполненному полю paid_at
     */
    public function test_is_paid_returns_true_when_paid(): void
    {
        // Arrange: Создаем оплаченный заказ (payment_status = paid)
        $order1 = Order::factory()->paid()->create();

        // Arrange: Создаем заказ с заполненным paid_at, но без payment_status
        $order2 = Order::factory()->create([
            'payment_status' => 'pending',
            'paid_at' => now(),
        ]);

        // Act & Assert: Оба заказа должны считаться оплаченными
        $this->assertTrue($order1->isPaid(), 'Заказ с payment_status=paid должен считаться оплаченным');
        $this->assertTrue($order2->isPaid(), 'Заказ с заполненным paid_at должен считаться оплаченным');
    }

    /**
     * Тест: Метод isPaid() возвращает false для неоплаченного заказа
     * 
     * Проверяем, что метод isPaid() правильно определяет неоплаченный заказ.
     */
    public function test_is_paid_returns_false_when_not_paid(): void
    {
        // Arrange: Создаем неоплаченный заказ
        $order = Order::factory()->pending()->create([
            'payment_status' => 'pending',
            'paid_at' => null,
        ]);

        // Act: Проверяем статус оплаты
        $isPaid = $order->isPaid();

        // Assert: Заказ не оплачен
        $this->assertFalse($isPaid, 'Заказ со статусом pending и без paid_at не должен считаться оплаченным');
    }

    /**
     * Тест: Метод isDelivered() возвращает true для доставленного заказа
     * 
     * Проверяем, что метод isDelivered() правильно определяет доставленный заказ:
     * - статус должен быть 'delivered'
     * - поле delivered_at должно быть заполнено
     */
    public function test_is_delivered_returns_true_when_delivered(): void
    {
        // Arrange: Создаем доставленный заказ
        $order = Order::factory()->delivered()->create();

        // Act: Проверяем статус доставки
        $isDelivered = $order->isDelivered();

        // Assert: Заказ доставлен
        $this->assertTrue($isDelivered, 'Доставленный заказ должен возвращать true для isDelivered()');
        $this->assertEquals('delivered', $order->status);
        $this->assertNotNull($order->delivered_at);
    }

    /**
     * Тест: Метод isDelivered() возвращает false для недоставленного заказа
     * 
     * Проверяем, что метод isDelivered() правильно определяет недоставленный заказ.
     */
    public function test_is_delivered_returns_false_when_not_delivered(): void
    {
        // Arrange: Создаем заказ, который еще не доставлен
        $order = Order::factory()->pending()->create([
            'status' => 'pending',
            'delivered_at' => null,
        ]);

        // Act: Проверяем статус доставки
        $isDelivered = $order->isDelivered();

        // Assert: Заказ не доставлен
        $this->assertFalse($isDelivered, 'Заказ со статусом pending не должен считаться доставленным');
    }

    /**
     * Тест: Метод isCancelled() возвращает true для отмененного заказа
     * 
     * Проверяем, что метод isCancelled() правильно определяет отмененный заказ:
     * - статус должен быть 'cancelled'
     * - поле cancelled_at должно быть заполнено
     */
    public function test_is_cancelled_returns_true_when_cancelled(): void
    {
        // Arrange: Создаем отмененный заказ
        $order = Order::factory()->cancelled()->create();

        // Act: Проверяем статус отмены
        $isCancelled = $order->isCancelled();

        // Assert: Заказ отменен
        $this->assertTrue($isCancelled, 'Отмененный заказ должен возвращать true для isCancelled()');
        $this->assertEquals('cancelled', $order->status);
        $this->assertNotNull($order->cancelled_at);
    }

    /**
     * Тест: Метод isCancelled() возвращает false для неотмененного заказа
     * 
     * Проверяем, что метод isCancelled() правильно определяет неотмененный заказ.
     */
    public function test_is_cancelled_returns_false_when_not_cancelled(): void
    {
        // Arrange: Создаем активный заказ
        $order = Order::factory()->pending()->create([
            'status' => 'pending',
            'cancelled_at' => null,
        ]);

        // Act: Проверяем статус отмены
        $isCancelled = $order->isCancelled();

        // Assert: Заказ не отменен
        $this->assertFalse($isCancelled, 'Активный заказ не должен считаться отмененным');
    }

    // ==========================================
    // ТЕСТЫ: Расчет итоговой суммы заказа
    // ==========================================

    /**
     * Тест: Метод calculateTotal() правильно рассчитывает итоговую сумму
     * 
     * Проверяем базовую формулу расчета:
     * total = subtotal + delivery_cost - discount
     */
    public function test_calculates_total_correctly(): void
    {
        // Arrange: Создаем заказ с известными суммами
        $order = Order::factory()->create([
            'subtotal' => 1500.00,      // Сумма товаров
            'delivery_cost' => 300.00,  // Доставка
            'discount' => 0.00,         // Без скидки
        ]);

        // Act: Рассчитываем итоговую сумму
        $total = $order->calculateTotal();

        // Assert: Проверяем формулу: 1500 + 300 - 0 = 1800
        $this->assertEquals(1800.00, $total);
    }

    /**
     * Тест: calculateTotal() учитывает стоимость доставки
     * 
     * Проверяем, что стоимость доставки правильно добавляется к итоговой сумме.
     */
    public function test_includes_delivery_cost_in_total(): void
    {
        // Arrange: Создаем заказ с курьерской доставкой (300 руб)
        $order = Order::factory()->create([
            'subtotal' => 1000.00,
            'delivery_cost' => 300.00,
            'discount' => 0.00,
        ]);

        // Act: Рассчитываем итоговую сумму
        $total = $order->calculateTotal();

        // Assert: Итог должен включать доставку (1000 + 300 = 1300)
        $this->assertEquals(1300.00, $total);
    }

    /**
     * Тест: calculateTotal() вычитает скидку из итоговой суммы
     * 
     * Проверяем, что скидка правильно вычитается из итоговой суммы.
     */
    public function test_subtracts_discount_from_total(): void
    {
        // Arrange: Создаем заказ со скидкой 150 рублей (5% от 3000)
        $order = Order::factory()->create([
            'subtotal' => 3000.00,
            'delivery_cost' => 0.00,  // Бесплатная доставка при заказе > 2000
            'discount' => 150.00,     // Скидка 5% от суммы
        ]);

        // Act: Рассчитываем итоговую сумму
        $total = $order->calculateTotal();

        // Assert: Итог должен вычесть скидку (3000 + 0 - 150 = 2850)
        $this->assertEquals(2850.00, $total);
    }

    /**
     * Тест: calculateTotal() правильно работает с комплексным расчетом
     * 
     * Проверяем полную формулу со всеми компонентами:
     * subtotal + delivery_cost - discount
     */
    public function test_calculates_complex_total_correctly(): void
    {
        // Arrange: Создаем заказ с товарами, доставкой и скидкой
        $order = Order::factory()->create([
            'subtotal' => 3500.00,    // Сумма товаров
            'delivery_cost' => 400.00, // Доставка почтой
            'discount' => 175.00,     // Скидка 5% (3500 * 0.05)
        ]);

        // Act: Рассчитываем итоговую сумму
        $total = $order->calculateTotal();

        // Assert: Проверяем: 3500 + 400 - 175 = 3725
        $this->assertEquals(3725.00, $total);
    }

    /**
     * Тест: calculateTotal() округляет результат до 2 знаков после запятой
     * 
     * Проверяем, что итоговая сумма всегда округляется до копеек.
     */
    public function test_calculates_total_rounds_to_two_decimals(): void
    {
        // Arrange: Создаем заказ с суммами, дающими много знаков после запятой
        $order = Order::factory()->create([
            'subtotal' => 1234.567,   // Больше 2 знаков
            'delivery_cost' => 300.123,
            'discount' => 50.789,
        ]);

        // Act: Рассчитываем итоговую сумму
        $total = $order->calculateTotal();

        // Assert: Результат должен быть округлен до 2 знаков
        // (1234.567 + 300.123 - 50.789 = 1483.901 ≈ 1483.90)
        $this->assertEquals(1483.90, $total);
        
        // Дополнительно проверяем, что это именно 2 знака после запятой
        $totalString = number_format($total, 2, '.', '');
        $this->assertEquals('1483.90', $totalString);
    }

    /**
     * Тест: calculateTotal() работает с нулевой стоимостью доставки
     * 
     * Проверяем расчет для заказа с самовывозом (delivery_cost = 0).
     */
    public function test_calculates_total_with_zero_delivery_cost(): void
    {
        // Arrange: Создаем заказ с самовывозом (бесплатная доставка)
        $order = Order::factory()->pickup()->create([
            'subtotal' => 2500.00,
            'delivery_cost' => 0.00,
            'discount' => 0.00,
        ]);

        // Act: Рассчитываем итоговую сумму
        $total = $order->calculateTotal();

        // Assert: Итог равен только сумме товаров (2500 + 0 - 0 = 2500)
        $this->assertEquals(2500.00, $total);
    }

    /**
     * Тест: calculateTotal() работает с нулевой скидкой
     * 
     * Проверяем расчет для заказа без скидки.
     */
    public function test_calculates_total_with_zero_discount(): void
    {
        // Arrange: Создаем заказ без скидки
        $order = Order::factory()->create([
            'subtotal' => 1800.00,
            'delivery_cost' => 300.00,
            'discount' => 0.00,
        ]);

        // Act: Рассчитываем итоговую сумму
        $total = $order->calculateTotal();

        // Assert: Итог = subtotal + delivery (1800 + 300 = 2100)
        $this->assertEquals(2100.00, $total);
    }

    // ==========================================
    // ТЕСТЫ: Query Scopes
    // ==========================================

    /**
     * Тест: Scope pending() фильтрует заказы со статусом pending
     * 
     * Проверяем, что scope pending() возвращает только заказы,
     * ожидающие обработки.
     */
    public function test_pending_scope_filters_pending_orders(): void
    {
        // Arrange: Создаем заказы с разными статусами
        Order::factory()->pending()->create(); // Pending 1
        Order::factory()->pending()->create(); // Pending 2
        Order::factory()->paid()->create();    // Paid
        Order::factory()->delivered()->create(); // Delivered

        // Act: Получаем только pending заказы
        $pendingOrders = Order::pending()->get();

        // Assert: Должно быть ровно 2 pending заказа
        $this->assertCount(2, $pendingOrders);
        
        // Проверяем, что все заказы имеют статус pending
        foreach ($pendingOrders as $order) {
            $this->assertEquals('pending', $order->status);
        }
    }

    /**
     * Тест: Scope paid() фильтрует оплаченные заказы
     * 
     * Проверяем, что scope paid() возвращает только заказы
     * с payment_status = 'paid'.
     */
    public function test_paid_scope_filters_paid_orders(): void
    {
        // Arrange: Создаем заказы с разными статусами оплаты
        Order::factory()->paid()->create();     // Paid 1
        Order::factory()->paid()->create();     // Paid 2
        Order::factory()->pending()->create();  // Not paid
        Order::factory()->cancelled()->create(); // Not paid

        // Act: Получаем только оплаченные заказы
        $paidOrders = Order::paid()->get();

        // Assert: Должно быть ровно 2 оплаченных заказа
        $this->assertCount(2, $paidOrders);
        
        // Проверяем, что все заказы имеют payment_status = paid
        foreach ($paidOrders as $order) {
            $this->assertEquals('paid', $order->payment_status);
        }
    }

    /**
     * Тест: Scope delivered() фильтрует доставленные заказы
     * 
     * Проверяем, что scope delivered() возвращает только заказы
     * со статусом 'delivered'.
     */
    public function test_delivered_scope_filters_delivered_orders(): void
    {
        // Arrange: Создаем заказы с разными статусами
        Order::factory()->delivered()->create(); // Delivered 1
        Order::factory()->delivered()->create(); // Delivered 2
        Order::factory()->delivered()->create(); // Delivered 3
        Order::factory()->pending()->create();   // Not delivered
        Order::factory()->paid()->create();      // Not delivered

        // Act: Получаем только доставленные заказы
        $deliveredOrders = Order::delivered()->get();

        // Assert: Должно быть ровно 3 доставленных заказа
        $this->assertCount(3, $deliveredOrders);
        
        // Проверяем, что все заказы имеют статус delivered
        foreach ($deliveredOrders as $order) {
            $this->assertEquals('delivered', $order->status);
        }
    }

    /**
     * Тест: Scope cancelled() фильтрует отмененные заказы
     * 
     * Проверяем, что scope cancelled() возвращает только заказы
     * со статусом 'cancelled'.
     */
    public function test_cancelled_scope_filters_cancelled_orders(): void
    {
        // Arrange: Создаем заказы с разными статусами
        Order::factory()->cancelled()->create(); // Cancelled 1
        Order::factory()->cancelled()->create(); // Cancelled 2
        Order::factory()->pending()->create();   // Not cancelled
        Order::factory()->paid()->create();      // Not cancelled

        // Act: Получаем только отмененные заказы
        $cancelledOrders = Order::cancelled()->get();

        // Assert: Должно быть ровно 2 отмененных заказа
        $this->assertCount(2, $cancelledOrders);
        
        // Проверяем, что все заказы имеют статус cancelled
        foreach ($cancelledOrders as $order) {
            $this->assertEquals('cancelled', $order->status);
        }
    }

    /**
     * Тест: Scope byUser() фильтрует заказы по пользователю
     * 
     * Проверяем, что scope byUser() возвращает только заказы
     * конкретного пользователя.
     */
    public function test_by_user_scope_filters_orders_by_user(): void
    {
        // Arrange: Создаем пользователей
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Создаем заказы для первого пользователя
        Order::factory()->count(3)->create(['user_id' => $user1->id]);
        
        // Создаем заказы для второго пользователя
        Order::factory()->count(2)->create(['user_id' => $user2->id]);

        // Создаем гостевой заказ
        Order::factory()->guest()->create();

        // Act: Получаем заказы первого пользователя
        $user1Orders = Order::byUser($user1->id)->get();

        // Assert: У первого пользователя должно быть 3 заказа
        $this->assertCount(3, $user1Orders);
        
        // Проверяем, что все заказы принадлежат первому пользователю
        foreach ($user1Orders as $order) {
            $this->assertEquals($user1->id, $order->user_id);
        }
    }

    /**
     * Тест: Scope recent() сортирует заказы по дате (новые первые)
     * 
     * Проверяем, что scope recent() возвращает заказы
     * отсортированные по created_at в порядке убывания.
     */
    public function test_recent_scope_sorts_orders_by_date_descending(): void
    {
        // Arrange: Создаем заказы в разное время
        $oldOrder = Order::factory()->create(['created_at' => now()->subDays(5)]);
        $recentOrder = Order::factory()->create(['created_at' => now()->subDay()]);
        $newestOrder = Order::factory()->create(['created_at' => now()]);

        // Act: Получаем заказы с сортировкой recent
        $orders = Order::recent()->get();

        // Assert: Заказы должны быть отсортированы от новых к старым
        $this->assertEquals($newestOrder->id, $orders[0]->id, 'Первый заказ должен быть самым новым');
        $this->assertEquals($recentOrder->id, $orders[1]->id, 'Второй заказ должен быть средним по дате');
        $this->assertEquals($oldOrder->id, $orders[2]->id, 'Третий заказ должен быть самым старым');
    }

    // ==========================================
    // ТЕСТЫ: Связи (Relations)
    // ==========================================

    /**
     * Тест: Заказ связан с пользователем
     * 
     * Проверяем, что заказ правильно связан с пользователем через user_id.
     */
    public function test_belongs_to_user(): void
    {
        // Arrange: Создаем пользователя и его заказ
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        // Act: Загружаем связь с пользователем
        $order->load('user');

        // Assert: Заказ принадлежит пользователю
        $this->assertNotNull($order->user);
        $this->assertEquals($user->id, $order->user->id);
        $this->assertInstanceOf(User::class, $order->user);
    }

    /**
     * Тест: Гостевой заказ не имеет пользователя
     * 
     * Проверяем, что гостевой заказ (user_id = null) не связан с пользователем.
     */
    public function test_guest_order_has_no_user(): void
    {
        // Arrange: Создаем гостевой заказ
        $order = Order::factory()->guest()->create();

        // Act: Загружаем связь с пользователем
        $order->load('user');

        // Assert: У гостевого заказа нет пользователя
        $this->assertNull($order->user_id);
        $this->assertNull($order->user);
    }

    /**
     * Тест: Заказ имеет позиции (items)
     * 
     * Проверяем, что заказ правильно связан с позициями заказа.
     */
    public function test_has_many_items(): void
    {
        // Arrange: Создаем заказ с 3 позициями
        $order = Order::factory()->create();
        
        OrderItem::factory()->count(3)->create([
            'order_id' => $order->id,
        ]);

        // Act: Загружаем связь с позициями
        $order->load('items');

        // Assert: У заказа должно быть 3 позиции
        $this->assertCount(3, $order->items);
        $this->assertInstanceOf(OrderItem::class, $order->items->first());
    }

    // ==========================================
    // ТЕСТЫ: Атрибуты и текстовые представления
    // ==========================================

    /**
     * Тест: Атрибут status_text возвращает текстовое описание статуса
     * 
     * Проверяем, что каждый статус заказа имеет правильное русское описание.
     */
    public function test_status_text_attribute_returns_russian_description(): void
    {
        // Arrange & Act & Assert: Проверяем все статусы
        $order = Order::factory()->pending()->create();
        $this->assertEquals('Ожидает обработки', $order->status_text);

        $order = Order::factory()->create(['status' => 'processing']);
        $this->assertEquals('В обработке', $order->status_text);

        $order = Order::factory()->paid()->create();
        $this->assertEquals('Оплачен', $order->status_text);

        $order = Order::factory()->create(['status' => 'shipped']);
        $this->assertEquals('Отправлен', $order->status_text);

        $order = Order::factory()->delivered()->create();
        $this->assertEquals('Доставлен', $order->status_text);

        $order = Order::factory()->cancelled()->create();
        $this->assertEquals('Отменён', $order->status_text);
    }

    /**
     * Тест: Атрибут payment_status_text возвращает текстовое описание статуса оплаты
     * 
     * Проверяем, что каждый статус оплаты имеет правильное русское описание.
     */
    public function test_payment_status_text_attribute_returns_russian_description(): void
    {
        // Arrange & Act & Assert: Проверяем все статусы оплаты
        $order = Order::factory()->pending()->create(['payment_status' => 'pending']);
        $this->assertEquals('Ожидает оплаты', $order->payment_status_text);

        $order = Order::factory()->paid()->create(['payment_status' => 'paid']);
        $this->assertEquals('Оплачено', $order->payment_status_text);

        $order = Order::factory()->create(['payment_status' => 'failed']);
        $this->assertEquals('Ошибка оплаты', $order->payment_status_text);
    }

    /**
     * Тест: Атрибут delivery_method_text возвращает текстовое описание способа доставки
     * 
     * Проверяем, что каждый способ доставки имеет правильное русское описание.
     */
    public function test_delivery_method_text_attribute_returns_russian_description(): void
    {
        // Arrange & Act & Assert: Проверяем все способы доставки
        $order = Order::factory()->courier()->create();
        $this->assertEquals('Курьерская доставка', $order->delivery_method_text);

        $order = Order::factory()->pickup()->create();
        $this->assertEquals('Самовывоз', $order->delivery_method_text);

        $order = Order::factory()->post()->create();
        $this->assertEquals('Почта России', $order->delivery_method_text);
    }

    /**
     * Тест: Атрибут payment_method_text возвращает текстовое описание способа оплаты
     * 
     * Проверяем, что каждый способ оплаты имеет правильное русское описание.
     */
    public function test_payment_method_text_attribute_returns_russian_description(): void
    {
        // Arrange & Act & Assert: Проверяем все способы оплаты
        $order = Order::factory()->create(['payment_method' => 'cash']);
        $this->assertEquals('Наличными при получении', $order->payment_method_text);

        $order = Order::factory()->create(['payment_method' => 'card']);
        $this->assertEquals('Картой при получении', $order->payment_method_text);

        $order = Order::factory()->create(['payment_method' => 'online']);
        $this->assertEquals('Онлайн оплата', $order->payment_method_text);
    }
}
