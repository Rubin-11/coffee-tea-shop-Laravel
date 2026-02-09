<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для работы с отзывами на товары
 * 
 * Этот сервис инкапсулирует всю бизнес-логику работы с отзывами:
 * - Создание отзывов на товары
 * - Проверка возможности оставить отзыв (купил ли пользователь товар)
 * - Пересчет среднего рейтинга товара
 * - Модерация отзывов
 * - Подсчет статистики отзывов
 */
final readonly class ReviewService
{
    /**
     * Создать отзыв на товар
     * 
     * Выполняет:
     * 1. Валидацию данных (выполнена в StoreReviewRequest)
     * 2. Проверку, не оставлял ли пользователь уже отзыв на этот товар
     * 3. Проверку, покупал ли пользователь этот товар (verified purchase)
     * 4. Создание отзыва
     * 5. Пересчет рейтинга товара
     * 
     * @param int $productId ID товара
     * @param array $data Данные отзыва (rating, comment, pros, cons)
     * @return Review Созданный отзыв
     * @throws \Exception При ошибках валидации или создания
     */
    public function createReview(int $productId, array $data): Review
    {
        // Проверяем, существует ли товар
        $product = Product::findOrFail($productId);

        // Проверяем, авторизован ли пользователь
        if (!Auth::check()) {
            throw new \Exception('Для добавления отзыва необходимо авторизоваться');
        }

        $userId = Auth::id();

        // Проверяем, не оставлял ли пользователь уже отзыв на этот товар
        $existingReview = Review::where('product_id', $productId)
            ->where('user_id', $userId)
            ->first();

        if ($existingReview) {
            throw new \Exception('Вы уже оставили отзыв на этот товар');
        }

        // Проверяем, покупал ли пользователь этот товар
        $isVerifiedPurchase = $this->hasUserPurchasedProduct($userId, $productId);

        // Используем транзакцию для обеспечения целостности данных
        return DB::transaction(function () use ($productId, $userId, $data, $isVerifiedPurchase) {
            // Создаем отзыв
            $review = Review::create([
                'product_id' => $productId,
                'user_id' => $userId,
                'rating' => $data['rating'],
                'comment' => $data['comment'],
                'pros' => $data['pros'] ?? null,
                'cons' => $data['cons'] ?? null,
                'is_verified_purchase' => $isVerifiedPurchase,
                'is_approved' => false, // По умолчанию отзыв требует модерации
            ]);

            // Если отзыв от проверенного покупателя - автоматически одобряем
            if ($isVerifiedPurchase) {
                $review->is_approved = true;
                $review->save();
            }

            // Пересчитываем рейтинг товара (только если отзыв одобрен)
            if ($review->is_approved) {
                $this->updateProductRating($productId);
            }

            Log::info("Создан отзыв на товар", [
                'review_id' => $review->id,
                'product_id' => $productId,
                'user_id' => $userId,
                'rating' => $review->rating,
                'is_verified' => $isVerifiedPurchase,
            ]);

            return $review->fresh(['user', 'product']);
        });
    }

    /**
     * Обновить средний рейтинг товара
     * 
     * Пересчитывает средний рейтинг на основе всех одобренных отзывов.
     * Также обновляет количество отзывов.
     * 
     * @param int $productId ID товара
     * @return void
     */
    public function updateProductRating(int $productId): void
    {
        // Получаем все одобренные отзывы на товар
        $approvedReviews = Review::where('product_id', $productId)
            ->approved()
            ->get();

        $reviewsCount = $approvedReviews->count();

        if ($reviewsCount === 0) {
            // Если нет отзывов - сбрасываем рейтинг
            Product::where('id', $productId)->update([
                'rating' => 0,
                'reviews_count' => 0,
            ]);
            return;
        }

        // Рассчитываем средний рейтинг
        $averageRating = $approvedReviews->avg('rating');

        // Обновляем товар
        Product::where('id', $productId)->update([
            'rating' => round($averageRating, 2),
            'reviews_count' => $reviewsCount,
        ]);

        Log::debug("Обновлен рейтинг товара", [
            'product_id' => $productId,
            'rating' => round($averageRating, 2),
            'reviews_count' => $reviewsCount,
        ]);
    }

    /**
     * Одобрить отзыв (модерация)
     * 
     * Используется администратором для одобрения отзыва после проверки.
     * После одобрения пересчитывается рейтинг товара.
     * 
     * @param Review $review Отзыв для одобрения
     * @return Review Обновленный отзыв
     */
    public function approveReview(Review $review): Review
    {
        if ($review->is_approved) {
            throw new \Exception('Отзыв уже одобрен');
        }

        DB::transaction(function () use ($review) {
            $review->is_approved = true;
            $review->save();

            // Пересчитываем рейтинг товара
            $this->updateProductRating($review->product_id);

            Log::info("Отзыв одобрен", [
                'review_id' => $review->id,
                'product_id' => $review->product_id,
            ]);
        });

        return $review->fresh();
    }

    /**
     * Отклонить отзыв (модерация)
     * 
     * Используется администратором для отклонения отзыва.
     * Отзыв остается в БД, но не учитывается в рейтинге.
     * 
     * @param Review $review Отзыв для отклонения
     * @return Review Обновленный отзыв
     */
    public function rejectReview(Review $review): Review
    {
        $wasApproved = $review->is_approved;

        $review->is_approved = false;
        $review->save();

        // Если отзыв был одобрен - пересчитываем рейтинг
        if ($wasApproved) {
            $this->updateProductRating($review->product_id);
        }

        Log::info("Отзыв отклонен", [
            'review_id' => $review->id,
            'product_id' => $review->product_id,
        ]);

        return $review->fresh();
    }

    /**
     * Удалить отзыв
     * 
     * Полностью удаляет отзыв из БД и пересчитывает рейтинг товара.
     * 
     * @param Review $review Отзыв для удаления
     * @return bool Успешность удаления
     */
    public function deleteReview(Review $review): bool
    {
        $productId = $review->product_id;
        $wasApproved = $review->is_approved;

        $deleted = $review->delete();

        // Если отзыв был одобрен - пересчитываем рейтинг
        if ($deleted && $wasApproved) {
            $this->updateProductRating($productId);
        }

        Log::info("Отзыв удален", [
            'review_id' => $review->id,
            'product_id' => $productId,
        ]);

        return $deleted;
    }

    /**
     * Проверить, покупал ли пользователь товар
     * 
     * Проверяет, есть ли у пользователя хотя бы один оплаченный
     * и доставленный заказ с этим товаром.
     * 
     * @param int $userId ID пользователя
     * @param int $productId ID товара
     * @return bool true если пользователь покупал товар
     */
    public function hasUserPurchasedProduct(int $userId, int $productId): bool
    {
        return Order::where('user_id', $userId)
            ->whereIn('status', ['delivered', 'paid', 'shipped']) // Учитываем только реальные заказы
            ->where('payment_status', 'paid') // Заказ должен быть оплачен
            ->whereHas('items', function ($query) use ($productId) {
                $query->where('product_id', $productId);
            })
            ->exists();
    }

    /**
     * Проверить, может ли пользователь оставить отзыв на товар
     * 
     * Проверяет:
     * 1. Авторизован ли пользователь
     * 2. Не оставлял ли он уже отзыв
     * 
     * @param int $productId ID товара
     * @return array{can_review: bool, reason: string|null}
     */
    public function canUserReview(int $productId): array
    {
        if (!Auth::check()) {
            return [
                'can_review' => false,
                'reason' => 'Для добавления отзыва необходимо авторизоваться',
            ];
        }

        $userId = Auth::id();

        // Проверяем, не оставлял ли пользователь уже отзыв
        $existingReview = Review::where('product_id', $productId)
            ->where('user_id', $userId)
            ->first();

        if ($existingReview) {
            return [
                'can_review' => false,
                'reason' => 'Вы уже оставили отзыв на этот товар',
            ];
        }

        return [
            'can_review' => true,
            'reason' => null,
        ];
    }

    /**
     * Получить статистику отзывов на товар
     * 
     * Возвращает распределение отзывов по рейтингу (1-5 звезд)
     * 
     * @param int $productId ID товара
     * @return array{
     *     total: int,
     *     average_rating: float,
     *     ratings_distribution: array<int, int>,
     *     verified_count: int
     * }
     */
    public function getReviewStatistics(int $productId): array
    {
        $approvedReviews = Review::where('product_id', $productId)
            ->approved()
            ->get();

        $total = $approvedReviews->count();
        $averageRating = $total > 0 ? round($approvedReviews->avg('rating'), 2) : 0;
        $verifiedCount = $approvedReviews->where('is_verified_purchase', true)->count();

        // Подсчитываем распределение по рейтингу (количество отзывов для каждой звезды)
        $ratingsDistribution = [
            5 => $approvedReviews->where('rating', 5)->count(),
            4 => $approvedReviews->where('rating', 4)->count(),
            3 => $approvedReviews->where('rating', 3)->count(),
            2 => $approvedReviews->where('rating', 2)->count(),
            1 => $approvedReviews->where('rating', 1)->count(),
        ];

        return [
            'total' => $total,
            'average_rating' => $averageRating,
            'ratings_distribution' => $ratingsDistribution,
            'verified_count' => $verifiedCount,
        ];
    }

    /**
     * Получить процентное распределение отзывов по рейтингу
     * 
     * @param int $productId ID товара
     * @return array<int, float> Массив [рейтинг => процент]
     */
    public function getRatingsPercentage(int $productId): array
    {
        $statistics = $this->getReviewStatistics($productId);
        $total = $statistics['total'];

        if ($total === 0) {
            return [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        }

        $percentages = [];
        foreach ($statistics['ratings_distribution'] as $rating => $count) {
            $percentages[$rating] = round(($count / $total) * 100, 1);
        }

        return $percentages;
    }

    /**
     * Получить последние отзывы для товара
     * 
     * @param int $productId ID товара
     * @param int $limit Количество отзывов (по умолчанию 5)
     * @return \Illuminate\Database\Eloquent\Collection<Review>
     */
    public function getLatestReviews(int $productId, int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return Review::where('product_id', $productId)
            ->approved()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить отзывы с фильтрацией
     * 
     * @param int $productId ID товара
     * @param array $filters Фильтры (rating, verified_only, sort)
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getFilteredReviews(int $productId, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Review::where('product_id', $productId)
            ->approved()
            ->with('user');

        // Фильтр по рейтингу
        if (isset($filters['rating']) && $filters['rating'] > 0) {
            $query->where('rating', $filters['rating']);
        }

        // Только проверенные покупки
        if (isset($filters['verified_only']) && $filters['verified_only']) {
            $query->verified();
        }

        // Сортировка
        $sortBy = $filters['sort'] ?? 'latest';
        match ($sortBy) {
            'latest' => $query->orderBy('created_at', 'desc'),
            'oldest' => $query->orderBy('created_at', 'asc'),
            'highest_rating' => $query->orderBy('rating', 'desc'),
            'lowest_rating' => $query->orderBy('rating', 'asc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        return $query->paginate(10);
    }
}
