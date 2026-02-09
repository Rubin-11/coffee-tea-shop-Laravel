<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * Сервис для работы с корзиной покупок
 * 
 * Этот сервис инкапсулирует всю бизнес-логику работы с корзиной:
 * - Добавление товаров в корзину
 * - Обновление количества товаров
 * - Удаление товаров из корзины
 * - Расчет общей стоимости
 * - Подсчет количества позиций
 * - Очистка корзины
 * 
 * Поддерживает как авторизованных пользователей (через user_id),
 * так и гостей (через session_id).
 */
final readonly class CartService
{
    /**
     * Получить все товары из корзины текущего пользователя
     * 
     * Загружает товары с их связями (product, product.images)
     * для отображения в корзине
     * 
     * @return Collection<CartItem>
     */
    public function getCartItems(): Collection
    {
        // Проверяем, авторизован ли пользователь
        if (Auth::check()) {
            // Для авторизованного пользователя получаем корзину по user_id
            return CartItem::with(['product.primaryImage', 'product.category'])
                ->byUser(Auth::id())
                ->get();
        }

        // Для гостя получаем корзину по session_id
        return CartItem::with(['product.primaryImage', 'product.category'])
            ->bySession($this->getSessionId())
            ->get();
    }

    /**
     * Добавить товар в корзину
     * 
     * Если товар уже есть в корзине, увеличивает его количество.
     * Если товара нет - создает новую позицию.
     * 
     * @param int $productId ID товара
     * @param int $quantity Количество (по умолчанию 1)
     * @return CartItem Созданная или обновленная позиция корзины
     * @throws \Exception Если товар не найден или недостаточно на складе
     */
    public function addItem(int $productId, int $quantity = 1): CartItem
    {
        // Находим товар или выбрасываем исключение
        $product = Product::available()->findOrFail($productId);

        // Проверяем наличие на складе
        if ($product->stock < $quantity) {
            throw new \Exception(
                "Недостаточно товара на складе. Доступно: {$product->stock} шт."
            );
        }

        // Используем транзакцию для обеспечения целостности данных
        return DB::transaction(function () use ($productId, $quantity, $product) {
            // Ищем существующую позицию в корзине
            $cartItem = $this->findExistingCartItem($productId);

            if ($cartItem) {
                // Если товар уже в корзине - увеличиваем количество
                $newQuantity = $cartItem->quantity + $quantity;

                // Проверяем, достаточно ли товара для нового количества
                if ($product->stock < $newQuantity) {
                    throw new \Exception(
                        "Недостаточно товара на складе. Доступно: {$product->stock} шт., в корзине: {$cartItem->quantity} шт."
                    );
                }

                $cartItem->quantity = $newQuantity;
                $cartItem->save();

                return $cartItem;
            }

            // Создаем новую позицию в корзине
            return CartItem::create([
                'user_id' => Auth::id(), // null для гостей
                'session_id' => Auth::check() ? null : $this->getSessionId(),
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $product->price, // Фиксируем цену на момент добавления
            ]);
        });
    }

    /**
     * Обновить количество товара в корзине
     * 
     * @param int $cartItemId ID позиции корзины
     * @param int $quantity Новое количество
     * @return CartItem Обновленная позиция корзины
     * @throws \Exception Если позиция не найдена или недостаточно товара
     */
    public function updateItem(int $cartItemId, int $quantity): CartItem
    {
        // Находим позицию корзины
        $cartItem = $this->findCartItem($cartItemId);

        if (!$cartItem) {
            throw new \Exception('Позиция не найдена в корзине');
        }

        // Проверяем наличие товара на складе
        if ($cartItem->product->stock < $quantity) {
            throw new \Exception(
                "Недостаточно товара на складе. Доступно: {$cartItem->product->stock} шт."
            );
        }

        // Обновляем количество
        $cartItem->quantity = $quantity;
        $cartItem->save();

        return $cartItem->fresh();
    }

    /**
     * Удалить товар из корзины
     * 
     * @param int $cartItemId ID позиции корзины
     * @return bool Успешность удаления
     * @throws \Exception Если позиция не найдена
     */
    public function removeItem(int $cartItemId): bool
    {
        $cartItem = $this->findCartItem($cartItemId);

        if (!$cartItem) {
            throw new \Exception('Позиция не найдена в корзине');
        }

        return $cartItem->delete();
    }

    /**
     * Очистить всю корзину
     * 
     * Удаляет все товары из корзины текущего пользователя
     * 
     * @return int Количество удаленных позиций
     */
    public function clearCart(): int
    {
        if (Auth::check()) {
            return CartItem::byUser(Auth::id())->delete();
        }

        return CartItem::bySession($this->getSessionId())->delete();
    }

    /**
     * Получить общую стоимость корзины
     * 
     * Вычисляет сумму всех товаров с учетом их количества
     * 
     * @return float Общая стоимость корзины
     */
    public function getTotal(): float
    {
        $items = $this->getCartItems();

        return round(
            $items->sum(fn(CartItem $item) => $item->getSubtotal()),
            2
        );
    }

    /**
     * Получить количество товаров в корзине
     * 
     * Возвращает общее количество единиц товаров (не позиций)
     * 
     * @return int Общее количество товаров
     */
    public function getItemsCount(): int
    {
        $items = $this->getCartItems();

        return $items->sum(fn(CartItem $item) => $item->quantity);
    }

    /**
     * Получить количество позиций в корзине
     * 
     * Возвращает количество уникальных товаров (позиций)
     * 
     * @return int Количество позиций
     */
    public function getItemsQuantity(): int
    {
        return $this->getCartItems()->count();
    }

    /**
     * Проверить, пуста ли корзина
     * 
     * @return bool true если корзина пуста
     */
    public function isEmpty(): bool
    {
        return $this->getItemsQuantity() === 0;
    }

    /**
     * Синхронизировать цены в корзине с актуальными ценами товаров
     * 
     * Обновляет цены в корзине в соответствии с текущими ценами товаров.
     * Полезно перед оформлением заказа.
     * 
     * @return int Количество обновленных позиций
     */
    public function syncPrices(): int
    {
        $items = $this->getCartItems();
        $updatedCount = 0;

        foreach ($items as $item) {
            if ($item->hasPriceChanged()) {
                $item->updatePrice();
                $updatedCount++;
            }
        }

        return $updatedCount;
    }

    /**
     * Проверить доступность всех товаров в корзине
     * 
     * Проверяет, что все товары в корзине:
     * - Доступны для заказа (is_available = true)
     * - Есть в достаточном количестве на складе
     * 
     * @return array{available: bool, unavailable_items: array} Результат проверки
     */
    public function checkAvailability(): array
    {
        $items = $this->getCartItems();
        $unavailableItems = [];

        foreach ($items as $item) {
            if (!$item->isAvailable()) {
                $unavailableItems[] = [
                    'cart_item_id' => $item->id,
                    'product_name' => $item->product->name,
                    'requested_quantity' => $item->quantity,
                    'available_quantity' => $item->product->stock,
                    'is_available' => $item->product->is_available,
                ];
            }
        }

        return [
            'available' => empty($unavailableItems),
            'unavailable_items' => $unavailableItems,
        ];
    }

    /**
     * Перенести корзину гостя на авторизованного пользователя
     * 
     * Используется после авторизации/регистрации пользователя.
     * Объединяет гостевую корзину с корзиной пользователя.
     * 
     * @param int $userId ID пользователя
     * @return void
     */
    public function mergeGuestCart(int $userId): void
    {
        $sessionId = $this->getSessionId();

        // Получаем гостевую корзину
        $guestItems = CartItem::bySession($sessionId)->get();

        if ($guestItems->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($guestItems, $userId, $sessionId) {
            foreach ($guestItems as $guestItem) {
                // Ищем, есть ли такой товар в корзине пользователя
                $userItem = CartItem::byUser($userId)
                    ->where('product_id', $guestItem->product_id)
                    ->first();

                if ($userItem) {
                    // Если товар уже есть - увеличиваем количество
                    $userItem->quantity += $guestItem->quantity;
                    $userItem->save();
                } else {
                    // Если товара нет - переносим гостевую позицию
                    $guestItem->user_id = $userId;
                    $guestItem->session_id = null;
                    $guestItem->save();
                }
            }

            // Удаляем оставшиеся гостевые позиции (те, которые были объединены)
            CartItem::bySession($sessionId)->delete();
        });
    }

    /**
     * Найти существующую позицию товара в корзине
     * 
     * @param int $productId ID товара
     * @return CartItem|null Позиция корзины или null
     */
    private function findExistingCartItem(int $productId): ?CartItem
    {
        if (Auth::check()) {
            return CartItem::byUser(Auth::id())
                ->where('product_id', $productId)
                ->first();
        }

        return CartItem::bySession($this->getSessionId())
            ->where('product_id', $productId)
            ->first();
    }

    /**
     * Найти позицию корзины по ID
     * 
     * Проверяет права доступа - возвращает позицию только если она
     * принадлежит текущему пользователю или гостевой сессии
     * 
     * @param int $cartItemId ID позиции корзины
     * @return CartItem|null Позиция корзины или null
     */
    private function findCartItem(int $cartItemId): ?CartItem
    {
        if (Auth::check()) {
            return CartItem::byUser(Auth::id())
                ->where('id', $cartItemId)
                ->first();
        }

        return CartItem::bySession($this->getSessionId())
            ->where('id', $cartItemId)
            ->first();
    }

    /**
     * Получить ID текущей сессии
     * 
     * Создает новую сессию, если она не существует
     * 
     * @return string ID сессии
     */
    private function getSessionId(): string
    {
        if (!Session::has('cart_session_id')) {
            Session::put('cart_session_id', Session::getId());
        }

        return Session::get('cart_session_id');
    }
}
