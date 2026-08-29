<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Seeder для создания категорий и подкатегорий товаров
 *
 * Создает структуру категорий магазина:
 * - 4 главные категории (Свежеобжаренный кофе, Чай и кофейные напитки, Продукция вендинга, Здоровое питание)
 * - 26 подкатегорий (распределены по главным категориям)
 */
class CategorySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Запуск seeder'а для заполнения категорий
     *
     * Создает главные категории и их подкатегории в правильном порядке.
     * Использует транзакцию для целостности данных.
     */
    public function run(): void
    {
        $this->command->info('📁 Создание категорий и подкатегорий...');

        // Создаем главные категории
        $mainCategories = $this->createMainCategories();

        // Создаем подкатегории для всех категорий
        $this->createFreshCoffeeSubcategories($mainCategories);
        $this->createTeaDrinksSubcategories($mainCategories);
        $this->createVendingSubcategories($mainCategories);
        $this->createHealthyFoodSubcategories($mainCategories);

        $this->command->info('✅ Создано категорий: '.Category::count());
    }

    /**
     * Создание главных (родительских) категорий
     *
     * Создает 4 основные категории магазина, которые будут отображаться
     * в главном меню и на главной странице.
     *
     * @return array<string, Category> Массив созданных категорий с ключами-именами
     */
    private function createMainCategories(): array
    {
        $categories = [];

        // Массив с данными главных категорий
        $mainCategoriesData = [
            [
                'name' => 'Свежеобжаренный кофе',
                'slug' => 'svezheobzharennyy-kofe',
                'description' => 'Премиальный кофе свежей обжарки из лучших плантаций мира. Арабика и робуста высшего качества из Африки, Азии, Центральной и Латинской Америки.',
                'image' => 'categories/fresh-roasted-coffee.png',
                'sort_order' => 1,
            ],
            [
                'name' => 'Чай и кофейные напитки',
                'slug' => 'chay-i-kofejnye-napitki',
                'description' => 'Качественный листовой чай всех видов и готовые кофейные напитки. Черный, зеленый, улун, матча, пуэр и растворимый кофе.',
                'image' => 'categories/tea-coffee-drinks.png',
                'sort_order' => 2,
            ],
            [
                'name' => 'Продукция вендинга',
                'slug' => 'produktsiya-vendinga',
                'description' => 'Профессиональные решения для вендинговых аппаратов. Растворимый кофе, цикорий, какао, сухое молоко и сопутствующие товары.',
                'image' => 'categories/vending-products.png',
                'sort_order' => 3,
            ],
            [
                'name' => 'Здоровое питание',
                'slug' => 'zdorovoe-pitanie',
                'description' => 'Полезные продукты для здорового образа жизни: цикорий, ячменные напитки, протеиновые смеси, толокняные каши и органический кофе.',
                'image' => 'categories/healthy-food.png',
                'sort_order' => 4,
            ],
        ];

        // Создаем категории в базе данных
        foreach ($mainCategoriesData as $categoryData) {
            $category = Category::create([
                'name' => $categoryData['name'],
                'slug' => $categoryData['slug'],
                'description' => $categoryData['description'],
                'image' => $categoryData['image'],
                'parent_id' => null, // Главная категория не имеет родителя
                'sort_order' => $categoryData['sort_order'],
                'is_active' => true,
            ]);

            // Сохраняем категорию в массив для последующего использования
            $categories[$categoryData['name']] = $category;

            $this->command->line("  ✓ Создана главная категория: {$categoryData['name']}");
        }

        return $categories;
    }

    /**
     * Создание подкатегорий для "Свежеобжаренный кофе"
     *
     * Создает 7 подкатегорий по географическим регионам
     *
     * @param  array<string, Category>  $mainCategories  Массив главных категорий
     */
    private function createFreshCoffeeSubcategories(array $mainCategories): void
    {
        $coffeeCategory = $mainCategories['Свежеобжаренный кофе'];

        $subcategoriesData = [
            [
                'name' => 'Африка',
                'slug' => 'afrika',
                'description' => 'Кофе из африканских стран: Кения, Руанда, Танзания, Бурунди. Яркие фруктовые и цветочные ноты.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Йемен',
                'slug' => 'yemen',
                'description' => 'Легендарный йеменский кофе Мокка с уникальным винно-шоколадным вкусом. Один из древнейших сортов.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Уганда',
                'slug' => 'uganda',
                'description' => 'Угандийская арабика и робуста с плотным телом и мягким вкусом. Отличное соотношение цена-качество.',
                'sort_order' => 3,
            ],
            [
                'name' => 'Эфиопия',
                'slug' => 'efiopiya',
                'description' => 'Родина кофе. Эфиопский кофе славится яркими фруктовыми и цветочными нотами, высокой кислотностью.',
                'sort_order' => 4,
            ],
            [
                'name' => 'Азия',
                'slug' => 'aziya',
                'description' => 'Азиатский кофе из Индонезии, Вьетнама, Индии. Характерны землистые ноты, плотное тело, низкая кислотность.',
                'sort_order' => 5,
            ],
            [
                'name' => 'Центральная Америка',
                'slug' => 'centralnaya-amerika',
                'description' => 'Кофе из Гватемалы, Коста-Рики, Панамы, Сальвадора. Сбалансированный вкус с нотами какао и цитрусов.',
                'sort_order' => 6,
            ],
            [
                'name' => 'Латинская Америка',
                'slug' => 'latinskaya-amerika',
                'description' => 'Колумбия, Бразилия, Перу, Эквадор. Классические сорта с шоколадно-ореховым профилем, средней кислотностью.',
                'sort_order' => 7,
            ],
        ];

        foreach ($subcategoriesData as $subcategoryData) {
            Category::create([
                'name' => $subcategoryData['name'],
                'slug' => $subcategoryData['slug'],
                'description' => $subcategoryData['description'],
                'image' => null,
                'parent_id' => $coffeeCategory->id,
                'sort_order' => $subcategoryData['sort_order'],
                'is_active' => true,
            ]);

            $this->command->line("  ✓ Создана подкатегория: {$subcategoryData['name']} (родитель: {$coffeeCategory->name})");
        }
    }

    /**
     * Создание подкатегорий для "Чай и кофейные напитки"
     *
     * Создает 7 подкатегорий разных видов чая и кофейных напитков
     *
     * @param  array<string, Category>  $mainCategories  Массив главных категорий
     */
    private function createTeaDrinksSubcategories(array $mainCategories): void
    {
        $teaCategory = $mainCategories['Чай и кофейные напитки'];

        $subcategoriesData = [
            [
                'name' => 'Черный чай',
                'slug' => 'chernyy-chay',
                'description' => 'Классический черный чай из Индии, Цейлона, Китая. Насыщенный вкус и аромат.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Зеленый чай',
                'slug' => 'zelenyy-chay',
                'description' => 'Зеленый чай из Китая и Японии. Освежающий вкус, богат антиоксидантами.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Молочный улун',
                'slug' => 'molochnyy-ulung',
                'description' => 'Популярный полуферментированный чай с нежным молочно-сливочным вкусом.',
                'sort_order' => 3,
            ],
            [
                'name' => 'Травяной чай',
                'slug' => 'travyanoy-chay',
                'description' => 'Травяные и фруктовые чаи без кофеина: ройбуш, каркаде, мята, ромашка.',
                'sort_order' => 4,
            ],
            [
                'name' => 'Матча',
                'slug' => 'matcha',
                'description' => 'Японский порошковый зеленый чай матча премиум качества. Богат антиоксидантами.',
                'sort_order' => 5,
            ],
            [
                'name' => 'Пуэр',
                'slug' => 'puer',
                'description' => 'Выдержанный китайский чай пуэр. Глубокий, землистый вкус, полезен для пищеварения.',
                'sort_order' => 6,
            ],
            [
                'name' => 'Кофейные напитки',
                'slug' => 'kofejnye-napitki',
                'description' => 'Растворимый кофе, капучино, латте-миксы. Удобные готовые решения.',
                'sort_order' => 7,
            ],
        ];

        foreach ($subcategoriesData as $subcategoryData) {
            Category::create([
                'name' => $subcategoryData['name'],
                'slug' => $subcategoryData['slug'],
                'description' => $subcategoryData['description'],
                'image' => null,
                'parent_id' => $teaCategory->id,
                'sort_order' => $subcategoryData['sort_order'],
                'is_active' => true,
            ]);

            $this->command->line("  ✓ Создана подкатегория: {$subcategoryData['name']} (родитель: {$teaCategory->name})");
        }
    }

    /**
     * Создание подкатегорий для "Продукция вендинга"
     *
     * Создает 7 подкатегорий для вендинговых аппаратов
     *
     * @param  array<string, Category>  $mainCategories  Массив главных категорий
     */
    private function createVendingSubcategories(array $mainCategories): void
    {
        $vendingCategory = $mainCategories['Продукция вендинга'];

        $subcategoriesData = [
            [
                'name' => 'Гранулированный кофе',
                'slug' => 'granulirovannyy-kofe',
                'description' => 'Растворимый гранулированный кофе для вендинговых аппаратов. Быстрое приготовление.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Гранулированный цикорий',
                'slug' => 'granulirovannyy-tsikoriy',
                'description' => 'Растворимый цикорий для вендинга. Альтернатива кофе без кофеина.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Зерновой кофе',
                'slug' => 'zernovoy-kofe-vending',
                'description' => 'Зерновой кофе для профессиональных кофейных автоматов.',
                'sort_order' => 3,
            ],
            [
                'name' => 'Гранулированный какао',
                'slug' => 'granulirovannyy-kakao',
                'description' => 'Растворимый какао-порошок для приготовления горячего шоколада в автоматах.',
                'sort_order' => 4,
            ],
            [
                'name' => 'Гранулированные кофейные напитки',
                'slug' => 'granulirovannye-kofejnye-napitki',
                'description' => 'Готовые миксы: капучино, латте, горячий шоколад для вендинга.',
                'sort_order' => 5,
            ],
            [
                'name' => 'Кофе порошкообразный',
                'slug' => 'kofe-poroshkoobraznyy',
                'description' => 'Порошковый кофе для вендинговых аппаратов. Быстрое растворение.',
                'sort_order' => 6,
            ],
            [
                'name' => 'Сухое молоко гранулированное',
                'slug' => 'suhoe-moloko-granulirovannoe',
                'description' => 'Сухие сливки и молоко для приготовления напитков в автоматах.',
                'sort_order' => 7,
            ],
        ];

        foreach ($subcategoriesData as $subcategoryData) {
            Category::create([
                'name' => $subcategoryData['name'],
                'slug' => $subcategoryData['slug'],
                'description' => $subcategoryData['description'],
                'image' => null,
                'parent_id' => $vendingCategory->id,
                'sort_order' => $subcategoryData['sort_order'],
                'is_active' => true,
            ]);

            $this->command->line("  ✓ Создана подкатегория: {$subcategoryData['name']} (родитель: {$vendingCategory->name})");
        }
    }

    /**
     * Создание подкатегорий для "Здоровое питание"
     *
     * Создает 5 подкатегорий полезных продуктов
     *
     * @param  array<string, Category>  $mainCategories  Массив главных категорий
     */
    private function createHealthyFoodSubcategories(array $mainCategories): void
    {
        $healthyCategory = $mainCategories['Здоровое питание'];

        $subcategoriesData = [
            [
                'name' => 'Цикорий и корень цикория',
                'slug' => 'tsikoriy-i-koren-tsikoriya',
                'description' => 'Натуральный цикорий - полезная альтернатива кофе без кофеина. Улучшает пищеварение.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Ячменные напитки',
                'slug' => 'yachmennye-napitki',
                'description' => 'Напитки из обжаренного ячменя. Мягкий вкус, без кофеина, полезны для здоровья.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Напитки для здоровья',
                'slug' => 'napitki-dlya-zdorovya',
                'description' => 'Функциональные напитки: детокс-чаи, имбирные напитки, куркума латте.',
                'sort_order' => 3,
            ],
            [
                'name' => 'Протеиновые смеси',
                'slug' => 'proteinovye-smesi',
                'description' => 'Протеиновые смеси с кофе, матча и шоколадом. Для спортивного питания.',
                'sort_order' => 4,
            ],
            [
                'name' => 'Толокняные каши',
                'slug' => 'toloknyannye-kashi',
                'description' => 'Полезные толокняные и овсяные каши с натуральными добавками.',
                'sort_order' => 5,
            ],
        ];

        foreach ($subcategoriesData as $subcategoryData) {
            Category::create([
                'name' => $subcategoryData['name'],
                'slug' => $subcategoryData['slug'],
                'description' => $subcategoryData['description'],
                'image' => null,
                'parent_id' => $healthyCategory->id,
                'sort_order' => $subcategoryData['sort_order'],
                'is_active' => true,
            ]);

            $this->command->line("  ✓ Создана подкатегория: {$subcategoryData['name']} (родитель: {$healthyCategory->name})");
        }
    }
}
