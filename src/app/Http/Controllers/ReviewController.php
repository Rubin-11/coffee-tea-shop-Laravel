<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Models\Product;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * Контроллер для работы с отзывами на товары
 * 
 * Отвечает за создание отзывов покупателями на товары.
 * Контроллер остается "тонким" - вся бизнес-логика находится
 * в ReviewService, валидация - в StoreReviewRequest.
 * 
 * Особенности:
 * - Отзывы могут оставлять только авторизованные пользователи
 * - Один пользователь может оставить только один отзыв на товар
 * - Отзывы от проверенных покупателей одобряются автоматически
 * - Остальные отзывы требуют модерации
 */
final class ReviewController extends Controller
{
    /**
     * Конструктор контроллера
     * 
     * Внедряем зависимость ReviewService через конструктор.
     * Это упрощает тестирование и следует принципам Dependency Injection.
     * 
     * @param ReviewService $reviewService Сервис для работы с отзывами
     */
    public function __construct(
        private readonly ReviewService $reviewService
    ) {}

    /**
     * Создать отзыв на товар
     * 
     * Этот метод обрабатывает POST запрос с формы отзыва.
     * 
     * ПРОЦЕСС СОЗДАНИЯ ОТЗЫВА:
     * 
     * 1. ВАЛИДАЦИЯ (выполняется автоматически в StoreReviewRequest):
     *    - Проверка авторизации пользователя
     *    - Проверка корректности данных (rating, comment, pros, cons)
     *    - Проверка, что пользователь не оставлял отзыв ранее
     * 
     * 2. СОЗДАНИЕ ОТЗЫВА (в ReviewService):
     *    - Проверка, покупал ли пользователь товар
     *    - Создание записи в БД
     *    - Автоматическое одобрение для проверенных покупателей
     *    - Пересчет рейтинга товара
     * 
     * 3. ВОЗВРАТ РЕЗУЛЬТАТА:
     *    - При успехе: редирект на страницу товара с сообщением
     *    - При ошибке: редирект назад с ошибкой
     * 
     * @param StoreReviewRequest $request Валидированный запрос с данными отзыва
     * @param \App\Models\Product $product Модель товара (автоматически из Route Model Binding)
     * @return RedirectResponse Редирект на страницу товара
     */
    public function store(StoreReviewRequest $request, Product $product): RedirectResponse
    {
        try {
            // ==========================================
            // ПОЛУЧЕНИЕ ВАЛИДИРОВАННЫХ ДАННЫХ
            // ==========================================
            // 
            // Данные уже прошли валидацию в StoreReviewRequest:
            // - rating: integer, 1-5
            // - comment: string, min:10, max:1000
            // - pros: nullable, string, max:500
            // - cons: nullable, string, max:500
            $validated = $request->validated();

            // ==========================================
            // СОЗДАНИЕ ОТЗЫВА ЧЕРЕЗ СЕРВИС
            // ==========================================
            // 
            // ReviewService выполняет всю бизнес-логику:
            // - Проверяет, покупал ли пользователь товар
            // - Создает отзыв в БД
            // - Автоматически одобряет, если проверенная покупка
            // - Пересчитывает рейтинг товара
            // - Логирует операцию
            $review = $this->reviewService->createReview(
                productId: $product->id,
                data: $validated
            );

            // ==========================================
            // ФОРМИРОВАНИЕ ОТВЕТА
            // ==========================================
            // 
            // Показываем разные сообщения в зависимости от статуса отзыва
            if ($review->is_approved) {
                // Отзыв одобрен автоматически (проверенная покупка)
                $message = 'Спасибо за ваш отзыв! Он опубликован на странице товара.';
            } else {
                // Отзыв ожидает модерации
                $message = 'Спасибо за ваш отзыв! Он будет опубликован после проверки модератором.';
            }

            // Логируем успешное создание отзыва
            Log::info('Отзыв успешно создан', [
                'review_id' => $review->id,
                'product_id' => $product->id,
                'user_id' => auth()->id,
                'is_approved' => $review->is_approved,
            ]);

            // Редиректим на страницу товара с якорем на раздел отзывов
            return redirect()
                ->route('products.show', $product->slug)
                ->with('success', $message)
                ->withFragment('reviews'); // Прокручиваем к секции отзывов

        } catch (\Exception $e) {
            // ==========================================
            // ОБРАБОТКА ОШИБОК
            // ==========================================
            // 
            // Если произошла ошибка при создании отзыва:
            // - Логируем ошибку для отладки
            // - Показываем пользователю понятное сообщение
            // - Редиректим обратно с сохранением введенных данных
            
            Log::error('Ошибка при создании отзыва', [
                'product_id' => $product->id,
                'user_id' => auth()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Возвращаем пользователя назад с сообщением об ошибке
            // withInput() сохраняет введенные данные в форме
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }
}
