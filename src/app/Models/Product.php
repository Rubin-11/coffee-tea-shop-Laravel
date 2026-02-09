<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Модель товара (кофе, чай, аксессуары)
 * 
 * Основная модель для товаров в магазине.
 * Содержит всю информацию: цену, вес, рейтинг, характеристики (горчинка, кислинка).
 * Поддерживает мягкое удаление (soft deletes) для сохранения истории.
 *
 * @property int $id
 * @property int $category_id
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property string|null $long_description
 * @property numeric $price
 * @property numeric|null $old_price
 * @property int $weight
 * @property string $sku
 * @property int $stock
 * @property numeric $rating
 * @property-read int|null $reviews_count
 * @property int|null $bitterness_percent
 * @property int|null $acidity_percent
 * @property bool $is_featured
 * @property bool $is_available
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Review> $approvedReviews
 * @property-read int|null $approved_reviews_count
 * @property-read \App\Models\Category $category
 * @property-read int|null $discount_percent
 * @property-read float|null $savings
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductImage> $images
 * @property-read int|null $images_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductImage> $primaryImage
 * @property-read int|null $primary_image_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Review> $reviews
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Tag> $tags
 * @property-read int|null $tags_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product available()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product byCategory(int $categoryId)
 * @method static \Database\Factories\ProductFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product featured()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product highestRated()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product inStockScope()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product priceRange(float $min, float $max)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereAcidityPercent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereBitternessPercent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereIsAvailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereIsFeatured($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereLongDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereMetaDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereMetaTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereOldPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereReviewsCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereWeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product withoutTrashed()
 * @mixin \Eloquent
 */
final class Product extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Поля, которые можно массово заполнять
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'category_id',          // ID категории товара
        'name',                 // Название (например: "Colombia Supremo", "Кения АА")
        'slug',                 // URL-дружественное название
        'description',          // Краткое описание товара
        'long_description',     // Подробное описание
        'price',                // Текущая цена (например: 450.00)
        'old_price',            // Старая цена для отображения скидки
        'weight',               // Вес в граммах (250, 500, 1000)
        'sku',                  // Артикул товара (уникальный код)
        'stock',                // Количество на складе
        'rating',               // Средний рейтинг (от 0 до 5.00)
        'reviews_count',        // Количество отзывов
        'bitterness_percent',   // Процент горчинки (0, 2, 4, 6, 8, 10)
        'acidity_percent',      // Процент кислинки
        'is_featured',          // Рекомендуемый товар (для главной страницы)
        'is_available',         // Доступен ли для заказа
        'meta_title',           // SEO заголовок
        'meta_description',     // SEO описание
    ];

    /**
     * Атрибуты, которые должны быть скрыты в массивах и JSON
     * 
     * @var array<int, string>
     */
    protected $hidden = [
        'deleted_at',  // Скрываем дату удаления при сериализации
    ];

    /**
     * Преобразование типов атрибутов
     * 
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',              // Цена с 2 знаками после запятой
            'old_price' => 'decimal:2',          // Старая цена с 2 знаками
            'rating' => 'decimal:2',             // Рейтинг с 2 знаками (например: 4.75)
            'weight' => 'integer',               // Вес - целое число
            'stock' => 'integer',                // Количество - целое число
            'reviews_count' => 'integer',        // Количество отзывов - целое
            'bitterness_percent' => 'integer',   // Процент горчинки - целое
            'acidity_percent' => 'integer',      // Процент кислинки - целое
            'is_featured' => 'boolean',          // Рекомендуемый - булев
            'is_available' => 'boolean',         // Доступен - булев
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Получить категорию товара
     * 
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Получить все изображения товара
     * 
     * Товар может иметь несколько изображений для галереи
     * 
     * @return HasMany
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /**
     * Получить главное изображение товара
     * 
     * Используется для отображения в списке товаров
     * 
     * @return HasMany
     */
    public function primaryImage(): HasMany
    {
        return $this->hasMany(ProductImage::class)->where('is_primary', true);
    }

    /**
     * Получить все теги товара
     * 
     * Связь многие-ко-многим. Товар может иметь несколько тегов:
     * "Новинка", "Акция", "Органический"
     * 
     * @return BelongsToMany
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'product_tag')
                    ->withTimestamps();
    }

    /**
     * Получить все отзывы на товар
     * 
     * @return HasMany
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Получить только одобренные отзывы
     * 
     * Отфильтровывает отзывы, прошедшие модерацию
     * 
     * @return HasMany
     */
    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('is_approved', true);
    }

    /**
     * Проверить, есть ли товар в наличии
     * 
     * @return bool
     */
    public function inStock(): bool
    {
        return $this->stock > 0;
    }

    /**
     * Проверить, есть ли скидка на товар
     * 
     * @return bool
     */
    public function hasDiscount(): bool
    {
        return !is_null($this->old_price) && $this->old_price > $this->price;
    }

    /**
     * Получить размер скидки в процентах
     * 
     * @return int|null
     */
    public function getDiscountPercentAttribute(): ?int
    {
        if (!$this->hasDiscount()) {
            return null;
        }

        return (int) round((($this->old_price - $this->price) / $this->old_price) * 100);
    }

    /**
     * Получить сумму экономии при скидке
     * 
     * @return float|null
     */
    public function getSavingsAttribute(): ?float
    {
        if (!$this->hasDiscount()) {
            return null;
        }

        return round($this->old_price - $this->price, 2);
    }

    /**
     * Scope для получения только доступных товаров
     * 
     * Использование: Product::available()->get()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope для получения рекомендуемых товаров
     * 
     * Использование: Product::featured()->get()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope для получения товаров в наличии
     * 
     * Использование: Product::inStock()->get()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInStockScope($query)
    {
        return $query->where('stock', '>', 0);
    }

    /**
     * Scope для фильтрации по категории
     * 
     * Использование: Product::byCategory($categoryId)->get()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $categoryId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope для фильтрации по диапазону цен
     * 
     * Использование: Product::priceRange(200, 500)->get()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $min
     * @param float $max
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePriceRange($query, float $min, float $max)
    {
        return $query->whereBetween('price', [$min, $max]);
    }

    /**
     * Scope для сортировки по рейтингу
     * 
     * Использование: Product::highestRated()->get()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHighestRated($query)
    {
        return $query->orderBy('rating', 'desc');
    }
}
