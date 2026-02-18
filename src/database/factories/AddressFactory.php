<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory для генерации адресов доставки
 * 
 * Создает реалистичные адреса доставки для пользователей:
 * - Российские города и улицы
 * - Дома, квартиры, почтовые индексы
 * - Контактные телефоны
 * - Названия адресов (Дом, Работа, Дача и т.д.)
 * - Основные и дополнительные адреса
 * 
 * Каждый пользователь может иметь несколько адресов,
 * один из которых может быть отмечен как основной (по умолчанию).
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Address>
 */
class AddressFactory extends Factory
{
    /**
     * Модель, для которой создается фабрика
     * 
     * @var string
     */
    protected $model = Address::class;

    /**
     * Список российских городов для генерации адресов
     * 
     * @var array<string>
     */
    private const CITIES = [
        'Калининград',
        'Москва',
        'Санкт-Петербург',
        'Нижний Новгород',
        'Казань',
        'Екатеринбург',
        'Новосибирск',
        'Краснодар',
        'Сочи',
        'Владивосток',
    ];

    /**
     * Список типичных названий улиц
     * 
     * @var array<string>
     */
    private const STREETS = [
        'Ленина',
        'Советская',
        'Мира',
        'Победы',
        'Гагарина',
        'Пушкина',
        'Лермонтова',
        'Чехова',
        'Горького',
        'Некрасова',
        'Маяковского',
        'Центральная',
        'Молодежная',
        'Садовая',
        'Заводская',
        'Кирова',
        'Московская',
        'Комсомольская',
    ];

    /**
     * Список типичных названий адресов
     * 
     * @var array<string>
     */
    private const ADDRESS_NAMES = [
        'Дом',
        'Работа',
        'Дача',
        'Родители',
        'Офис',
        'Квартира',
        'Загородный дом',
    ];

    /**
     * Определение состояния по умолчанию для модели
     * 
     * Генерирует случайный адрес доставки с реалистичными данными
     * 
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Выбираем город
        $city = fake()->randomElement(self::CITIES);

        // Выбираем улицу
        $street = fake()->randomElement(self::STREETS);

        // Генерируем номер дома (1-200)
        $house = (string) fake()->numberBetween(1, 200);

        // Добавляем корпус или литеру в 30% случаев
        if (fake()->boolean(30)) {
            $house .= fake()->randomElement(['А', 'Б', 'В', '/1', '/2', 'к1', 'к2']);
        }

        // Квартира (в 80% случаев)
        $apartment = fake()->boolean(80) ? (string) fake()->numberBetween(1, 300) : null;

        // Почтовый индекс (опционально, в 60% случаев)
        // Формат: 123456
        $postalCode = fake()->boolean(60) ? fake()->numerify('######') : null;

        // Название адреса
        $name = fake()->randomElement(self::ADDRESS_NAMES);

        // Полный адрес одной строкой (генерируется автоматически в модели, но можем задать вручную)
        $fullAddress = sprintf(
            'г. %s, ул. %s, д. %s%s%s',
            $city,
            $street,
            $house,
            $apartment ? ', кв. ' . $apartment : '',
            $postalCode ? ', индекс ' . $postalCode : ''
        );

        return [
            // ID пользователя-владельца адреса
            'user_id' => User::factory(),

            // Название адреса для удобства ("Дом", "Работа", "Дача")
            'name' => $name,

            // Полный адрес одной строкой
            'full_address' => $fullAddress,

            // Город
            'city' => $city,

            // Улица
            'street' => $street,

            // Номер дома (может включать корпус/литеру)
            'house' => $house,

            // Квартира/офис (опционально)
            'apartment' => $apartment,

            // Почтовый индекс (опционально)
            'postal_code' => $postalCode,

            // Телефон для связи при доставке
            'phone' => fake()->numerify('+7 (###) ###-##-##'),

            // Основной адрес (по умолчанию false, будет установлен явно)
            // У каждого пользователя может быть только один основной адрес
            'is_default' => false,
        ];
    }

    /**
     * Состояние для адреса конкретного пользователя
     * 
     * Использование: Address::factory()->forUser($user)->create()
     * 
     * @param \App\Models\User|int $user Пользователь или его ID
     * @return static
     */
    public function forUser($user): static
    {
        $userId = is_object($user) ? $user->id : $user;

        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }

    /**
     * Состояние для основного адреса (по умолчанию)
     * 
     * Использование: Address::factory()->default()->create()
     * 
     * @return static
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
            'name' => 'Дом', // Основной адрес обычно называется "Дом"
        ]);
    }

    /**
     * Состояние для домашнего адреса
     * 
     * Использование: Address::factory()->home()->create()
     * 
     * @return static
     */
    public function home(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Дом',
        ]);
    }

    /**
     * Состояние для рабочего адреса
     * 
     * Использование: Address::factory()->work()->create()
     * 
     * @return static
     */
    public function work(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Работа',
            'apartment' => fake()->optional(0.9)->numberBetween(1, 50) . fake()->randomElement(['', 'А', 'Б']), // Офисы обычно есть
        ]);
    }

    /**
     * Состояние для адреса дачи/загородного дома
     * 
     * Использование: Address::factory()->dacha()->create()
     * 
     * @return static
     */
    public function dacha(): static
    {
        return $this->state(function (array $attributes) {
            $city = fake()->randomElement([
                'Светлогорск',
                'Зеленоградск',
                'Балтийск',
                'Гурьевск',
                'Подмосковье',
            ]);

            $street = fake()->randomElement([
                'Садовая',
                'Дачная',
                'Лесная',
                'Полевая',
                'Центральная',
                'Береговая',
            ]);

            $house = (string) fake()->numberBetween(1, 100);

            return [
                'name' => 'Дача',
                'city' => $city,
                'street' => $street,
                'house' => $house,
                'apartment' => null, // Дачи обычно без квартир
                'full_address' => sprintf('г. %s, ул. %s, д. %s', $city, $street, $house),
            ];
        });
    }

    /**
     * Состояние для адреса в Калининграде
     * 
     * Полезно для тестирования доставки в основном городе магазина
     * 
     * Использование: Address::factory()->kaliningrad()->create()
     * 
     * @return static
     */
    public function kaliningrad(): static
    {
        return $this->state(function (array $attributes) {
            $street = fake()->randomElement([
                'проспект Мира',
                'Ленинский проспект',
                'улица Багратиона',
                'улица Черняховского',
                'проспект Победы',
                'улица Театральная',
                'Московский проспект',
                'улица Горького',
            ]);

            $house = (string) fake()->numberBetween(1, 150);
            $apartment = fake()->boolean(85) ? (string) fake()->numberBetween(1, 200) : null;

            return [
                'city' => 'Калининград',
                'street' => $street,
                'house' => $house,
                'apartment' => $apartment,
                'postal_code' => fake()->numerify('2360##'), // Калининградский индекс начинается с 2360xx
                'full_address' => sprintf(
                    'г. Калининград, %s, д. %s%s, индекс %s',
                    $street,
                    $house,
                    $apartment ? ', кв. ' . $apartment : '',
                    fake()->numerify('2360##')
                ),
            ];
        });
    }

    /**
     * Состояние для адреса в Москве
     * 
     * Использование: Address::factory()->moscow()->create()
     * 
     * @return static
     */
    public function moscow(): static
    {
        return $this->state(function (array $attributes) {
            $street = fake()->randomElement([
                'улица Тверская',
                'Кутузовский проспект',
                'Ленинский проспект',
                'улица Арбат',
                'проспект Мира',
                'улица Остоженка',
                'Садовое кольцо',
            ]);

            $house = (string) fake()->numberBetween(1, 200);
            $apartment = fake()->boolean(95) ? (string) fake()->numberBetween(1, 500) : null;

            return [
                'city' => 'Москва',
                'street' => $street,
                'house' => $house,
                'apartment' => $apartment,
                'postal_code' => fake()->numerify('1#####'), // Московский индекс начинается с 1
                'full_address' => sprintf(
                    'г. Москва, %s, д. %s%s, индекс %s',
                    $street,
                    $house,
                    $apartment ? ', кв. ' . $apartment : '',
                    fake()->numerify('1#####')
                ),
            ];
        });
    }

    /**
     * Состояние для адреса без почтового индекса
     * 
     * Использование: Address::factory()->noPostalCode()->create()
     * 
     * @return static
     */
    public function noPostalCode(): static
    {
        return $this->state(function (array $attributes) {
            // Пересоздаем full_address без индекса
            $fullAddress = sprintf(
                'г. %s, ул. %s, д. %s%s',
                $attributes['city'],
                $attributes['street'],
                $attributes['house'],
                $attributes['apartment'] ? ', кв. ' . $attributes['apartment'] : ''
            );

            return [
                'postal_code' => null,
                'full_address' => $fullAddress,
            ];
        });
    }

    /**
     * Состояние для адреса без квартиры (частный дом)
     * 
     * Использование: Address::factory()->privateHouse()->create()
     * 
     * @return static
     */
    public function privateHouse(): static
    {
        return $this->state(function (array $attributes) {
            // Пересоздаем full_address без квартиры
            $fullAddress = sprintf(
                'г. %s, ул. %s, д. %s%s',
                $attributes['city'],
                $attributes['street'],
                $attributes['house'],
                $attributes['postal_code'] ? ', индекс ' . $attributes['postal_code'] : ''
            );

            return [
                'apartment' => null,
                'full_address' => $fullAddress,
                'name' => fake()->randomElement(['Дом', 'Частный дом', 'Коттедж']),
            ];
        });
    }

    /**
     * Состояние для адреса с полной информацией (все поля заполнены)
     * 
     * Использование: Address::factory()->complete()->create()
     * 
     * @return static
     */
    public function complete(): static
    {
        return $this->state(function (array $attributes) {
            $city = $attributes['city'];
            $street = $attributes['street'];
            $house = $attributes['house'];
            $apartment = (string) fake()->numberBetween(1, 300);
            $postalCode = fake()->numerify('######');

            return [
                'apartment' => $apartment,
                'postal_code' => $postalCode,
                'full_address' => sprintf(
                    'г. %s, ул. %s, д. %s, кв. %s, индекс %s',
                    $city,
                    $street,
                    $house,
                    $apartment,
                    $postalCode
                ),
            ];
        });
    }
}
