<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

/**
 * Маршруты веб-приложения Coffee & Tea Shop
 *
 * Структура маршрутов:
 * - Главная страница и статические страницы
 * - Каталог товаров и категории
 * - Корзина покупок
 * - Оформление заказа
 * - История заказов (требует авторизации)
 * - Блог и рассылка
 */

// ==================== ГЛАВНАЯ СТРАНИЦА ====================

/**
 * Главная страница магазина.
 * Отображает рекомендуемые товары, популярные категории и последние статьи блога
 */
Route::get('/', [HomeController::class, 'index'])->name('home');

// ==================== КАТАЛОГ ТОВАРОВ ====================

/**
 * Группа маршрутов для работы с товарами
 */
Route::prefix('products')->name('products.')->group(function () {
    // Список всех товаров с фильтрацией и сортировкой
    Route::get('/', [ProductController::class, 'index'])->name('index');

    // Детальная страница товара
    Route::get('/{slug}', [ProductController::class, 'show'])->name('show');
});

// ==================== КАТЕГОРИИ ТОВАРОВ ====================

/**
 * Группа маршрутов для работы с категориями
 */
Route::prefix('categories')->name('categories.')->group(function () {
    // Список всех категорий
    Route::get('/', [CategoryController::class, 'index'])->name('index');

    // Товары в выбранной категории
    Route::get('/{slug}', [CategoryController::class, 'show'])->name('show');
});

// ==================== КОРЗИНА ПОКУПОК ====================

/**
 * Группа маршрутов для работы с корзиной
 * Доступна всем пользователям (включая гостей)
 */
Route::prefix('cart')->name('cart.')->group(function () {
    // Просмотр содержимого корзины
    Route::get('/', [CartController::class, 'index'])->name('index');

    // Добавить товар в корзину
    Route::post('/add', [CartController::class, 'add'])->name('add');

    // Обновить количество товара в корзине
    Route::patch('/{id}', [CartController::class, 'update'])->name('update');

    // Удалить товар из корзины
    Route::delete('/{id}', [CartController::class, 'remove'])->name('remove');

    // Очистить всю корзину
    Route::delete('/', [CartController::class, 'clear'])->name('clear');

    // Получить количество товаров в корзине (для AJAX)
    Route::get('/count', [CartController::class, 'count'])->name('count');
});

// ==================== ОФОРМЛЕНИЕ ЗАКАЗА ====================

/**
 * Группа маршрутов для оформления заказа
 * Доступна всем пользователям (включая гостей)
 * Middleware 'cart.not.empty' проверяет, что корзина не пуста
 */
Route::prefix('checkout')->name('checkout.')->middleware('cart.not.empty')->group(function () {
    // Форма оформления заказа
    Route::get('/', [CheckoutController::class, 'index'])->name('index');

    // Создание заказа
    Route::post('/', [CheckoutController::class, 'store'])->name('store');

    // Страница успешного оформления заказа (без проверки корзины, т.к. заказ уже создан)
    Route::get('/success/{order}', [CheckoutController::class, 'success'])
        ->name('success')
        ->withoutMiddleware('cart.not.empty');
});

// ==================== ИСТОРИЯ ЗАКАЗОВ ====================

/**
 * Группа маршрутов для работы с заказами
 * Требует авторизации пользователя
 */
Route::middleware('auth')->prefix('orders')->name('orders.')->group(function () {
    // Список всех заказов пользователя
    Route::get('/', [OrderController::class, 'index'])->name('index');

    // Детальная информация о заказе
    Route::get('/{id}', [OrderController::class, 'show'])->name('show');

    // Отменить заказ
    Route::post('/{id}/cancel', [OrderController::class, 'cancel'])->name('cancel');

    // Повторить заказ (добавить товары из заказа в корзину)
    Route::post('/{id}/reorder', [OrderController::class, 'reorder'])->name('reorder');

    // Скачать счет/квитанцию
    Route::get('/{id}/invoice', [OrderController::class, 'invoice'])->name('invoice');
});

// ==================== ОТЗЫВЫ НА ТОВАРЫ ====================

/**
 * Маршруты для работы с отзывами на товары
 * Добавление отзыва требует авторизации
 */
Route::middleware('auth')->group(function () {
    // Добавить отзыв на товар (используется Route Model Binding для Product)
    Route::post('/products/{product}/reviews', [ReviewController::class, 'store'])
        ->name('reviews.store');
});

// ==================== БЛОГ ====================

/**
 * Группа маршрутов для блога магазина
 * Доступен всем пользователям
 */
Route::prefix('blog')->name('blog.')->group(function () {
    // Список всех статей блога с фильтрацией
    Route::get('/', [BlogController::class, 'index'])->name('index');

    // Детальная страница статьи
    Route::get('/{slug}', [BlogController::class, 'show'])->name('show');
});

// ==================== EMAIL РАССЫЛКА ====================

/**
 * Группа маршрутов для управления подписками на рассылку
 * Доступна всем пользователям
 */
Route::prefix('newsletter')->name('newsletter.')->group(function () {
    // Подписаться на рассылку (POST запрос из формы в футере)
    Route::post('/subscribe', [NewsletterController::class, 'subscribe'])
        ->name('subscribe');

    // Отписаться от рассылки (GET запрос по ссылке из email)
    Route::get('/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])
        ->name('unsubscribe');
});

// ==================== ИНФОРМАЦИОННЫЕ СТРАНИЦЫ ====================

/**
 * Статические информационные страницы сайта
 * Доступны всем пользователям
 */
Route::prefix('pages')->name('pages.')->group(function () {
    // О компании
    Route::view('/about', 'pages.about')->name('about');

    // Доставка и оплата
    Route::view('/delivery', 'pages.delivery')->name('delivery');

    // Гарантии и возврат
    Route::view('/returns', 'pages.returns')->name('returns');

    // Политика конфиденциальности
    Route::view('/privacy', 'pages.privacy')->name('privacy');

    // Пользовательское соглашение
    Route::view('/terms', 'pages.terms')->name('terms');

    // Контакты
    Route::view('/contacts', 'pages.contacts')->name('contacts');
});

// ==================== ЛИЧНЫЙ КАБИНЕТ ====================

/**
 * Маршруты личного кабинета пользователя
 * Требует авторизации
 */
Route::middleware('auth')->prefix('profile')->name('profile.')->group(function () {
    // Главная страница профиля
    Route::view('/', 'profile.index')->name('index');
});
