<?php

declare(strict_types=1);

namespace Tests;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Базовый класс для всех тестов
 * 
 * Предоставляет общую функциональность и хелперы для тестирования
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * RefreshDatabase trait автоматически:
     * - Сбрасывает БД перед каждым тестом
     * - Запускает миграции
     * - Откатывает транзакции после каждого теста
     */
    use RefreshDatabase;

    /**
     * Создать тестового пользователя
     * 
     * @param array<string, mixed> $attributes Дополнительные атрибуты пользователя
     * @return User Созданный пользователь
     * 
     * @example
     * $user = $this->createUser(['email' => 'test@example.com']);
     * $admin = $this->createUser(['is_admin' => true]);
     */
    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    /**
     * Создать тестовый товар
     * 
     * @param array<string, mixed> $attributes Дополнительные атрибуты товара
     * @return Product Созданный товар
     * 
     * @example
     * $product = $this->createProduct(['price' => 500]);
     * $product = $this->createProduct(['name' => 'Кофе Арабика', 'stock' => 10]);
     */
    protected function createProduct(array $attributes = []): Product
    {
        return Product::factory()->create($attributes);
    }

    /**
     * Создать тестовую категорию
     * 
     * @param array<string, mixed> $attributes Дополнительные атрибуты категории
     * @return Category Созданная категория
     * 
     * @example
     * $category = $this->createCategory(['name' => 'Кофе']);
     * $subcategory = $this->createCategory(['parent_id' => $category->id]);
     */
    protected function createCategory(array $attributes = []): Category
    {
        return Category::factory()->create($attributes);
    }

    /**
     * Авторизовать тестового пользователя
     * 
     * Если пользователь не передан, создается новый обычный пользователь
     * 
     * @param User|null $user Пользователь для авторизации (опционально)
     * @return self Возвращает себя для цепочки вызовов
     * 
     * @example
     * $this->actingAsUser()->get('/profile'); // Создаст и авторизует нового пользователя
     * $this->actingAsUser($user)->post('/orders'); // Авторизует конкретного пользователя
     */
    protected function actingAsUser(?User $user = null): self
    {
        $user = $user ?? $this->createUser();
        $this->actingAs($user);
        return $this;
    }

    /**
     * Авторизовать администратора
     * 
     * Создает и авторизует пользователя с правами администратора
     * 
     * @return self Возвращает себя для цепочки вызовов
     * 
     * @example
     * $this->actingAsAdmin()->get('/admin/dashboard');
     * $this->actingAsAdmin()->delete('/admin/products/1');
     */
    protected function actingAsAdmin(): self
    {
        $admin = $this->createUser(['is_admin' => true]);
        $this->actingAs($admin);
        return $this;
    }

    /**
     * Создать несколько тестовых товаров
     * 
     * Удобный хелпер для массового создания товаров
     * 
     * @param int $count Количество товаров для создания
     * @param array<string, mixed> $attributes Общие атрибуты для всех товаров
     * @return \Illuminate\Database\Eloquent\Collection<int, Product> Коллекция созданных товаров
     * 
     * @example
     * $products = $this->createProducts(5); // Создать 5 товаров
     * $products = $this->createProducts(10, ['category_id' => $category->id]); // 10 товаров в категории
     */
    protected function createProducts(int $count, array $attributes = []): \Illuminate\Database\Eloquent\Collection
    {
        return Product::factory()->count($count)->create($attributes);
    }

    /**
     * Создать несколько тестовых пользователей
     * 
     * @param int $count Количество пользователей для создания
     * @param array<string, mixed> $attributes Общие атрибуты для всех пользователей
     * @return \Illuminate\Database\Eloquent\Collection<int, User> Коллекция созданных пользователей
     * 
     * @example
     * $users = $this->createUsers(3); // Создать 3 пользователей
     */
    protected function createUsers(int $count, array $attributes = []): \Illuminate\Database\Eloquent\Collection
    {
        return User::factory()->count($count)->create($attributes);
    }

    /**
     * Создать несколько тестовых категорий
     * 
     * @param int $count Количество категорий для создания
     * @param array<string, mixed> $attributes Общие атрибуты для всех категорий
     * @return \Illuminate\Database\Eloquent\Collection<int, Category> Коллекция созданных категорий
     * 
     * @example
     * $categories = $this->createCategories(5); // Создать 5 категорий
     */
    protected function createCategories(int $count, array $attributes = []): \Illuminate\Database\Eloquent\Collection
    {
        return Category::factory()->count($count)->create($attributes);
    }
}
