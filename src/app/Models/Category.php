<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Модель категории товаров
 * 
 * Представляет категории и подкатегории товаров в магазине.
 * Поддерживает вложенную структуру категорий через parent_id.
 * Например: "Кофе" -> "Арабика" -> "Колумбия"
 */
class Category extends Model
{
    use HasFactory;

    /**
     * Поля, которые можно массово заполнять
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'name',              // Название категории (например: "Кофе в зернах")
        'slug',              // URL-дружественное название (например: "coffee-beans")
        'description',       // Описание категории
        'image',             // Путь к изображению категории
        'parent_id',         // ID родительской категории (null для главных категорий)
        'sort_order',        // Порядок сортировки при отображении
        'is_active',         // Активна ли категория (для включения/выключения)
    ];

    /**
     * Преобразование типов атрибутов
     * 
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',    // Преобразуем в булев тип
            'sort_order' => 'integer',   // Преобразуем в целое число
            'created_at' => 'datetime',  // Преобразуем в объект Carbon
            'updated_at' => 'datetime',  // Преобразуем в объект Carbon
        ];
    }

    /**
     * Получить родительскую категорию
     * 
     * Например, для "Арабика" родителем будет "Кофе в зернах"
     * 
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Получить все дочерние категории (подкатегории)
     * 
     * Например, для "Кофе в зернах" вернет ["Арабика", "Робуста", "Эспрессо-смеси"]
     * 
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Получить все активные дочерние категории
     * 
     * Отфильтрованный список только активных подкатегорий
     * 
     * @return HasMany
     */
    public function activeChildren(): HasMany
    {
        return $this->children()->where('is_active', true);
    }

    /**
     * Получить все товары в этой категории
     * 
     * Возвращает все продукты, принадлежащие данной категории
     * 
     * @return HasMany
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Получить только доступные товары в категории
     * 
     * Фильтрует товары по is_available = true
     * 
     * @return HasMany
     */
    public function availableProducts(): HasMany
    {
        return $this->products()->where('is_available', true);
    }

    /**
     * Проверить, является ли категория главной (без родителя)
     * 
     * @return bool
     */
    public function isParent(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Проверить, имеет ли категория дочерние элементы
     * 
     * @return bool
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }
}
