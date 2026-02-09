<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Address;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Контроллер оформления заказа
 * 
 * Обрабатывает HTTP запросы, связанные с оформлением заказа:
 * - Отображение формы оформления заказа
 * - Создание заказа из корзины
 * - Обработка оплаты
 * - Подтверждение заказа
 * 
 * Вся бизнес-логика вынесена в OrderService и CartService.
 * Контроллер только обрабатывает HTTP запросы и возвращает ответы.
 */
final class CheckoutController extends Controller
{
    /**
     * Конструктор контроллера
     * 
     * Dependency Injection: внедряем сервисы для работы с корзиной и заказами
     * 
     * @param CartService $cartService Сервис для работы с корзиной
     * @param OrderService $orderService Сервис для работы с заказами
     */
    public function __construct(
        private readonly CartService $cartService,
        private readonly OrderService $orderService
    ) {}

    /**
     * Показать форму оформления заказа
     * 
     * GET /checkout
     * 
     * Отображает форму для оформления заказа с:
     * - Товарами из корзины
     * - Адресами пользователя (если авторизован)
     * - Способами доставки и оплаты
     * - Расчетом стоимости доставки
     * 
     * @return View|RedirectResponse Представление формы или перенаправление
     */
    public function index(): View|RedirectResponse
    {
        // Проверяем, что корзина не пуста
        if ($this->cartService->isEmpty()) {
            return redirect()
                ->route('cart.index')
                ->with('warning', 'Ваша корзина пуста. Добавьте товары перед оформлением заказа.');
        }

        // Получаем товары из корзины
        $cartItems = $this->cartService->getCartItems();

        // Проверяем доступность всех товаров
        $availability = $this->cartService->checkAvailability();
        if (!$availability['available']) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Некоторые товары в корзине недоступны. Пожалуйста, обновите корзину.');
        }

        // Синхронизируем цены в корзине с актуальными ценами
        $priceChangesCount = $this->cartService->syncPrices();
        if ($priceChangesCount > 0) {
            // Если цены изменились - уведомляем пользователя
            session()->flash('info', "Цены на {$priceChangesCount} товара(ов) были обновлены.");
        }

        // Получаем адреса пользователя (если авторизован)
        $addresses = Auth::check() 
            ? Address::byUser(Auth::id())->get() 
            : collect();

        // Рассчитываем предварительную стоимость для разных способов доставки
        $costEstimates = [
            'pickup' => $this->orderService->calculateOrderTotal($cartItems, 'pickup', Auth::user()),
            'courier' => $this->orderService->calculateOrderTotal($cartItems, 'courier', Auth::user()),
            'post' => $this->orderService->calculateOrderTotal($cartItems, 'post', Auth::user()),
        ];

        // Способы оплаты с описанием
        $paymentMethods = [
            'cash' => [
                'name' => 'Наличными при получении',
                'description' => 'Оплата наличными курьеру или в пункте выдачи',
                'available' => true,
            ],
            'card' => [
                'name' => 'Картой при получении',
                'description' => 'Оплата банковской картой при получении заказа',
                'available' => true,
            ],
            'online' => [
                'name' => 'Онлайн оплата',
                'description' => 'Безопасная оплата картой через платежную систему',
                'available' => true,
            ],
        ];

        // Способы доставки с описанием
        $deliveryMethods = [
            'pickup' => [
                'name' => 'Самовывоз',
                'description' => 'Забрать заказ из нашего магазина',
                'available' => true,
            ],
            'courier' => [
                'name' => 'Курьерская доставка',
                'description' => 'Доставка курьером по указанному адресу',
                'available' => true,
            ],
            'post' => [
                'name' => 'Почта России',
                'description' => 'Доставка Почтой России',
                'available' => true,
            ],
        ];

        // Возвращаем представление с данными для формы
        return view('checkout.index', [
            'cartItems' => $cartItems,
            'addresses' => $addresses,
            'paymentMethods' => $paymentMethods,
            'deliveryMethods' => $deliveryMethods,
            'costEstimates' => $costEstimates,
        ]);
    }

    /**
     * Создать заказ
     * 
     * POST /checkout
     * 
     * Обрабатывает данные формы оформления заказа:
     * 1. Валидирует данные (через CheckoutRequest)
     * 2. Создает заказ через OrderService
     * 3. Обрабатывает оплату (если онлайн)
     * 4. Отправляет уведомления
     * 5. Перенаправляет на страницу подтверждения
     * 
     * @param CheckoutRequest $request Валидированные данные запроса
     * @return RedirectResponse Перенаправление на страницу результата
     */
    public function store(CheckoutRequest $request): RedirectResponse
    {
        try {
            // Проверяем, что корзина не пуста
            if ($this->cartService->isEmpty()) {
                return redirect()
                    ->route('cart.index')
                    ->with('error', 'Корзина пуста');
            }

            // Получаем валидированные данные
            $validated = $request->validated();

            // Если выбран существующий адрес - добавляем его в данные
            if (isset($validated['address_id']) && $validated['address_id']) {
                $address = Address::find($validated['address_id']);
                if ($address) {
                    $validated['delivery_address'] = $address->getFullAddress();
                }
            }

            // Используем транзакцию для обеспечения целостности данных
            $order = DB::transaction(function () use ($validated) {
                // Создаем заказ через сервис
                $order = $this->orderService->createOrder($validated);

                // Если пользователь авторизован и создается новый адрес - сохраняем его
                if (Auth::check() && isset($validated['new_address']) && !isset($validated['address_id'])) {
                    $this->saveNewAddress($validated['new_address']);
                }

                return $order;
            });

            // Логируем успешное создание заказа
            Log::info("Заказ #{$order->order_number} успешно создан", [
                'order_id' => $order->id,
                'user_id' => Auth::id(),
                'total' => $order->total,
            ]);

            // Отправляем email-подтверждение
            $this->orderService->sendOrderConfirmation($order);

            // Обрабатываем оплату
            $paymentResult = $this->orderService->processPayment($order);

            // Если оплата онлайн - перенаправляем на страницу оплаты
            if ($order->payment_method === 'online' && isset($paymentResult['payment_url'])) {
                return redirect($paymentResult['payment_url']);
            }

            // Перенаправляем на страницу успешного заказа
            return redirect()
                ->route('checkout.success', ['order' => $order->id])
                ->with('success', 'Заказ успешно оформлен!');

        } catch (\Exception $e) {
            // Логируем ошибку
            Log::error('Ошибка при создании заказа', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Перенаправляем обратно с ошибкой
            return redirect()
                ->route('checkout.index')
                ->with('error', 'Ошибка при оформлении заказа: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Страница успешного оформления заказа
     * 
     * GET /checkout/success/{order}
     * 
     * Отображает страницу с подтверждением успешного создания заказа
     * и информацией о заказе.
     * 
     * @param int $orderId ID заказа
     * @return View|RedirectResponse Представление страницы успеха
     */
    public function success(int $orderId): View|RedirectResponse
    {
        // Получаем заказ
        $order = \App\Models\Order::with(['items.product'])
            ->findOrFail($orderId);

        // Проверяем права доступа
        // Гость может видеть заказ только в текущей сессии
        // Авторизованный пользователь - только свои заказы
        if (Auth::check()) {
            if ($order->user_id !== Auth::id()) {
                abort(403, 'У вас нет доступа к этому заказу');
            }
        } else {
            // Для гостей проверяем по email в сессии
            if (!session()->has('last_order_email') || 
                session('last_order_email') !== $order->customer_email) {
                abort(403, 'У вас нет доступа к этому заказу');
            }
        }

        // Сохраняем email в сессию (для гостей)
        if (!Auth::check()) {
            session(['last_order_email' => $order->customer_email]);
        }

        // Возвращаем представление
        return view('checkout.success', [
            'order' => $order,
        ]);
    }

    /**
     * Сохранить новый адрес пользователя
     * 
     * Вспомогательный метод для сохранения адреса из формы заказа
     * в базу данных для использования в будущих заказах.
     * 
     * @param array $addressData Данные адреса из формы
     * @return Address|null Созданный адрес или null
     */
    private function saveNewAddress(array $addressData): ?Address
    {
        // Сохраняем адрес только для авторизованных пользователей
        if (!Auth::check()) {
            return null;
        }

        try {
            // Создаем новый адрес
            $address = Address::create([
                'user_id' => Auth::id(),
                'name' => 'Адрес доставки', // Можно добавить поле в форму для названия
                'city' => $addressData['city'] ?? '',
                'street' => $addressData['street'] ?? '',
                'house' => $addressData['house'] ?? '',
                'apartment' => $addressData['apartment'] ?? null,
                'postal_code' => $addressData['postal_code'] ?? null,
                'phone' => request('phone'), // Берем телефон из основной формы
                'is_default' => Address::byUser(Auth::id())->count() === 0, // Первый адрес делаем основным
            ]);

            Log::info("Сохранен новый адрес пользователя", [
                'user_id' => Auth::id(),
                'address_id' => $address->id,
            ]);

            return $address;

        } catch (\Exception $e) {
            // Логируем ошибку, но не прерываем процесс оформления заказа
            Log::warning('Не удалось сохранить адрес пользователя', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
