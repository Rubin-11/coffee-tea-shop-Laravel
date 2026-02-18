<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Модель пользователя
 * 
 * Представляет зарегистрированных пользователей системы.
 * Пользователи могут быть покупателями (оставлять отзывы)
 * или авторами (писать статьи в блог, обычно администраторы)
 *
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string|null $phone
 * @property string $password
 * @property bool $is_admin
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Review> $approvedReviews
 * @property-read int|null $approved_reviews_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BlogPost> $blogPosts
 * @property-read int|null $blog_posts_count
 * @property-read string $full_name
 * @property-read int $published_posts_count
 * @property-read int|null $reviews_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BlogPost> $publishedBlogPosts
 * @property-read int|null $published_blog_posts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Review> $reviews
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @mixin \Eloquent
 */
final class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Поля, которые можно массово заполнять
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',  // Имя пользователя
        'last_name',   // Фамилия пользователя
        'email',       // Email (уникальный)
        'phone',       // Номер телефона
        'password',    // Хешированный пароль
        'is_admin',    // Является ли администратором
        'is_active',   // Активен ли аккаунт
    ];

    /**
     * Поля, которые должны быть скрыты при сериализации
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',  // Скрываем пароль
    ];

    /**
     * Преобразование типов атрибутов
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',   // Автоматическое хеширование пароля
            'is_admin' => 'boolean',  // Флаг администратора
            'is_active' => 'boolean', // Флаг активности
        ];
    }

    /**
     * Получить все заказы пользователя
     * 
     * Возвращает все заказы, сделанные этим пользователем
     * 
     * @return HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Получить позиции корзины пользователя
     * 
     * Возвращает товары в корзине пользователя
     * 
     * @return HasMany
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Получить адреса доставки пользователя
     * 
     * Возвращает все сохраненные адреса доставки
     * 
     * @return HasMany
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Получить все отзывы пользователя
     * 
     * Возвращает отзывы на товары, оставленные этим пользователем
     * 
     * @return HasMany
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Получить одобренные отзывы пользователя
     * 
     * Отзывы, прошедшие модерацию
     * 
     * @return HasMany
     */
    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('is_approved', true);
    }

    /**
     * Получить все статьи блога, написанные пользователем
     * 
     * Обычно используется для администраторов-авторов
     * 
     * @return HasMany
     */
    public function blogPosts(): HasMany
    {
        return $this->hasMany(BlogPost::class, 'author_id');
    }

    /**
     * Получить опубликованные статьи пользователя
     * 
     * @return HasMany
     */
    public function publishedBlogPosts(): HasMany
    {
        return $this->blogPosts()->where('is_published', true);
    }

    /**
     * Проверить, является ли пользователь автором блога
     * 
     * @return bool
     */
    public function isBlogAuthor(): bool
    {
        return $this->blogPosts()->exists();
    }

    /**
     * Получить количество отзывов пользователя
     * 
     * @return int
     */
    public function getReviewsCountAttribute(): int
    {
        return $this->reviews()->count();
    }

    /**
     * Получить количество опубликованных статей
     * 
     * @return int
     */
    public function getPublishedPostsCountAttribute(): int
    {
        return $this->publishedBlogPosts()->count();
    }

    /**
     * Получить полное имя пользователя
     * 
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Проверить, является ли пользователь администратором
     * 
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    /**
     * Получить адрес доставки по умолчанию
     * 
     * Возвращает адрес, отмеченный как основной (is_default = true).
     * Если основной адрес не задан, возвращает null.
     * 
     * @return \App\Models\Address|null
     */
    public function getDefaultAddress(): ?Address
    {
        return $this->addresses()->where('is_default', true)->first();
    }
}
