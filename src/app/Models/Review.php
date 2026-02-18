<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель отзыва на товар
 * 
 * Хранит отзывы покупателей на товары: рейтинг (1-5 звезд),
 * текст отзыва, достоинства и недостатки.
 * Отзывы проходят модерацию перед публикацией (is_approved).
 * Один пользователь может оставить только один отзыв на товар.
 *
 * @property int $id
 * @property int $product_id
 * @property int $user_id
 * @property int $rating
 * @property string $comment
 * @property string|null $pros
 * @property string|null $cons
 * @property bool $is_verified_purchase
 * @property bool $is_approved
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $rating_text
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review approved()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review byRating(int $rating)
 * @method static \Database\Factories\ReviewFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review latestFirst()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review negative()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review positive()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review verified()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereCons($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereIsApproved($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereIsVerifiedPurchase($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review wherePros($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereUserId($value)
 * @mixin \Eloquent
 */
final class Review extends Model
{
    use HasFactory;

    /**
     * Поля, которые можно массово заполнять
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',           // ID товара, на который оставлен отзыв
        'user_id',              // ID пользователя, оставившего отзыв
        'rating',               // Рейтинг от 1 до 5 звезд
        'comment',              // Основной текст отзыва
        'pros',                 // Достоинства товара
        'cons',                 // Недостатки товара
        'is_verified_purchase', // Проверенная покупка (купил в нашем магазине)
        'is_approved',          // Одобрен модератором (прошел проверку)
    ];

    /**
     * Преобразование типов атрибутов
     * 
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',               // Рейтинг - целое число (1-5)
            'is_verified_purchase' => 'boolean', // Проверенная покупка - булев
            'is_approved' => 'boolean',          // Одобрен - булев
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Получить товар, к которому относится отзыв
     * 
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Получить пользователя, который оставил отзыв
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Проверить, является ли отзыв положительным
     * 
     * Положительным считается отзыв с рейтингом 4 или 5 звезд
     * 
     * @return bool
     */
    public function isPositive(): bool
    {
        return $this->rating >= 4;
    }

    /**
     * Проверить, является ли отзыв отрицательным
     * 
     * Отрицательным считается отзыв с рейтингом 1 или 2 звезды
     * 
     * @return bool
     */
    public function isNegative(): bool
    {
        return $this->rating <= 2;
    }

    /**
     * Получить текстовое представление рейтинга
     * 
     * @return string
     */
    public function getRatingTextAttribute(): string
    {
        return match($this->rating) {
            5 => 'Отлично',
            4 => 'Хорошо',
            3 => 'Нормально',
            2 => 'Плохо',
            1 => 'Ужасно',
            default => 'Не указано',
        };
    }

    /**
     * Scope для получения только одобренных отзывов
     * 
     * Использование: Review::approved()->get()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope для получения отзывов от проверенных покупателей
     * 
     * Использование: Review::verified()->get()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified_purchase', true);
    }

    /**
     * Scope для получения положительных отзывов
     * 
     * Использование: Review::positive()->get()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePositive($query)
    {
        return $query->where('rating', '>=', 4);
    }

    /**
     * Scope для получения отрицательных отзывов
     * 
     * Использование: Review::negative()->get()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNegative($query)
    {
        return $query->where('rating', '<=', 2);
    }

    /**
     * Scope для сортировки по новизне
     * 
     * Использование: Review::latest()->get()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLatestFirst($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope для фильтрации по рейтингу
     * 
     * Использование: Review::byRating(5)->get()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $rating
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Boot модели для автоматических действий
     *
     * Выполняет валидацию рейтинга при создании и обновлении отзыва.
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        // Проверяем валидность рейтинга при создании и обновлении
        static::saving(function (Review $review) {
            if ($review->rating < 1 || $review->rating > 5) {
                throw new \InvalidArgumentException(
                    'Рейтинг отзыва должен быть от 1 до 5. Получено: ' . $review->rating
                );
            }
        });
    }
}
