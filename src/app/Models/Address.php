<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель адреса доставки
 * 
 * Представляет сохраненный адрес доставки пользователя.
 * Пользователь может иметь несколько адресов (дом, работа, дача и т.д.)
 * и выбирать нужный при оформлении заказа.
 * Один из адресов может быть отмечен как основной.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $full_address
 * @property string $city
 * @property string $street
 * @property string $house
 * @property string|null $apartment
 * @property string|null $postal_code
 * @property string $phone
 * @property bool $is_default
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $short_address
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address byUser(int $userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address default()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereApartment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereFullAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereHouse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereIsDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address wherePostalCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereStreet($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereUserId($value)
 * @mixin \Eloquent
 */
final class Address extends Model
{
    use HasFactory;

    /**
     * Поля, которые можно массово заполнять
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',          // ID пользователя-владельца адреса
        'name',             // Название адреса для удобства ("Дом", "Работа", "Дача")
        'full_address',     // Полный адрес одной строкой (для быстрого отображения)
        'city',             // Город (например: Калининград)
        'street',           // Улица (например: проспект Мира)
        'house',            // Номер дома (например: 125)
        'apartment',        // Квартира/офис (опционально)
        'postal_code',      // Почтовый индекс (опционально)
        'phone',            // Телефон для связи при доставке
        'is_default',       // Является ли адрес основным (по умолчанию)
    ];

    /**
     * Преобразование типов атрибутов
     * 
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',      // Основной адрес - булев тип
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Получить пользователя, которому принадлежит адрес
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Получить полный адрес в читаемом формате
     * 
     * Формирует адрес из отдельных компонентов:
     * "г. Калининград, ул. Ленина, д. 25, кв. 10, индекс 236000"
     * 
     * @return string
     */
    public function getFullAddress(): string
    {
        $parts = [];

        // Добавляем город
        if (!empty($this->city)) {
            $parts[] = "г. {$this->city}";
        }

        // Добавляем улицу
        if (!empty($this->street)) {
            $parts[] = "ул. {$this->street}";
        }

        // Добавляем дом
        if (!empty($this->house)) {
            $parts[] = "д. {$this->house}";
        }

        // Добавляем квартиру, если указана
        if (!empty($this->apartment)) {
            $parts[] = "кв. {$this->apartment}";
        }

        // Добавляем индекс, если указан
        if (!empty($this->postal_code)) {
            $parts[] = "индекс {$this->postal_code}";
        }

        return implode(', ', $parts);
    }

    /**
     * Получить краткое представление адреса
     * 
     * Для отображения в списке: "Дом (ул. Ленина, 25)"
     * 
     * @return string
     */
    public function getShortAddressAttribute(): string
    {
        return "{$this->name} ({$this->street}, {$this->house})";
    }

    /**
     * Установить этот адрес как основной
     * 
     * Автоматически снимает флаг "основной" с других адресов пользователя
     * 
     * @return void
     */
    public function setAsDefault(): void
    {
        // Снимаем флаг "основной" со всех адресов пользователя
        self::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        // Устанавливаем текущий адрес как основной
        $this->is_default = true;
        $this->save();
    }

    /**
     * Scope для получения основного адреса пользователя
     * 
     * Использование: Address::default()->first()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope для получения адресов пользователя
     * 
     * Использование: Address::byUser($userId)->get()
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
     * Boot модели для автоматических действий
     * 
     * При сохранении адреса автоматически генерируем full_address
     * 
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        // Перед сохранением автоматически генерируем полный адрес
        static::saving(function (Address $address) {
            if (empty($address->full_address)) {
                $address->full_address = $address->getFullAddress();
            }
        });

        // После сохранения, если адрес установлен как основной,
        // снимаем флаг с остальных адресов
        static::saved(function (Address $address) {
            if ($address->is_default) {
                self::where('user_id', $address->user_id)
                    ->where('id', '!=', $address->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }
}
