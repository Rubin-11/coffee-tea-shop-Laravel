<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Tag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeder для создания товаров с изображениями и тегами
 * 
 * Создает реалистичный ассортимент магазина:
 * - 35-42 товаров свежеобжаренного кофе (по географическим регионам)
 * - 21-24 товаров чая и кофейных напитков
 * - 14-16 товаров продукции вендинга
 * - 15-17 товаров здорового питания
 * 
 * К каждому товару добавляется:
 * - 2-4 изображения (одно главное)
 * - 1-3 тега ("Новинка", "Хит продаж", "Популярное" и т.д.)
 * - Привязка к категории и подкатегории
 */
class ProductSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Массив для хранения созданных тегов (кеш)
     * 
     * @var \Illuminate\Support\Collection
     */
    private $tags;

    /**
     * Массив для хранения созданных категорий (кеш)
     * 
     * @var \Illuminate\Support\Collection
     */
    private $categories;

    /**
     * Запуск seeder'а для заполнения товаров
     * 
     * Создает товары в следующем порядке:
     * 1. Свежеобжаренный кофе (35-42 шт)
     * 2. Чай и кофейные напитки (21-24 шт)
     * 3. Продукция вендинга (14-16 шт)
     * 4. Здоровое питание (15-17 шт)
     * 
     * Для каждого товара создаются изображения и привязываются теги.
     */
    public function run(): void
    {
        $this->command->info('☕ Создание товаров магазина...');
        $this->command->newLine();

        // Загружаем все теги и категории в память для быстрого доступа
        $this->tags = Tag::all();
        $this->categories = Category::all()->keyBy('slug');

        // Проверяем, что есть категории и теги
        if ($this->categories->isEmpty()) {
            $this->command->error('❌ Ошибка: сначала нужно запустить CategorySeeder!');
            return;
        }

        if ($this->tags->isEmpty()) {
            $this->command->error('❌ Ошибка: сначала нужно запустить TagSeeder!');
            return;
        }

        // Счетчик созданных товаров
        $totalProducts = 0;

        // 1. Создаем свежеобжаренный кофе (35-42 шт)
        $this->command->info('1️⃣  Создание свежеобжаренного кофе...');
        $freshCoffeeProducts = $this->createFreshRoastedCoffeeProducts();
        $totalProducts += $freshCoffeeProducts;
        $this->command->info("   ✅ Создано свежеобжаренного кофе: {$freshCoffeeProducts}");
        $this->command->newLine();

        // 2. Создаем чай и кофейные напитки (21-24 шт)
        $this->command->info('2️⃣  Создание чая и кофейных напитков...');
        $teaDrinksProducts = $this->createTeaCoffeeDrinksProducts();
        $totalProducts += $teaDrinksProducts;
        $this->command->info("   ✅ Создано чая и напитков: {$teaDrinksProducts}");
        $this->command->newLine();

        // 3. Создаем продукцию вендинга (14-16 шт)
        $this->command->info('3️⃣  Создание продукции вендинга...');
        $vendingProducts = $this->createVendingProducts();
        $totalProducts += $vendingProducts;
        $this->command->info("   ✅ Создано продукции вендинга: {$vendingProducts}");
        $this->command->newLine();

        // 4. Создаем здоровое питание (15-17 шт)
        $this->command->info('4️⃣  Создание товаров здорового питания...');
        $healthyProducts = $this->createHealthyFoodProducts();
        $totalProducts += $healthyProducts;
        $this->command->info("   ✅ Создано товаров здорового питания: {$healthyProducts}");
        $this->command->newLine();

        // Итоговая статистика
        $this->command->info('✅ Всего создано товаров: ' . $totalProducts);
        $this->command->info('📸 Всего создано изображений: ' . ProductImage::count());
        $this->command->info('🏷️  Товары привязаны к тегам и категориям');
    }

    /**
     * Создание свежеобжаренного кофе (35-42 товара)
     * 
     * Распределение по подкатегориям:
     * - Африка (5-6 товаров)
     * - Йемен (2-3 товара)
     * - Уганда (2-3 товара)
     * - Эфиопия (5-6 товаров)
     * - Азия (5-6 товаров)
     * - Центральная Америка (6-7 товаров)
     * - Латинская Америка (8-9 товаров)
     * 
     * @return int Количество созданных товаров
     */
    private function createFreshRoastedCoffeeProducts(): int
    {
        $count = 0;

        // Получаем категорию "Свежеобжаренный кофе"
        $freshCoffeeCat = $this->categories->get('svezheobzharennyy-kofe');
        
        // Получаем все подкатегории кофе
        $coffeeSubcategories = Category::where('parent_id', $freshCoffeeCat->id)->get()->keyBy('slug');

        // Названия товаров для каждой подкатегории
        $coffeeProductsByRegion = [
            'afrika' => [
                ['name' => 'Кения АА', 'price' => 580, 'weight' => [250, 500]],
                ['name' => 'Руанда Bourbon', 'price' => 520, 'weight' => [250]],
                ['name' => 'Танзания Пиберри', 'price' => 550, 'weight' => [250, 500]],
                ['name' => 'Бурунди', 'price' => 540, 'weight' => [250]],
                ['name' => 'Замбия', 'price' => 510, 'weight' => [500]],
            ],
            'yemen' => [
                ['name' => 'Йемен Мокка', 'price' => 780, 'weight' => [250]],
                ['name' => 'Йемен Матари', 'price' => 820, 'weight' => [250]],
            ],
            'uganda' => [
                ['name' => 'Уганда Бугису', 'price' => 460, 'weight' => [250, 500]],
                ['name' => 'Уганда Робуста', 'price' => 380, 'weight' => [500, 1000]],
            ],
            'efiopiya' => [
                ['name' => 'Эфиопия Иргачиф', 'price' => 520, 'weight' => [250, 500]],
                ['name' => 'Эфиопия Сидамо', 'price' => 490, 'weight' => [250, 500, 1000]],
                ['name' => 'Эфиопия Харар', 'price' => 560, 'weight' => [250]],
                ['name' => 'Эфиопия Йоргачеффе', 'price' => 580, 'weight' => [250, 500]],
                ['name' => 'Эфиопия Лиму', 'price' => 510, 'weight' => [500]],
            ],
            'aziya' => [
                ['name' => 'Суматра Манделинг', 'price' => 460, 'weight' => [250, 500]],
                ['name' => 'Ява Арабика', 'price' => 440, 'weight' => [250, 500, 1000]],
                ['name' => 'Сулавеси Торая', 'price' => 490, 'weight' => [250, 500]],
                ['name' => 'Бали Кинтамани', 'price' => 520, 'weight' => [250]],
                ['name' => 'Вьетнам Арабика', 'price' => 390, 'weight' => [500, 1000]],
            ],
            'centralnaya-amerika' => [
                ['name' => 'Гватемала Антигуа', 'price' => 480, 'weight' => [250, 500]],
                ['name' => 'Гватемала Уэуэтенанго', 'price' => 520, 'weight' => [250]],
                ['name' => 'Коста-Рика Тарразу', 'price' => 540, 'weight' => [250, 500]],
                ['name' => 'Коста-Рика Центральная Долина', 'price' => 510, 'weight' => [250]],
                ['name' => 'Панама Гейша', 'price' => 890, 'weight' => [250]],
                ['name' => 'Сальвадор', 'price' => 470, 'weight' => [250, 500]],
            ],
            'latinskaya-amerika' => [
                ['name' => 'Колумбия Супремо', 'price' => 450, 'weight' => [250, 500, 1000]],
                ['name' => 'Колумбия Уила', 'price' => 480, 'weight' => [250, 500]],
                ['name' => 'Колумбия Эксельсо', 'price' => 420, 'weight' => [250]],
                ['name' => 'Бразилия Сантос', 'price' => 380, 'weight' => [250, 500, 1000]],
                ['name' => 'Бразилия Моджиана', 'price' => 400, 'weight' => [250, 500]],
                ['name' => 'Бразилия Серрадо', 'price' => 420, 'weight' => [500, 1000]],
                ['name' => 'Перу', 'price' => 440, 'weight' => [250, 500]],
                ['name' => 'Эквадор', 'price' => 460, 'weight' => [250]],
            ],
        ];

        // Создаем товары для каждого региона
        foreach ($coffeeProductsByRegion as $regionSlug => $products) {
            $subcategory = $coffeeSubcategories->get($regionSlug);
            
            if (!$subcategory) {
                continue;
            }

            foreach ($products as $productData) {
                // Создаем товар для каждого доступного веса
                foreach ($productData['weight'] as $weight) {
                    $productName = $productData['name'] . ' ' . $weight . 'г';
                    $product = $this->createCoffeeProduct(
                        $productName,
                        $subcategory->id,
                        $productData['price'],
                        $weight
                    );
                    
                    if ($product) {
                        $count++;
                        $this->command->line("   ✓ {$productName} ({$subcategory->name})");
                    }
                }
            }
        }

        return $count;
    }

    /**
     * Создать один товар кофе
     * 
     * @param string $name Название товара
     * @param int $categoryId ID категории
     * @param float|null $price Цена товара (если null - генерируется случайная)
     * @param int|null $weight Вес товара в граммах (если null - генерируется случайный)
     * @return Product|null Созданный товар или null при ошибке
     */
    private function createCoffeeProduct(string $name, int $categoryId, ?float $price = null, ?int $weight = null): ?Product
    {
        try {
            // Генерируем уникальный slug
            $slug = Str::slug($name) . '-' . fake()->numberBetween(1, 999);

            // Используем переданные значения или генерируем случайные
            $productPrice = $price ?? fake()->randomFloat(2, 300, 800);
            $productWeight = $weight ?? fake()->randomElement([250, 500, 1000]);

            // Создаем товар кофе
            $product = Product::create([
                'category_id' => $categoryId,
                'name' => $name,
                'slug' => $slug,
                'description' => $this->generateCoffeeDescription($name),
                'long_description' => $this->generateCoffeeLongDescription($name),
                'price' => $productPrice,
                'old_price' => fake()->boolean(30) ? $productPrice * 1.3 : null,
                'weight' => $productWeight,
                'sku' => 'CF-' . fake()->unique()->numberBetween(1000, 9999),
                'stock' => fake()->numberBetween(5, 100),
                'rating' => fake()->randomFloat(2, 4.0, 5.0),
                'reviews_count' => 0, // Будет обновлено после создания отзывов
                'bitterness_percent' => fake()->randomElement([0, 2, 4, 6, 8, 10]),
                'acidity_percent' => fake()->randomElement([0, 2, 4, 6, 8, 10]),
                'is_featured' => fake()->boolean(25), // 25% товаров рекомендуемые
                'is_available' => true,
                'meta_title' => $name . ' - купить кофе в интернет-магазине',
                'meta_description' => "Купить {$name} по выгодной цене. Свежая обжарка, доставка по России.",
            ]);

            // Добавляем изображения к товару (2-4 изображения)
            $this->addProductImages($product, 'coffee');

            // Привязываем теги к товару (1-3 тега)
            $this->attachRandomTags($product);

            return $product;
            
        } catch (\Exception $e) {
            $this->command->error("   ✗ Ошибка при создании товара {$name}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Создание чая и кофейных напитков (21-24 товара)
     * 
     * Распределение по подкатегориям:
     * - Черный чай (3-4 товара)
     * - Зеленый чай (4-5 товаров)
     * - Молочный улун (3-4 товара)
     * - Травяной чай (3-4 товара)
     * - Матча (2-3 товара)
     * - Пуэр (2-3 товара)
     * - Кофейные напитки (4-5 товаров)
     * 
     * @return int Количество созданных товаров
     */
    private function createTeaCoffeeDrinksProducts(): int
    {
        $count = 0;

        // Получаем категорию "Чай и кофейные напитки"
        $teaCoffeeCat = $this->categories->get('chay-i-kofejnye-napitki');

        if (!$teaCoffeeCat) {
            $this->command->error('   ✗ Категория "Чай и кофейные напитки" не найдена!');
            return 0;
        }

        // Получаем подкатегории
        $subcategories = Category::where('parent_id', $teaCoffeeCat->id)->get()->keyBy('slug');

        // Товары для каждой подкатегории
        $teaProductsByCategory = [
            'chernyy-chay' => [
                ['name' => 'Черный чай Эрл Грей', 'price' => 280, 'weight' => [50, 100]],
                ['name' => 'Ассам FTGFOP1', 'price' => 320, 'weight' => [100]],
                ['name' => 'Дарджилинг первый сбор', 'price' => 450, 'weight' => [50, 100]],
                ['name' => 'Цейлонский Оранж Пеко', 'price' => 290, 'weight' => [100]],
            ],
            'zelenyy-chay' => [
                ['name' => 'Зеленый чай Сенча', 'price' => 260, 'weight' => [50, 100]],
                ['name' => 'Жасминовый чай Мао Фэн', 'price' => 340, 'weight' => [100]],
                ['name' => 'Генмайча', 'price' => 310, 'weight' => [100]],
                ['name' => 'Лунцзин', 'price' => 420, 'weight' => [50]],
                ['name' => 'Би Ло Чунь', 'price' => 380, 'weight' => [50]],
            ],
            'molochnyy-ulung' => [
                ['name' => 'Молочный улун классический', 'price' => 350, 'weight' => [50, 100]],
                ['name' => 'Молочный улун премиум', 'price' => 420, 'weight' => [50]],
                ['name' => 'Женьшень улун', 'price' => 380, 'weight' => [50, 100]],
            ],
            'travyanoy-chay' => [
                ['name' => 'Ройбуш ванильный', 'price' => 240, 'weight' => [100]],
                ['name' => 'Каркаде высшего сорта', 'price' => 220, 'weight' => [100, 250]],
                ['name' => 'Мятный чай', 'price' => 190, 'weight' => [50, 100]],
                ['name' => 'Ромашковый чай', 'price' => 180, 'weight' => [50]],
            ],
            'matcha' => [
                ['name' => 'Матча премиум класс', 'price' => 580, 'weight' => [30, 50]],
                ['name' => 'Матча церемониальная', 'price' => 680, 'weight' => [30]],
                ['name' => 'Матча кулинарная', 'price' => 420, 'weight' => [50, 100]],
            ],
            'puer' => [
                ['name' => 'Пуэр Шу 5 лет выдержки', 'price' => 450, 'weight' => [100, 250]],
                ['name' => 'Пуэр Шен 3 года', 'price' => 520, 'weight' => [100]],
                ['name' => 'Пуэр Мини То Ча', 'price' => 380, 'weight' => [50]],
            ],
            'kofejnye-napitki' => [
                ['name' => 'Растворимый кофе Премиум', 'price' => 320, 'weight' => [100, 200], 'sku_prefix' => 'IC'],
                ['name' => 'Сублимированный кофе Gold', 'price' => 450, 'weight' => [100], 'sku_prefix' => 'IC'],
                ['name' => 'Капучино 3в1', 'price' => 180, 'weight' => [150], 'sku_prefix' => 'IC'],
                ['name' => 'Латте микс', 'price' => 200, 'weight' => [150], 'sku_prefix' => 'IC'],
                ['name' => 'Айс-кофе микс', 'price' => 150, 'weight' => [100], 'sku_prefix' => 'IC'],
            ],
        ];

        // Создаем товары для каждой подкатегории
        foreach ($teaProductsByCategory as $categorySlug => $products) {
            $subcategory = $subcategories->get($categorySlug);
            
            if (!$subcategory) {
                continue;
            }

            foreach ($products as $productData) {
                // Создаем товар для каждого доступного веса
                foreach ($productData['weight'] as $weight) {
                    $productName = $productData['name'] . ' ' . $weight . 'г';
                    $skuPrefix = $productData['sku_prefix'] ?? 'TE';
                    
                    $product = $this->createTeaProduct(
                        $productName,
                        $subcategory->id,
                        $productData['price'],
                        $weight,
                        $skuPrefix
                    );
                    
                    if ($product) {
                        $count++;
                        $this->command->line("   ✓ {$productName} ({$subcategory->name})");
                    }
                }
            }
        }

        return $count;
    }

    /**
     * Создать один товар чая
     * 
     * @param string $name Название товара
     * @param int $categoryId ID категории
     * @param float|null $price Цена товара (если null - генерируется случайная)
     * @param int|null $weight Вес товара в граммах (если null - генерируется случайный)
     * @param string $skuPrefix Префикс для артикула (TE - чай, IC - растворимый кофе)
     * @return Product|null Созданный товар или null при ошибке
     */
    private function createTeaProduct(string $name, int $categoryId, ?float $price = null, ?int $weight = null, string $skuPrefix = 'TE'): ?Product
    {
        try {
            // Генерируем уникальный slug
            $slug = Str::slug($name) . '-' . fake()->numberBetween(1, 999);

            // Используем переданные значения или генерируем случайные
            $productPrice = $price ?? fake()->randomFloat(2, 200, 600);
            $productWeight = $weight ?? fake()->randomElement([50, 100, 250]);

            // Создаем товар чая
            $product = Product::create([
                'category_id' => $categoryId,
                'name' => $name,
                'slug' => $slug,
                'description' => $this->generateTeaDescription($name),
                'long_description' => $this->generateTeaLongDescription($name),
                'price' => $productPrice,
                'old_price' => fake()->boolean(25) ? $productPrice * 1.3 : null,
                'weight' => $productWeight,
                'sku' => $skuPrefix . '-' . fake()->unique()->numberBetween(1000, 9999),
                'stock' => fake()->numberBetween(10, 80),
                'rating' => fake()->randomFloat(2, 4.0, 5.0),
                'reviews_count' => 0, // Будет обновлено после создания отзывов
                'bitterness_percent' => 0, // Для чая не применяется
                'acidity_percent' => 0, // Для чая не применяется
                'is_featured' => fake()->boolean(20), // 20% товаров рекомендуемые
                'is_available' => true,
                'meta_title' => $name . ' - купить чай в интернет-магазине',
                'meta_description' => "Купить {$name} по выгодной цене. Качественный листовой чай, быстрая доставка.",
            ]);

            // Добавляем изображения к товару (2-3 изображения)
            $this->addProductImages($product, 'tea');

            // Привязываем теги к товару (1-3 тега)
            $this->attachRandomTags($product);

            return $product;
            
        } catch (\Exception $e) {
            $this->command->error("   ✗ Ошибка при создании товара {$name}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Создание продукции вендинга (14-16 товаров)
     * 
     * Распределение по подкатегориям:
     * - Гранулированный кофе (2-3 товара)
     * - Гранулированный цикорий (2 товара)
     * - Зерновой кофе (2-3 товара)
     * - Гранулированный какао (2 товара)
     * - Гранулированные кофейные напитки (2-3 товара)
     * - Кофе порошкообразный (2 товара)
     * - Сухое молоко гранулированное (2 товара)
     * 
     * @return int Количество созданных товаров
     */
    private function createVendingProducts(): int
    {
        $count = 0;

        // Получаем категорию "Продукция вендинга"
        $vendingCat = $this->categories->get('produktsiya-vendinga');

        if (!$vendingCat) {
            $this->command->error('   ✗ Категория "Продукция вендинга" не найдена!');
            return 0;
        }

        // Получаем подкатегории
        $subcategories = Category::where('parent_id', $vendingCat->id)->get()->keyBy('slug');

        // Товары для вендинга
        $vendingProductsByCategory = [
            'granulirovannyy-kofe' => [
                ['name' => 'Растворимый кофе для вендинга', 'price' => 890, 'weight' => 1000],
                ['name' => 'Растворимый кофе премиум', 'price' => 1050, 'weight' => 1000],
                ['name' => 'Растворимый кофе эконом', 'price' => 720, 'weight' => 1000],
            ],
            'granulirovannyy-tsikoriy' => [
                ['name' => 'Цикорий гранулированный', 'price' => 450, 'weight' => 1000],
                ['name' => 'Цикорий с женьшенем', 'price' => 380, 'weight' => 500],
            ],
            'zernovoy-kofe-vending' => [
                ['name' => 'Зерновой кофе для автоматов', 'price' => 980, 'weight' => 1000],
                ['name' => 'Зерновой кофе смесь', 'price' => 850, 'weight' => 1000],
            ],
            'granulirovannyy-kakao' => [
                ['name' => 'Какао-порошок для автоматов', 'price' => 650, 'weight' => 1000],
                ['name' => 'Какао премиум', 'price' => 780, 'weight' => 1000],
            ],
            'granulirovannye-kofejnye-napitki' => [
                ['name' => 'Капучино для вендинга', 'price' => 720, 'weight' => 1000],
                ['name' => 'Латте-микс', 'price' => 750, 'weight' => 1000],
                ['name' => 'Горячий шоколад микс', 'price' => 720, 'weight' => 1000],
            ],
            'kofe-poroshkoobraznyy' => [
                ['name' => 'Кофе порошкообразный', 'price' => 680, 'weight' => 1000],
                ['name' => 'Кофе порошкообразный со сливками', 'price' => 820, 'weight' => 1000],
            ],
            'suhoe-moloko-granulirovannoe' => [
                ['name' => 'Сливки растительные', 'price' => 280, 'weight' => 500],
                ['name' => 'Сухое молоко цельное', 'price' => 420, 'weight' => 500],
            ],
        ];

        // Создаем товары для каждой подкатегории
        foreach ($vendingProductsByCategory as $categorySlug => $products) {
            $subcategory = $subcategories->get($categorySlug);
            
            if (!$subcategory) {
                continue;
            }

            foreach ($products as $productData) {
                $productName = $productData['name'] . ' ' . $productData['weight'] . 'г';
                
                $product = $this->createVendingProduct(
                    $productName,
                    $subcategory->id,
                    $productData['price'],
                    $productData['weight']
                );
                
                if ($product) {
                    $count++;
                    $this->command->line("   ✓ {$productName} ({$subcategory->name})");
                }
            }
        }

        return $count;
    }

    /**
     * Создание товаров здорового питания (15-17 товаров)
     * 
     * Распределение по подкатегориям:
     * - Цикорий и корень цикория (3-4 товара)
     * - Ячменные напитки (3 товара)
     * - Напитки для здоровья (3-4 товара)
     * - Протеиновые смеси (3 товара)
     * - Толокняные каши (3-4 товара)
     * 
     * @return int Количество созданных товаров
     */
    private function createHealthyFoodProducts(): int
    {
        $count = 0;

        // Получаем категорию "Здоровое питание"
        $healthyCat = $this->categories->get('zdorovoe-pitanie');

        if (!$healthyCat) {
            $this->command->error('   ✗ Категория "Здоровое питание" не найдена!');
            return 0;
        }

        // Получаем подкатегории
        $subcategories = Category::where('parent_id', $healthyCat->id)->get()->keyBy('slug');

        // Товары здорового питания
        $healthyProductsByCategory = [
            'tsikoriy-i-koren-tsikoriya' => [
                ['name' => 'Цикорий растворимый натуральный', 'price' => 180, 'weight' => [100, 200]],
                ['name' => 'Корень цикория молотый', 'price' => 160, 'weight' => [100]],
                ['name' => 'Цикорий с экстрактом женьшеня', 'price' => 220, 'weight' => [100]],
                ['name' => 'Цикорий с витаминами', 'price' => 240, 'weight' => [200]],
            ],
            'yachmennye-napitki' => [
                ['name' => 'Ячменный напиток классический', 'price' => 150, 'weight' => [200, 500]],
                ['name' => 'Ячменный напиток с цикорием', 'price' => 170, 'weight' => [200]],
                ['name' => 'Ячменный напиток с имбирем', 'price' => 190, 'weight' => [200]],
            ],
            'napitki-dlya-zdorovya' => [
                ['name' => 'Напитки для здоровья', 'price' => 280, 'weight' => [100]],
                ['name' => 'Детокс-чай травяной', 'price' => 280, 'weight' => [40]],
                ['name' => 'Имбирный напиток', 'price' => 250, 'weight' => [100]],
                ['name' => 'Куркума латте', 'price' => 320, 'weight' => [150]],
            ],
            'proteinovye-smesi' => [
                ['name' => 'Протеиновая смесь с кофе', 'price' => 680, 'weight' => [300]],
                ['name' => 'Протеиновая смесь с матча', 'price' => 720, 'weight' => [300]],
                ['name' => 'Протеиновая смесь шоколадная', 'price' => 850, 'weight' => [500]],
            ],
            'toloknyannye-kashi' => [
                ['name' => 'Толокняная каша классическая', 'price' => 180, 'weight' => [300]],
                ['name' => 'Толокняная каша с ягодами', 'price' => 220, 'weight' => [300]],
                ['name' => 'Толокняная каша с орехами', 'price' => 240, 'weight' => [300]],
                ['name' => 'Овсяная каша с суперфудами', 'price' => 280, 'weight' => [250]],
            ],
        ];

        // Создаем товары для каждой подкатегории
        foreach ($healthyProductsByCategory as $categorySlug => $products) {
            $subcategory = $subcategories->get($categorySlug);
            
            if (!$subcategory) {
                continue;
            }

            foreach ($products as $productData) {
                // Если есть массив весов, создаем для каждого веса
                $weights = is_array($productData['weight']) ? $productData['weight'] : [$productData['weight']];
                
                foreach ($weights as $weight) {
                    $productName = $productData['name'] . ' ' . $weight . 'г';
                    
                    $product = $this->createHealthyProduct(
                        $productName,
                        $subcategory->id,
                        $productData['price'],
                        $weight
                    );
                    
                    if ($product) {
                        $count++;
                        $this->command->line("   ✓ {$productName} ({$subcategory->name})");
                    }
                }
            }
        }

        return $count;
    }

    /**
     * Создать один товар вендинга
     * 
     * @param string $name Название товара
     * @param int $categoryId ID категории
     * @param float $price Цена товара
     * @param int $weight Вес товара в граммах
     * @return Product|null Созданный товар или null при ошибке
     */
    private function createVendingProduct(string $name, int $categoryId, float $price, int $weight): ?Product
    {
        try {
            // Генерируем уникальный slug
            $slug = Str::slug($name) . '-' . fake()->numberBetween(1, 999);

            // Создаем товар вендинга
            $product = Product::create([
                'category_id' => $categoryId,
                'name' => $name,
                'slug' => $slug,
                'description' => $this->generateVendingDescription($name),
                'long_description' => $this->generateVendingLongDescription($name),
                'price' => $price,
                'old_price' => fake()->boolean(20) ? $price * 1.25 : null,
                'weight' => $weight,
                'sku' => 'VN-' . fake()->unique()->numberBetween(1000, 9999),
                'stock' => fake()->numberBetween(20, 100),
                'rating' => fake()->randomFloat(2, 4.0, 5.0),
                'reviews_count' => 0, // Будет обновлено после создания отзывов
                'bitterness_percent' => 0, // Для вендинга не применяется
                'acidity_percent' => 0, // Для вендинга не применяется
                'is_featured' => fake()->boolean(15), // 15% товаров рекомендуемые
                'is_available' => true,
                'meta_title' => $name . ' - купить для вендинга',
                'meta_description' => "Купить {$name} по оптовой цене. Продукция для вендинговых аппаратов.",
            ]);

            // Добавляем изображения к товару (2-3 изображения)
            $this->addProductImages($product, 'vending');

            // Привязываем теги к товару (1-2 тега)
            $this->attachRandomTags($product, 1, 2);

            return $product;
            
        } catch (\Exception $e) {
            $this->command->error("   ✗ Ошибка при создании товара {$name}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Создать один товар здорового питания
     * 
     * @param string $name Название товара
     * @param int $categoryId ID категории
     * @param float $price Цена товара
     * @param int $weight Вес товара в граммах
     * @return Product|null Созданный товар или null при ошибке
     */
    private function createHealthyProduct(string $name, int $categoryId, float $price, int $weight): ?Product
    {
        try {
            // Генерируем уникальный slug
            $slug = Str::slug($name) . '-' . fake()->numberBetween(1, 999);

            // Создаем товар здорового питания
            $product = Product::create([
                'category_id' => $categoryId,
                'name' => $name,
                'slug' => $slug,
                'description' => $this->generateHealthyDescription($name),
                'long_description' => $this->generateHealthyLongDescription($name),
                'price' => $price,
                'old_price' => fake()->boolean(25) ? $price * 1.3 : null,
                'weight' => $weight,
                'sku' => 'HF-' . fake()->unique()->numberBetween(1000, 9999),
                'stock' => fake()->numberBetween(15, 80),
                'rating' => fake()->randomFloat(2, 4.0, 5.0),
                'reviews_count' => 0, // Будет обновлено после создания отзывов
                'bitterness_percent' => 0, // Для здорового питания не применяется
                'acidity_percent' => 0, // Для здорового питания не применяется
                'is_featured' => fake()->boolean(20), // 20% товаров рекомендуемые
                'is_available' => true,
                'meta_title' => $name . ' - купить в интернет-магазине',
                'meta_description' => "Купить {$name} по выгодной цене. Полезные продукты для здорового образа жизни.",
            ]);

            // Добавляем изображения к товару (2-3 изображения)
            $this->addProductImages($product, 'healthy');

            // Привязываем теги к товару (1-3 тега)
            $this->attachRandomTags($product);

            return $product;
            
        } catch (\Exception $e) {
            $this->command->error("   ✗ Ошибка при создании товара {$name}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Добавить изображения к товару
     * 
     * Создает изображения для товара.
     * Для кофе используется единое изображение products/coffee/coffee.png
     * Первое изображение помечается как главное (is_primary = true).
     * 
     * @param Product $product Товар, к которому добавляются изображения
     * @param string $type Тип товара: 'coffee', 'tea', 'accessory'
     * @return void
     */
    private function addProductImages(Product $product, string $type): void
    {
        // Определяем путь к изображению в зависимости от типа товара
        if ($type === 'coffee') {
            // Для всех кофейных товаров используем одно изображение
            $imagePath = "products/coffee/coffee.png";
            
            // Создаем одно главное изображение для кофе
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $imagePath,
                'alt_text' => $product->name,
                'sort_order' => 0,
                'is_primary' => true, // Главное изображение
            ]);
        } else {
            // Для остальных товаров создаем 2-4 изображения (старая логика)
            $imagesCount = fake()->numberBetween(2, 4);

            for ($i = 0; $i < $imagesCount; $i++) {
                // Генерируем номер изображения
                $imageNumber = fake()->numberBetween(1, 20);
                
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => "products/{$type}-{$imageNumber}.jpg",
                    'alt_text' => $product->name,
                    'sort_order' => $i,
                    'is_primary' => $i === 0, // Первое изображение - главное
                ]);
            }
        }
    }

    /**
     * Привязать случайные теги к товару
     * 
     * Выбирает 1-3 случайных тега из доступных и привязывает к товару
     * через промежуточную таблицу product_tag.
     * 
     * @param Product $product Товар, к которому привязываются теги
     * @param int $min Минимальное количество тегов
     * @param int $max Максимальное количество тегов
     * @return void
     */
    private function attachRandomTags(Product $product, int $min = 1, int $max = 3): void
    {
        // Количество тегов: от $min до $max
        $tagsCount = fake()->numberBetween($min, $max);
        
        // Выбираем случайные теги
        $randomTags = $this->tags->random(min($tagsCount, $this->tags->count()));
        
        // Привязываем теги к товару
        $product->tags()->attach($randomTags->pluck('id'));
    }

    /**
     * Генерация краткого описания для кофе
     * 
     * @param string $name Название товара
     * @return string Описание
     */
    private function generateCoffeeDescription(string $name): string
    {
        $descriptions = [
            'Премиальный кофе с насыщенным вкусом и богатым ароматом.',
            'Свежеобжаренный кофе из лучших плантаций мира.',
            'Кофе с уникальным вкусовым профилем и яркими нотами.',
            'Отборные зерна арабики с идеальной степенью обжарки.',
            'Элитный кофе для истинных ценителей напитка.',
        ];

        return fake()->randomElement($descriptions);
    }

    /**
     * Генерация подробного описания для кофе
     * 
     * @param string $name Название товара
     * @return string Подробное описание
     */
    private function generateCoffeeLongDescription(string $name): string
    {
        $tastingNotes = [
            'шоколада и орехов',
            'цитрусовых и ягод',
            'карамели и ванили',
            'фруктов и цветов',
            'пряностей и какао',
        ];

        $body = fake()->randomElement(['легкое', 'среднее', 'плотное', 'насыщенное']);
        $acidity = fake()->randomElement(['низкая', 'средняя', 'высокая', 'яркая']);
        $notes = fake()->randomElement($tastingNotes);

        return "Этот кофе обладает {$body} телом и {$acidity} кислотностью. " .
               "Во вкусе четко прослеживаются ноты {$notes}. " .
               "Идеально подходит для приготовления эспрессо, капучино и латте. " .
               "Рекомендуем заваривать при температуре 92-96°C.";
    }

    /**
     * Генерация краткого описания для чая
     * 
     * @param string $name Название товара
     * @return string Описание
     */
    private function generateTeaDescription(string $name): string
    {
        $descriptions = [
            'Качественный листовой чай с утонченным вкусом.',
            'Традиционный чай из лучших чайных регионов.',
            'Премиальный чай для настоящих ценителей.',
            'Чай с богатым ароматом и мягким послевкусием.',
            'Отборный чай высшего сорта с уникальным букетом.',
        ];

        return fake()->randomElement($descriptions);
    }

    /**
     * Генерация подробного описания для чая
     * 
     * @param string $name Название товара
     * @return string Подробное описание
     */
    private function generateTeaLongDescription(string $name): string
    {
        $characteristics = [
            'насыщенный аромат и яркий вкус',
            'деликатный вкус с цветочными нотами',
            'глубокий вкус с медовым послевкусием',
            'освежающий вкус с фруктовыми нотами',
        ];

        $characteristic = fake()->randomElement($characteristics);
        $temperature = fake()->numberBetween(70, 95);
        $time = fake()->numberBetween(2, 5);

        return "Этот чай отличается {$characteristic}. " .
               "Собран вручную в экологически чистых регионах. " .
               "Для заваривания рекомендуем использовать воду температурой {$temperature}°C " .
               "и настаивать {$time} минут. Можно заваривать повторно.";
    }

    /**
     * Генерация краткого описания для вендинга
     * 
     * @param string $name Название товара
     * @return string Описание
     */
    private function generateVendingDescription(string $name): string
    {
        $descriptions = [
            'Профессиональная продукция для вендинговых аппаратов.',
            'Качественный продукт для коммерческого использования в автоматах.',
            'Оптимальное решение для кофейных автоматов и вендинга.',
            'Проверенная продукция для вендингового бизнеса.',
            'Стабильное качество для использования в торговых автоматах.',
        ];

        return fake()->randomElement($descriptions);
    }

    /**
     * Генерация подробного описания для вендинга
     * 
     * @param string $name Название товара
     * @return string Подробное описание
     */
    private function generateVendingLongDescription(string $name): string
    {
        return "Специально разработано для использования в вендинговых аппаратах. " .
               "Обеспечивает стабильное качество напитка и легкую растворимость. " .
               "Подходит для большинства моделей кофейных автоматов. " .
               "Продукт прошел сертификацию и соответствует всем стандартам качества. " .
               "Удобная упаковка для коммерческого использования.";
    }

    /**
     * Генерация краткого описания для здорового питания
     * 
     * @param string $name Название товара
     * @return string Описание
     */
    private function generateHealthyDescription(string $name): string
    {
        $descriptions = [
            'Полезный продукт для здорового образа жизни без вредных добавок.',
            'Натуральный продукт высокого качества для правильного питания.',
            'Органический продукт для заботы о вашем здоровье.',
            'Функциональный продукт с полезными свойствами для организма.',
            'Здоровая альтернатива без химических добавок и красителей.',
        ];

        return fake()->randomElement($descriptions);
    }

    /**
     * Генерация подробного описания для здорового питания
     * 
     * @param string $name Название товара
     * @return string Подробное описание
     */
    private function generateHealthyLongDescription(string $name): string
    {
        $benefits = [
            'улучшает пищеварение и обмен веществ',
            'содержит витамины и микроэлементы',
            'способствует поддержанию энергии в течение дня',
            'богат клетчаткой и антиоксидантами',
            'поддерживает иммунную систему',
        ];

        $benefit = fake()->randomElement($benefits);

        return "Натуральный продукт, который {$benefit}. " .
               "Произведен из экологически чистого сырья без использования химических добавок. " .
               "Идеально подходит для людей, заботящихся о своем здоровье. " .
               "Рекомендован диетологами как часть сбалансированного питания. " .
               "Удобная упаковка сохраняет все полезные свойства продукта.";
    }
}
