<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Unit тесты для модели Review
 *
 * Тестируем:
 * - Валидация рейтинга (1-5)
 * - Query scopes (approved, verified)
 * - Связи с товаром и пользователем
 */
#[Group('unit')]
#[Group('models')]
#[Group('review')]
final class ReviewTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // ТЕСТЫ: Валидация рейтинга
    // ==========================================

    /**
     * Тест: рейтинг должен быть от 1 до 5
     *
     * Отзывы с валидным рейтингом (1-5) сохраняются успешно.
     */
    public function test_rating_is_between_1_and_5(): void
    {
        $product = $this->createProduct();

        // Создаем отзывы с каждым допустимым рейтингом (нужен уникальный user на продукт)
        foreach ([1, 2, 3, 4, 5] as $rating) {
            $user = $this->createUser();
            $review = Review::factory()->create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'rating' => $rating,
                'comment' => 'Тестовый отзыв для рейтинга ' . $rating,
            ]);

            $this->assertEquals($rating, $review->rating);
        }
    }

    /**
     * Тест: ошибка при невалидном рейтинге
     *
     * При сохранении отзыва с рейтингом < 1 или > 5 выбрасывается InvalidArgumentException.
     */
    public function test_throws_exception_for_invalid_rating(): void
    {
        $product = $this->createProduct();
        $user = $this->createUser();

        // Рейтинг 0 - невалидный
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Рейтинг отзыва должен быть от 1 до 5');

        Review::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => 0,
            'comment' => 'Тест невалидного рейтинга',
        ]);
    }

    /**
     * Тест: ошибка при рейтинге больше 5
     */
    public function test_throws_exception_when_rating_exceeds_five(): void
    {
        $product = $this->createProduct();
        $user = $this->createUser();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Рейтинг отзыва должен быть от 1 до 5');

        Review::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => 6,
            'comment' => 'Тест рейтинга > 5',
        ]);
    }

    // ==========================================
    // ТЕСТЫ: Query Scopes
    // ==========================================

    /**
     * Тест: scope approved фильтрует только одобренные отзывы
     */
    public function test_approved_scope_filters_approved_reviews(): void
    {
        $product = $this->createProduct();
        $user = $this->createUser();

        Review::factory()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'is_approved' => true,
        ]);
        Review::factory()->create([
            'product_id' => $product->id,
            'user_id' => User::factory()->create()->id,
            'is_approved' => false,
        ]);

        $approvedReviews = Review::approved()->get();

        $this->assertCount(1, $approvedReviews);
        $this->assertTrue($approvedReviews->first()->is_approved);
    }

    /**
     * Тест: scope verified фильтрует отзывы от проверенных покупателей
     */
    public function test_verified_scope_filters_verified_purchases(): void
    {
        $product = $this->createProduct();

        Review::factory()->verified()->create([
            'product_id' => $product->id,
            'user_id' => $this->createUser()->id,
        ]);
        Review::factory()->create([
            'product_id' => $product->id,
            'user_id' => $this->createUser()->id,
            'is_verified_purchase' => false,
        ]);

        $verifiedReviews = Review::verified()->get();

        $this->assertCount(1, $verifiedReviews);
        $this->assertTrue($verifiedReviews->first()->is_verified_purchase);
    }

    // ==========================================
    // ТЕСТЫ: Связи
    // ==========================================

    /**
     * Тест: отзыв принадлежит товару
     */
    public function test_belongs_to_product(): void
    {
        $product = $this->createProduct(['name' => 'Кофе Арабика']);
        $user = $this->createUser();

        $review = Review::factory()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);

        $review->load('product');

        $this->assertNotNull($review->product);
        $this->assertEquals($product->id, $review->product->id);
        $this->assertEquals('Кофе Арабика', $review->product->name);
    }

    /**
     * Тест: отзыв принадлежит пользователю
     */
    public function test_belongs_to_user(): void
    {
        $product = $this->createProduct();
        $user = $this->createUser(['first_name' => 'Иван', 'last_name' => 'Иванов']);

        $review = Review::factory()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);

        $review->load('user');

        $this->assertNotNull($review->user);
        $this->assertEquals($user->id, $review->user->id);
        $this->assertEquals('Иван', $review->user->first_name);
    }
}
