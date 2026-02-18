# Документация по Factories (Фабрикам моделей)

## Обзор

Factories (фабрики) в Laravel - это классы для генерации тестовых данных моделей. Они используются в тестах для быстрого создания экземпляров моделей с реалистичными данными.

## Расположение

Все фабрики находятся в директории: `src/database/factories/`

## Список созданных фабрик

### ✅ Основные модели

#### 1. UserFactory.php
**Описание**: Генерация пользователей системы

**Основные параметры**:
- `first_name` - имя пользователя
- `last_name` - фамилия пользователя
- `email` - уникальный email
- `phone` - телефон (опционально)
- `password` - хешированный пароль (по умолчанию 'password')
- `is_admin` - флаг администратора
- `is_active` - флаг активности

**Доступные состояния**:
```php
User::factory()->create();                    // Обычный пользователь
User::factory()->admin()->create();           // Администратор
User::factory()->inactive()->create();        // Неактивный пользователь
```

---

#### 2. CategoryFactory.php
**Описание**: Генерация категорий товаров

**Основные параметры**:
- `name` - название категории
- `slug` - URL-slug
- `description` - описание (опционально)
- `parent_id` - ID родительской категории (для подкатегорий)
- `sort_order` - порядок сортировки
- `is_active` - активность категории

**Доступные состояния**:
```php
Category::factory()->create();                // Обычная категория
Category::factory()->parent()->create();      // Главная категория
Category::factory()->child($parentId)->create(); // Подкатегория
Category::factory()->inactive()->create();    // Неактивная категория
```

---

#### 3. ProductFactory.php
**Описание**: Генерация товаров (кофе, чай, аксессуары)

**Основные параметры**:
- `name` - название товара
- `slug` - URL-slug
- `description` - краткое описание
- `long_description` - подробное описание
- `price` - цена
- `old_price` - старая цена (для скидок)
- `sku` - артикул (CF-XXXX для кофе, TE-XXXX для чая, AC-XXXX для аксессуаров)
- `stock` - количество на складе
- `weight` - вес в граммах
- `rating` - средний рейтинг
- `bitterness_percent` - уровень горчинки (для кофе)
- `acidity_percent` - уровень кислинки (для кофе)

**Доступные состояния**:
```php
Product::factory()->create();                 // Случайный товар
Product::factory()->coffee()->create();       // Кофе
Product::factory()->tea()->create();          // Чай
Product::factory()->accessory()->create();    // Аксессуар
Product::factory()->discounted()->create();   // Товар со скидкой
Product::factory()->featured()->create();     // Рекомендуемый товар
Product::factory()->outOfStock()->create();   // Товар не в наличии
```

---

#### 4. ReviewFactory.php
**Описание**: Генерация отзывов на товары

**Основные параметры**:
- `product_id` - ID товара
- `user_id` - ID пользователя
- `rating` - оценка (1-5 звезд)
- `comment` - текст отзыва
- `pros` - достоинства (опционально)
- `cons` - недостатки (опционально)
- `is_verified_purchase` - проверенная покупка
- `is_approved` - одобрен модератором

**Доступные состояния**:
```php
Review::factory()->create();                  // Случайный отзыв
Review::factory()->positive()->create();      // Положительный (4-5 звезд)
Review::factory()->negative()->create();      // Отрицательный (1-2 звезды)
Review::factory()->verified()->create();      // Проверенная покупка
Review::factory()->pending()->create();       // На модерации
Review::factory()->excellent()->create();     // Отличный (5 звезд)
```

---

### ✅ Модели заказов

#### 5. OrderFactory.php
**Описание**: Генерация заказов покупателей

**Основные параметры**:
- `user_id` - ID пользователя (null для гостевых заказов)
- `order_number` - уникальный номер (ORD-2026-00001)
- `customer_name` - имя покупателя
- `customer_email` - email покупателя
- `customer_phone` - телефон покупателя
- `delivery_address` - адрес доставки
- `delivery_method` - способ доставки (courier, pickup, post)
- `payment_method` - способ оплаты (cash, card, online)
- `subtotal` - сумма товаров
- `delivery_cost` - стоимость доставки
- `discount` - размер скидки
- `total` - итоговая сумма
- `status` - статус заказа
- `payment_status` - статус оплаты

**Доступные состояния**:
```php
Order::factory()->create();                   // Случайный заказ
Order::factory()->guest()->create();          // Гостевой заказ
Order::factory()->forUser($user)->create();   // Заказ пользователя
Order::factory()->pending()->create();        // Ожидает обработки
Order::factory()->paid()->create();           // Оплаченный заказ
Order::factory()->delivered()->create();      // Доставленный заказ
Order::factory()->cancelled()->create();      // Отмененный заказ
Order::factory()->courier()->create();        // С курьерской доставкой
Order::factory()->pickup()->create();         // С самовывозом
Order::factory()->post()->create();           // С доставкой почтой
Order::factory()->large()->create();          // Крупный заказ (>3000 руб)
```

**Примеры использования**:
```php
// Создать оплаченный заказ с курьерской доставкой
$order = Order::factory()
    ->paid()
    ->courier()
    ->create();

// Создать заказ для конкретного пользователя
$user = User::factory()->create();
$order = Order::factory()
    ->forUser($user)
    ->delivered()
    ->create();
```

---

#### 6. OrderItemFactory.php
**Описание**: Генерация позиций (товаров) в заказе

**Основные параметры**:
- `order_id` - ID заказа
- `product_id` - ID товара
- `product_name` - название товара (snapshot на момент заказа)
- `quantity` - количество единиц
- `price` - цена за единицу (snapshot на момент заказа)
- `total` - общая стоимость (price × quantity)

**Доступные состояния**:
```php
OrderItem::factory()->create();               // Случайная позиция
OrderItem::factory()->forOrder($order)->create(); // Для конкретного заказа
OrderItem::factory()->forProduct($product)->create(); // Для конкретного товара
OrderItem::factory()->single()->create();     // 1 единица товара
OrderItem::factory()->bulk()->create();       // 5-20 единиц
OrderItem::factory()->premium()->create();    // Дорогой товар
OrderItem::factory()->discounted()->create(); // Со скидкой
OrderItem::factory()->coffee()->create();     // Кофе
OrderItem::factory()->tea()->create();        // Чай
```

**Примеры использования**:
```php
// Создать заказ с 5 позициями
$order = Order::factory()
    ->has(OrderItem::factory()->count(5), 'items')
    ->create();

// Добавить конкретный товар в заказ
$product = Product::factory()->create(['price' => 500]);
$orderItem = OrderItem::factory()
    ->forOrder($order)
    ->forProduct($product)
    ->create();
```

---

### ✅ Модели корзины

#### 7. CartItemFactory.php
**Описание**: Генерация позиций в корзине покупок

**Основные параметры**:
- `user_id` - ID пользователя (null для гостей)
- `session_id` - ID сессии (для гостевых корзин)
- `product_id` - ID товара
- `quantity` - количество единиц
- `price` - цена на момент добавления в корзину

**Доступные состояния**:
```php
CartItem::factory()->create();                // Случайная позиция (70% авторизованные)
CartItem::factory()->forUser($user)->create(); // Для конкретного пользователя
CartItem::factory()->guest('session-id')->create(); // Гостевая корзина
CartItem::factory()->forProduct($product)->create(); // Для конкретного товара
CartItem::factory()->single()->create();      // 1 единица
CartItem::factory()->multiple(5)->create();   // 5 единиц
CartItem::factory()->old()->create();         // Старая позиция (>1 месяца)
CartItem::factory()->fresh()->create();       // Свежая (сегодня/вчера)
CartItem::factory()->outdatedPrice()->create(); // Цена устарела
CartItem::factory()->bulk()->create();        // 10-50 единиц
CartItem::factory()->coffee()->create();      // Кофе
CartItem::factory()->tea()->create();         // Чай
```

**Примеры использования**:
```php
// Создать корзину пользователя с 3 товарами
$user = User::factory()->create();
CartItem::factory()
    ->forUser($user)
    ->count(3)
    ->create();

// Создать гостевую корзину
$sessionId = 'test-session-12345';
CartItem::factory()
    ->guest($sessionId)
    ->count(2)
    ->create();

// Добавить конкретный товар в корзину
$product = Product::factory()->create();
CartItem::factory()
    ->forUser($user)
    ->forProduct($product)
    ->create();
```

---

### ✅ Модели адресов

#### 8. AddressFactory.php
**Описание**: Генерация адресов доставки

**Основные параметры**:
- `user_id` - ID пользователя
- `name` - название адреса ("Дом", "Работа", "Дача")
- `full_address` - полный адрес одной строкой
- `city` - город
- `street` - улица
- `house` - номер дома
- `apartment` - квартира/офис (опционально)
- `postal_code` - почтовый индекс (опционально)
- `phone` - телефон для связи
- `is_default` - основной адрес

**Доступные состояния**:
```php
Address::factory()->create();                 // Случайный адрес
Address::factory()->forUser($user)->create(); // Для конкретного пользователя
Address::factory()->default()->create();      // Основной адрес
Address::factory()->home()->create();         // Домашний адрес
Address::factory()->work()->create();         // Рабочий адрес
Address::factory()->dacha()->create();        // Дача/загородный дом
Address::factory()->kaliningrad()->create();  // В Калининграде
Address::factory()->moscow()->create();       // В Москве
Address::factory()->noPostalCode()->create(); // Без индекса
Address::factory()->privateHouse()->create(); // Частный дом (без квартиры)
Address::factory()->complete()->create();     // Полная информация
```

**Примеры использования**:
```php
// Создать основной адрес пользователя
$user = User::factory()->create();
$address = Address::factory()
    ->forUser($user)
    ->default()
    ->kaliningrad()
    ->create();

// Создать несколько адресов для пользователя
$user->addresses()->saveMany([
    Address::factory()->home()->make(),
    Address::factory()->work()->make(),
    Address::factory()->dacha()->make(),
]);
```

---

## Примеры комплексного использования

### Создание полного заказа

```php
// Создаем пользователя
$user = User::factory()->create();

// Создаем товары
$products = Product::factory()->count(3)->create();

// Создаем заказ с позициями
$order = Order::factory()
    ->forUser($user)
    ->paid()
    ->courier()
    ->create();

// Добавляем товары в заказ
foreach ($products as $product) {
    OrderItem::factory()
        ->forOrder($order)
        ->forProduct($product)
        ->create();
}
```

### Создание пользователя с полной историей

```php
// Создаем пользователя со всеми связями
$user = User::factory()
    ->has(Order::factory()->count(5)->delivered(), 'orders')
    ->has(Review::factory()->count(10)->verified(), 'reviews')
    ->has(Address::factory()->count(2), 'addresses')
    ->has(CartItem::factory()->count(3), 'cartItems')
    ->create();
```

### Создание корзины и оформление заказа

```php
// Создаем пользователя
$user = User::factory()->create();

// Создаем товары в корзине
$cartItems = CartItem::factory()
    ->forUser($user)
    ->count(3)
    ->create();

// Оформляем заказ из корзины
$order = Order::factory()
    ->forUser($user)
    ->pending()
    ->create();

// Переносим товары из корзины в заказ
foreach ($cartItems as $cartItem) {
    OrderItem::factory()
        ->forOrder($order)
        ->forProduct($cartItem->product)
        ->create([
            'quantity' => $cartItem->quantity,
            'price' => $cartItem->price,
        ]);
}
```

---

## Рекомендации по использованию

### 1. Используйте состояния для читаемости

**Плохо:**
```php
$product = Product::factory()->create([
    'stock' => 0,
    'is_available' => false,
]);
```

**Хорошо:**
```php
$product = Product::factory()->outOfStock()->create();
```

### 2. Комбинируйте состояния

```php
$product = Product::factory()
    ->coffee()
    ->discounted()
    ->featured()
    ->create();
```

### 3. Используйте relationships

```php
// Создать заказ с позициями одной командой
$order = Order::factory()
    ->has(OrderItem::factory()->count(5), 'items')
    ->create();
```

### 4. Переопределяйте только нужные поля

```php
$order = Order::factory()->create([
    'customer_email' => 'test@example.com',
    // Остальные поля генерируются автоматически
]);
```

---

## Тестирование фабрик

Все фабрики протестированы в файле `tests/Unit/FactoriesTest.php`

Запуск тестов:
```bash
php artisan test --filter=FactoriesTest
```

Результат:
```
✅ Tests: 18 passed (113 assertions)
✅ Duration: 0.99s
```

---

## Дата создания

**Создано**: 12 февраля 2026  
**Последнее обновление**: 12 февраля 2026  
**Автор**: AI Assistant

---

## Полезные ссылки

- [Laravel Factories Documentation](https://laravel.com/docs/12.x/eloquent-factories)
- [Plan Unit Testing](./UNIT_TESTING_PLAN.md)
- [Testing Setup](./TESTING_SETUP.md)
