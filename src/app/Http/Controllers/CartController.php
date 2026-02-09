<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AddToCartRequest;
use App\Http\Requests\UpdateCartRequest;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Контроллер корзины покупок
 * 
 * Обрабатывает все HTTP запросы, связанные с корзиной:
 * - Просмотр содержимого корзины
 * - Добавление товаров в корзину
 * - Изменение количества товаров
 * - Удаление товаров из корзины
 * - Очистка корзины
 * 
 * Вся бизнес-логика вынесена в CartService для соблюдения принципа SRP.
 * Контроллер только обрабатывает HTTP запросы и возвращает ответы.
 */
final class CartController extends Controller
{
    /**
     * Конструктор контроллера
     * 
     * Dependency Injection: внедряем CartService для работы с корзиной
     * 
     * @param CartService $cartService Сервис для работы с корзиной
     */
    public function __construct(
        private readonly CartService $cartService
    ) {}

    /**
     * Показать содержимое корзины
     * 
     * GET /cart
     * 
     * Получает все товары из корзины текущего пользователя
     * и отображает их на странице корзины с расчетом общей стоимости.
     * 
     * @return View Представление страницы корзины
     */
    public function index(): View
    {
        // Получаем все товары из корзины через сервис
        $cartItems = $this->cartService->getCartItems();
        
        // Рассчитываем общую стоимость корзины
        $total = $this->cartService->getTotal();
        
        // Получаем количество товаров в корзине
        $itemsCount = $this->cartService->getItemsCount();

        // Проверяем доступность товаров
        $availability = $this->cartService->checkAvailability();

        // Возвращаем представление с данными корзины
        return view('cart.index', [
            'cartItems' => $cartItems,
            'total' => $total,
            'itemsCount' => $itemsCount,
            'availability' => $availability,
        ]);
    }

    /**
     * Добавить товар в корзину
     * 
     * POST /cart/add
     * 
     * Добавляет товар в корзину или увеличивает его количество,
     * если товар уже есть в корзине.
     * 
     * @param AddToCartRequest $request Валидированные данные запроса
     * @return RedirectResponse|JsonResponse Перенаправление или JSON ответ
     */
    public function add(AddToCartRequest $request): RedirectResponse|JsonResponse
    {
        try {
            // Получаем валидированные данные
            $validated = $request->validated();

            // Добавляем товар в корзину через сервис
            $cartItem = $this->cartService->addItem(
                productId: $validated['product_id'],
                quantity: $validated['quantity'] ?? 1
            );

            // Если запрос AJAX - возвращаем JSON ответ
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Товар добавлен в корзину',
                    'cart_item' => $cartItem,
                    'items_count' => $this->cartService->getItemsCount(),
                    'total' => $this->cartService->getTotal(),
                ], 201);
            }

            // Для обычного запроса - перенаправляем с сообщением
            return redirect()
                ->back()
                ->with('success', 'Товар успешно добавлен в корзину');

        } catch (\Exception $e) {
            // Обработка ошибок
            
            // Для AJAX запроса - возвращаем JSON с ошибкой
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 400);
            }

            // Для обычного запроса - перенаправляем с ошибкой
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Обновить количество товара в корзине
     * 
     * PATCH /cart/{id}
     * 
     * Изменяет количество товара в корзине на указанное значение.
     * 
     * @param UpdateCartRequest $request Валидированные данные запроса
     * @param int $id ID позиции корзины
     * @return RedirectResponse|JsonResponse Перенаправление или JSON ответ
     */
    public function update(UpdateCartRequest $request, int $id): RedirectResponse|JsonResponse
    {
        try {
            // Получаем валидированные данные
            $validated = $request->validated();

            // Обновляем количество товара через сервис
            $cartItem = $this->cartService->updateItem(
                cartItemId: $id,
                quantity: $validated['quantity']
            );

            // Если запрос AJAX - возвращаем JSON ответ
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Количество товара обновлено',
                    'cart_item' => $cartItem,
                    'items_count' => $this->cartService->getItemsCount(),
                    'total' => $this->cartService->getTotal(),
                ]);
            }

            // Для обычного запроса - перенаправляем с сообщением
            return redirect()
                ->route('cart.index')
                ->with('success', 'Количество товара обновлено');

        } catch (\Exception $e) {
            // Обработка ошибок
            
            // Для AJAX запроса - возвращаем JSON с ошибкой
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 400);
            }

            // Для обычного запроса - перенаправляем с ошибкой
            return redirect()
                ->route('cart.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Удалить товар из корзины
     * 
     * DELETE /cart/{id}
     * 
     * Удаляет позицию из корзины полностью.
     * 
     * @param int $id ID позиции корзины
     * @return RedirectResponse|JsonResponse Перенаправление или JSON ответ
     */
    public function remove(int $id): RedirectResponse|JsonResponse
    {
        try {
            // Удаляем товар из корзины через сервис
            $this->cartService->removeItem($id);

            // Если запрос AJAX - возвращаем JSON ответ
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Товар удален из корзины',
                    'items_count' => $this->cartService->getItemsCount(),
                    'total' => $this->cartService->getTotal(),
                ]);
            }

            // Для обычного запроса - перенаправляем с сообщением
            return redirect()
                ->route('cart.index')
                ->with('success', 'Товар удален из корзины');

        } catch (\Exception $e) {
            // Обработка ошибок
            
            // Для AJAX запроса - возвращаем JSON с ошибкой
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 400);
            }

            // Для обычного запроса - перенаправляем с ошибкой
            return redirect()
                ->route('cart.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Очистить всю корзину
     * 
     * DELETE /cart
     * 
     * Удаляет все товары из корзины текущего пользователя.
     * 
     * @return RedirectResponse|JsonResponse Перенаправление или JSON ответ
     */
    public function clear(): RedirectResponse|JsonResponse
    {
        try {
            // Очищаем корзину через сервис
            $deletedCount = $this->cartService->clearCart();

            // Если запрос AJAX - возвращаем JSON ответ
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Корзина очищена ({$deletedCount} товаров удалено)",
                    'items_count' => 0,
                    'total' => 0,
                ]);
            }

            // Для обычного запроса - перенаправляем с сообщением
            return redirect()
                ->route('cart.index')
                ->with('success', "Корзина очищена ({$deletedCount} товаров удалено)");

        } catch (\Exception $e) {
            // Обработка ошибок
            
            // Для AJAX запроса - возвращаем JSON с ошибкой
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка при очистке корзины',
                ], 500);
            }

            // Для обычного запроса - перенаправляем с ошибкой
            return redirect()
                ->route('cart.index')
                ->with('error', 'Ошибка при очистке корзины');
        }
    }

    /**
     * Получить количество товаров в корзине (для AJAX)
     * 
     * GET /cart/count
     * 
     * Возвращает количество товаров в корзине в формате JSON.
     * Используется для обновления счетчика в хедере.
     * 
     * @return JsonResponse JSON ответ с количеством товаров
     */
    public function count(): JsonResponse
    {
        return response()->json([
            'items_count' => $this->cartService->getItemsCount(),
            'items_quantity' => $this->cartService->getItemsQuantity(),
            'total' => $this->cartService->getTotal(),
        ]);
    }
}
