<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Модель тега товара
 *
 * Теги используются для маркировки товаров специальными метками.
 * Примеры: "Новинка", "Хит продаж", "Акция", "Органический", "Премиум"
 * Один товар может иметь несколько тегов, один тег может быть у многих товаров
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Product> $availableProducts
 * @property-read int|null $available_products_count
 * @property-read int|null $products_count
 * @property-read Collection<int, Product> $products
 *
 * @method static \Database\Factories\TagFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
final class Tag extends Model
{
    use HasFactory;

    /**
     * Поля, которые можно массово заполнять
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',    // Название тега (например: "Новинка", "Хит продаж")
        'slug',    // URL-дружественное название (например: "new", "bestseller")
    ];

    /**
     * Преобразование типов атрибутов
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Получить все товары с этим тегом
     *
     * Связь многие-ко-многим через таблицу product_tag
     * Например, тег "Новинка" может быть у 10 разных товаров
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_tag')
            ->withTimestamps(); // Сохраняем время создания связи
    }

    /**
     * Получить только доступные товары с этим тегом
     */
    public function availableProducts(): BelongsToMany
    {
        return $this->products()->where('is_available', true);
    }

    /**
     * Получить количество товаров с этим тегом
     */
    public function getProductsCountAttribute(): int
    {
        return $this->products()->count();
    }
}
