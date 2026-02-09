<?php

declare(strict_types=1);

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
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string $excerpt
 * @property string $content
 * @property string|null $featured_image
 * @property int $author_id
 * @property string|null $category
 * @property int $views_count
 * @property bool $is_published
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $author
 * @property-read string|null $image_url
 * @property-read int $reading_time
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost byCategory(string $category)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost draft()
 * @method static \Database\Factories\BlogPostFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost latestFirst()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost popular()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost published()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost whereAuthorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost whereExcerpt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost whereFeaturedImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost whereIsPublished($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost whereMetaDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost whereMetaTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlogPost whereViewsCount($value)
 * @mixin \Eloquent
 */
final class BlogPost extends Model
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
