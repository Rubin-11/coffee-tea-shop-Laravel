<?php

namespace App\Http\Middleware;

use App\Services\CartService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware для проверки, что корзина не пуста
 * 
 * Используется на странице оформления заказа (checkout).
 * Если корзина пуста, перенаправляет пользователя на страницу корзины
 * с сообщением о том, что нужно добавить товары.
 */
class CheckCartNotEmpty
{
    /**
     * Конструктор Middleware
     * 
     * Внедряем CartService для проверки состояния корзины
     * 
     * @param CartService $cartService Сервис для работы с корзиной
     */
    public function __construct(
        protected CartService $cartService
    ) {}

    /**
     * Обработать входящий запрос
     * 
     * Проверяет, что в корзине есть хотя бы один товар.
     * Если корзина пуста, перенаправляет на страницу корзины.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Проверяем, пуста ли корзина
        if ($this->cartService->isEmpty()) {
            // Если корзина пуста, перенаправляем на страницу корзины
            // с сообщением об ошибке
            return redirect()
                ->route('cart.index')
                ->with('error', 'Ваша корзина пуста. Добавьте товары перед оформлением заказа.');
        }

        // Если в корзине есть товары, пропускаем запрос дальше
        return $next($request);
    }
}
