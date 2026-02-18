# Тестирование Coffee-Tea Shop Laravel

Этот файл содержит информацию по настройке и запуску тестов проекта.

## 📚 Связанная документация

- [📋 UNIT_TESTING_PLAN.md](../../docs/UNIT_TESTING_PLAN.md) - Детальный план unit тестирования (286 тестов)
- [🏭 FACTORIES.md](../../docs/FACTORIES.md) - Документация по фабрикам моделей
- [📖 README.md](../../README.md) - Главная документация проекта

## Структура тестов

```
tests/
├── Unit/               # Unit тесты (286 тестов)
│   ├── Models/         # Product, CartItem, Order, Category, Review, User (98 тестов)
│   ├── Services/      # CartService, OrderService, ReviewService, ProductFilterService (153 теста)
│   ├── FactoriesTest.php
│   └── ExampleUnitTest.php
├── Feature/            # Feature тесты (запланированы)
├── TestCase.php        # Базовый класс для всех тестов
└── README.md           # Эта документация
```

## Настройка тестового окружения

### 1. Тестовая база данных

Проект использует отдельную PostgreSQL базу данных для тестов: `coffee_tea_shop_test`

**Создание тестовой базы данных:**

```bash
# Войти в контейнер PostgreSQL
docker compose exec postgres bash

# Войти в psql
psql -U sail

# Создать тестовую базу данных
CREATE DATABASE coffee_tea_shop_test;

# Выход
\q
exit
```

### 2. Конфигурация

**Файлы конфигурации:**

- `phpunit.xml` - конфигурация PHPUnit
- `.env.testing` - переменные окружения для тестов
- `config/database.php` - добавлено подключение `pgsql_testing`

### 3. Проверка установки

Убедитесь, что все зависимости установлены:

```bash
# Войти в PHP контейнер
make shell

# Или напрямую
docker compose exec php bash

# Проверить версию PHPUnit
./vendor/bin/phpunit --version
```

## Запуск тестов

### Все тесты

```bash
# Запустить все тесты
php artisan test

# Или через PHPUnit напрямую
./vendor/bin/phpunit
```

### Unit тесты

```bash
# Только unit тесты
php artisan test --testsuite=Unit

# Или
./vendor/bin/phpunit --testsuite=Unit
```

### Feature тесты

```bash
# Только feature тесты
php artisan test --testsuite=Feature

# Или
./vendor/bin/phpunit --testsuite=Feature
```

### Конкретный тестовый класс

```bash
# Запустить конкретный класс
php artisan test --filter CartServiceTest

# Или указать полный путь
php artisan test tests/Unit/Services/CartServiceTest.php
```

### Конкретный метод теста

```bash
# Запустить один метод
php artisan test --filter test_can_add_product_to_cart

# Или указать класс и метод
php artisan test --filter CartServiceTest::test_can_add_product_to_cart
```

### С выводом информации

```bash
# Подробный вывод
php artisan test --verbose

# С трассировкой ошибок
php artisan test --debug
```

## Покрытие кода

### Требования

Для анализа покрытия кода требуется расширение Xdebug:

```bash
# Установка Xdebug в контейнере (если не установлен)
docker compose exec php bash
apt-get update
apt-get install php8.4-xdebug
```

### Запуск с покрытием

```bash
# Простой отчет в терминале
php artisan test --coverage

# С минимальным порогом покрытия (80%)
php artisan test --coverage --min=80

# Генерация HTML отчета
./vendor/bin/phpunit --coverage-html coverage-report

# Открыть отчет (из Windows)
# Откройте файл: \\wsl.localhost\Ubuntu\home\rubin11\projects\coffee-tea-shop-Laravel\src\coverage-report\index.html
```

### Целевые показатели покрытия

- **Сервисы**: 90-95% (критическая бизнес-логика)
- **Модели**: 80-85% (методы и scopes)
- **Контроллеры**: 70-75% (Feature тесты)
- **Общее покрытие**: 75-80%

## Написание тестов

### Базовый пример Unit теста

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\CartService;
use Tests\TestCase;

final class CartServiceTest extends TestCase
{
    private CartService $cartService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cartService = app(CartService::class);
    }

    public function test_can_add_product_to_cart(): void
    {
        // Arrange (Подготовка)
        $user = $this->createUser();
        $product = $this->createProduct(['price' => 500, 'stock' => 10]);

        // Act (Действие)
        $cartItem = $this->cartService->addToCart($user->id, $product->id, 2);

        // Assert (Проверка)
        $this->assertNotNull($cartItem);
        $this->assertEquals(2, $cartItem->quantity);
        $this->assertEquals(500, $cartItem->price);
    }
}
```

### Доступные хелперы в TestCase

```php
// Создание тестовых данных
$user = $this->createUser(['email' => 'test@example.com']);
$product = $this->createProduct(['price' => 500]);
$category = $this->createCategory(['name' => 'Кофе']);

// Массовое создание
$products = $this->createProducts(10); // 10 товаров
$users = $this->createUsers(5); // 5 пользователей
$categories = $this->createCategories(3); // 3 категории

// Авторизация
$this->actingAsUser(); // Создать и авторизовать пользователя
$this->actingAsUser($user); // Авторизовать конкретного пользователя
$this->actingAsAdmin(); // Авторизовать администратора

// Использование в цепочке
$this->actingAsUser()->get('/profile');
$this->actingAsAdmin()->delete('/admin/products/1');
```

### Лучшие практики

1. **Именование тестов**: используйте snake_case и описательные имена
   ```php
   // ✅ Хорошо
   test_can_add_product_to_cart()
   test_throws_exception_when_insufficient_stock()
   
   // ❌ Плохо
   testAddToCart()
   test1()
   ```

2. **AAA паттерн**: Arrange (подготовка), Act (действие), Assert (проверка)
   ```php
   public function test_example(): void
   {
       // Arrange
       $user = $this->createUser();
       
       // Act
       $result = $this->service->doSomething($user);
       
       // Assert
       $this->assertTrue($result);
   }
   ```

3. **Изоляция тестов**: каждый тест должен быть независимым
4. **Один тест - одна проверка**: проверяйте только одну функциональность
5. **Используйте Factories**: не создавайте данные вручную

## Дополнительные команды

### Создание тестовых классов

```bash
# Unit тест для модели
php artisan make:test Models/ProductTest --unit

# Unit тест для сервиса
php artisan make:test Services/CartServiceTest --unit

# Feature тест
php artisan make:test Cart/AddToCartTest
```

### Очистка тестовой БД

```bash
# База очищается автоматически перед каждым тестом (RefreshDatabase trait)
# Но при необходимости можно очистить вручную:

docker compose exec postgres psql -U sail -c "DROP DATABASE IF EXISTS coffee_tea_shop_test;"
docker compose exec postgres psql -U sail -c "CREATE DATABASE coffee_tea_shop_test;"
```

### Отладка тестов

```bash
# Остановить выполнение при первой ошибке
php artisan test --stop-on-failure

# Показать предупреждения
php artisan test --display-warnings

# Подробный вывод с трассировкой
php artisan test --verbose --debug
```

## Проблемы и решения

### Проблема: "Database does not exist"

**Решение**: Создайте тестовую базу данных:
```bash
docker compose exec postgres psql -U sail -c "CREATE DATABASE coffee_tea_shop_test;"
```

### Проблема: "Class 'Tests\TestCase' not found"

**Решение**: Убедитесь, что composer autoload обновлен:
```bash
composer dump-autoload
```

### Проблема: Тесты падают с timeout

**Решение**: Увеличьте время выполнения в phpunit.xml или используйте --stop-on-failure

## Полезные ссылки

- [Laravel Testing Documentation](https://laravel.com/docs/12.x/testing)
- [PHPUnit Documentation](https://docs.phpunit.de/)
- [Laravel Factories](https://laravel.com/docs/12.x/eloquent-factories)
- [Mockery Documentation](http://docs.mockery.io/)

## Контрибьюторы

При добавлении новых тестов:

1. Следуйте структуре существующих тестов
2. Добавляйте комментарии для сложной логики
3. Обновляйте документацию при необходимости
4. Стремитесь к высокому покрытию критических компонентов
