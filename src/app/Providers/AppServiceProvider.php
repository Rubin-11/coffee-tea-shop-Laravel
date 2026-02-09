<?php

namespace App\Providers;

use App\Http\ViewComposers\CartComposer;
use App\Http\ViewComposers\CategoriesComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * Сервис-провайдер приложения
 * 
 * Регистрирует и загружает сервисы приложения:
 * - View Composers для автоматической передачи данных в представления
 * - Глобальные настройки и конфигурации
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     * 
     * Регистрируем View Composers для автоматической передачи данных
     * в представления Blade.
     */
    public function boot(): void
    {
        // Регистрируем CartComposer для всех представлений
        // Теперь в любом Blade-шаблоне доступны переменные:
        // - $cartItemsCount (количество позиций в корзине)
        // - $cartTotal (общая сумма корзины)
        // - $cartTotalQuantity (общее количество единиц товаров)
        View::composer('*', CartComposer::class);

        // Регистрируем CategoriesComposer для всех представлений
        // Теперь в любом Blade-шаблоне доступны переменные:
        // - $categories (список главных категорий с подкатегориями)
        // - $mainCategories (алиас для $categories)
        View::composer('*', CategoriesComposer::class);

        // Альтернативный вариант: регистрировать только для определённых views
        // Это более оптимально, если данные нужны не везде:
        // View::composer(['layouts.app', 'layouts.header'], CartComposer::class);
        // View::composer(['layouts.app', 'layouts.header', 'layouts.sidebar'], CategoriesComposer::class);
    }
}
