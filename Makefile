# ═══════════════════════════════════════════════════════════════════
# 🎯 Makefile для Laravel-проекта с Docker
# ═══════════════════════════════════════════════════════════════════
#
# 📌 Быстрый старт:
#   make help               - показать все доступные команды
#
# 🐳 Docker:
#   make up                 - запустить контейнеры
#   make down               - остановить контейнеры
#   make restart            - перезапустить контейнеры
#   make status             - показать статус контейнеров
#   make shell              - войти в PHP-контейнер
#
# 🗄 База данных:
#   make migrate            - выполнить миграции
#   make fresh              - пересоздать БД с сидами (быстрая команда)
#   make db                 - войти в консоль PostgreSQL
#
# 🚀 Генерация кода:
#   make model Product      - создать модель (-m для миграции, -c для контроллера)
#   make controller PostController -- --resource
#   make crud Product       - создать полный CRUD (модель, контроллер, миграция, фабрика, сидер)
#
# ⚠️  ВАЖНО: При использовании флагов Laravel добавляйте ' -- ' перед ними:
#   make controller TestController -- --invokable
#   make controller PostController -- --resource --model=Post
#
# 🧪 Разработка:
#   make test               - запустить тесты
#   make tinker             - запустить Tinker
#   make routes             - показать все маршруты
#   make clear              - очистить весь кэш
#   make optimize           - оптимизировать приложение
#
# 📦 Зависимости:
#   make install            - установить Composer зависимости
#   make npm-install        - установить NPM зависимости (локально, требуется Node.js)

.PHONY: up down down-v restart status ps build shell \
       migrate migrate-fresh fresh migrate-rollback migrate-rollback-step migrate-reset seed db \
       routes tinker key \
       config-clear cache-clear clear optimize \
       test test-failures \
       install require remove composer-update \
       npm-install npm-dev npm-build npm-watch \
       logs logs-php logs-postgres logs-nginx \
       fix-permissions help \
       model controller migration seeder factory request middleware \
       job event listener notification observer policy mail view crud

# ═══════════════════════════════════════════════════════════════════
# 🔧 Переменные
# ═══════════════════════════════════════════════════════════════════
APP = php
COMPOSE = docker compose
DB = postgres

# ═══════════════════════════════════════════════════════════════════
# 🐳 Docker - Управление контейнерами
# ═══════════════════════════════════════════════════════════════════
up:
	$(COMPOSE) up -d
	@echo "✅ Контейнеры запущены: http://localhost"

down:
	$(COMPOSE) down
	@echo "🛑 Контейнеры остановлены"

down-v:
	$(COMPOSE) down -v
	@echo "🛑 Контейнеры и тома удалены"

restart:
	$(COMPOSE) restart
	@echo "🔄 Контейнеры перезапущены"

status:
	$(COMPOSE) ps

ps: status

build:
	$(COMPOSE) build
	@echo "🔨 Docker образы пересобраны"

shell:
	$(COMPOSE) exec $(APP) bash

# ═══════════════════════════════════════════════════════════════════
# 🗄 База данных
# ═══════════════════════════════════════════════════════════════════
migrate:
	$(COMPOSE) exec $(APP) php /var/www/artisan migrate
	@echo "✅ Миграции выполнены"

migrate-fresh:
	$(COMPOSE) exec $(APP) php /var/www/artisan migrate:fresh --seed
	@echo "✅ БД пересоздана с сидами"

fresh: migrate-fresh

migrate-rollback:
	$(COMPOSE) exec $(APP) php /var/www/artisan migrate:rollback
	@echo "⏪ Миграция откачена"

migrate-rollback-step:
	$(COMPOSE) exec $(APP) php /var/www/artisan migrate:rollback --step=$(step)
	@echo "⏪ Откат миграций на $(step) шаг(ов)"

migrate-reset:
	$(COMPOSE) exec $(APP) php /var/www/artisan migrate:reset
	@echo "⏪ Все миграции откачены"

seed:
	$(COMPOSE) exec $(APP) php /var/www/artisan db:seed $(call get_args)
	@echo "🌱 Сиды выполнены"

db:
	$(COMPOSE) exec $(DB) psql -U postgres -d coffee_tea_shop
	@echo "🗄️  Вход в консоль PostgreSQL"

# ═══════════════════════════════════════════════════════════════════
# 🚀 Laravel Artisan - Основные команды
# ═══════════════════════════════════════════════════════════════════
routes:
	$(COMPOSE) exec $(APP) php /var/www/artisan route:list

tinker:
	$(COMPOSE) exec $(APP) php /var/www/artisan tinker

key:
	$(COMPOSE) exec $(APP) php /var/www/artisan key:generate
	@echo "🔑 APP_KEY сгенерирован"

# ═══════════════════════════════════════════════════════════════════
# 🧹 Кэш и оптимизация
# ═══════════════════════════════════════════════════════════════════
config-clear:
	$(COMPOSE) exec $(APP) php /var/www/artisan config:clear
	@echo "🧹 Кэш конфигурации очищен"

cache-clear:
	$(COMPOSE) exec $(APP) php /var/www/artisan cache:clear
	@echo "🧹 Кэш приложения очищен"

clear:
	$(COMPOSE) exec $(APP) php /var/www/artisan config:clear
	$(COMPOSE) exec $(APP) php /var/www/artisan cache:clear
	$(COMPOSE) exec $(APP) php /var/www/artisan route:clear
	$(COMPOSE) exec $(APP) php /var/www/artisan view:clear
	@echo "🧹 Весь кэш Laravel очищен"

optimize:
	$(COMPOSE) exec $(APP) php /var/www/artisan config:cache
	$(COMPOSE) exec $(APP) php /var/www/artisan route:cache
	$(COMPOSE) exec $(APP) php /var/www/artisan view:cache
	@echo "⚡ Laravel оптимизирован (кэш создан)"

# ═══════════════════════════════════════════════════════════════════
# 🧪 Тестирование
# ═══════════════════════════════════════════════════════════════════
test:
	$(COMPOSE) exec $(APP) php /var/www/artisan test

test-failures:
	$(COMPOSE) exec $(APP) php /var/www/artisan test --failures

# ═══════════════════════════════════════════════════════════════════
# 📦 Composer - Управление зависимостями PHP
# ═══════════════════════════════════════════════════════════════════
install:
	$(COMPOSE) run --rm composer install
	@echo "📦 Composer зависимости установлены"

require:
	$(COMPOSE) run --rm composer require $(package)
	@echo "✅ Пакет $(package) установлен"

remove:
	$(COMPOSE) run --rm composer remove $(package)
	@echo "🗑️  Пакет $(package) удален"

composer-update:
	$(COMPOSE) run --rm composer update
	@echo "📦 Composer зависимости обновлены"

# ═══════════════════════════════════════════════════════════════════
# 🎨 NPM - Управление фронтенд зависимостями
# ═══════════════════════════════════════════════════════════════════
# Примечание: NPM команды выполняются локально (требуется установленный Node.js)
npm-install:
	cd src && npm install
	@echo "📦 NPM зависимости установлены"

npm-dev:
	cd src && npm run dev
	@echo "🎨 Фронтенд собран (dev режим)"

npm-build:
	cd src && npm run build
	@echo "🎨 Фронтенд собран (production)"

npm-watch:
	cd src && npm run watch
	@echo "👀 Наблюдение за изменениями фронтенда"

# ═══════════════════════════════════════════════════════════════════
# 📋 Логи
# ═══════════════════════════════════════════════════════════════════
logs:
	$(COMPOSE) logs -f

logs-php:
	$(COMPOSE) logs -f php

logs-postgres:
	$(COMPOSE) logs -f postgres

logs-nginx:
	$(COMPOSE) logs -f nginx

# ═══════════════════════════════════════════════════════════════════
# 🛠 Генерация кода - Короткие команды с поддержкой флагов
# ═══════════════════════════════════════════════════════════════════

# Функция для извлечения аргументов (используется для передачи параметров в команды)
get_args = $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))

# --- Основные компоненты ---
model:
	$(COMPOSE) exec $(APP) php /var/www/artisan make:model $(call get_args)
	@echo "✅ Модель создана"

controller:
	$(COMPOSE) exec $(APP) php /var/www/artisan make:controller $(call get_args)
	@echo "✅ Контроллер создан"

migration:
	$(COMPOSE) exec $(APP) php /var/www/artisan make:migration $(call get_args)
	@echo "✅ Миграция создана"

seeder:
	$(COMPOSE) exec $(APP) php /var/www/artisan make:seeder $(call get_args)
	@echo "✅ Сидер создан"

factory:
	$(COMPOSE) exec $(APP) php /var/www/artisan make:factory $(call get_args)
	@echo "✅ Фабрика создана"

# --- Дополнительные компоненты ---
request:
	$(COMPOSE) exec $(APP) php /var/www/artisan make:request $(call get_args)
	@echo "✅ Form Request создан"

middleware:
	$(COMPOSE) exec $(APP) php /var/www/artisan make:middleware $(call get_args)
	@echo "✅ Middleware создан"

job:
	$(COMPOSE) exec $(APP) php /var/www/artisan make:job $(call get_args)
	@echo "✅ Job создан"

event:
	$(COMPOSE) exec $(APP) php /var/www/artisan make:event $(call get_args)
	@echo "✅ Event создан"

listener:
	$(COMPOSE) exec $(APP) php /var/www/artisan make:listener $(call get_args)
	@echo "✅ Listener создан"

notification:
	$(COMPOSE) exec $(APP) php /var/www/artisan make:notification $(call get_args)
	@echo "✅ Notification создана"

observer:
	$(COMPOSE) exec $(APP) php /var/www/artisan make:observer $(call get_args)
	@echo "✅ Observer создан"

policy:
	$(COMPOSE) exec $(APP) php /var/www/artisan make:policy $(call get_args)
	@echo "✅ Policy создана"

mail:
	$(COMPOSE) exec $(APP) php /var/www/artisan make:mail $(call get_args)
	@echo "✅ Mailable создан"

view:
	@mkdir -p src/resources/views/$(dir)
	@echo "<h1>View: $*</h1>" > src/resources/views/$(call get_args).blade.php
	@echo "✅ View создан: resources/views/$(call get_args).blade.php"

# ═══════════════════════════════════════════════════════════════════
# 🚀 CRUD - Быстрое создание полного функционала
# ═══════════════════════════════════════════════════════════════════
# Создаёт: модель, миграцию, контроллер, фабрику, сидер
crud:
	@echo "🛠  Создание CRUD для $(call get_args)..."
	$(COMPOSE) exec $(APP) php /var/www/artisan make:model $(call get_args) -m
	$(COMPOSE) exec $(APP) php /var/www/artisan make:controller $(call get_args)Controller --resource
	$(COMPOSE) exec $(APP) php /var/www/artisan make:factory $(call get_args)Factory --model=$(call get_args)
	$(COMPOSE) exec $(APP) php /var/www/artisan make:seeder $(call get_args)Seeder
	@echo "✅ CRUD для $(call get_args) создан!"
	@echo "   📁 Модель: app/Models/$(call get_args).php"
	@echo "   📁 Контроллер: app/Http/Controllers/$(call get_args)Controller.php"
	@echo "   📁 Миграция: database/migrations/*_create_$(call get_args)_table.php"
	@echo "   📁 Фабрика: database/factories/$(call get_args)Factory.php"
	@echo "   📁 Сидер: database/seeders/$(call get_args)Seeder.php"

# ═══════════════════════════════════════════════════════════════════
# 🔧 Утилиты
# ═══════════════════════════════════════════════════════════════════
fix-permissions:
	$(COMPOSE) exec $(APP) chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
	$(COMPOSE) exec $(APP) chmod -R 775 /var/www/storage /var/www/bootstrap/cache
	@echo "✅ Права доступа исправлены"

# ═══════════════════════════════════════════════════════════════════
# ❓ Справка и вспомогательные правила
# ═══════════════════════════════════════════════════════════════════

# Игнорируем все остальные цели, которые не соответствуют указанным правилам
%:
	@:

help:
	@echo ""
	@echo "═══════════════════════════════════════════════════════════════════"
	@echo "🎯 Laravel Docker Makefile - Полное руководство"
	@echo "═══════════════════════════════════════════════════════════════════"
	@echo ""
	@echo "🐳 DOCKER - Управление контейнерами:"
	@echo "  make up                    - запустить все контейнеры"
	@echo "  make down                  - остановить все контейнеры"
	@echo "  make down-v                - остановить и удалить тома (volumes)"
	@echo "  make restart               - перезапустить все контейнеры"
	@echo "  make status (ps)           - показать статус контейнеров"
	@echo "  make build                 - пересобрать Docker образы"
	@echo "  make shell                 - войти в PHP-контейнер"
	@echo ""
	@echo "🗄  БАЗА ДАННЫХ:"
	@echo "  make migrate               - выполнить миграции"
	@echo "  make fresh                 - пересоздать БД с сидами (быстрая команда)"
	@echo "  make migrate-fresh         - пересоздать БД с сидами"
	@echo "  make migrate-rollback      - откатить последнюю миграцию"
	@echo "  make migrate-rollback-step step=3 - откатить N миграций"
	@echo "  make migrate-reset         - откатить все миграции"
	@echo "  make seed                  - запустить сиды"
	@echo "  make db                    - войти в консоль PostgreSQL"
	@echo ""
	@echo "🚀 ГЕНЕРАЦИЯ КОДА - Основные компоненты:"
	@echo "  make model Product                    - создать модель"
	@echo "  make model Product -- -m              - модель + миграция"
	@echo "  make model Product -- -mc             - модель + миграция + контроллер"
	@echo "  make controller ProductController     - создать контроллер"
	@echo "  make controller TestController -- --invokable - инвокабельный контроллер"
	@echo "  make controller PostController -- --resource --model=Post - CRUD контроллер"
	@echo "  make migration create_products_table  - создать миграцию"
	@echo "  make seeder ProductSeeder             - создать сидер"
	@echo "  make factory ProductFactory           - создать фабрику"
	@echo ""
	@echo "🛠  ГЕНЕРАЦИЯ КОДА - Дополнительные компоненты:"
	@echo "  make request StoreProductRequest      - создать Form Request"
	@echo "  make middleware CheckAge              - создать Middleware"
	@echo "  make job SendEmailJob                 - создать Job"
	@echo "  make event OrderShipped               - создать Event"
	@echo "  make listener SendNotification        - создать Listener"
	@echo "  make notification OrderSent           - создать Notification"
	@echo "  make observer ProductObserver         - создать Observer"
	@echo "  make policy ProductPolicy             - создать Policy"
	@echo "  make mail OrderShipped                - создать Mailable"
	@echo "  make view products.index              - создать Blade-представление"
	@echo ""
	@echo "⚡ CRUD - Быстрое создание:"
	@echo "  make crud Product          - создать модель + контроллер + миграцию + фабрику + сидер"
	@echo ""
	@echo "🧪 ТЕСТИРОВАНИЕ:"
	@echo "  make test                  - запустить все тесты"
	@echo "  make test-failures         - запустить только проваленные тесты"
	@echo ""
	@echo "🧹 КЭШ И ОПТИМИЗАЦИЯ:"
	@echo "  make clear                 - очистить весь кэш Laravel"
	@echo "  make cache-clear           - очистить кэш приложения"
	@echo "  make config-clear          - очистить кэш конфигурации"
	@echo "  make optimize              - оптимизировать приложение (создать кэш)"
	@echo ""
	@echo "📦 ЗАВИСИМОСТИ - Composer:"
	@echo "  make install               - установить Composer зависимости"
	@echo "  make require package=name  - установить пакет"
	@echo "  make remove package=name   - удалить пакет"
	@echo "  make composer-update       - обновить все зависимости"
	@echo ""
	@echo "🎨 ЗАВИСИМОСТИ - NPM (фронтенд) [локально]:"
	@echo "  make npm-install           - установить NPM зависимости"
	@echo "  make npm-dev               - собрать фронтенд (dev режим)"
	@echo "  make npm-build             - собрать фронтенд (production)"
	@echo "  make npm-watch             - наблюдение за изменениями"
	@echo "  ⚠️  Требуется установленный Node.js на системе"
	@echo ""
	@echo "📋 ЛОГИ:"
	@echo "  make logs                  - логи всех контейнеров"
	@echo "  make logs-php              - логи PHP-контейнера"
	@echo "  make logs-postgres         - логи PostgreSQL"
	@echo "  make logs-nginx            - логи Nginx"
	@echo ""
	@echo "🔧 УТИЛИТЫ:"
	@echo "  make routes                - показать все маршруты"
	@echo "  make tinker                - запустить Tinker (REPL)"
	@echo "  make key                   - сгенерировать APP_KEY"
	@echo "  make fix-permissions       - исправить права доступа"
	@echo ""
	@echo "⚠️  ВАЖНО: При использовании флагов Laravel добавляйте ' -- ' перед ними:"
	@echo "  Пример: make controller TestController -- --invokable"
	@echo ""
	@echo "═══════════════════════════════════════════════════════════════════"
	@echo ""