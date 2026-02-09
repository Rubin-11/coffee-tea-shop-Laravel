<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель позиции корзины
 * 
 * Представляет товар, добавленный в корзину покупок.
 * Поддерживает как авторизованных пользователей (через user_id),
 * так и гостей (через session_id).
 * Хранит цену на момент добавления товара в корзину.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $session_id
 * @property int $product_id
 * @property int $quantity
 * @property numeric $price
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CartItem available()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CartItem bySession(string $sessionId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CartItem byUser(int $userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CartItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CartItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CartItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CartItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CartItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CartItem wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CartItem whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CartItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CartItem whereSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CartItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CartItem whereUserId($value)
 * @mixin \Eloquent
 */
final class CartItem extends Model
{
    use HasFactory;

    /**
     * Поля, которые можно массово заполнять
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',      // ID авторизованного пользователя (null для гостей)
        'session_id',   // ID сессии для гостевых корзин
        'product_id',   // ID товара
        'quantity',     // Количество единиц товара
        'price',        // Цена на момент добавления в корзину
    ];

    /**
     * Преобразование типов атрибутов
     * 
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',        // Количество - целое число
            'price' => 'decimal:2',         // Цена с 2 знаками после запятой
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Получить пользователя, владельца корзины
     * 
     * Для гостевых корзин вернет null
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Получить товар из корзины
     * 
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Получить промежуточную сумму позиции
     * 
     * Вычисляет: цена * количество
     * Это общая стоимость данной позиции в корзине
     * 
     * @return float
     */
    public function getSubtotal(): float
    {
        return round((float) $this->price * (int) $this->quantity, 2);
    }

    /**
     * Получить актуальную цену товара
     * 
     * Возвращает текущую цену товара из БД
     * (может отличаться от цены в корзине, если цена изменилась)
     * 
     * @return float|null
     */
    public function getCurrentPrice(): ?float
    {
        return $this->product ? (float) $this->product->price : null;
    }

    /**
     * Проверить, изменилась ли цена товара
     * 
     * Сравнивает цену в корзине с актуальной ценой товара
     * 
     * @return bool
     */
    public function hasPriceChanged(): bool
    {
        $currentPrice = $this->getCurrentPrice();
        
        if (is_null($currentPrice)) {
            return false;
        }

        return abs($currentPrice - (float) $this->price) > 0.01;
    }

    /**
     * Обновить цену в корзине до актуальной
     * 
     * Синхронизирует цену в корзине с текущей ценой товара
     * 
     * @return bool
     */
    public function updatePrice(): bool
    {
        $currentPrice = $this->getCurrentPrice();
        
        if (is_null($currentPrice)) {
            return false;
        }

        $this->price = $currentPrice;
        return $this->save();
    }

    /**
     * Проверить, доступен ли товар для заказа
     * 
     * Товар доступен, если он есть в наличии в нужном количестве
     * 
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->product 
               && $this->product->is_available 
               && $this->product->stock >= $this->quantity;
    }

    /**
     * Получить максимально доступное количество товара
     * 
     * @return int
     */
    public function getMaxAvailableQuantity(): int
    {
        return $this->product ? (int) $this->product->stock : 0;
    }

    /**
     * Scope для получения корзины авторизованного пользователя
     * 
     * Использование: CartItem::byUser($userId)->get()
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
     * Scope для получения гостевой корзины по ID сессии
     * 
     * Использование: CartItem::bySession($sessionId)->get()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $sessionId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId)->whereNull('user_id');
    }

    /**
     * Scope для получения доступных позиций
     * 
     * Фильтрует только те позиции, товары которых доступны для заказа
     * 
     * Использование: CartItem::available()->get()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAvailable($query)
    {
        return $query->whereHas('product', function ($q) {
            $q->where('is_available', true)
              ->where('stock', '>', 0);
        });
    }

    /**
     * Boot модели для автоматических действий
     * 
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        // При создании позиции корзины автоматически устанавливаем текущую цену товара
        static::creating(function (CartItem $cartItem) {
            if (is_null($cartItem->price) && $cartItem->product) {
                $cartItem->price = $cartItem->product->price;
            }
        });
    }
}
