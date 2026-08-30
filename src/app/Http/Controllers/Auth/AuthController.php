<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Контроллер аутентификации
 *
 * Обрабатывает вход, регистрацию, выход и восстановление пароля.
 * Авторизация — сессионная (драйвер 'session' в config/auth.php).
 *
 * Восстановление пароля использует стандартный механизм Laravel
 * (Password::broker + таблица password_reset_tokens).
 */
final class AuthController extends Controller
{
    /**
     * Показать форму входа
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Выполнить вход
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        // remember — «запомнить меня» (опционально, из формы)
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            // Сессию лучше перегенерировать при входе — защита от фиксации сессии
            $request->session()->regenerate();

            return redirect()->intended(route('profile.index'));
        }

        // Не раскрываем, что именно неверно (email или пароль)
        return back()
            ->withErrors(['email' => 'Неверный email или пароль'])
            ->onlyInput('email');
    }

    /**
     * Показать форму регистрации
     */
    public function showRegisterForm(): View
    {
        return view('auth.register');
    }

    /**
     * Зарегистрировать нового пользователя
     */
    public function register(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Пароль хешируется автоматически (cast 'hashed' в модели User)
        $user = User::query()->create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('profile.index');
    }

    /**
     * Выйти из аккаунта
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        // Инвалидируем сессию и CSRF-токен — стандартная практика после выхода
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /**
     * Показать форму запроса ссылки на сброс пароля
     */
    public function showForgotForm(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Отправить ссылку на сброс пароля
     */
    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        // ВАЖНО: ответ одинаковый и для существующего, и для несуществующего email —
        // не даём «перебирать» зарегистрированные адреса.
        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __('Если указанный email зарегистрирован, мы отправили на него ссылку для сброса пароля.'))
            : back()->withErrors(['email' => __('Не удалось отправить ссылку для сброса пароля. Попробуйте позже.')]);
    }

    /**
     * Показать форму установки нового пароля (по токену из письма)
     */
    public function showResetForm(string $token): View
    {
        return view('auth.reset-password', ['token' => $token]);
    }

    /**
     * Сохранить новый пароль
     */
    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                ])->save();

                // После смены пароля инвалидируем все токены «запомнить меня»
                $user->setRememberToken(Str::random(60));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            // Сразу логиним пользователя после сброса — удобно и безопасно
            if ($user = User::query()->where('email', $request->email)->first()) {
                Auth::login($user);
                $request->session()->regenerate();
            }

            return redirect()->route('profile.index')->with('status', __('Пароль успешно изменён.'));
        }

        return back()
            ->withErrors(['email' => __('Ссылка для сброса пароля недействительна или устарела.')])
            ->onlyInput('email');
    }
}
