<?php

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
 */
class User extends Authenticatable
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
}
