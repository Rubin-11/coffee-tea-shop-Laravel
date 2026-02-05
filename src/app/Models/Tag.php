<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Модель тега товара
 * 
 * Теги используются для маркировки товаров специальными метками.
 * Примеры: "Новинка", "Хит продаж", "Акция", "Органический", "Премиум"
 * Один товар может иметь несколько тегов, один тег может быть у многих товаров
 */
class Tag extends Model
{
    use HasFactory;

    /**
     * Поля, которые можно массово заполнять
     * 
     * @var array<int, string>
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
     * 
     * @return BelongsToMany
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_tag')
                    ->withTimestamps(); // Сохраняем время создания связи
    }

    /**
     * Получить только доступные товары с этим тегом
     * 
     * @return BelongsToMany
     */
    public function availableProducts(): BelongsToMany
    {
        return $this->products()->where('is_available', true);
    }

    /**
     * Получить количество товаров с этим тегом
     * 
     * @return int
     */
    public function getProductsCountAttribute(): int
    {
        return $this->products()->count();
    }
}
