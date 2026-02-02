# Makefile для Laravel-проекта с Docker
#
# 📌 Инструкция по использованию:
#
# Используйте короткие команды:
#
#   make up                 - запустить контейнеры
#   make down               - остановить
#   make shell              - зайти в PHP-контейнер
#
# 🚀 Генерация кода (короткий формат):
#   make model Product		- создать модель, -m миграцию -c контроллер
#   make controller TestController -- --invokable
#   make controller PostController -- --resource --model=Post
#   make migrate create_products_table
#   make seeder ProductSeeder
#   make factory Product
#   make request StoreProductRequest
#   make middleware CheckAge
#   make crud Product
#
# ⚠️ Важно: при использовании флагов Laravel (например --invokable, --resource)
# добавляйте двойное тире ' -- ' перед ними, чтобы make не интерпретировал их как свои опции.
#
# Пример:
#   make controller TestController -- --invokable
#   make controller PostController -- --resource --model=Post
#
# 🛠 Прочие команды:
#   make routes             			- показать маршруты
#   make migrate            			- миграции
#	make migrate-rollback   			- откат последней миграции
#	make migrate-rollback-step step=3	- откат миграций на несколько шагов
#	make migrate-reset					- откат всех миграций
#	make migrate-fresh					- пересоздать все миграции и запустите все исходные данные базы данных
#   make tinker             			- Tinker
#   make key                			- сгенерировать ключ
#   make test               			- тесты
#	make seed							- запуск сидов
#   make clear              			- очистить кэш
#   make help               			- помощь

.PHONY: up down shell routes migrate migrate-fresh tinker key \
       config-clear cache-clear test install composer logs logs-php \
       logs-postgres logs-nginx npm-install npm-dev npm-build \
       clear help \
       model controller migration seeder factory request middleware \
       job event listener notification observer policy mail view crud

# Переменные
APP = php
COMPOSE = docker compose

# Основные команды
up:
	$(COMPOSE) up -d
	@echo "✅ Контейнеры запущены: http://localhost"

down:
	$(COMPOSE) down
	@echo "🛑 Контейнеры остановлены"

down-v:
	$(COMPOSE) down -v
	@echo "🛑 Контейнеры и тома удалены"

shell:
	$(COMPOSE) exec $(APP) bash

# Laravel Artisan
routes:
	$(COMPOSE) exec $(APP) php /var/www/artisan route:list

migrate:
	$(COMPOSE) exec $(APP) php /var/www/artisan migrate

migrate-fresh:
	$(COMPOSE) exec $(APP) php /var/www/artisan migrate:fresh --seed

migrate-rollback:
	$(COMPOSE) exec $(APP) php /var/www/artisan migrate:rollback

migrate-rollback-step:
	$(COMPOSE) exec $(APP) php /var/www/artisan migrate:rollback --step=$(step)

migrate-reset:
	$(COMPOSE) exec $(APP) php /var/www/artisan migrate:reset

tinker:
	$(COMPOSE) exec $(APP) php /var/www/artisan tinker

key:
	$(COMPOSE) exec $(APP) php /var/www/artisan key:generate

config-clear:
	$(COMPOSE) exec $(APP) php /var/www/artisan config:clear

cache-clear:
	$(COMPOSE) exec $(APP) php /var/www/artisan cache:clear

test:
	$(COMPOSE) exec $(APP) php /var/www/artisan test

test-failures:
	$(COMPOSE) exec $(APP) php /var/www/artisan test --failures

# Composer
install:
	$(COMPOSE) run --rm composer install

require:
	$(COMPOSE) run --rm composer require $(package)

remove:
	$(COMPOSE) run --rm composer remove $(package)

# NPM
npm-install:
	docker compose run --rm node npm install

npm-dev:
	docker compose run --rm node npm run dev

npm-build:
	docker compose run --rm node npm run build

# Логи
logs:
	$(COMPOSE) logs -f

logs-php:
	$(COMPOSE) logs -f php

logs-postgres:
	$(COMPOSE) logs -f postgres

logs-nginx:
	$(COMPOSE) logs -f nginx

# Очистка
clear:
	$(COMPOSE) exec $(APP) php /var/www/artisan config:clear
	$(COMPOSE) exec $(APP) php /var/www/artisan cache:clear
	$(COMPOSE) exec $(APP) php /var/www/artisan route:clear
	$(COMPOSE) exec $(APP) php /var/www/artisan view:clear
	@echo "🧹 Кэш Laravel очищен"

# Генерация кода: короткие команды с поддержкой флагов

# Функция для извлечения аргументов
get_args = $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))

# Правила для генерации кода
model:
	$(COMPOSE) exec $(APP) php /var/www/artisan make:model $(call get_args)

controller:
	$(COMPOSE) exec $(APP) php /var/www/artisan make:controller $(call get_args)

migration:
	$(COMPOSE) exec $(APP) php /var/www/artisan make:migration $(call get_args)

seeder:
	$(COMPOSE) exec $(APP) php /var/www/artisan make:seeder $(call get_args)

seed:
	$(COMPOSE) exec $(APP) php /var/www/artisan db:seed $(call get_args)

factory:
	$(COMPOSE) exec $(APP) php /var/www/artisan make:factory $(call get_args)

request:
	$(COMPOSE) exec $(APP) php /var/www/artisan make:request $(call get_args)

middleware:
	$(COMPOSE) exec $(APP) php /var/www/artisan make:middleware $(call get_args)

job:
	$(COMPOSE) exec $(APP) php /var/www/artisan make:job $(call get_args)

event:
	$(COMPOSE) exec $(APP) php /var/www/artisan make:event $(call get_args)

listener:
	$(COMPOSE) exec $(APP) php /var/www/artisan make:listener $(call get_args)

notification:
	$(COMPOSE) exec $(APP) php /var/www/artisan make:notification $(call get_args)

observer:
	$(COMPOSE) exec $(APP) php /var/www/artisan make:observer $(call get_args)

policy:
	$(COMPOSE) exec $(APP) php /var/www/artisan make:policy $(call get_args)

mail:
	$(COMPOSE) exec $(APP) php /var/www/artisan make:mail $(call get_args)

view:
	@mkdir -p src/resources/views/$(dir)
	@echo "<h1>View: $*</h1>" > src/resources/views/$(call get_args).blade.php
	@echo "✅ View created: resources/views/$(call get_args).blade.php"

# 🚀 CRUD: создаёт модель, контроллер, миграцию, фабрику, сидер
crud:
	@echo "🛠 Создание CRUD для $(call get_args)..."
	$(COMPOSE) exec $(APP) php /var/www/artisan make:model $(call get_args) -m
	$(COMPOSE) exec $(APP) php /var/www/artisan make:controller $(call get_args)Controller --resource
	$(COMPOSE) exec $(APP) php /var/www/artisan make:factory $(call get_args)Factory --model=$(call get_args)
	$(COMPOSE) exec $(APP) php /var/www/artisan make:seeder $(call get_args)Seeder
	@echo "✅ CRUD для $(call get_args) создан: модель, контроллер, фабрика, сидер, миграция"

# Игнорируем все остальные цели, которые не соответствуют указанным правилам
%:
	@:

# Справка
help:
	@echo ""
	@echo "🎯 Laravel Docker Makefile"
	@echo "----------------------------"
	@echo " make up                 	- запустить контейнеры"
	@echo " make down               	- остановить"
	@echo " make down-v             	- остановить и удалить тома"
	@echo " make shell              	- войти в PHP-контейнер"
	@echo ""
	@echo " make routes             	- показать маршруты"
	@echo " make migrate            	- выполнить миграции"
	@echo " make migrate-fresh      	- пересоздать БД"
	@echo " make migrate-rollback		- откат последней миграции"
	@echo " make migrate-rollback-step	- откатить миграции на несколько шагов назад"
	@echo " make migrate-reset			- откатить все миграции"
	@echo " migrate-fresh				- пересоздать все миграции и запустите все исходные данные базы данных"
	@echo " make tinker             	- запустить Tinker"
	@echo " make key                	- сгенерировать APP_KEY"
	@echo ""
	@echo " make model Product          - создать модель, -m - миграцию -c - контроллер"
	@echo " make controller Product     - создать контроллер"
	@echo " make controller Product -- --invokable - инвокабельный контроллер"
	@echo " make controller Post -- --resource --model=Post - CRUD с моделью"
	@echo " make migration name         - создать миграцию"
	@echo " make seeder Product         - создать сидер"
	@echo " make seed					- запуск сидов"
	@echo " make factory Product        - создать фабрику"
	@echo " make request StoreProduct   - создать Form Request"
	@echo " make middleware CheckAge    - создать middleware"
	@echo " make job SendEmail          - создать Job"
	@echo " make event OrderShipped     - создать Event"
	@echo " make listener SendNotify    - создать Listener"
	@echo " make notification OrderSent - создать Notification"
	@echo " make observer Product       - создать Observer"
	@echo " make policy ProductPolicy   - создать Policy"
	@echo " make mail OrderShipped      - создать Mailable"
	@echo " make view products.index    - создать Blade-представление"
	@echo ""
	@echo " make crud Product           - полный CRUD: модель, контроллер, фабрика, сидер"
	@echo ""
	@echo " make test                   - запустить тесты"
	@echo " make install                - установить зависимости"
	@echo " make require package=имя    - установить пакет"
	@echo " make remove package=имя     - удалить пакет"
	@echo ""
	@echo " make logs                   - логи всех контейнеров"
	@echo " make clear                  - очистить кэш Laravel"
	@echo " make help                   - показать это описание"
	@echo ""