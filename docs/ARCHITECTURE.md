# Архитектура Coffee-Tea Shop

**Последнее обновление:** 2026-02-11  
**Версия Laravel:** 12.0  
**PHP:** 8.3+ (в Docker — 8.5)

---

## 🎯 Описание проекта

**Coffee-Tea Shop** — интернет-магазин для продажи кофе, чая и сопутствующих товаров. Проект построен на Laravel 13 с использованием современных практик веб-разработки.

### Основной функционал:
- Каталог товаров с фильтрацией и сортировкой
- Корзина покупок (для авторизованных и гостей)
- Оформление заказов с выбором способа доставки и оплаты
- Система отзывов на товары
- Блог о кофе и чае
- Email-рассылка для подписчиков
- Личный кабинет с историей заказов

---

## 🛠 Технологический стек

### Backend:
- **Laravel 13.0** - основной фреймворк
- **PHP 8.3+** - язык программирования (в Docker — 8.5)
- **PostgreSQL 18** - основная СУБД
- **Eloquent ORM** - работа с базой данных

### Frontend:
- **Blade** - шаблонизатор Laravel
- **Vite** - сборщик фронтенда
- **CSS/JavaScript** - стилизация и интерактивность

### Инфраструктура:
- **Docker Compose** - контейнеризация
  - `nginx:alpine` - веб-сервер
  - `php:8.2-fpm` - PHP-FPM (кастомный образ)
  - `postgres:18` - база данных
- **WSL 2** - среда разработки (Ubuntu)

### Инструменты разработки:
- **Laravel Pint** - code style (PSR-12)
- **PHPUnit** - тестирование
- **Laravel IDE Helper** - автодополнение в IDE
- **Laravel Pail** - просмотр логов
- **Faker** - генерация тестовых данных

---

## 🏗 Архитектурные паттерны

### 1. **MVC (Model-View-Controller)** — основной паттерн
- **Models** (`app/Models/`) - бизнес-логика, работа с БД
- **Views** (`resources/views/`) - Blade-шаблоны
- **Controllers** (`app/Http/Controllers/`) - тонкие контроллеры (координация запросов)

### 2. **Service Layer Pattern** — бизнес-логика
- `app/Services/` - сложная бизнес-логика вынесена в сервисы
- Сервисы используются контроллерами через dependency injection
- Примеры: `CartService`, `OrderService`, `ReviewService`, `ProductFilterService`

### 3. **Repository Pattern** (частично)
- Eloquent модели выступают в роли репозиториев
- Query Scopes используются для переиспользуемых запросов
- Пример: `Product::available()->featured()->get()`

### 4. **View Composer Pattern**
- `app/Http/ViewComposers/` - предоставление данных для views
- `CartComposer` - передает данные корзины во все представления (header)

### 5. **Form Request Validation**
- `app/Http/Requests/` - валидация входящих данных
- Отделение валидации от контроллеров

### 6. **Middleware Pattern**
- `app/Http/Middleware/` - промежуточная обработка запросов
- `cart.not.empty` - проверка что корзина не пуста перед checkout

### 7. **Soft Deletes**
- Модели поддерживают мягкое удаление (SoftDeletes trait)
- Сохранение истории удаленных товаров/заказов

---

## 📦 Структура проекта

```
coffee-tea-shop-Laravel/
├── docker/                      # Docker конфигурация
│   ├── nginx/                   # Конфиг Nginx
│   └── php/                     # Dockerfile для PHP-FPM
├── src/                         # Laravel приложение
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/     # Контроллеры (9 штук)
│   │   │   ├── Middleware/      # Middleware
│   │   │   ├── Requests/        # Form Requests для валидации
│   │   │   └── ViewComposers/   # View Composers
│   │   ├── Models/              # Eloquent модели (12 моделей)
│   │   ├── Services/            # Бизнес-логика (4 сервиса)
│   │   └── Providers/           # Service providers
│   ├── database/
│   │   ├── migrations/          # Миграции БД
│   │   ├── seeders/             # Сидеры
│   │   ├── factories/           # Фабрики для тестов
│   │   └── UML.drawio          # ERD диаграмма БД
│   ├── resources/
│   │   ├── views/               # Blade шаблоны
│   │   ├── css/                 # Стили
│   │   └── js/                  # JavaScript
│   ├── routes/
│   │   ├── web.php             # Web маршруты (170 строк)
│   │   └── console.php         # Artisan команды
│   ├── public/                  # Публичная директория
│   ├── storage/                 # Хранилище (логи, кэш, uploads)
│   └── tests/                   # Тесты
├── docs/                        # Документация проекта
├── .cursor/rules/              # Правила для Cursor AI
├── docker-compose.yml
├── Makefile                     # Команды для управления проектом
└── README.md
```

---

## 🎨 Основные компоненты

### Контроллеры (Controllers)

| Контроллер | Назначение |
|-----------|-----------|
| `HomeController` | Главная страница (featured товары, новинки, блог) |
| `ProductController` | Каталог товаров, детальная страница товара |
| `CategoryController` | Категории товаров, фильтрация по категориям |
| `CartController` | Управление корзиной (add, update, remove, clear) |
| `CheckoutController` | Оформление заказа, страница успеха |
| `OrderController` | История заказов, детали, отмена, повтор, invoice |
| `ReviewController` | Добавление отзывов на товары |
| `BlogController` | Список и детали статей блога |
| `NewsletterController` | Подписка/отписка от рассылки |

### Модели (Models)

| Модель | Назначение | Основные связи |
|--------|-----------|---------------|
| `User` | Пользователи | hasMany: orders, reviews, addresses, cartItems |
| `Product` | Товары | belongsTo: category; hasMany: images, reviews; belongsToMany: tags |
| `Category` | Категории | hasMany: products; belongsTo: parent; hasMany: children |
| `Order` | Заказы | belongsTo: user, address; hasMany: orderItems |
| `OrderItem` | Позиции заказа | belongsTo: order, product |
| `CartItem` | Позиции корзины | belongsTo: user, product |
| `Review` | Отзывы | belongsTo: user, product |
| `BlogPost` | Статьи блога | belongsTo: author (User); belongsToMany: tags |
| `ProductImage` | Изображения товаров | belongsTo: product |
| `Tag` | Теги | belongsToMany: products, blogPosts |
| `Address` | Адреса доставки | belongsTo: user |
| `Subscriber` | Подписчики рассылки | - |

### Сервисы (Services)

| Сервис | Назначение |
|--------|-----------|
| `CartService` | Управление корзиной (add, update, remove, merge, sync) |
| `OrderService` | Создание заказов, обработка статусов |
| `ProductFilterService` | Фильтрация товаров (цена, категория, теги, рейтинг) |
| `ReviewService` | Создание отзывов, модерация, подсчет рейтинга |

### View Composers

| Composer | Назначение |
|----------|-----------|
| `CartComposer` | Передает данные корзины (количество, сумма) в header/footer |

---

## 🗄️ База данных

**СУБД:** PostgreSQL 18

**Основные таблицы:**
- `users` - пользователи и администраторы
- `products` - товары (кофе, чай, аксессуары)
- `categories` - иерархические категории товаров
- `orders` - заказы покупателей
- `order_items` - позиции в заказах
- `cart_items` - корзина (для пользователей и гостей через session_id)
- `reviews` - отзывы на товары
- `product_images` - изображения товаров
- `addresses` - адреса доставки
- `blog_posts` - статьи блога
- `tags` - теги для товаров и блога
- `subscribers` - подписчики email-рассылки

**ERD диаграмма:** см. `src/database/UML.drawio`  
**Детальная схема:** см. [docs/DATABASE.md](docs/DATABASE.md)

---

## 🔄 Жизненный цикл запроса

### Пример: Добавление товара в корзину

1. **Пользователь** нажимает "Добавить в корзину" на странице товара
2. **Маршрут** `POST /cart/add` → `CartController@add`
3. **Middleware** проверяет CSRF токен
4. **Request Validation** валидирует product_id и quantity
5. **CartController** вызывает `CartService->addItem()`
6. **CartService**:
   - Проверяет доступность товара (Product::available())
   - Проверяет остаток на складе
   - Создает/обновляет запись в `cart_items`
   - Фиксирует цену на момент добавления
7. **Response** возвращает JSON с обновленными данными корзины
8. **JavaScript** (frontend) обновляет счетчик корзины в header

---

## 🔐 Аутентификация и авторизация

- **Laravel Breeze** - стандартная аутентификация
- Роли: `is_admin` (boolean в таблице users)
- Middleware `auth` для защищенных маршрутов (orders, reviews)
- Корзина доступна как авторизованным пользователям, так и гостям

---

## 🚀 Деплой и окружение

### Локальная разработка:
```bash
make up          # Запуск Docker контейнеров
make migrate     # Миграции БД
make seed        # Заполнение тестовыми данными
make dev         # Запуск dev-сервера с hot reload
```

### Docker Services:
- **nginx** - `http://localhost:80`
- **postgres** - `localhost:5432` (доступна извне для DBeaver)
- **php-fpm** - внутренний сервис

### Переменные окружения:
- `.env.example` - шаблон конфигурации
- `.env` - реальная конфигурация (не коммитится)

---

## 🧪 Тестирование

```bash
php artisan test       # Запуск всех тестов
composer test         # Alias для запуска тестов
```

**Типы тестов:**
- Unit tests (`tests/Unit/`) - тестирование отдельных классов
- Feature tests (`tests/Feature/`) - тестирование HTTP маршрутов

---

## 📚 Дополнительные документы

- [DATABASE.md](DATABASE.md) - детальная схема базы данных
- [FEATURES.md](FEATURES.md) - список реализованных и планируемых фич
- [PROGRESS.md](PROGRESS.md) - текущий статус разработки
- [FRONTEND-STRUCTURE.md](FRONTEND-STRUCTURE.md) - структура фронтенда и компоненты
- [API.md](API.md) - API документация (когда появится)

---

## 🔗 Полезные ссылки

- [Laravel Documentation](https://laravel.com/docs/12.x)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/18/)
- [Docker Documentation](https://docs.docker.com/)

---

**Контакты разработчика:** rubin11  
**Git репозиторий:** https://github.com/Rubin-11/coffee-tea-shop-Laravel
