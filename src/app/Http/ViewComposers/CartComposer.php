<?php

namespace App\Http\ViewComposers;

use App\Services\CartService;
use Illuminate\View\View;

/**
 * View Composer для передачи данных корзины во все представления
 * 
 * Автоматически добавляет информацию о корзине во все views,
 * где она нужна (обычно в шапке сайта - хедере).
 * 
 * Передаваемые данные:
 * - $cartItemsCount - количество позиций в корзине
 * - $cartTotal - общая сумма товаров в корзине
 * - $cartTotalQuantity - общее количество единиц товаров
 */
class CartComposer
{
    /**
     * Конструктор View Composer
     * 
     * Внедряем CartService для получения данных о корзине
     * 
     * @param CartService $cartService Сервис для работы с корзиной
     */
    public function __construct(
        protected CartService $cartService
    ) {}

    /**
     * Связать данные с представлением
     * 
     * Этот метод вызывается автоматически перед рендерингом view.
     * Добавляет данные о корзине в переменные представления.
     *
     * @param View $view Объект представления
     * @return void
     */
    public function compose(View $view): void
    {
        // Получаем количество позиций в корзине (для бейджа в хедере)
        $cartItemsCount = $this->cartService->getItemsCount();
        
        // Получаем общую сумму корзины (опционально)
        $cartTotal = $this->cartService->getTotal();
        
        // Получаем общее количество единиц товаров (опционально)
        $cartTotalQuantity = $this->cartService->getItemsQuantity();

        // Передаём данные в представление
        // Теперь в любом Blade-шаблоне доступны переменные:
        // {{ $cartItemsCount }} - количество позиций
        // {{ $cartTotal }} - сумма корзины
        // {{ $cartTotalQuantity }} - количество единиц товаров
        $view->with([
            'cartItemsCount' => $cartItemsCount,
            'cartTotal' => $cartTotal,
            'cartTotalQuantity' => $cartTotalQuantity,
        ]);
    }
}
