<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke-тесты страниц (диагностическая разбивка: каждая страница — свой тест).
 */
class PagesSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function seedData(): array
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'is_available' => true]);
        $post = BlogPost::factory()->published()->create();

        return [$category, $product, $post];
    }

    public function test_home(): void
    {
        $this->seedData();
        $this->get(route('home'))->assertOk();
    }

    public function test_blog_index(): void
    {
        $this->seedData();
        $this->get(route('blog.index'))->assertOk();
    }

    public function test_blog_show(): void
    {
        [, , $post] = $this->seedData();
        $this->get(route('blog.show', $post->slug))->assertOk();
    }

    public function test_cart_index(): void
    {
        $this->seedData();
        $this->get(route('cart.index'))->assertOk();
    }

    public function test_categories_show(): void
    {
        [$category] = $this->seedData();
        $this->get(route('categories.show', $category->slug))->assertOk();
    }

    public function test_products_show(): void
    {
        [, $product] = $this->seedData();
        $this->get(route('products.show', $product->slug))->assertOk();
    }

    public function test_pages_about(): void
    {
        $this->seedData();
        $this->get(route('pages.about'))->assertOk();
    }

    public function test_pages_contacts(): void
    {
        $this->seedData();
        $this->get(route('pages.contacts'))->assertOk();
    }

    public function test_pages_delivery(): void
    {
        $this->seedData();
        $this->get(route('pages.delivery'))->assertOk();
    }

    public function test_pages_returns(): void
    {
        $this->seedData();
        $this->get(route('pages.returns'))->assertOk();
    }

    public function test_pages_privacy(): void
    {
        $this->seedData();
        $this->get(route('pages.privacy'))->assertOk();
    }

    public function test_pages_terms(): void
    {
        $this->seedData();
        $this->get(route('pages.terms'))->assertOk();
    }

    public function test_auth_login(): void
    {
        $this->seedData();
        $this->get(route('auth.login'))->assertOk();
    }

    public function test_auth_register(): void
    {
        $this->seedData();
        $this->get(route('auth.register'))->assertOk();
    }

    public function test_auth_forgot(): void
    {
        $this->seedData();
        $this->get(route('auth.forgot'))->assertOk();
    }

    public function test_products_index_redirect(): void
    {
        $this->seedData();
        $this->get(route('products.index'))->assertRedirect('/categories/svezheobzharennyy-kofe');
    }

    public function test_categories_index_redirect(): void
    {
        $this->seedData();
        $this->get(route('categories.index'))->assertRedirect('/#catalog');
    }

    public function test_unknown_page_404(): void
    {
        $this->seedData();
        $this->get('/this-page-does-not-exist')->assertNotFound();
    }
}
