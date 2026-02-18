<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Unit тесты для модели Category
 *
 * Тестируем:
 * - Иерархия категорий (parent, children, ancestors, descendants)
 * - Scope active для фильтрации активных категорий
 * - Связи с товарами
 */
#[Group('unit')]
#[Group('models')]
#[Group('category')]
final class CategoryTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // ТЕСТЫ: Иерархия категорий
    // ==========================================

    /**
     * Тест: категория имеет родителя
     *
     * Подкатегория должна возвращать родительскую категорию через parent().
     */
    public function test_has_parent_category(): void
    {
        // Arrange: Создаем родительскую и дочернюю категорию
        $parent = $this->createCategory(['name' => 'Кофе в зернах']);
        $child = $this->createCategory([
            'name' => 'Арабика',
            'parent_id' => $parent->id,
        ]);

        // Act: Загружаем связь родителя
        $child->load('parent');

        // Assert: Дочерняя категория имеет правильного родителя
        $this->assertNotNull($child->parent);
        $this->assertEquals($parent->id, $child->parent->id);
        $this->assertEquals('Кофе в зернах', $child->parent->name);
    }

    /**
     * Тест: категория имеет дочерние категории
     */
    public function test_has_child_categories(): void
    {
        // Arrange: Создаем родительскую категорию и подкатегории
        $parent = $this->createCategory(['name' => 'Кофе']);
        $child1 = $this->createCategory(['parent_id' => $parent->id, 'name' => 'Арабика']);
        $child2 = $this->createCategory(['parent_id' => $parent->id, 'name' => 'Робуста']);

        // Act: Загружаем дочерние категории
        $parent->load('children');

        // Assert: Родитель имеет 2 дочерние категории
        $this->assertCount(2, $parent->children);
        $childIds = $parent->children->pluck('id')->toArray();
        $this->assertContains($child1->id, $childIds);
        $this->assertContains($child2->id, $childIds);
    }

    /**
     * Тест: корневая категория не имеет родителя
     *
     * Категория с parent_id = null является главной (корневой).
     */
    public function test_is_root_category_when_no_parent(): void
    {
        // Arrange: Создаем категорию без родителя
        $rootCategory = $this->createCategory([
            'name' => 'Кофе в зернах',
            'parent_id' => null,
        ]);

        // Act & Assert: isParent() возвращает true, parent_id = null
        $this->assertTrue($rootCategory->isParent());
        $this->assertNull($rootCategory->parent_id);
        $this->assertNull($rootCategory->parent);
    }

    /**
     * Тест: получение всех предков категории
     *
     * getAllAncestors() должен вернуть цепочку: ближайший родитель -> корень.
     */
    public function test_gets_all_ancestors(): void
    {
        // Arrange: Создаем цепочку: root -> level1 -> level2
        $root = $this->createCategory(['name' => 'Кофе', 'parent_id' => null]);
        $level1 = $this->createCategory(['name' => 'Арабика', 'parent_id' => $root->id]);
        $level2 = $this->createCategory(['name' => 'Колумбия', 'parent_id' => $level1->id]);

        // Act: Получаем предков для level2
        $ancestors = $level2->getAllAncestors();

        // Assert: Должны быть level1 и root (в порядке от ближайшего к дальнему)
        $this->assertCount(2, $ancestors);
        $this->assertEquals($level1->id, $ancestors->first()->id);
        $this->assertEquals($root->id, $ancestors->last()->id);
    }

    /**
     * Тест: получение всех потомков категории
     *
     * getAllDescendants() должен рекурсивно вернуть все подкатегории любого уровня.
     */
    public function test_gets_all_descendants(): void
    {
        // Arrange: root -> child1, child2; child1 -> grandchild
        $root = $this->createCategory(['name' => 'Кофе', 'parent_id' => null]);
        $child1 = $this->createCategory(['name' => 'Арабика', 'parent_id' => $root->id]);
        $child2 = $this->createCategory(['name' => 'Робуста', 'parent_id' => $root->id]);
        $grandchild = $this->createCategory(['name' => 'Колумбия', 'parent_id' => $child1->id]);

        // Act: Получаем всех потомков root
        $descendants = $root->getAllDescendants();

        // Assert: Должны быть child1, child2, grandchild (3 потомка)
        $this->assertCount(3, $descendants);
        $descendantIds = $descendants->pluck('id')->toArray();
        $this->assertContains($child1->id, $descendantIds);
        $this->assertContains($child2->id, $descendantIds);
        $this->assertContains($grandchild->id, $descendantIds);
    }

    // ==========================================
    // ТЕСТЫ: Активность категории
    // ==========================================

    /**
     * Тест: scope active фильтрует только активные категории
     */
    public function test_active_scope_filters_active_categories(): void
    {
        // Arrange: Создаем активные и неактивные категории
        $active1 = $this->createCategory(['is_active' => true]);
        $active2 = $this->createCategory(['is_active' => true]);
        $this->createCategory(['is_active' => false]);
        $this->createCategory(['is_active' => false]);

        // Act: Используем scope active
        $activeCategories = Category::active()->get();

        // Assert: Только активные категории
        $this->assertCount(2, $activeCategories);
        foreach ($activeCategories as $cat) {
            $this->assertTrue($cat->is_active);
        }
    }

    // ==========================================
    // ТЕСТЫ: Связи с товарами
    // ==========================================

    /**
     * Тест: категория имеет товары
     */
    public function test_has_products(): void
    {
        // Arrange: Создаем категорию и товары в ней
        $category = $this->createCategory(['name' => 'Кофе']);
        $product1 = $this->createProduct(['category_id' => $category->id]);
        $product2 = $this->createProduct(['category_id' => $category->id]);

        // Act: Загружаем связь товаров
        $category->load('products');

        // Assert: Категория имеет 2 товара
        $this->assertCount(2, $category->products);
        $productIds = $category->products->pluck('id')->toArray();
        $this->assertContains($product1->id, $productIds);
        $this->assertContains($product2->id, $productIds);
    }

    /**
     * Тест: правильный подсчет товаров в категории
     */
    public function test_counts_products_correctly(): void
    {
        // Arrange: Создаем категорию с 5 товарами
        $category = $this->createCategory();
        $this->createProducts(5, ['category_id' => $category->id]);

        // Act: Подсчитываем товары
        $count = $category->products()->count();

        // Assert: 5 товаров
        $this->assertEquals(5, $count);
    }
}
