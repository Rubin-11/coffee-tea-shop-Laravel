<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * Валидация формы регистрации
 *
 * Email приводим к нижнему регистру — чтобы не плодить дубли
 * вида User@mail.ru / user@mail.ru.
 */
final class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => mb_strtolower(trim((string) $this->email)),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100', 'regex:/^[А-Яа-яЁёA-Za-z\s\-]+$/u'],
            'last_name' => ['required', 'string', 'max:100', 'regex:/^[А-Яа-яЁёA-Za-z\s\-]+$/u'],
            'email' => ['required', 'string', 'email', 'max:250', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed', PasswordRule::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'Введите имя',
            'first_name.regex' => 'Имя может содержать только буквы',
            'last_name.required' => 'Введите фамилию',
            'last_name.regex' => 'Фамилия может содержать только буквы',
            'email.required' => 'Введите email',
            'email.email' => 'Некорректный формат email',
            'email.unique' => 'Пользователь с таким email уже зарегистрирован',
            'password.required' => 'Придумайте пароль',
            'password.min' => 'Пароль должен быть не короче :min символов',
            'password.confirmed' => 'Пароли не совпадают',
        ];
    }
}
