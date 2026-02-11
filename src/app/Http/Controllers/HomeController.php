<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\BlogPost;
use Illuminate\Contracts\View\View;

/**
 * Контроллер главной страницы
 * 
 * Отвечает за отображение главной страницы интернет-магазина.
 * Собирает все необходимые данные:
 * - Рекомендуемые товары (featured products)
 * - Популярные категории верхнего уровня
 * - Последние статьи блога
 * 
 * Принцип работы: контроллер остается "тонким" - он только получает
 * данные из моделей и передает их в представление (view).
 * Вся бизнес-логика находится в моделях (через scopes) и сервисах.
 */
final class HomeController extends Controller
{
    /**
     * Отображение главной страницы магазина
     * 
     * Этот метод собирает все необходимые данные для главной страницы:
     * 
     * 1. РЕКОМЕНДУЕМЫЕ ТОВАРЫ
     *    - Получаем товары с флагом is_featured = true
     *    - Фильтруем только доступные (is_available = true)
     *    - Ограничиваем до 8 товаров
     *    - Загружаем связи (category, images) для оптимизации запросов
     * 
     * 2. КАТЕГОРИИ
     *    - Получаем только главные категории (parent_id = null)
     *    - Фильтруем только активные (is_active = true)
     *    - Загружаем количество товаров в каждой категории
     * 
     * 3. СТАТЬИ БЛОГА
     *    - Получаем только опубликованные статьи
     *    - Сортируем по дате публикации (newest first)
     *    - Ограничиваем до 3 последних статей
     * 
     * @return View Возвращает представление главной страницы с данными
     */
    public function index(): View
    {
        // ==========================================
        // ПОЛУЧЕНИЕ РЕКОМЕНДУЕМЫХ ТОВАРОВ
        // ==========================================
        // 
        // featured() - scope из модели Product, фильтрует по is_featured = true
        // available() - scope из модели Product, фильтрует по is_available = true
        // with(['category', 'images']) - eager loading (загрузка связанных данных)
        //   Это оптимизирует запросы к БД: вместо N+1 запросов делаем всего 3 запроса
        // limit(8) - ограничиваем результат до 8 товаров
        $featuredProducts = Product::featured()
            ->available()
            ->with(['category', 'images'])
            ->limit(8)
            ->get();

        // ==========================================
        // ПОЛУЧЕНИЕ ГЛАВНЫХ КАТЕГОРИЙ
        // ==========================================
        // 
        // whereNull('parent_id') - выбираем только главные категории (без родителя)
        // where('is_active', true) - только активные категории
        // withCount('availableProducts') - подсчет доступных товаров в категории
        //   Добавляет атрибут available_products_count к каждой категории
        // orderBy('sort_order') - сортируем по заданному порядку
        $categories = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->withCount('availableProducts')
            ->orderBy('sort_order')
            ->get();

        // ==========================================
        // ПОЛУЧЕНИЕ ПОСЛЕДНИХ СТАТЕЙ БЛОГА
        // ==========================================
        // 
        // published() - scope из BlogPost, фильтрует опубликованные статьи
        //   (is_published = true и published_at <= now())
        // with('author') - загружаем автора статьи (пользователя)
        // latest('published_at') - сортируем по дате публикации (новые первые)
        // limit(3) - показываем 3 последние статьи
        $blogPosts = BlogPost::published()
            ->with('author')
            ->latest('published_at')
            ->limit(3)
            ->get();

        // ==========================================
        // ВОЗВРАТ ПРЕДСТАВЛЕНИЯ С ДАННЫМИ
        // ==========================================
        // 
        // Передаем все собранные данные в view 'home'
        // В Blade-шаблоне эти переменные будут доступны напрямую:
        // {{ $featuredProducts }}, {{ $categories }}, {{ $blogPosts }}
        return view('home', compact(
            'featuredProducts',
            'categories',
            'blogPosts'
        ));
    }
}
