<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Feature-тесты восстановления пароля
 *
 * Проверяют «забыли пароль»: запрос ссылки (шаг 1) и установку
 * нового пароля по токену (шаг 2).
 *
 * Письма уходят в лог (MAIL_MAILER=log), поэтому в тестах работаем
 * с брокером сброса напрямую — так надёжнее, чем парсить лог.
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    // ==================== ЗАПРОС ССЫЛКИ (ШАГ 1) ====================

    /**
     * Гость видит форму запроса ссылки
     */
    public function test_guest_sees_forgot_password_form(): void
    {
        $this->get(route('auth.forgot'))
            ->assertOk()
            ->assertSee('Восстановление пароля');
    }

    /**
     * Запрос ссылки для существующего email — статус «отправлено»
     */
    public function test_send_reset_link_for_existing_email(): void
    {
        $this->createUser(['email' => 'ivan@example.com']);

        $this->post(route('auth.forgot.send'), [
            'email' => 'ivan@example.com',
        ])->assertSessionHas('status');

        // Токен реально создан в таблице сброса
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'ivan@example.com',
        ]);
    }

    /**
     * Запрос ссылки для несуществующего email — тот же ответ
     * (не раскрываем, что аккаунта нет — защита от перебора адресов)
     */
    public function test_send_reset_link_for_unknown_email_does_not_reveal_account(): void
    {
        $this->post(route('auth.forgot.send'), [
            'email' => 'noone@example.com',
        ])->assertSessionHas('status');

        // Никакого токена в БД нет
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'noone@example.com',
        ]);
    }

    /**
     * Невалидный email отклоняется
     */
    public function test_send_reset_link_rejects_invalid_email(): void
    {
        $this->post(route('auth.forgot.send'), [
            'email' => 'не-email',
        ])->assertSessionHasErrors('email');
    }

    // ==================== СБРОС ПО ТОКЕНУ (ШАГ 2) ====================

    /**
     * По валидному токену пароль меняется, пользователь залогинен
     */
    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = $this->createUser([
            'email' => 'ivan@example.com',
            'password' => Hash::make('old-password'),
        ]);

        // Создаём настоящий токен через брокер (как при отправке письма)
        $token = Password::createToken($user);

        $this->post(route('auth.reset'), [
            'token' => $token,
            'email' => 'ivan@example.com',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect(route('profile.index'));

        // Пароль реально изменён
        $user->refresh();
        $this->assertTrue(Hash::check('new-password', $user->password));
        $this->assertFalse(Hash::check('old-password', $user->password));

        // Пользователь залогинен после сброса
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Невалидный токен отклоняется
     */
    public function test_reset_password_fails_with_invalid_token(): void
    {
        $this->createUser(['email' => 'ivan@example.com']);

        $this->post(route('auth.reset'), [
            'token' => 'not-a-real-token',
            'email' => 'ivan@example.com',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasErrors('email');

        // Пароль не изменился
        $user = User::where('email', 'ivan@example.com')->first();
        $this->assertFalse(Hash::check('new-password', $user->password));
        $this->assertGuest();
    }

    /**
     * Просроченный токен отклоняется
     */
    public function test_reset_password_fails_with_expired_token(): void
    {
        $user = $this->createUser(['email' => 'ivan@example.com']);
        $token = Password::createToken($user);

        // «Старим» токен на 2 часа — больше стандартного срока жизни (60 минут)
        DB::table('password_reset_tokens')
            ->where('email', 'ivan@example.com')
            ->update(['created_at' => now()->subHours(2)]);

        $this->post(route('auth.reset'), [
            'token' => $token,
            'email' => 'ivan@example.com',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasErrors('email');

        $user->refresh();
        $this->assertFalse(Hash::check('new-password', $user->password));
    }

    /**
     * Короткий новый пароль отклоняется
     */
    public function test_reset_password_rejects_short_password(): void
    {
        $user = $this->createUser(['email' => 'ivan@example.com']);
        $token = Password::createToken($user);

        $this->post(route('auth.reset'), [
            'token' => $token,
            'email' => 'ivan@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');
    }

    /**
     * Несовпадающие пароли отклоняются
     */
    public function test_reset_password_rejects_mismatched_passwords(): void
    {
        $user = $this->createUser(['email' => 'ivan@example.com']);
        $token = Password::createToken($user);

        $this->post(route('auth.reset'), [
            'token' => $token,
            'email' => 'ivan@example.com',
            'password' => 'new-password',
            'password_confirmation' => 'other-password',
        ])->assertSessionHasErrors('password');
    }
}
