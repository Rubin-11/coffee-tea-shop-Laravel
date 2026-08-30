# Структура страниц сайта (Views)

**Последнее обновление:** 2026-02-11  
**Статус:** Все страницы созданы (заглушки), верстка в процессе

---

## 📝 Примечания (11.02.2026)

- **Динамические title и meta_description** корректно работают для: blog/show, categories/show, products/show, orders/show
- **ProductCard** — class-based компонент (`App\View\Components\ProductCard`), используется на главной и в каталоге

---

## 📄 Список всех страниц

### ✅ Главная страница
- **Файл:** `home.blade.php`
- **Маршрут:** `GET /` → `home`
- **Контроллер:** `HomeController@index`
- **Статус:** ✅ Полностью сверстана (09.02.2026)

---

### 🛍️ Каталог и товары

#### 1. Каталог всех товаров
- **Файл:** `products/index.blade.php`
- **Маршрут:** `GET /products` → `products.index`
- **Контроллер:** `ProductController@index`
- **Статус:** 📝 Заглушка (будет: фильтры, сортировка, пагинация)

#### 2. Карточка товара
- **Файл:** `products/show.blade.php`
- **Маршрут:** `GET /products/{slug}` → `products.show`
- **Контроллер:** `ProductController@show`
- **Статус:** 📝 Заглушка (будет: галерея, характеристики, отзывы)

#### 3. Список категорий
- **Файл:** `categories/index.blade.php`
- **Маршрут:** `GET /categories` → `categories.index`
- **Контроллер:** `CategoryController@index`
- **Статус:** 📝 Заглушка (будет: сетка категорий)

#### 4. Категория товаров
- **Файл:** `categories/show.blade.php`
- **Маршрут:** `GET /categories/{slug}` → `categories.show`
- **Контроллер:** `CategoryController@show`
- **Статус:** 📝 Заглушка (будет: товары категории, фильтры)

---

### 🛒 Корзина и заказы

#### 5. Корзина
- **Файл:** `cart/index.blade.php`
- **Маршрут:** `GET /cart` → `cart.index`
- **Контроллер:** `CartController@index`
- **Статус:** 📝 Заглушка (будет: управление товарами, расчет доставки)

#### 6. Оформление заказа
- **Файл:** `checkout/index.blade.php`
- **Маршрут:** `GET /checkout` → `checkout.index`
- **Контроллер:** `CheckoutController@index`
- **Статус:** 📝 Заглушка (будет: форма с данными, способы доставки/оплаты)

#### 7. Успешное оформление
- **Файл:** `checkout/success.blade.php`
- **Маршрут:** `GET /checkout/success/{order}` → `checkout.success`
- **Контроллер:** `CheckoutController@success`
- **Статус:** 📝 Заглушка (будет: благодарность, номер заказа)

#### 8. История заказов (требует auth)
- **Файл:** `orders/index.blade.php`
- **Маршрут:** `GET /orders` → `orders.index`
- **Контроллер:** `OrderController@index`
- **Middleware:** `auth`
- **Статус:** 📝 Заглушка (будет: список заказов, фильтры по статусам)

#### 9. Детали заказа (требует auth)
- **Файл:** `orders/show.blade.php`
- **Маршрут:** `GET /orders/{id}` → `orders.show`
- **Контроллер:** `OrderController@show`
- **Middleware:** `auth`
- **Статус:** 📝 Заглушка (будет: состав заказа, трек-номер, управление)

---

### 📝 Блог

#### 10. Список статей блога
- **Файл:** `blog/index.blade.php`
- **Маршрут:** `GET /blog` → `blog.index`
- **Контроллер:** `BlogController@index`
- **Статус:** 📝 Заглушка (будет: превью статей, фильтры, пагинация)

#### 11. Статья блога
- **Файл:** `blog/show.blade.php`
- **Маршрут:** `GET /blog/{slug}` → `blog.show`
- **Контроллер:** `BlogController@show`
- **Статус:** 📝 Заглушка (будет: полный текст, изображения, комментарии)

---

### 📄 Информационные страницы

#### 12. О компании
- **Файл:** `pages/about.blade.php`
- **Маршрут:** `GET /pages/about` → `pages.about`
- **Тип:** View route (без контроллера)
- **Статус:** ✅ Готово (2026-08-30): история, ценности, география

#### 13. Контакты
- **Файл:** `pages/contacts.blade.php`
- **Маршрут:** `GET /pages/contacts` → `pages.contacts`
- **Тип:** View route
- **Статус:** ✅ Готово (2026-08-30): телефоны, email, адреса, карта (Яндекс.Карты)

#### 14. Доставка и оплата
- **Файл:** `pages/delivery.blade.php`
- **Маршрут:** `GET /pages/delivery` → `pages.delivery`
- **Тип:** View route
- **Статус:** ✅ Готово (2026-08-30): способы доставки, стоимость, оплата

#### 15. Гарантии и возврат
- **Файл:** `pages/returns.blade.php`
- **Маршрут:** `GET /pages/returns` → `pages.returns`
- **Тип:** View route
- **Статус:** ✅ Готово (2026-08-30): условия возврата, гарантия свежести

#### 16. Политика конфиденциальности
- **Файл:** `pages/privacy.blade.php`
- **Маршрут:** `GET /pages/privacy` → `pages.privacy`
- **Тип:** View route
- **Статус:** ✅ Готово (2026-08-30): обработка персональных данных

#### 17. Пользовательское соглашение
- **Файл:** `pages/terms.blade.php`
- **Маршрут:** `GET /pages/terms` → `pages.terms`
- **Тип:** View route
- **Статус:** ✅ Готово (2026-08-30): правила использования сайта

---

### 👤 Личный кабинет

#### 18. Профиль пользователя (требует auth)
- **Файл:** `profile/index.blade.php`
- **Маршрут:** `GET /profile` → `profile.index`
- **Контроллер:** `ProfileController@index`
- **Middleware:** `auth`
- **Статус:** ✅ Готово (2026-08-30): карточка пользователя, последние заказы, выход

#### 19. Мои заказы (требует auth)
- **Файл:** `orders/index.blade.php`
- **Маршрут:** `GET /orders` → `orders.index`
- **Контроллер:** `OrderController@index`
- **Middleware:** `auth`
- **Статус:** ✅ Готово (2026-08-30): список заказов, фильтры по статусу, пагинация

#### 20. Детали заказа (требует auth)
- **Файл:** `orders/show.blade.php`
- **Маршрут:** `GET /orders/{id}` → `orders.show`
- **Контроллер:** `OrderController@show`
- **Middleware:** `auth`
- **Статус:** ✅ Готово (2026-08-30): состав, сводка, повтор/отмена заказа

---

### 🔐 Авторизация

#### 21. Вход
- **Файл:** `auth/login.blade.php`
- **Маршруты:** `GET/POST /auth/login` → `auth.login` / `auth.login.submit`
- **Контроллер:** `Auth\AuthController@showLoginForm` / `@login`
- **Middleware:** `guest`
- **Статус:** ✅ Готово (2026-08-30)

#### 22. Регистрация
- **Файл:** `auth/register.blade.php`
- **Маршруты:** `GET/POST /auth/register` → `auth.register` / `auth.register.submit`
- **Контроллер:** `Auth\AuthController@showRegisterForm` / `@register`
- **Middleware:** `guest`
- **Статус:** ✅ Готово (2026-08-30)

#### 23. Восстановление пароля
- **Файлы:** `auth/forgot-password.blade.php`, `auth/reset-password.blade.php`
- **Маршруты:** `GET/POST /auth/forgot-password`, `GET /auth/reset-password/{token}`, `POST /auth/reset-password`
- **Контроллер:** `Auth\AuthController` (sendResetLink / showResetForm / reset)
- **Middleware:** `guest`
- **Статус:** ✅ Готово (2026-08-30): таблица `password_reset_tokens`, письма через MAIL_MAILER=log

---

### ❌ Страницы ошибок

#### 19. Страница 404
- **Файл:** `errors/404.blade.php`
- **Маршрут:** Автоматически (при несуществующем URL)
- **Статус:** ✅ Готова (11.02.2026)

---

## 🎨 Общие компоненты

### Компоненты layout
- **`layouts/app.blade.php`** - главный layout (header + content + footer)
- **`components/header.blade.php`** - шапка сайта ✅
- **`components/footer.blade.php`** - подвал сайта ✅

### Переиспользуемые компоненты
- **`components/product-card.blade.php`** - карточка товара ✅
- **`components/blog-card.blade.php`** - карточка новости/статьи ✅
- **`components/button.blade.php`** - универсальная кнопка ✅

### Секции главной страницы (в папке `home/`)
- `hero.blade.php` - главный баннер ✅
- `catalog.blade.php` - каталог продукции ✅
- `featured-products.blade.php` - товары со скидкой ✅
- `advantages.blade.php` - преимущества ✅
- `blog-banner.blade.php` - баннер блога ✅
- `news.blade.php` - новости компании ✅
- `instagram.blade.php` - галерея Instagram ✅
- `newsletter.blade.php` - форма подписки ✅

---

## 📊 Статистика

| Категория | Количество | Статус |
|-----------|-----------|--------|
| Всего страниц | 19 | 18 заглушек + 1 готовая |
| Компонентов | 11 | 8 готовых + 3 в разработке |
| Секций главной | 8 | ✅ Все готовы |
| Layout файлов | 1 | ✅ Готов |

---

## 🔄 Следующие шаги

### Приоритет 1 (высокий):
1. Верстка каталога товаров
2. Верстка карточки товара
3. Верстка корзины
4. Верстка оформления заказа

### Приоритет 2 (средний):
5. Верстка страниц блога
6. Верстка информационных страниц
7. Модальные окна авторизации

### Приоритет 3 (низкий):
8. Личный кабинет
9. Адаптивная версия

---

## 🎯 Примечания

- Все страницы используют общий `layouts/app.blade.php` с header и footer
- Все заглушки содержат только текст с описанием будущего контента
- Верстка будет выполняться постепенно, начиная с каталога
- Дизайн основан на макете Pixso "Frame 2"
- Используется желтый бренд-цвет `#FDB913`

---

**Ответственный:** rubin11  
**Следующее обновление:** После верстки первых страниц
