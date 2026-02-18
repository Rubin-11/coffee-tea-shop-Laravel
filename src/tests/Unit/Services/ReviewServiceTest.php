<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Services\ReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit тесты для ReviewService
 * 
 * Тестируем всю бизнес-логику работы с отзывами на товары:
 * - Создание отзывов и проверка прав доступа
 * - Проверка покупки товара пользователем (verified purchase)
 * - Модерация отзывов (одобрение/отклонение)
 * - Автоматический расчет среднего рейтинга товара
 * - Удаление отзывов и пересчет рейтинга
 * - Статистика отзывов и их распределение
 * - Фильтрация и получение отзывов
 */
class ReviewServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Сервис отзывов для тестов
     */
    private ReviewService $reviewService;

    /**
     * Подготовка перед каждым тестом
     * 
     * Выполняется автоматически перед каждым тестовым методом
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Создаем экземпляр сервиса для использования в тестах
        $this->reviewService = app(ReviewService::class);
    }

    // ==========================================
    // ТЕСТЫ: СОЗДАНИЕ ОТЗЫВА
    // ==========================================

    /**
     * Тест: Можно успешно создать отзыв на товар
     * 
     * Проверяем базовый сценарий создания отзыва авторизованным пользователем
     */
    #[Test]
    public function test_can_create_review(): void
    {
        // Arrange (Подготовка)
        // Создаем пользователя и авторизуем его
        $user = $this->createUser();
        Auth::login($user);
        
        // Создаем товар
        $product = $this->createProduct();
        
        // Данные для отзыва
        $reviewData = [
            'rating' => 5,
            'comment' => 'Отличный товар! Очень доволен покупкой.',
            'pros' => 'Качество, цена, доставка',
            'cons' => null,
        ];

        // Act (Действие)
        // Создаем отзыв
        $review = $this->reviewService->createReview($product->id, $reviewData);

        // Assert (Проверка)
        // Проверяем, что отзыв был создан
        $this->assertInstanceOf(Review::class, $review);
        
        // Проверяем данные отзыва
        $this->assertEquals(5, $review->rating);
        $this->assertEquals('Отличный товар! Очень доволен покупкой.', $review->comment);
        $this->assertEquals('Качество, цена, доставка', $review->pros);
        
        // Проверяем связи
        $this->assertEquals($user->id, $review->user_id);
        $this->assertEquals($product->id, $review->product_id);
        
        // Проверяем, что отзыв сохранен в БД
        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 5,
        ]);
    }

    /**
     * Тест: Выбрасывается исключение при попытке создать отзыв без авторизации
     * 
     * Только авторизованные пользователи могут оставлять отзывы
     */
    #[Test]
    public function test_throws_exception_when_user_not_authenticated(): void
    {
        // Arrange (Подготовка)
        // Убеждаемся, что пользователь НЕ авторизован
        Auth::logout();
        
        $product = $this->createProduct();
        
        $reviewData = [
            'rating' => 4,
            'comment' => 'Хороший товар',
        ];

        // Assert & Act (Проверка и Действие)
        // Ожидаем исключение с сообщением о необходимости авторизации
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Для добавления отзыва необходимо авторизоваться');
        
        // Пытаемся создать отзыв без авторизации
        $this->reviewService->createReview($product->id, $reviewData);
    }

    /**
     * Тест: Выбрасывается исключение при попытке создать дубликат отзыва
     * 
     * Один пользователь может оставить только один отзыв на товар
     */
    #[Test]
    public function test_throws_exception_when_duplicate_review(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        $product = $this->createProduct();
        
        // Создаем первый отзыв
        $reviewData = [
            'rating' => 5,
            'comment' => 'Отличный товар!',
        ];
        $this->reviewService->createReview($product->id, $reviewData);

        // Assert & Act (Проверка и Действие)
        // Ожидаем исключение при попытке создать второй отзыв
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Вы уже оставили отзыв на этот товар');
        
        // Пытаемся создать еще один отзыв на тот же товар
        $this->reviewService->createReview($product->id, $reviewData);
    }

    /**
     * Тест: Отмечает отзыв как проверенную покупку, если пользователь купил товар
     * 
     * Если пользователь действительно покупал товар, отзыв помечается как verified_purchase
     */
    #[Test]
    public function test_marks_as_verified_purchase_when_user_bought_product(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        $product = $this->createProduct();
        
        // Создаем доставленный и оплаченный заказ с этим товаром
        $order = Order::factory()
            ->forUser($user)
            ->delivered()
            ->create();
        
        // Добавляем товар в заказ
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $product->price,
        ]);
        
        $reviewData = [
            'rating' => 5,
            'comment' => 'Купил и остался доволен!',
        ];

        // Act (Действие)
        $review = $this->reviewService->createReview($product->id, $reviewData);

        // Assert (Проверка)
        // Проверяем, что отзыв помечен как проверенная покупка
        $this->assertTrue($review->is_verified_purchase);
    }

    /**
     * Тест: Автоматически одобряет отзывы от проверенных покупателей
     * 
     * Отзывы от пользователей, которые купили товар, одобряются автоматически
     */
    #[Test]
    public function test_auto_approves_verified_purchases(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        $product = $this->createProduct();
        
        // Создаем доставленный заказ с товаром
        $order = Order::factory()
            ->forUser($user)
            ->delivered()
            ->create();
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $product->price,
        ]);
        
        $reviewData = [
            'rating' => 4,
            'comment' => 'Хороший товар',
        ];

        // Act (Действие)
        $review = $this->reviewService->createReview($product->id, $reviewData);

        // Assert (Проверка)
        // Отзыв должен быть автоматически одобрен
        $this->assertTrue($review->is_approved);
        $this->assertTrue($review->is_verified_purchase);
    }

    /**
     * Тест: Требует модерации для непроверенных покупок
     * 
     * Отзывы от пользователей, которые не покупали товар, требуют модерации
     */
    #[Test]
    public function test_requires_moderation_for_unverified_purchases(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        $product = $this->createProduct();
        
        // Пользователь НЕ покупал этот товар
        $reviewData = [
            'rating' => 3,
            'comment' => 'Читал отзывы, решил оставить свое мнение',
        ];

        // Act (Действие)
        $review = $this->reviewService->createReview($product->id, $reviewData);

        // Assert (Проверка)
        // Отзыв НЕ должен быть одобрен (требует модерации)
        $this->assertFalse($review->is_approved);
        $this->assertFalse($review->is_verified_purchase);
    }

    /**
     * Тест: Создает отзыв и загружает связи (user, product)
     * 
     * После создания отзыв должен содержать загруженные связи
     */
    #[Test]
    public function test_creates_review_with_loaded_relationships(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        $product = $this->createProduct();
        
        $reviewData = [
            'rating' => 5,
            'comment' => 'Отлично!',
        ];

        // Act (Действие)
        $review = $this->reviewService->createReview($product->id, $reviewData);

        // Assert (Проверка)
        // Проверяем, что связи загружены
        $this->assertTrue($review->relationLoaded('user'));
        $this->assertTrue($review->relationLoaded('product'));
        
        // Проверяем доступ к связанным моделям
        $this->assertEquals($user->id, $review->user->id);
        $this->assertEquals($product->id, $review->product->id);
    }

    // ==========================================
    // ТЕСТЫ: ОБНОВЛЕНИЕ РЕЙТИНГА ТОВАРА
    // ==========================================

    /**
     * Тест: Обновляет рейтинг товара после создания отзыва
     * 
     * После добавления одобренного отзыва рейтинг товара должен обновиться
     */
    #[Test]
    public function test_updates_product_rating_after_review(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        $product = $this->createProduct([
            'rating' => 0,
            'reviews_count' => 0,
        ]);
        
        // Создаем доставленный заказ (чтобы отзыв был verified и auto-approved)
        $order = Order::factory()->forUser($user)->delivered()->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $product->price,
        ]);
        
        $reviewData = [
            'rating' => 5,
            'comment' => 'Отличный товар!',
        ];

        // Act (Действие)
        $this->reviewService->createReview($product->id, $reviewData);

        // Assert (Проверка)
        // Обновляем данные товара из БД
        $product->refresh();
        
        // Рейтинг товара должен быть обновлен
        $this->assertEquals(5.0, (float) $product->rating);
        $this->assertEquals(1, $product->reviews_count);
    }

    /**
     * Тест: Правильно рассчитывает средний рейтинг товара
     * 
     * Средний рейтинг = сумма всех рейтингов / количество отзывов
     */
    #[Test]
    public function test_calculates_average_rating_correctly(): void
    {
        // Arrange (Подготовка)
        $product = $this->createProduct([
            'rating' => 0,
            'reviews_count' => 0,
        ]);
        
        // Создаем несколько одобренных отзывов с разными рейтингами
        Review::factory()->create([
            'product_id' => $product->id,
            'rating' => 5,
            'is_approved' => true,
        ]);
        
        Review::factory()->create([
            'product_id' => $product->id,
            'rating' => 4,
            'is_approved' => true,
        ]);
        
        Review::factory()->create([
            'product_id' => $product->id,
            'rating' => 3,
            'is_approved' => true,
        ]);
        
        // Средний рейтинг: (5 + 4 + 3) / 3 = 4.0

        // Act (Действие)
        $this->reviewService->updateProductRating($product->id);

        // Assert (Проверка)
        $product->refresh();
        
        // Проверяем средний рейтинг (должен быть округлен до 2 знаков)
        $this->assertEquals(4.0, (float) $product->rating);
    }

    /**
     * Тест: Обновляет количество отзывов у товара
     * 
     * Поле reviews_count должно отражать количество одобренных отзывов
     */
    #[Test]
    public function test_updates_reviews_count(): void
    {
        // Arrange (Подготовка)
        $product = $this->createProduct([
            'rating' => 0,
            'reviews_count' => 0,
        ]);
        
        // Создаем 5 одобренных отзывов
        Review::factory()->count(5)->create([
            'product_id' => $product->id,
            'is_approved' => true,
        ]);

        // Act (Действие)
        $this->reviewService->updateProductRating($product->id);

        // Assert (Проверка)
        $product->refresh();
        
        // Количество отзывов должно быть 5
        $this->assertEquals(5, $product->reviews_count);
    }

    /**
     * Тест: Учитывает только одобренные отзывы при расчете рейтинга
     * 
     * Неодобренные отзывы (на модерации) не должны влиять на рейтинг
     */
    #[Test]
    public function test_only_counts_approved_reviews(): void
    {
        // Arrange (Подготовка)
        $product = $this->createProduct([
            'rating' => 0,
            'reviews_count' => 0,
        ]);
        
        // Создаем 3 одобренных отзыва с рейтингом 5
        Review::factory()->count(3)->create([
            'product_id' => $product->id,
            'rating' => 5,
            'is_approved' => true,
        ]);
        
        // Создаем 2 неодобренных отзыва с рейтингом 1 (они не должны учитываться)
        Review::factory()->count(2)->create([
            'product_id' => $product->id,
            'rating' => 1,
            'is_approved' => false,
        ]);
        
        // Если бы учитывались все: (5+5+5+1+1) / 5 = 3.4
        // Но должны учитываться только одобренные: (5+5+5) / 3 = 5.0

        // Act (Действие)
        $this->reviewService->updateProductRating($product->id);

        // Assert (Проверка)
        $product->refresh();
        
        // Рейтинг должен быть 5.0 (только одобренные)
        $this->assertEquals(5.0, (float) $product->rating);
        
        // Количество должно быть 3 (только одобренные)
        $this->assertEquals(3, $product->reviews_count);
    }

    /**
     * Тест: Сбрасывает рейтинг товара, если нет одобренных отзывов
     * 
     * Если все отзывы удалены или отклонены, рейтинг = 0
     */
    #[Test]
    public function test_resets_rating_when_no_approved_reviews(): void
    {
        // Arrange (Подготовка)
        $product = $this->createProduct([
            'rating' => 4.5,
            'reviews_count' => 10,
        ]);
        
        // Создаем только неодобренные отзывы
        Review::factory()->count(3)->create([
            'product_id' => $product->id,
            'is_approved' => false,
        ]);

        // Act (Действие)
        $this->reviewService->updateProductRating($product->id);

        // Assert (Проверка)
        $product->refresh();
        
        // Рейтинг и количество должны быть сброшены
        $this->assertEquals(0.0, (float) $product->rating);
        $this->assertEquals(0, $product->reviews_count);
    }

    // ==========================================
    // ТЕСТЫ: МОДЕРАЦИЯ ОТЗЫВОВ
    // ==========================================

    /**
     * Тест: Может одобрить отзыв (модерация)
     * 
     * Администратор может одобрить отзыв, который был на модерации
     */
    #[Test]
    public function test_can_approve_review(): void
    {
        // Arrange (Подготовка)
        $product = $this->createProduct();
        
        // Создаем неодобренный отзыв
        $review = Review::factory()->pending()->create([
            'product_id' => $product->id,
            'rating' => 4,
        ]);
        
        // Проверяем начальное состояние
        $this->assertFalse($review->is_approved);

        // Act (Действие)
        $approvedReview = $this->reviewService->approveReview($review);

        // Assert (Проверка)
        // Отзыв должен быть одобрен
        $this->assertTrue($approvedReview->is_approved);
        
        // Проверяем в БД
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'is_approved' => true,
        ]);
    }

    /**
     * Тест: Может отклонить отзыв (модерация)
     * 
     * Администратор может отклонить отзыв
     */
    #[Test]
    public function test_can_reject_review(): void
    {
        // Arrange (Подготовка)
        $product = $this->createProduct();
        
        // Создаем одобренный отзыв
        $review = Review::factory()->create([
            'product_id' => $product->id,
            'rating' => 3,
            'is_approved' => true,
        ]);

        // Act (Действие)
        $rejectedReview = $this->reviewService->rejectReview($review);

        // Assert (Проверка)
        // Отзыв должен быть отклонен
        $this->assertFalse($rejectedReview->is_approved);
        
        // Проверяем в БД
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'is_approved' => false,
        ]);
    }

    /**
     * Тест: Пересчитывает рейтинг товара после одобрения отзыва
     * 
     * После одобрения отзыва рейтинг товара должен обновиться
     */
    #[Test]
    public function test_recalculates_rating_after_approval(): void
    {
        // Arrange (Подготовка)
        $product = $this->createProduct([
            'rating' => 0,
            'reviews_count' => 0,
        ]);
        
        // Создаем неодобренный отзыв
        $review = Review::factory()->pending()->create([
            'product_id' => $product->id,
            'rating' => 5,
        ]);
        
        // Проверяем, что рейтинг еще не обновился
        $product->refresh();
        $this->assertEquals(0.0, (float) $product->rating);

        // Act (Действие)
        // Одобряем отзыв
        $this->reviewService->approveReview($review);

        // Assert (Проверка)
        $product->refresh();
        
        // Рейтинг должен обновиться
        $this->assertEquals(5.0, (float) $product->rating);
        $this->assertEquals(1, $product->reviews_count);
    }

    /**
     * Тест: Пересчитывает рейтинг товара после отклонения отзыва
     * 
     * После отклонения ранее одобренного отзыва рейтинг должен пересчитаться
     */
    #[Test]
    public function test_recalculates_rating_after_rejection(): void
    {
        // Arrange (Подготовка)
        $product = $this->createProduct();
        
        // Создаем 2 одобренных отзыва с рейтингом 5
        Review::factory()->count(2)->create([
            'product_id' => $product->id,
            'rating' => 5,
            'is_approved' => true,
        ]);
        
        // Создаем еще один одобренный отзыв с рейтингом 1
        $review = Review::factory()->create([
            'product_id' => $product->id,
            'rating' => 1,
            'is_approved' => true,
        ]);
        
        // Обновляем рейтинг: (5+5+1) / 3 = 3.67
        $this->reviewService->updateProductRating($product->id);
        $product->refresh();
        $this->assertEquals(3.67, (float) $product->rating);

        // Act (Действие)
        // Отклоняем отзыв с рейтингом 1
        $this->reviewService->rejectReview($review);

        // Assert (Проверка)
        $product->refresh();
        
        // Рейтинг должен пересчитаться: (5+5) / 2 = 5.0
        $this->assertEquals(5.0, (float) $product->rating);
        $this->assertEquals(2, $product->reviews_count);
    }

    /**
     * Тест: Выбрасывается исключение при попытке одобрить уже одобренный отзыв
     * 
     * Нельзя одобрить отзыв, который уже одобрен
     */
    #[Test]
    public function test_throws_exception_when_approving_already_approved_review(): void
    {
        // Arrange (Подготовка)
        $review = Review::factory()->create([
            'is_approved' => true,
        ]);

        // Assert & Act (Проверка и Действие)
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Отзыв уже одобрен');
        
        // Пытаемся одобрить уже одобренный отзыв
        $this->reviewService->approveReview($review);
    }

    // ==========================================
    // ТЕСТЫ: УДАЛЕНИЕ ОТЗЫВА
    // ==========================================

    /**
     * Тест: Может успешно удалить отзыв
     * 
     * Базовый сценарий удаления отзыва
     */
    #[Test]
    public function test_can_delete_review(): void
    {
        // Arrange (Подготовка)
        $review = Review::factory()->create();
        
        $reviewId = $review->id;

        // Act (Действие)
        $result = $this->reviewService->deleteReview($review);

        // Assert (Проверка)
        // Проверяем, что метод вернул true
        $this->assertTrue($result);
        
        // Проверяем, что отзыв удален из БД
        $this->assertDatabaseMissing('reviews', [
            'id' => $reviewId,
        ]);
    }

    /**
     * Тест: Пересчитывает рейтинг товара после удаления одобренного отзыва
     * 
     * После удаления одобренного отзыва рейтинг товара должен обновиться
     */
    #[Test]
    public function test_recalculates_rating_after_deletion(): void
    {
        // Arrange (Подготовка)
        $product = $this->createProduct();
        
        // Создаем 3 одобренных отзыва
        Review::factory()->count(2)->create([
            'product_id' => $product->id,
            'rating' => 5,
            'is_approved' => true,
        ]);
        
        $reviewToDelete = Review::factory()->create([
            'product_id' => $product->id,
            'rating' => 2,
            'is_approved' => true,
        ]);
        
        // Обновляем рейтинг: (5+5+2) / 3 = 4.0
        $this->reviewService->updateProductRating($product->id);
        $product->refresh();
        $this->assertEquals(4.0, (float) $product->rating);
        $this->assertEquals(3, $product->reviews_count);

        // Act (Действие)
        // Удаляем отзыв с рейтингом 2
        $this->reviewService->deleteReview($reviewToDelete);

        // Assert (Проверка)
        $product->refresh();
        
        // Рейтинг должен пересчитаться: (5+5) / 2 = 5.0
        $this->assertEquals(5.0, (float) $product->rating);
        $this->assertEquals(2, $product->reviews_count);
    }

    /**
     * Тест: НЕ пересчитывает рейтинг после удаления неодобренного отзыва
     * 
     * Если удаляется неодобренный отзыв, рейтинг не должен меняться
     */
    #[Test]
    public function test_does_not_recalculate_rating_after_deleting_unapproved_review(): void
    {
        // Arrange (Подготовка)
        $product = $this->createProduct();
        
        // Создаем одобренный отзыв
        Review::factory()->create([
            'product_id' => $product->id,
            'rating' => 5,
            'is_approved' => true,
        ]);
        
        // Создаем неодобренный отзыв
        $unapprovedReview = Review::factory()->pending()->create([
            'product_id' => $product->id,
            'rating' => 1,
        ]);
        
        // Обновляем рейтинг (должен быть 5.0 от одного одобренного)
        $this->reviewService->updateProductRating($product->id);
        $product->refresh();
        $this->assertEquals(5.0, (float) $product->rating);
        $this->assertEquals(1, $product->reviews_count);

        // Act (Действие)
        // Удаляем неодобренный отзыв
        $this->reviewService->deleteReview($unapprovedReview);

        // Assert (Проверка)
        $product->refresh();
        
        // Рейтинг не должен измениться (неодобренный не учитывался)
        $this->assertEquals(5.0, (float) $product->rating);
        $this->assertEquals(1, $product->reviews_count);
    }

    // ==========================================
    // ТЕСТЫ: ПРОВЕРКА ПОКУПКИ ТОВАРА
    // ==========================================

    /**
     * Тест: Проверяет, покупал ли пользователь товар
     * 
     * Метод hasUserPurchasedProduct() возвращает true, если пользователь купил товар
     */
    #[Test]
    public function test_checks_if_user_purchased_product(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        $product = $this->createProduct();
        
        // Создаем доставленный заказ с товаром
        $order = Order::factory()
            ->forUser($user)
            ->delivered()
            ->create();
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $product->price,
        ]);

        // Act (Действие)
        $hasPurchased = $this->reviewService->hasUserPurchasedProduct($user->id, $product->id);

        // Assert (Проверка)
        // Пользователь купил товар
        $this->assertTrue($hasPurchased);
    }

    /**
     * Тест: Возвращает true для доставленных заказов
     * 
     * Заказы со статусом delivered считаются реальными покупками
     */
    #[Test]
    public function test_returns_true_for_delivered_orders(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        $product = $this->createProduct();
        
        // Создаем доставленный заказ
        $order = Order::factory()
            ->forUser($user)
            ->delivered()
            ->create();
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $product->price,
        ]);

        // Act (Действие)
        $hasPurchased = $this->reviewService->hasUserPurchasedProduct($user->id, $product->id);

        // Assert (Проверка)
        $this->assertTrue($hasPurchased);
    }

    /**
     * Тест: Возвращает false для pending заказов
     * 
     * Заказы в ожидании не считаются реальными покупками
     */
    #[Test]
    public function test_returns_false_for_pending_orders(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        $product = $this->createProduct();
        
        // Создаем заказ в статусе pending (ожидает обработки)
        $order = Order::factory()
            ->forUser($user)
            ->pending()
            ->create();
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $product->price,
        ]);

        // Act (Действие)
        $hasPurchased = $this->reviewService->hasUserPurchasedProduct($user->id, $product->id);

        // Assert (Проверка)
        // Pending заказ не считается покупкой
        $this->assertFalse($hasPurchased);
    }

    /**
     * Тест: Возвращает false для неоплаченных заказов
     * 
     * Даже если заказ доставлен, но не оплачен, это не считается покупкой
     */
    #[Test]
    public function test_returns_false_for_unpaid_orders(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        $product = $this->createProduct();
        
        // Создаем заказ со статусом доставлен, но payment_status = pending
        $order = Order::factory()
            ->forUser($user)
            ->create([
                'status' => 'delivered',
                'payment_status' => 'pending', // НЕ оплачен
                'paid_at' => null,
            ]);
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $product->price,
        ]);

        // Act (Действие)
        $hasPurchased = $this->reviewService->hasUserPurchasedProduct($user->id, $product->id);

        // Assert (Проверка)
        // Неоплаченный заказ не считается покупкой
        $this->assertFalse($hasPurchased);
    }

    /**
     * Тест: Возвращает true для оплаченных и отправленных заказов
     * 
     * Заказы со статусом paid или shipped и оплатой считаются покупками
     */
    #[Test]
    public function test_returns_true_for_paid_and_shipped_orders(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        $product = $this->createProduct();
        
        // Создаем оплаченный заказ
        $order = Order::factory()
            ->forUser($user)
            ->paid()
            ->create();
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $product->price,
        ]);

        // Act (Действие)
        $hasPurchased = $this->reviewService->hasUserPurchasedProduct($user->id, $product->id);

        // Assert (Проверка)
        $this->assertTrue($hasPurchased);
    }

    /**
     * Тест: Возвращает false если пользователь не покупал товар
     * 
     * Если у пользователя нет заказов с этим товаром
     */
    #[Test]
    public function test_returns_false_when_user_never_bought_product(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        $product = $this->createProduct();
        
        // Пользователь не делал заказов

        // Act (Действие)
        $hasPurchased = $this->reviewService->hasUserPurchasedProduct($user->id, $product->id);

        // Assert (Проверка)
        $this->assertFalse($hasPurchased);
    }

    // ==========================================
    // ТЕСТЫ: ПРОВЕРКА ВОЗМОЖНОСТИ ОСТАВИТЬ ОТЗЫВ
    // ==========================================

    /**
     * Тест: Проверяет, может ли пользователь оставить отзыв
     * 
     * Метод canUserReview() проверяет права пользователя на создание отзыва
     */
    #[Test]
    public function test_can_check_if_user_can_review(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        $product = $this->createProduct();

        // Act (Действие)
        $result = $this->reviewService->canUserReview($product->id);

        // Assert (Проверка)
        // Пользователь может оставить отзыв
        $this->assertTrue($result['can_review']);
        $this->assertNull($result['reason']);
    }

    /**
     * Тест: Нельзя оставить отзыв без авторизации
     * 
     * Неавторизованный пользователь не может оставлять отзывы
     */
    #[Test]
    public function test_cannot_review_when_not_authenticated(): void
    {
        // Arrange (Подготовка)
        Auth::logout();
        
        $product = $this->createProduct();

        // Act (Действие)
        $result = $this->reviewService->canUserReview($product->id);

        // Assert (Проверка)
        $this->assertFalse($result['can_review']);
        $this->assertEquals('Для добавления отзыва необходимо авторизоваться', $result['reason']);
    }

    /**
     * Тест: Нельзя оставить отзыв если уже оставлял
     * 
     * Один пользователь может оставить только один отзыв на товар
     */
    #[Test]
    public function test_cannot_review_when_already_reviewed(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        Auth::login($user);
        
        $product = $this->createProduct();
        
        // Пользователь уже оставил отзыв
        Review::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        // Act (Действие)
        $result = $this->reviewService->canUserReview($product->id);

        // Assert (Проверка)
        $this->assertFalse($result['can_review']);
        $this->assertEquals('Вы уже оставили отзыв на этот товар', $result['reason']);
    }

    // ==========================================
    // ТЕСТЫ: СТАТИСТИКА ОТЗЫВОВ
    // ==========================================

    /**
     * Тест: Рассчитывает статистику отзывов на товар
     * 
     * Метод возвращает полную статистику: количество, средний рейтинг, распределение
     */
    #[Test]
    public function test_calculates_review_statistics(): void
    {
        // Arrange (Подготовка)
        $product = $this->createProduct();
        
        // Создаем одобренные отзывы с разными рейтингами
        Review::factory()->count(3)->create([
            'product_id' => $product->id,
            'rating' => 5,
            'is_approved' => true,
            'is_verified_purchase' => true,
        ]);
        
        Review::factory()->count(2)->create([
            'product_id' => $product->id,
            'rating' => 4,
            'is_approved' => true,
            'is_verified_purchase' => false,
        ]);
        
        Review::factory()->create([
            'product_id' => $product->id,
            'rating' => 3,
            'is_approved' => true,
            'is_verified_purchase' => false,
        ]);
        
        // Создаем неодобренный отзыв (не должен учитываться)
        Review::factory()->pending()->create([
            'product_id' => $product->id,
            'rating' => 1,
        ]);

        // Act (Действие)
        $statistics = $this->reviewService->getReviewStatistics($product->id);

        // Assert (Проверка)
        // Проверяем общее количество (только одобренные)
        $this->assertEquals(6, $statistics['total']);
        
        // Проверяем средний рейтинг: (5*3 + 4*2 + 3*1) / 6 = 26/6 = 4.33
        $this->assertEquals(4.33, $statistics['average_rating']);
        
        // Проверяем количество проверенных покупок
        $this->assertEquals(3, $statistics['verified_count']);
        
        // Проверяем распределение по рейтингу
        $this->assertEquals(3, $statistics['ratings_distribution'][5]);
        $this->assertEquals(2, $statistics['ratings_distribution'][4]);
        $this->assertEquals(1, $statistics['ratings_distribution'][3]);
        $this->assertEquals(0, $statistics['ratings_distribution'][2]);
        $this->assertEquals(0, $statistics['ratings_distribution'][1]);
    }

    /**
     * Тест: Рассчитывает распределение отзывов по рейтингу
     * 
     * Проверяем количество отзывов для каждой звезды (1-5)
     */
    #[Test]
    public function test_calculates_ratings_distribution(): void
    {
        // Arrange (Подготовка)
        $product = $this->createProduct();
        
        // Создаем отзывы с разными рейтингами
        Review::factory()->count(10)->create([
            'product_id' => $product->id,
            'rating' => 5,
            'is_approved' => true,
        ]);
        
        Review::factory()->count(7)->create([
            'product_id' => $product->id,
            'rating' => 4,
            'is_approved' => true,
        ]);
        
        Review::factory()->count(5)->create([
            'product_id' => $product->id,
            'rating' => 3,
            'is_approved' => true,
        ]);
        
        Review::factory()->count(2)->create([
            'product_id' => $product->id,
            'rating' => 2,
            'is_approved' => true,
        ]);
        
        Review::factory()->create([
            'product_id' => $product->id,
            'rating' => 1,
            'is_approved' => true,
        ]);

        // Act (Действие)
        $statistics = $this->reviewService->getReviewStatistics($product->id);

        // Assert (Проверка)
        $distribution = $statistics['ratings_distribution'];
        
        $this->assertEquals(10, $distribution[5]);
        $this->assertEquals(7, $distribution[4]);
        $this->assertEquals(5, $distribution[3]);
        $this->assertEquals(2, $distribution[2]);
        $this->assertEquals(1, $distribution[1]);
    }

    /**
     * Тест: Рассчитывает процентное распределение отзывов
     * 
     * Метод getRatingsPercentage() возвращает процент для каждого рейтинга
     */
    #[Test]
    public function test_calculates_ratings_percentage(): void
    {
        // Arrange (Подготовка)
        $product = $this->createProduct();
        
        // Создаем 100 отзывов для удобства подсчета процентов
        Review::factory()->count(50)->create([
            'product_id' => $product->id,
            'rating' => 5,
            'is_approved' => true,
        ]);
        
        Review::factory()->count(30)->create([
            'product_id' => $product->id,
            'rating' => 4,
            'is_approved' => true,
        ]);
        
        Review::factory()->count(10)->create([
            'product_id' => $product->id,
            'rating' => 3,
            'is_approved' => true,
        ]);
        
        Review::factory()->count(10)->create([
            'product_id' => $product->id,
            'rating' => 2,
            'is_approved' => true,
        ]);

        // Act (Действие)
        $percentages = $this->reviewService->getRatingsPercentage($product->id);

        // Assert (Проверка)
        // 50/100 = 50%
        $this->assertEquals(50.0, $percentages[5]);
        
        // 30/100 = 30%
        $this->assertEquals(30.0, $percentages[4]);
        
        // 10/100 = 10%
        $this->assertEquals(10.0, $percentages[3]);
        $this->assertEquals(10.0, $percentages[2]);
        
        // 0/100 = 0%
        $this->assertEquals(0.0, $percentages[1]);
    }

    /**
     * Тест: Возвращает нулевые проценты если нет отзывов
     * 
     * Если у товара нет отзывов, все проценты = 0
     */
    #[Test]
    public function test_returns_zero_percentages_when_no_reviews(): void
    {
        // Arrange (Подготовка)
        $product = $this->createProduct();
        
        // У товара нет отзывов

        // Act (Действие)
        $percentages = $this->reviewService->getRatingsPercentage($product->id);

        // Assert (Проверка)
        $this->assertEquals(0.0, $percentages[5]);
        $this->assertEquals(0.0, $percentages[4]);
        $this->assertEquals(0.0, $percentages[3]);
        $this->assertEquals(0.0, $percentages[2]);
        $this->assertEquals(0.0, $percentages[1]);
    }

    // ==========================================
    // ТЕСТЫ: ПОЛУЧЕНИЕ И ФИЛЬТРАЦИЯ ОТЗЫВОВ
    // ==========================================

    /**
     * Тест: Получает последние отзывы на товар
     * 
     * Метод getLatestReviews() возвращает N последних одобренных отзывов
     */
    #[Test]
    public function test_gets_latest_reviews(): void
    {
        // Arrange (Подготовка)
        $product = $this->createProduct();
        
        // Создаем 10 одобренных отзывов
        Review::factory()->count(10)->create([
            'product_id' => $product->id,
            'is_approved' => true,
        ]);

        // Act (Действие)
        // Получаем 5 последних отзывов
        $latestReviews = $this->reviewService->getLatestReviews($product->id, 5);

        // Assert (Проверка)
        // Должно быть ровно 5 отзывов
        $this->assertCount(5, $latestReviews);
        
        // Проверяем, что связь user загружена
        $this->assertTrue($latestReviews->first()->relationLoaded('user'));
    }

    /**
     * Тест: Получает отзывы с фильтрацией по рейтингу
     * 
     * Можно отфильтровать отзывы по конкретному рейтингу
     */
    #[Test]
    public function test_filters_reviews_by_rating(): void
    {
        // Arrange (Подготовка)
        $product = $this->createProduct();
        
        // Создаем отзывы с разными рейтингами
        Review::factory()->count(5)->create([
            'product_id' => $product->id,
            'rating' => 5,
            'is_approved' => true,
        ]);
        
        Review::factory()->count(3)->create([
            'product_id' => $product->id,
            'rating' => 4,
            'is_approved' => true,
        ]);

        // Act (Действие)
        // Фильтруем только отзывы с рейтингом 5
        $reviews = $this->reviewService->getFilteredReviews($product->id, [
            'rating' => 5,
        ]);

        // Assert (Проверка)
        // Должно быть 5 отзывов с рейтингом 5
        $this->assertEquals(5, $reviews->total());
        
        // Все отзывы должны иметь рейтинг 5
        foreach ($reviews as $review) {
            $this->assertEquals(5, $review->rating);
        }
    }

    /**
     * Тест: Фильтрует только проверенные покупки
     * 
     * Можно показать только отзывы от покупателей, которые купили товар
     */
    #[Test]
    public function test_filters_only_verified_purchases(): void
    {
        // Arrange (Подготовка)
        $product = $this->createProduct();
        
        // Создаем 5 отзывов от проверенных покупателей
        Review::factory()->count(5)->verified()->create([
            'product_id' => $product->id,
        ]);
        
        // Создаем 3 отзыва от непроверенных
        Review::factory()->count(3)->create([
            'product_id' => $product->id,
            'is_verified_purchase' => false,
            'is_approved' => true,
        ]);

        // Act (Действие)
        // Фильтруем только проверенные покупки
        $reviews = $this->reviewService->getFilteredReviews($product->id, [
            'verified_only' => true,
        ]);

        // Assert (Проверка)
        // Должно быть 5 проверенных отзывов
        $this->assertEquals(5, $reviews->total());
        
        // Все отзывы должны быть проверенными
        foreach ($reviews as $review) {
            $this->assertTrue($review->is_verified_purchase);
        }
    }

    /**
     * Тест: Сортирует отзывы по дате (новые первые)
     * 
     * По умолчанию отзывы сортируются от новых к старым
     */
    #[Test]
    public function test_sorts_reviews_by_latest(): void
    {
        // Arrange (Подготовка)
        $product = $this->createProduct();
        
        // Создаем отзывы с разными датами
        $oldReview = Review::factory()->create([
            'product_id' => $product->id,
            'is_approved' => true,
            'created_at' => now()->subDays(10),
        ]);
        
        $newReview = Review::factory()->create([
            'product_id' => $product->id,
            'is_approved' => true,
            'created_at' => now(),
        ]);

        // Act (Действие)
        $reviews = $this->reviewService->getFilteredReviews($product->id, [
            'sort' => 'latest',
        ]);

        // Assert (Проверка)
        // Первым должен быть новый отзыв
        $this->assertEquals($newReview->id, $reviews->first()->id);
    }

    /**
     * Тест: Сортирует отзывы по рейтингу (высокий рейтинг первый)
     * 
     * Можно отсортировать отзывы по рейтингу от высокого к низкому
     */
    #[Test]
    public function test_sorts_reviews_by_highest_rating(): void
    {
        // Arrange (Подготовка)
        $product = $this->createProduct();
        
        // Создаем отзывы с разными рейтингами
        Review::factory()->create([
            'product_id' => $product->id,
            'rating' => 3,
            'is_approved' => true,
        ]);
        
        $highRatedReview = Review::factory()->create([
            'product_id' => $product->id,
            'rating' => 5,
            'is_approved' => true,
        ]);

        // Act (Действие)
        $reviews = $this->reviewService->getFilteredReviews($product->id, [
            'sort' => 'highest_rating',
        ]);

        // Assert (Проверка)
        // Первым должен быть отзыв с рейтингом 5
        $this->assertEquals($highRatedReview->id, $reviews->first()->id);
        $this->assertEquals(5, $reviews->first()->rating);
    }

    /**
     * Тест: Возвращает пагинированный результат
     * 
     * Метод getFilteredReviews() возвращает пагинацию (по 10 на странице)
     */
    #[Test]
    public function test_returns_paginated_reviews(): void
    {
        // Arrange (Подготовка)
        $product = $this->createProduct();
        
        // Создаем 25 одобренных отзывов
        Review::factory()->count(25)->create([
            'product_id' => $product->id,
            'is_approved' => true,
        ]);

        // Act (Действие)
        $reviews = $this->reviewService->getFilteredReviews($product->id);

        // Assert (Проверка)
        // Первая страница должна содержать 10 отзывов
        $this->assertCount(10, $reviews->items());
        
        // Всего 25 отзывов
        $this->assertEquals(25, $reviews->total());
        
        // 3 страницы (25 / 10 = 2.5, округляем до 3)
        $this->assertEquals(3, $reviews->lastPage());
    }
}
