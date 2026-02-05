<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Модель подписчика на email рассылку
 * 
 * Хранит email-адреса подписчиков на новости магазина.
 * Каждый подписчик имеет уникальный токен для отписки.
 * Форма подписки доступна в футере сайта.
 */
class Subscriber extends Model
{
    use HasFactory;

    /**
     * Поля, которые можно массово заполнять
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'email',           // Email подписчика (уникальный)
        'token',           // Уникальный токен для отписки
        'is_active',       // Активна ли подписка
        'subscribed_at',   // Дата и время подписки
        'unsubscribed_at', // Дата и время отписки (если отписался)
    ];

    /**
     * Преобразование типов атрибутов
     * 
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',         // Активна - булев
            'subscribed_at' => 'datetime',    // Дата подписки - объект Carbon
            'unsubscribed_at' => 'datetime',  // Дата отписки - объект Carbon
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * События модели
     * 
     * Автоматически генерируем токен и дату подписки при создании
     */
    protected static function boot()
    {
        parent::boot();

        // При создании нового подписчика генерируем токен и устанавливаем дату
        static::creating(function ($subscriber) {
            if (empty($subscriber->token)) {
                $subscriber->token = Str::random(64);
            }
            
            if (empty($subscriber->subscribed_at)) {
                $subscriber->subscribed_at = now();
            }
        });
    }

    /**
     * Проверить, активна ли подписка
     * 
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Отписать подписчика
     * 
     * Устанавливает is_active = false и записывает дату отписки
     * 
     * @return bool
     */
    public function unsubscribe(): bool
    {
        $this->is_active = false;
        $this->unsubscribed_at = now();
        
        return $this->save();
    }

    /**
     * Повторно подписать пользователя
     * 
     * Активирует подписку и обнуляет дату отписки
     * 
     * @return bool
     */
    public function resubscribe(): bool
    {
        $this->is_active = true;
        $this->unsubscribed_at = null;
        $this->subscribed_at = now();
        
        return $this->save();
    }

    /**
     * Получить URL для отписки
     * 
     * Генерирует ссылку для отписки с токеном
     * 
     * @return string
     */
    public function getUnsubscribeUrlAttribute(): string
    {
        return route('newsletter.unsubscribe', ['token' => $this->token]);
    }

    /**
     * Scope для получения только активных подписчиков
     * 
     * Использование: Subscriber::active()->get()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope для получения неактивных подписчиков
     * 
     * Использование: Subscriber::inactive()->get()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope для получения недавно подписавшихся
     * 
     * Использование: Subscriber::recent(7)->get() // за последние 7 дней
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days Количество дней
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('subscribed_at', '>=', now()->subDays($days));
    }
}
