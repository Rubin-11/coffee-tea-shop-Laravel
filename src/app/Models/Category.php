<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Модель категории товаров
 *
 * Представляет категории и подкатегории товаров в магазине.
 * Поддерживает вложенную структуру категорий через parent_id.
 * Например: "Кофе" -> "Арабика" -> "Колумбия"
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $image
 * @property int|null $parent_id
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Category> $activeChildren
 * @property-read int|null $active_children_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Product> $availableProducts
 * @property-read int|null $available_products_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Category> $children
 * @property-read int|null $children_count
 * @property-read Category|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Product> $products
 * @property-read int|null $products_count
 *
 * @method static \Database\Factories\CategoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
final class Category extends Model
{
    use HasFactory;

    /**
     * Поля, которые можно массово заполнять
     *
     * @var list<string>
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
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Получить все дочерние категории (подкатегории)
     *
     * Например, для "Кофе в зернах" вернет ["Арабика", "Робуста", "Эспрессо-смеси"]
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Получить все активные дочерние категории
     *
     * Отфильтрованный список только активных подкатегорий
     */
    public function activeChildren(): HasMany
    {
        return $this->children()->where('is_active', true);
    }

    /**
     * Получить все товары в этой категории
     *
     * Возвращает все продукты, принадлежащие данной категории
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Получить только доступные товары в категории
     *
     * Фильтрует товары по is_available = true
     */
    public function availableProducts(): HasMany
    {
        return $this->products()->where('is_available', true);
    }

    /**
     * Scope для фильтрации только активных категорий
     *
     * Использование: Category::active()->get()
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Получить всех предков категории (родитель, родитель родителя и т.д.)
     *
     * Рекурсивно обходит цепочку parent до корневой категории.
     * Возвращает коллекцию от ближайшего родителя к самому дальнему предку.
     *
     * @return Collection<int, Category>
     */
    public function getAllAncestors(): Collection
    {
        $ancestors = collect();
        $current = $this->parent;

        while ($current !== null) {
            $ancestors->push($current);
            $current = $current->parent;
        }

        return $ancestors;
    }

    /**
     * Получить всех потомков категории (дети, дети детей и т.д.)
     *
     * Рекурсивно обходит дерево дочерних категорий.
     * Возвращает плоскую коллекцию всех подкатегорий любого уровня вложенности.
     *
     * @return Collection<int, Category>
     */
    public function getAllDescendants(): Collection
    {
        $descendants = collect();

        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getAllDescendants());
        }

        return $descendants;
    }

    /**
     * Проверить, является ли категория главной (без родителя)
     */
    public function isParent(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Проверить, имеет ли категория дочерние элементы
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Получить полный URL изображения категории
     *
     * Если поле image заполнено, возвращает публичный URL к файлу через storage
     * Если поле image пустое, возвращает плейсхолдер
     */
    public function getImageUrlAttribute(): string
    {
        // Если изображение задано в БД
        if ($this->image) {
            // Используем asset() для формирования URL
            // storage/categories/имя-файла.png → http://домен/storage/categories/имя-файла.png
            return asset('storage/'.$this->image);
        }

        // Если изображения нет - возвращаем плейсхолдер
        return asset('images/placeholder-category.png');
    }
}
