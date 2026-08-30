<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Feature-тесты авторизации
 *
 * Проверяют «сайт как пользователь»: регистрацию, вход, выход
 * и права доступа к закрытым страницам.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    // ==================== РЕГИСТРАЦИЯ ====================

    /**
     * Гость видит форму регистрации
     */
    public function test_guest_sees_register_form(): void
    {
        $this->get(route('auth.register'))
            ->assertOk()
            ->assertSee('Регистрация');
    }

    /**
     * Авторизованный пользователь не видит форму регистрации (редирект в ЛК)
     */
    public function test_authenticated_user_is_redirected_from_register(): void
    {
        $this->actingAsUser()
            ->get(route('auth.register'))
            ->assertRedirect(route('profile.index'));
    }

    /**
     * Регистрация создаёт пользователя, хэширует пароль и логинит
     */
    public function test_user_can_register(): void
    {
        $response = $this->post(route('auth.register.submit'), [
            'first_name' => 'Иван',
            'last_name' => 'Петров',
            'email' => 'ivan@example.com',
            'phone' => '+7 (999) 123-45-67',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect(route('profile.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'ivan@example.com',
            'first_name' => 'Иван',
            'last_name' => 'Петров',
        ]);

        // Пароль в БД — хэш, а не открытый текст
        $user = User::where('email', 'ivan@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotSame('secret123', $user->password);
        $this->assertTrue(Hash::check('secret123', $user->password));

        // Пользователь залогинен
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Дубль email отклоняется
     */
    public function test_registration_rejects_duplicate_email(): void
    {
        $this->createUser(['email' => 'ivan@example.com']);

        $this->post(route('auth.register.submit'), [
            'first_name' => 'Иван',
            'last_name' => 'Петров',
            'email' => 'ivan@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
    }

    /**
     * Короткий пароль отклоняется
     */
    public function test_registration_rejects_short_password(): void
    {
        $this->post(route('auth.register.submit'), [
            'first_name' => 'Иван',
            'last_name' => 'Петров',
            'email' => 'ivan@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    /**
     * Несовпадающие пароли отклоняются
     */
    public function test_registration_rejects_mismatched_passwords(): void
    {
        $this->post(route('auth.register.submit'), [
            'first_name' => 'Иван',
            'last_name' => 'Петров',
            'email' => 'ivan@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret456',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    /**
     * Невалидный email отклоняется
     */
    public function test_registration_rejects_invalid_email(): void
    {
        $this->post(route('auth.register.submit'), [
            'first_name' => 'Иван',
            'last_name' => 'Петров',
            'email' => 'не-email',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 0);
    }

    /**
     * Пустые обязательные поля отклоняются
     */
    public function test_registration_requires_all_fields(): void
    {
        $this->post(route('auth.register.submit'), [])
            ->assertSessionHasErrors(['first_name', 'last_name', 'email', 'password']);
    }

    // ==================== ВХОД И ВЫХОД ====================

    /**
     * Гость видит форму входа
     */
    public function test_guest_sees_login_form(): void
    {
        $this->get(route('auth.login'))
            ->assertOk()
            ->assertSee('Вход');
    }

    /**
     * Вход с верными данными пускает в ЛК
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = $this->createUser([
            'email' => 'ivan@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $this->post(route('auth.login.submit'), [
            'email' => 'ivan@example.com',
            'password' => 'secret123',
        ])->assertRedirect(route('profile.index'));

        $this->assertAuthenticatedAs($user);
    }

    /**
     * Вход с неверным паролем отклоняется, пользователь не залогинен
     */
    public function test_login_fails_with_wrong_password(): void
    {
        $this->createUser([
            'email' => 'ivan@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $this->post(route('auth.login.submit'), [
            'email' => 'ivan@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /**
     * Вход с несуществующим email отклоняется
     */
    public function test_login_fails_with_unknown_email(): void
    {
        $this->post(route('auth.login.submit'), [
            'email' => 'noone@example.com',
            'password' => 'secret123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /**
     * Выход разлогинивает пользователя и ведёт на главную
     */
    public function test_user_can_logout(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->post(route('auth.logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }

    // ==================== ПРАВА ДОСТУПА ====================

    /**
     * Гость без входа не попадает в ЛК (редирект на форму входа)
     */
    public function test_guest_is_redirected_to_login_from_profile(): void
    {
        $this->get(route('profile.index'))
            ->assertRedirect(route('auth.login'));
    }

    /**
     * Гость без входа не попадает в заказы (редирект на форму входа)
     */
    public function test_guest_is_redirected_to_login_from_orders(): void
    {
        $this->get(route('orders.index'))
            ->assertRedirect(route('auth.login'));
    }

    /**
     * Авторизованный пользователь открывает ЛК
     */
    public function test_authenticated_user_can_open_profile(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('profile.index'))
            ->assertOk()
            ->assertSee($user->full_name);
    }
}
