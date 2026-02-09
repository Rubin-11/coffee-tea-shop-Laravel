<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Модель заказа
 * 
 * Представляет заказ клиента в интернет-магазине.
 * Содержит информацию о покупателе, способах доставки и оплаты,
 * статусах обработки, а также связь с товарами в заказе.
 * Поддерживает как авторизованных пользователей, так и гостевые заказы.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $order_number
 * @property string $customer_name
 * @property string $customer_email
 * @property string $customer_phone
 * @property string $delivery_address
 * @property string $delivery_method
 * @property string $payment_method
 * @property numeric $subtotal
 * @property numeric $delivery_cost
 * @property numeric $discount
 * @property numeric $total
 * @property string $status
 * @property string $payment_status
 * @property string|null $notes
 * @property string|null $admin_notes
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property \Illuminate\Support\Carbon|null $shipped_at
 * @property \Illuminate\Support\Carbon|null $delivered_at
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $delivery_method_text
 * @property-read string $payment_method_text
 * @property-read string $payment_status_text
 * @property-read string $status_text
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OrderItem> $items
 * @property-read int|null $items_count
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order byUser(int $userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order cancelled()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order delivered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order paid()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order recent()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereAdminNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCancelledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCustomerEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCustomerName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCustomerPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereDeliveredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereDeliveryAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereDeliveryCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereDeliveryMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereOrderNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order wherePaymentStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereShippedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereUserId($value)
 * @mixin \Eloquent
 */
final class Order extends Model
{
    use HasFactory;

    /**
     * Поля, которые можно массово заполнять
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',              // ID пользователя (null для гостевых заказов)
        'order_number',         // Уникальный номер заказа (ORD-2026-00001)
        'customer_name',        // Имя покупателя
        'customer_email',       // Email покупателя
        'customer_phone',       // Телефон покупателя
        'delivery_address',     // Полный адрес доставки
        'delivery_method',      // Способ доставки (courier, pickup, post)
        'payment_method',       // Способ оплаты (cash, card, online)
        'subtotal',             // Сумма товаров без доставки
        'delivery_cost',        // Стоимость доставки
        'discount',             // Размер скидки
        'total',                // Итоговая сумма к оплате
        'status',               // Статус заказа (pending, processing, paid, shipped, delivered, cancelled)
        'payment_status',       // Статус оплаты (pending, paid, failed)
        'notes',                // Комментарий клиента к заказу
        'admin_notes',          // Внутренние заметки администратора
        'paid_at',              // Дата и время оплаты
        'shipped_at',           // Дата и время отправки
        'delivered_at',         // Дата и время доставки
        'cancelled_at',         // Дата и время отмены
    ];

    /**
     * Атрибуты, которые должны быть скрыты в массивах и JSON
     * 
     * @var array<int, string>
     */
    protected $hidden = [
        'admin_notes',  // Скрываем внутренние заметки от клиентов
    ];

    /**
     * Преобразование типов атрибутов
     * 
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',          // Сумма товаров с 2 знаками после запятой
            'delivery_cost' => 'decimal:2',     // Стоимость доставки с 2 знаками
            'discount' => 'decimal:2',          // Скидка с 2 знаками
            'total' => 'decimal:2',             // Итоговая сумма с 2 знаками
            'paid_at' => 'datetime',            // Преобразуем в объект Carbon
            'shipped_at' => 'datetime',         
            'delivered_at' => 'datetime',           
            'cancelled_at' => 'datetime',       
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Получить пользователя, сделавшего заказ
     * 
     * Для гостевых заказов вернет null
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Получить все позиции (товары) в заказе
     * 
     * @return HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Рассчитать общую сумму заказа
     * 
     * Вычисляет: сумма товаров + доставка - скидка
     * 
     * @return float
     */
    public function calculateTotal(): float
    {
        return round(
            (float) $this->subtotal + (float) $this->delivery_cost - (float) $this->discount,
            2
        );
    }

    /**
     * Проверить, оплачен ли заказ
     * 
     * @return bool
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid' || !is_null($this->paid_at);
    }

    /**
     * Проверить, доставлен ли заказ
     * 
     * @return bool
     */
    public function isDelivered(): bool
    {
        return $this->status === 'delivered' && !is_null($this->delivered_at);
    }

    /**
     * Проверить, отменен ли заказ
     * 
     * @return bool
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled' && !is_null($this->cancelled_at);
    }

    /**
     * Проверить, можно ли отменить заказ
     * 
     * Заказ можно отменить только если он еще не отправлен
     * 
     * @return bool
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'processing', 'paid']) 
               && !$this->isCancelled();
    }

    /**
     * Scope для получения заказов, ожидающих обработки
     * 
     * Использование: Order::pending()->get()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope для получения оплаченных заказов
     * 
     * Использование: Order::paid()->get()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * Scope для получения доставленных заказов
     * 
     * Использование: Order::delivered()->get()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    /**
     * Scope для получения отмененных заказов
     * 
     * Использование: Order::cancelled()->get()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope для получения заказов пользователя
     * 
     * Использование: Order::byUser($userId)->get()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope для получения недавних заказов (по дате создания)
     * 
     * Использование: Order::recent()->get()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Получить текстовое представление статуса заказа
     * 
     * @return string
     */
    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Ожидает обработки',
            'processing' => 'В обработке',
            'paid' => 'Оплачен',
            'shipped' => 'Отправлен',
            'delivered' => 'Доставлен',
            'cancelled' => 'Отменён',
            default => 'Неизвестный статус',
        };
    }

    /**
     * Получить текстовое представление статуса оплаты
     * 
     * @return string
     */
    public function getPaymentStatusTextAttribute(): string
    {
        return match ($this->payment_status) {
            'pending' => 'Ожидает оплаты',
            'paid' => 'Оплачено',
            'failed' => 'Ошибка оплаты',
            default => 'Неизвестный статус',
        };
    }

    /**
     * Получить текстовое представление способа доставки
     * 
     * @return string
     */
    public function getDeliveryMethodTextAttribute(): string
    {
        return match ($this->delivery_method) {
            'courier' => 'Курьерская доставка',
            'pickup' => 'Самовывоз',
            'post' => 'Почта России',
            default => 'Неизвестный способ',
        };
    }

    /**
     * Получить текстовое представление способа оплаты
     * 
     * @return string
     */
    public function getPaymentMethodTextAttribute(): string
    {
        return match ($this->payment_method) {
            'cash' => 'Наличными при получении',
            'card' => 'Картой при получении',
            'online' => 'Онлайн оплата',
            default => 'Неизвестный способ',
        };
    }
}
