<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для работы с заказами
 * 
 * Этот сервис инкапсулирует всю бизнес-логику создания и обработки заказов:
 * - Создание заказа из корзины
 * - Расчет стоимости заказа (товары, доставка, скидки)
 * - Уменьшение остатков товаров на складе
 * - Обработка оплаты (заглушка для интеграции с платежной системой)
 * - Отправка уведомлений о заказе
 * - Управление статусами заказа
 */
final readonly class OrderService
{
    /**
     * Конструктор сервиса
     * 
     * @param CartService $cartService Сервис корзины для получения товаров
     */
    public function __construct(
        private CartService $cartService
    ) {}

    /**
     * Создать заказ из корзины
     * 
     * Основной метод создания заказа. Выполняет:
     * 1. Валидацию корзины (не пуста, товары доступны)
     * 2. Расчет стоимости заказа
     * 3. Создание заказа и позиций заказа
     * 4. Уменьшение остатков товаров
     * 5. Очистку корзины
     * 
     * @param array $data Данные заказа из CheckoutRequest
     * @return Order Созданный заказ
     * @throws \Exception При ошибках валидации или создания
     */
    public function createOrder(array $data): Order
    {
        // Проверяем, что корзина не пуста
        if ($this->cartService->isEmpty()) {
            throw new \Exception('Корзина пуста. Добавьте товары перед оформлением заказа.');
        }

        // Получаем товары из корзины
        $cartItems = $this->cartService->getCartItems();

        // Проверяем доступность всех товаров
        $availability = $this->cartService->checkAvailability();
        if (!$availability['available']) {
            throw new \Exception(
                'Некоторые товары в корзине недоступны или отсутствуют в нужном количестве.'
            );
        }

        // Используем транзакцию для обеспечения целостности данных
        return DB::transaction(function () use ($data, $cartItems) {
            // Рассчитываем стоимость заказа
            $subtotal = $this->calculateSubtotal($cartItems);
            $deliveryCost = $this->calculateDeliveryCost($data['delivery_method'], $subtotal);
            $discount = $this->calculateDiscount($subtotal, Auth::user());
            $total = $subtotal + $deliveryCost - $discount;

            // Создаем заказ
            $order = Order::create([
                'user_id' => Auth::id(), // null для гостей
                'order_number' => $this->generateOrderNumber(),
                'customer_name' => $data['name'],
                'customer_email' => $data['email'],
                'customer_phone' => $data['phone'],
                'delivery_address' => $this->formatDeliveryAddress($data),
                'delivery_method' => $data['delivery_method'],
                'payment_method' => $data['payment_method'],
                'subtotal' => $subtotal,
                'delivery_cost' => $deliveryCost,
                'discount' => $discount,
                'total' => $total,
                'status' => 'pending',
                'payment_status' => 'pending',
                'notes' => $data['comment'] ?? null,
            ]);

            // Создаем позиции заказа
            $this->createOrderItems($order, $cartItems);

            // Уменьшаем количество товаров на складе
            $this->decreaseStock($cartItems);

            // Очищаем корзину
            $this->cartService->clearCart();

            // Логируем создание заказа
            Log::info("Создан заказ #{$order->order_number}", [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'total' => $order->total,
            ]);

            return $order->fresh(['items.product']);
        });
    }

    /**
     * Рассчитать промежуточную сумму заказа (стоимость товаров)
     * 
     * @param Collection<CartItem> $cartItems Товары из корзины
     * @return float Промежуточная сумма
     */
    public function calculateSubtotal(Collection $cartItems): float
    {
        return round(
            $cartItems->sum(fn(CartItem $item) => $item->getSubtotal()),
            2
        );
    }

    /**
     * Рассчитать стоимость доставки
     * 
     * Логика расчета стоимости доставки:
     * - Самовывоз (pickup): бесплатно
     * - Курьерская доставка (courier): 300 руб. (бесплатно при заказе от 2000 руб.)
     * - Почта России (post): 400 руб.
     * 
     * @param string $deliveryMethod Способ доставки
     * @param float $subtotal Промежуточная сумма заказа
     * @return float Стоимость доставки
     */
    public function calculateDeliveryCost(string $deliveryMethod, float $subtotal): float
    {
        return match ($deliveryMethod) {
            'pickup' => 0.00, // Самовывоз - бесплатно
            'courier' => $subtotal >= 2000 ? 0.00 : 300.00, // Бесплатно от 2000 руб.
            'post' => 400.00, // Почта России - фиксированная стоимость
            default => 0.00,
        };
    }

    /**
     * Рассчитать скидку на заказ
     * 
     * Логика расчета скидки:
     * - Скидка может зависеть от статуса пользователя, промокодов и т.д.
     * - Сейчас реализована простая логика: скидка 5% для заказов от 3000 руб.
     * 
     * @param float $subtotal Промежуточная сумма заказа
     * @param User|null $user Пользователь (null для гостей)
     * @return float Размер скидки
     */
    public function calculateDiscount(float $subtotal, ?User $user): float
    {
        $discount = 0.00;

        // Скидка 5% для заказов от 3000 рублей
        if ($subtotal >= 3000) {
            $discount = round($subtotal * 0.05, 2);
        }

        // Здесь можно добавить логику для:
        // - Промокодов
        // - Скидок для зарегистрированных пользователей
        // - Программы лояльности
        // - Сезонных акций

        return $discount;
    }

    /**
     * Рассчитать итоговую сумму заказа
     * 
     * @param Collection<CartItem> $cartItems Товары из корзины
     * @param string $deliveryMethod Способ доставки
     * @param User|null $user Пользователь
     * @return array{subtotal: float, delivery_cost: float, discount: float, total: float}
     */
    public function calculateOrderTotal(
        Collection $cartItems,
        string $deliveryMethod,
        ?User $user = null
    ): array {
        $subtotal = $this->calculateSubtotal($cartItems);
        $deliveryCost = $this->calculateDeliveryCost($deliveryMethod, $subtotal);
        $discount = $this->calculateDiscount($subtotal, $user);
        $total = $subtotal + $deliveryCost - $discount;

        return [
            'subtotal' => round($subtotal, 2),
            'delivery_cost' => round($deliveryCost, 2),
            'discount' => round($discount, 2),
            'total' => round($total, 2),
        ];
    }

    /**
     * Обработать оплату заказа
     * 
     * Это заглушка для интеграции с платежной системой.
     * В реальном приложении здесь будет:
     * - Перенаправление на страницу оплаты
     * - Вызов API платежной системы (Stripe, PayPal, ЮKassa и т.д.)
     * - Обработка webhook-уведомлений о статусе оплаты
     * 
     * @param Order $order Заказ для оплаты
     * @return array{success: bool, payment_url: string|null, message: string}
     */
    public function processPayment(Order $order): array
    {
        // Для способа оплаты "наличными" или "картой при получении" - оплата не требуется
        if (in_array($order->payment_method, ['cash', 'card'])) {
            return [
                'success' => true,
                'payment_url' => null,
                'message' => 'Оплата будет произведена при получении заказа',
            ];
        }

        // Для онлайн-оплаты - имитируем успешный результат
        // В реальном приложении здесь будет вызов API платежной системы
        Log::info("Обработка онлайн-оплаты для заказа #{$order->order_number}");

        return [
            'success' => true,
            'payment_url' => route('orders.show', $order->id), // В реальности - URL платежной системы
            'message' => 'Перенаправление на страницу оплаты',
        ];
    }

    /**
     * Отметить заказ как оплаченный
     * 
     * Используется при получении подтверждения оплаты от платежной системы
     * 
     * @param Order $order Заказ
     * @return Order Обновленный заказ
     */
    public function markAsPaid(Order $order): Order
    {
        $order->payment_status = 'paid';
        $order->status = 'paid';
        $order->paid_at = now();
        $order->save();

        Log::info("Заказ #{$order->order_number} оплачен");

        return $order->fresh();
    }

    /**
     * Отправить email-подтверждение о создании заказа
     * 
     * Отправляет письмо клиенту с:
     * - Номером заказа
     * - Списком товаров
     * - Суммой к оплате
     * - Способом доставки
     * - Ссылкой для отслеживания статуса
     * 
     * @param Order $order Заказ
     * @return void
     */
    public function sendOrderConfirmation(Order $order): void
    {
        // Здесь будет реализация отправки email
        // Используя встроенные Mail и Notification Laravel
        
        Log::info("Отправлено email-подтверждение заказа #{$order->order_number} на {$order->customer_email}");

        // Пример реализации (закомментировано):
        // Mail::to($order->customer_email)->send(new OrderConfirmationMail($order));
        
        // Или с использованием уведомлений:
        // Notification::route('mail', $order->customer_email)
        //     ->notify(new OrderPlacedNotification($order));
    }

    /**
     * Отменить заказ
     * 
     * Выполняет:
     * 1. Проверку возможности отмены
     * 2. Изменение статуса заказа
     * 3. Возврат товаров на склад
     * 4. Уведомление клиента
     * 
     * @param Order $order Заказ для отмены
     * @param string|null $reason Причина отмены
     * @return Order Обновленный заказ
     * @throws \Exception Если заказ нельзя отменить
     */
    public function cancelOrder(Order $order, ?string $reason = null): Order
    {
        if (!$order->canBeCancelled()) {
            throw new \Exception('Заказ не может быть отменен. Он уже отправлен или доставлен.');
        }

        DB::transaction(function () use ($order, $reason) {
            // Возвращаем товары на склад
            foreach ($order->items as $item) {
                $product = $item->product;
                $product->stock += $item->quantity;
                $product->save();
            }

            // Обновляем статус заказа
            $order->status = 'cancelled';
            $order->cancelled_at = now();
            if ($reason) {
                $order->admin_notes = "Причина отмены: {$reason}";
            }
            $order->save();

            Log::info("Заказ #{$order->order_number} отменен", [
                'reason' => $reason,
            ]);
        });

        return $order->fresh();
    }

    /**
     * Создать позиции заказа из корзины
     * 
     * @param Order $order Заказ
     * @param Collection<CartItem> $cartItems Товары из корзины
     * @return void
     */
    private function createOrderItems(Order $order, Collection $cartItems): void
    {
        foreach ($cartItems as $cartItem) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $cartItem->product_id,
                'product_name' => $cartItem->product->name, // Сохраняем название на момент заказа
                'quantity' => $cartItem->quantity,
                'price' => $cartItem->price, // Цена на момент добавления в корзину
                'subtotal' => $cartItem->getSubtotal(),
            ]);
        }
    }

    /**
     * Уменьшить количество товаров на складе
     * 
     * @param Collection<CartItem> $cartItems Товары из корзины
     * @return void
     */
    private function decreaseStock(Collection $cartItems): void
    {
        foreach ($cartItems as $cartItem) {
            $product = Product::find($cartItem->product_id);
            
            if ($product) {
                $product->stock -= $cartItem->quantity;
                $product->save();

                Log::debug("Уменьшен остаток товара", [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $cartItem->quantity,
                    'new_stock' => $product->stock,
                ]);
            }
        }
    }

    /**
     * Сгенерировать уникальный номер заказа
     * 
     * Формат: ORD-YYYY-XXXXX
     * Например: ORD-2026-00001
     * 
     * @return string Уникальный номер заказа
     */
    private function generateOrderNumber(): string
    {
        $year = date('Y');
        $prefix = "ORD-{$year}-";

        // Находим последний заказ текущего года
        $lastOrder = Order::where('order_number', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastOrder) {
            // Извлекаем последний номер и увеличиваем на 1
            $lastNumber = (int) substr($lastOrder->order_number, -5);
            $newNumber = $lastNumber + 1;
        } else {
            // Если это первый заказ в году
            $newNumber = 1;
        }

        // Форматируем номер с ведущими нулями (5 цифр)
        return $prefix . str_pad((string) $newNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Форматировать адрес доставки из данных формы
     * 
     * @param array $data Данные формы заказа
     * @return string Отформатированный адрес
     */
    private function formatDeliveryAddress(array $data): string
    {
        // Если выбран самовывоз - используем адрес магазина
        if ($data['delivery_method'] === 'pickup') {
            return 'Самовывоз из магазина: г. Москва, ул. Примерная, д. 1';
        }

        // Если указан существующий адрес
        if (isset($data['address_id']) && $data['address_id']) {
            // Здесь можно загрузить Address модель и отформатировать
            // Для простоты возвращаем текстовое представление
            return $data['delivery_address'] ?? '';
        }

        // Если указан новый адрес
        if (isset($data['new_address'])) {
            $address = $data['new_address'];
            return implode(', ', [
                $address['city'] ?? '',
                $address['street'] ?? '',
                isset($address['house']) ? "д. {$address['house']}" : '',
                isset($address['apartment']) ? "кв. {$address['apartment']}" : '',
                isset($address['postal_code']) ? "индекс {$address['postal_code']}" : '',
            ]);
        }

        return '';
    }
}
