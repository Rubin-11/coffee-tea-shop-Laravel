<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель статьи блога
 * 
 * Представляет статьи в блоге магазина.
 * Темы статей: "Здоровое питание", "Рецепты с кофе", 
 * "Гид по выбору кофе", "История происхождения"
 * Статьи имеют автора (пользователя) и могут быть черновиками или опубликованными.
 */
class BlogPost extends Model
{
    use HasFactory;

    /**
     * Поля, которые можно массово заполнять
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'title',              // Заголовок статьи
        'slug',               // URL-дружественное название
        'excerpt',            // Краткое описание (анонс статьи)
        'content',            // Полный текст статьи
        'featured_image',     // Главное изображение статьи
        'author_id',          // ID автора (пользователя)
        'category',           // Категория статьи ("Здоровое питание", "Рецепты")
        'views_count',        // Количество просмотров
        'is_published',       // Опубликована ли статья
        'published_at',       // Дата и время публикации
        'meta_title',         // SEO заголовок
        'meta_description',   // SEO описание
    ];

    /**
     * Преобразование типов атрибутов
     * 
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'views_count' => 'integer',      // Количество просмотров - целое число
            'is_published' => 'boolean',     // Опубликована - булев
            'published_at' => 'datetime',    // Дата публикации - объект Carbon
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Получить автора статьи
     * 
     * Автором является зарегистрированный пользователь (обычно администратор)
     * 
     * @return BelongsTo
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Проверить, опубликована ли статья
     * 
     * @return bool
     */
    public function isPublished(): bool
    {
        return $this->is_published && 
               !is_null($this->published_at) && 
               $this->published_at->isPast();
    }

    /**
     * Проверить, является ли статья черновиком
     * 
     * @return bool
     */
    public function isDraft(): bool
    {
        return !$this->is_published;
    }

    /**
     * Увеличить счетчик просмотров
     * 
     * Вызывается при открытии статьи
     * 
     * @return void
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Получить время чтения статьи (в минутах)
     * 
     * Рассчитывается исходя из количества слов в контенте
     * Средняя скорость чтения: 200 слов в минуту
     * 
     * @return int
     */
    public function getReadingTimeAttribute(): int
    {
        $wordCount = str_word_count(strip_tags($this->content));
        $minutes = ceil($wordCount / 200);
        
        return max(1, $minutes); // Минимум 1 минута
    }

    /**
     * Получить URL изображения статьи
     * 
     * @return string|null
     */
    public function getImageUrlAttribute(): ?string
    {
        if (is_null($this->featured_image)) {
            return null;
        }

        // Если путь начинается с http/https, возвращаем как есть
        if (str_starts_with($this->featured_image, 'http')) {
            return $this->featured_image;
        }
        
        // Иначе добавляем базовый путь из public/storage
        return asset('storage/' . $this->featured_image);
    }

    /**
     * Scope для получения только опубликованных статей
     * 
     * Использование: BlogPost::published()->get()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
    }

    /**
     * Scope для получения черновиков
     * 
     * Использование: BlogPost::draft()->get()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDraft($query)
    {
        return $query->where('is_published', false);
    }

    /**
     * Scope для фильтрации по категории
     * 
     * Использование: BlogPost::byCategory('Здоровое питание')->get()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $category
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope для сортировки по популярности
     * 
     * Использование: BlogPost::popular()->get()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePopular($query)
    {
        return $query->orderBy('views_count', 'desc');
    }

    /**
     * Scope для получения последних статей
     * 
     * Использование: BlogPost::latest()->get()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLatestFirst($query)
    {
        return $query->orderBy('published_at', 'desc');
    }
}
