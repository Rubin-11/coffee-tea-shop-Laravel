<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель изображения товара
 * 
 * Хранит информацию об изображениях товаров.
 * Один товар может иметь несколько изображений (галерея).
 * Одно из изображений помечается как главное (is_primary = true)
 */
class ProductImage extends Model
{
    use HasFactory;

    /**
     * Поля, которые можно массово заполнять
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',    // ID товара, которому принадлежит изображение
        'image_path',    // Путь к файлу изображения (например: "products/coffee-1.jpg")
        'alt_text',      // Альтернативный текст для SEO и доступности
        'sort_order',    // Порядок отображения в галерее (0 - первое)
        'is_primary',    // Главное изображение товара (показывается в списке)
    ];

    /**
     * Преобразование типов атрибутов
     * 
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',   // Преобразуем в булев тип
            'sort_order' => 'integer',   // Преобразуем в целое число
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Получить товар, которому принадлежит изображение
     * 
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Получить полный URL изображения
     * 
     * Добавляет базовый URL к пути изображения
     * 
     * @return string
     */
    public function getUrlAttribute(): string
    {
        // Если путь начинается с http/https, возвращаем как есть
        if (str_starts_with($this->image_path, 'http')) {
            return $this->image_path;
        }
        
        // Иначе добавляем базовый путь из public/storage
        return asset('storage/' . $this->image_path);
    }

    /**
     * Scope для получения только главных изображений
     * 
     * Использование: ProductImage::primary()->get()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope для сортировки по порядку отображения
     * 
     * Использование: ProductImage::ordered()->get()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
