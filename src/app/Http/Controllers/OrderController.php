<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Контроллер для работы с заказами
 * 
 * Обрабатывает HTTP запросы, связанные с просмотром заказов:
 * - Список всех заказов пользователя (история заказов)
 * - Детальная информация о конкретном заказе
 * - Отмена заказа
 * - Повторный заказ
 * 
 * Доступен только для авторизованных пользователей (через middleware 'auth').
 * Контроллер не содержит бизнес-логики, только обработку HTTP запросов.
 */
final class OrderController extends Controller
{
    /**
     * Конструктор контроллера
     * 
     * Внедряем OrderService для работы с заказами.
     * Middleware 'auth' применяется в маршрутах (web.php).
     * 
     * @param OrderService $orderService Сервис для работы с заказами
     */
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    /**
     * Показать список всех заказов пользователя
     * 
     * GET /orders
     * 
     * Отображает историю заказов текущего пользователя
     * с пагинацией и возможностью фильтрации по статусу.
     * 
     * @return View Представление списка заказов
     */
    public function index(): View
    {
        // Получаем параметр фильтрации по статусу (если передан)
        $status = request('status');

        // Создаем запрос для получения заказов пользователя
        $query = Order::with(['items.product'])
            ->byUser(Auth::id())
            ->recent(); // Сортируем по дате создания (новые сначала)

        // Применяем фильтр по статусу, если указан
        if ($status && in_array($status, ['pending', 'processing', 'paid', 'shipped', 'delivered', 'cancelled'])) {
            $query->where('status', $status);
        }

        // Получаем заказы с пагинацией (10 заказов на странице)
        $orders = $query->paginate(10);

        // Подсчитываем количество заказов по статусам (для фильтров)
        $statusCounts = [
            'all' => Order::byUser(Auth::id())->count(),
            'pending' => Order::byUser(Auth::id())->pending()->count(),
            'paid' => Order::byUser(Auth::id())->paid()->count(),
            'delivered' => Order::byUser(Auth::id())->delivered()->count(),
            'cancelled' => Order::byUser(Auth::id())->cancelled()->count(),
        ];

        // Возвращаем представление со списком заказов
        return view('orders.index', [
            'orders' => $orders,
            'statusCounts' => $statusCounts,
            'currentStatus' => $status,
        ]);
    }

    /**
     * Показать детальную информацию о заказе
     * 
     * GET /orders/{id}
     * 
     * Отображает подробную информацию о конкретном заказе:
     * - Список товаров с ценами
     * - Адрес доставки
     * - Способ оплаты и доставки
     * - Статус заказа
     * - История изменений статуса (если реализовано)
     * 
     * @param int $id ID заказа
     * @return View|RedirectResponse Представление заказа или перенаправление
     */
    public function show(int $id): View|RedirectResponse
    {
        // Находим заказ с загрузкой связанных данных
        $order = Order::with([
                'items.product.primaryImage', // Товары с изображениями
                'user', // Пользователь (если заказ от авторизованного)
            ])
            ->findOrFail($id);

        // Проверяем права доступа: заказ должен принадлежать текущему пользователю
        if ($order->user_id !== Auth::id()) {
            abort(403, 'У вас нет доступа к этому заказу');
        }

        // Возвращаем представление с детальной информацией
        return view('orders.show', [
            'order' => $order,
        ]);
    }

    /**
     * Отменить заказ
     * 
     * POST /orders/{id}/cancel
     * 
     * Отменяет заказ, если это возможно (заказ еще не отправлен).
     * Возвращает товары на склад и уведомляет пользователя.
     * 
     * @param int $id ID заказа
     * @return RedirectResponse Перенаправление обратно с сообщением
     */
    public function cancel(int $id): RedirectResponse
    {
        try {
            // Находим заказ
            $order = Order::findOrFail($id);

            // Проверяем права доступа
            if ($order->user_id !== Auth::id()) {
                abort(403, 'У вас нет доступа к этому заказу');
            }

            // Проверяем, можно ли отменить заказ
            if (!$order->canBeCancelled()) {
                return redirect()
                    ->route('orders.show', $order->id)
                    ->with('error', 'Этот заказ нельзя отменить. Он уже отправлен или доставлен.');
            }

            // Получаем причину отмены из запроса (опционально)
            $reason = request('reason', 'Отменено пользователем');

            // Отменяем заказ через сервис
            $this->orderService->cancelOrder($order, $reason);

            // Перенаправляем с сообщением об успехе
            return redirect()
                ->route('orders.show', $order->id)
                ->with('success', 'Заказ успешно отменен. Средства будут возвращены в течение 3-5 рабочих дней.');

        } catch (\Exception $e) {
            // Перенаправляем с ошибкой
            return redirect()
                ->back()
                ->with('error', 'Ошибка при отмене заказа: ' . $e->getMessage());
        }
    }

    /**
     * Повторить заказ
     * 
     * POST /orders/{id}/reorder
     * 
     * Добавляет все товары из заказа в корзину для повторного заказа.
     * Полезно для быстрого оформления похожего заказа.
     * 
     * @param int $id ID заказа
     * @return RedirectResponse Перенаправление в корзину
     */
    public function reorder(int $id): RedirectResponse
    {
        try {
            // Находим заказ с товарами
            $order = Order::with('items.product')
                ->findOrFail($id);

            // Проверяем права доступа
            if ($order->user_id !== Auth::id()) {
                abort(403, 'У вас нет доступа к этому заказу');
            }

            // Внедряем CartService для добавления товаров
            $cartService = app(\App\Services\CartService::class);

            $addedCount = 0;
            $unavailableItems = [];

            // Добавляем каждый товар из заказа в корзину
            foreach ($order->items as $item) {
                try {
                    // Проверяем, доступен ли товар
                    if (!$item->product || !$item->product->is_available) {
                        $unavailableItems[] = $item->product_name;
                        continue;
                    }

                    // Добавляем товар в корзину
                    $cartService->addItem(
                        productId: $item->product_id,
                        quantity: $item->quantity
                    );

                    $addedCount++;

                } catch (\Exception $e) {
                    // Если товар недоступен - добавляем в список недоступных
                    $unavailableItems[] = $item->product_name;
                }
            }

            // Формируем сообщение о результате
            $message = "Добавлено товаров в корзину: {$addedCount}";
            
            if (!empty($unavailableItems)) {
                $message .= '. Недоступны: ' . implode(', ', $unavailableItems);
            }

            // Перенаправляем в корзину
            return redirect()
                ->route('cart.index')
                ->with($addedCount > 0 ? 'success' : 'warning', $message);

        } catch (\Exception $e) {
            // Перенаправляем с ошибкой
            return redirect()
                ->back()
                ->with('error', 'Ошибка при повторении заказа: ' . $e->getMessage());
        }
    }

    /**
     * Скачать счет/квитанцию по заказу
     * 
     * GET /orders/{id}/invoice
     * 
     * Генерирует PDF документ с информацией о заказе для печати.
     * (Заглушка для будущей реализации)
     * 
     * @param int $id ID заказа
     * @return RedirectResponse Перенаправление (пока не реализовано)
     */
    public function invoice(int $id): RedirectResponse
    {
        // Находим заказ
        $order = Order::findOrFail($id);

        // Проверяем права доступа
        if ($order->user_id !== Auth::id()) {
            abort(403, 'У вас нет доступа к этому заказу');
        }

        // TODO: Реализовать генерацию PDF счета
        // Можно использовать пакеты:
        // - barryvdh/laravel-dompdf
        // - spatie/laravel-pdf
        
        return redirect()
            ->back()
            ->with('info', 'Функция скачивания счета будет доступна в ближайшее время');
    }
}
