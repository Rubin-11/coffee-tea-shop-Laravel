# ☕ Coffee-Tea Shop

> Интернет-магазин премиального кофе и чая на Laravel 13

[![Laravel](https://img.shields.io/badge/Laravel-13.0-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3+-blue.svg)](https://php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-18-blue.svg)](https://postgresql.org)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## 📖 О проекте

**Coffee-Tea Shop** — современный интернет-магазин для продажи элитного кофе, чая и сопутствующих товаров. Проект построен на Laravel 13 с использованием лучших практик веб-разработки.

### Основные возможности:

- 🛍️ Каталог товаров с фильтрацией и сортировкой
- 🛒 Корзина покупок (для авторизованных и гостей)
- 💳 Оформление заказов с различными способами оплаты и доставки
- ⭐ Система отзывов на товары
- 📝 Блог о кофе и чае
- 📧 Email-рассылка для подписчиков
- 👤 Личный кабинет с историей заказов
- ✅ 286 unit-тестов (сервисы, модели, фабрики)

---

## 🚀 Быстрый старт

### Требования

- Docker & Docker Compose
- WSL 2 (для Windows разработчиков)
- Git

### Установка и запуск

```bash
# 1. Клонировать репозиторий
git clone https://github.com/rubin11/coffee-tea-shop-Laravel.git
cd coffee-tea-shop-Laravel

# 2. Запустить Docker контейнеры
make up

# 3. Установить зависимости (внутри контейнера)
make composer-install
make npm-install

# 4. Настроить окружение
cp src/.env.example src/.env
make key-generate

# 5. Запустить миграции и сидеры
make migrate
make seed

# 6. Открыть в браузере
# http://localhost
```

**Готово! 🎉** Магазин доступен по адресу `http://localhost`

---

## 📚 Документация

### Основные документы:

| Документ | Описание |
|----------|----------|
| [📋 ARCHITECTURE.md](docs/ARCHITECTURE.md) | Полная архитектура проекта, технологический стек, паттерны |
| [🗄️ DATABASE.md](docs/DATABASE.md) | Схема базы данных, таблицы, связи, индексы |
| [✨ FEATURES.md](docs/FEATURES.md) | Реализованный и запланированный функционал |
| [📊 PROGRESS.md](docs/PROGRESS.md) | Текущий статус разработки, последние изменения |
| [📄 PAGES.md](docs/PAGES.md) | Список всех страниц сайта (views) |
| [🎨 FRONTEND-STRUCTURE.md](docs/FRONTEND-STRUCTURE.md) | Структура фронтенда и компонентов |
| [📋 UNIT_TESTING_PLAN.md](docs/UNIT_TESTING_PLAN.md) | План unit тестирования (286 тестов) |
| [🏭 FACTORIES.md](docs/FACTORIES.md) | Документация по фабрикам моделей |

### Для разработчиков:

- [🔧 Makefile](Makefile) - все доступные команды для управления проектом
- [🐳 Docker Setup](docker-compose.yml) - конфигурация Docker окружения
- [📐 UML Диаграмма](src/database/UML.drawio) - ERD схема базы данных

---

## 🛠 Основные команды

### Docker

```bash
make up              # Запустить контейнеры
make down            # Остановить контейнеры
make restart         # Перезапустить контейнеры
make logs            # Просмотр логов
```

### База данных

```bash
make migrate         # Выполнить миграции
make migrate-fresh   # Пересоздать БД
make seed            # Заполнить тестовыми данными
make db-reset        # Сбросить и заполнить БД
```

### Разработка

```bash
make shell           # Войти в контейнер PHP
make tinker          # Laravel Tinker
make test            # Запустить тесты
make routes          # Показать список маршрутов
make clear           # Очистить кэш
```

### Генерация кода

```bash
make model Product                              # Создать модель
make controller ProductController               # Создать контроллер
make migration create_products_table            # Создать миграцию
make seeder ProductSeeder                       # Создать сидер
make request StoreProductRequest                # Создать Form Request
make crud Product                               # Создать полный CRUD
```

**Полный список команд:** см. [Makefile](Makefile) или выполните `make help`

---

## 🏗 Архитектура (кратко)

```
coffee-tea-shop-Laravel/
├── docker/              # Docker конфигурация
├── src/                 # Laravel приложение
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/    # 9 контроллеров
│   │   │   ├── Middleware/     # Middleware
│   │   │   ├── Requests/       # Form Requests
│   │   │   └── ViewComposers/  # View Composers
│   │   ├── Models/             # 12 моделей
│   │   └── Services/           # 4 бизнес-сервиса
│   ├── database/
│   │   ├── migrations/         # Миграции БД
│   │   └── UML.drawio         # ERD диаграмма
│   ├── resources/
│   │   ├── views/             # Blade шаблоны
│   │   ├── css/               # Стили
│   │   └── js/                # JavaScript
│   └── routes/
│       └── web.php            # Web маршруты
├── docs/                # Документация проекта
│   ├── DATABASE.md      # Схема БД
│   ├── FEATURES.md      # Функционал
│   └── PROGRESS.md      # Прогресс
├── ARCHITECTURE.md      # Архитектура
├── docker-compose.yml   # Docker конфигурация
├── Makefile            # Команды управления
└── README.md           # Этот файл
```

**Подробнее:** см. [ARCHITECTURE.md](ARCHITECTURE.md)

---

## 🔧 Технологический стек

### Backend
- **Laravel 13** - PHP фреймворк
- **PHP 8.3+** - язык программирования (в Docker — 8.5)
- **PostgreSQL 18** - база данных
- **Eloquent ORM** - работа с БД

### Frontend
- **Blade** - шаблонизатор
- **Vite** - сборщик
- **CSS/JavaScript** - стилизация и интерактивность

### DevOps
- **Docker** - контейнеризация
- **Nginx** - веб-сервер
- **Makefile** - автоматизация команд
- **WSL 2** - среда разработки (для Windows)

---

## 📊 Статус проекта

**Текущая готовность:** ~65%

| Компонент | Статус |
|-----------|--------|
| База данных | ✅ 100% |
| Backend (контроллеры, сервисы) | ✅ 95% |
| Views (Blade шаблоны) | 🔄 40% |
| Frontend (CSS, JS) | 🔄 30% |
| Документация | ✅ 100% |
| Тесты | 🔄 25% (CartService: 100%) |

**Подробнее:** см. [PROGRESS.md](docs/PROGRESS.md)

---

## 🧪 Тестирование

### Быстрый старт

```bash
# Запустить все тесты
make test

# Запустить только Unit тесты
docker compose exec php php artisan test --testsuite=Unit

# Запустить конкретный тест
docker compose exec php php artisan test --filter CartServiceTest

# С покрытием кода
docker compose exec php php artisan test --coverage
```

### Документация по тестированию

| Документ | Описание |
|----------|----------|
| [📋 UNIT_TESTING_PLAN.md](docs/UNIT_TESTING_PLAN.md) | Детальный план unit тестирования (230+ тестов) |
| [🏭 FACTORIES.md](docs/FACTORIES.md) | Документация по фабрикам моделей |
| [📖 src/tests/README.md](src/tests/README.md) | Полная документация по написанию и запуску тестов |

### Текущее состояние

- ✅ Тестовое окружение настроено
- ✅ TestCase с хелперами готов
- ✅ **Все фабрики моделей созданы (8 штук)**
- ✅ Фабрики протестированы (18 тестов / 113 assertions)
- ✅ **CartService** - 43 теста / 95 assertions 🎯
- ✅ **OrderService** - 32 теста / 80 assertions 🎯
- ✅ **ReviewService** - 38 тестов / 106 assertions 🎯
- ✅ **ProductFilterService** - 39 тестов / 131 assertions 🎯
- ✅ **Product Model** - 26 тестов / 61 assertions 🎯
- ✅ **CartItem Model** - 23 теста / 45 assertions 🎯
- ✅ **Order Model** - 30 тестов / 67 assertions 🎯
- ⏳ Feature тесты - запланированы

**Общая статистика тестов:** 231 тест / 598 assertions - все проходят успешно ✅

---

## 🤝 Участие в разработке

### Git Workflow

Проект использует Feature Branch Workflow:

```bash
# Создать новую ветку
git checkout -b feature/название-фичи

# После завершения работы
git add .
git commit  # Откроется nano для детального сообщения
git push -u origin feature/название-фичи
```

**Важно:** Следуйте [Git правилам проекта](.cursor/rules/git-workflow.mdc)

### Правила коммитов

Используйте формат Conventional Commits:

```
type: Краткое описание

- Детали изменения
- Еще детали

Файлы: путь/к/файлу.php
```

**Типы:** `feat`, `fix`, `refactor`, `docs`, `style`, `test`, `chore`

**Примеры:**

```bash
feat: Добавлена система поиска товаров
- Создан SearchController
- Реализован полнотекстовый поиск
- Добавлен AJAX автокомплит

Файлы: app/Http/Controllers/SearchController.php
```

```bash
fix: Исправлен расчет суммы в корзине
- Учтена скидка при расчете
- Исправлено округление

Файлы: app/Services/CartService.php
```

---

## 📝 Важные правила

### Обновление документации

**ВАЖНО:** Перед каждым коммитом обязательно обновляйте документацию!

- При изменении БД → обновите `docs/DATABASE.md`
- При добавлении фичи → обновите `docs/FEATURES.md`
- При **любом коммите** → обновите `docs/PROGRESS.md`

**Подробнее:** см. [.cursor/rules/documentation-update.mdc](.cursor/rules/documentation-update.mdc)

---

## 📝 Лицензия

MIT License - см. [LICENSE](LICENSE)

---

## 👨‍💻 Автор

**Разработчик:** rubin11

**Проект:** Coffee-Tea Shop Laravel  
**Репозиторий:** https://github.com/Rubin-11/coffee-tea-shop-Laravel  
**Год:** 2026

---

## 🙏 Благодарности

- [Laravel Framework](https://laravel.com)
- [PostgreSQL](https://postgresql.org)
- [Docker](https://docker.com)
- И всем, кто вносит вклад в Open Source!

---

<p align="center">Сделано с ❤️ и ☕</p>
