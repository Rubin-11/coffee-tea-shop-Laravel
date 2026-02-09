<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель позиции заказа
 * 
 * Представляет отдельный товар в заказе.
 * Хранит "снапшот" информации о товаре на момент оформления заказа:
 * название, количество, цену. Это важно, т.к. цены товаров могут меняться,
 * но заказ должен хранить историческую информацию.
 *
 * @property int $id
 * @property int $order_id
 * @property int $product_id
 * @property string $product_name
 * @property int $quantity
 * @property numeric $price
 * @property numeric $total
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Order $order
 * @property-read \App\Models\Product $product
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereProductName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereUpdatedAt($value)
 * @mixin \Eloquent
 */
final class OrderItem extends Model
{
    use HasFactory;

    /**
     * Поля, которые можно массово заполнять
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',         // ID заказа, к которому относится позиция
        'product_id',       // ID товара
        'product_name',     // Название товара на момент заказа (снапшот)
        'quantity',         // Количество единиц товара
        'price',            // Цена за единицу на момент заказа
        'total',            // Итоговая стоимость позиции (quantity * price)
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
            'total' => 'decimal:2',         // Итоговая сумма с 2 знаками
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Получить заказ, к которому относится позиция
     * 
     * @return BelongsTo
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Получить товар
     * 
     * Связь с товаром нужна для отображения актуальной информации
     * (изображение, наличие и т.д.), но цена берется из snapshot
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
     * Вычисляет: количество * цена за единицу
     * Этот метод полезен для пересчета, если total не был сохранен
     * 
     * @return float
     */
    public function getSubtotal(): float
    {
        return round((float) $this->price * (int) $this->quantity, 2);
    }

    /**
     * Получить размер скидки для позиции
     * 
     * Если на момент заказа была старая цена у товара,
     * можно вычислить размер скидки на единицу товара.
     * Этот метод предполагает, что скидка уже учтена в price.
     * 
     * @param float|null $originalPrice Оригинальная цена (если была скидка)
     * @return float
     */
    public function getDiscountAmount(?float $originalPrice = null): float
    {
        if (is_null($originalPrice)) {
            return 0.00;
        }

        $discountPerUnit = $originalPrice - (float) $this->price;
        return round($discountPerUnit * (int) $this->quantity, 2);
    }

    /**
     * Проверить, доступен ли товар в данный момент
     * 
     * Полезно для повторного заказа или отображения актуальности товара
     * 
     * @return bool
     */
    public function isProductAvailable(): bool
    {
        return $this->product 
               && $this->product->is_available 
               && $this->product->stock >= $this->quantity;
    }

    /**
     * Получить актуальную цену товара
     * 
     * Возвращает текущую цену товара (для сравнения с ценой в заказе)
     * 
     * @return float|null
     */
    public function getCurrentPrice(): ?float
    {
        return $this->product ? (float) $this->product->price : null;
    }

    /**
     * Проверить, изменилась ли цена товара с момента заказа
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
}
