<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Пример Unit теста
 * 
 * Этот файл демонстрирует базовую структуру unit теста
 * и использование хелперов из TestCase
 */
final class ExampleUnitTest extends TestCase
{
    /**
     * Простой пример теста
     * 
     * Проверяет, что базовая математика работает корректно
     */
    public function test_basic_assertion(): void
    {
        // Arrange (Подготовка)
        $expected = 4;
        
        // Act (Действие)
        $actual = 2 + 2;
        
        // Assert (Проверка)
        $this->assertEquals($expected, $actual);
    }

    /**
     * Пример использования хелпера createUser
     * 
     * Демонстрирует создание тестового пользователя
     */
    public function test_can_create_user_with_helper(): void
    {
        // Arrange & Act
        $user = $this->createUser([
            'first_name' => 'Тестовый',
            'last_name' => 'Пользователь',
            'email' => 'test@example.com',
        ]);
        
        // Assert
        $this->assertNotNull($user->id);
        $this->assertEquals('Тестовый', $user->first_name);
        $this->assertEquals('Пользователь', $user->last_name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    /**
     * Пример использования хелпера createProduct
     * 
     * Демонстрирует создание тестового товара
     */
    public function test_can_create_product_with_helper(): void
    {
        // Arrange & Act
        $product = $this->createProduct([
            'name' => 'Тестовый Кофе',
            'price' => 500,
            'stock' => 10,
        ]);
        
        // Assert
        $this->assertNotNull($product->id);
        $this->assertEquals('Тестовый Кофе', $product->name);
        $this->assertEquals(500, $product->price);
        $this->assertEquals(10, $product->stock);
    }

    /**
     * Пример использования хелпера createCategory
     * 
     * Демонстрирует создание тестовой категории
     */
    public function test_can_create_category_with_helper(): void
    {
        // Arrange & Act
        $category = $this->createCategory([
            'name' => 'Кофе',
            'slug' => 'coffee',
        ]);
        
        // Assert
        $this->assertNotNull($category->id);
        $this->assertEquals('Кофе', $category->name);
        $this->assertEquals('coffee', $category->slug);
    }

    /**
     * Пример массового создания товаров
     * 
     * Демонстрирует хелпер createProducts для создания нескольких товаров
     */
    public function test_can_create_multiple_products(): void
    {
        // Arrange & Act
        $products = $this->createProducts(5, [
            'stock' => 100,
        ]);
        
        // Assert
        $this->assertCount(5, $products);
        foreach ($products as $product) {
            $this->assertEquals(100, $product->stock);
        }
    }

    /**
     * Пример теста с исключением
     * 
     * Демонстрирует проверку выброса исключений
     */
    public function test_throws_exception_for_division_by_zero(): void
    {
        // Expect
        $this->expectException(\DivisionByZeroError::class);
        
        // Act
        $result = 10 / 0;
    }

    /**
     * Пример теста с assertions
     * 
     * Демонстрирует различные типы assertions
     */
    public function test_various_assertions(): void
    {
        // Assert равенство
        $this->assertEquals(10, 10);
        
        // Assert true/false
        $this->assertTrue(true);
        $this->assertFalse(false);
        
        // Assert null
        $this->assertNull(null);
        $this->assertNotNull('value');
        
        // Assert массивы
        $this->assertIsArray([1, 2, 3]);
        $this->assertCount(3, [1, 2, 3]);
        $this->assertEmpty([]);
        $this->assertNotEmpty([1]);
        
        // Assert строки
        $this->assertStringContainsString('world', 'Hello world');
        $this->assertStringStartsWith('Hello', 'Hello world');
        $this->assertStringEndsWith('world', 'Hello world');
    }
}
