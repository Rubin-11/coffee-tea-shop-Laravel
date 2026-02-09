<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Запрос для валидации подписки на email-рассылку
 * 
 * Этот класс проверяет корректность данных при подписке:
 * - Email должен быть валидным
 * - Email не должен быть уже подписан
 * - Дополнительные поля для персонализации рассылки
 */
final class NewsletterSubscribeRequest extends FormRequest
{
    /**
     * Определить, авторизован ли пользователь для выполнения этого запроса
     * 
     * @return bool Возвращает true, так как подписаться могут все
     */
    public function authorize(): bool
    {
        // Подписка на рассылку доступна всем посетителям сайта
        return true;
    }

    /**
     * Подготовить данные для валидации
     * 
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Приводим email к нижнему регистру для единообразия
        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower(trim($this->email)),
            ]);
        }
    }

    /**
     * Правила валидации для запроса
     * 
     * Описание полей:
     * - email: Email адрес для рассылки (обязательный, уникальный)
     * - name: Имя подписчика (необязательное, для персонализации)
     * - categories: Интересующие категории товаров (необязательное)
     * 
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Валидация email адреса
            // required - обязательное поле
            // string - строковое значение
            // email - должен быть корректным email адресом
            // max:255 - максимум 255 символов
            // unique - email не должен быть уже в базе подписчиков
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('subscribers', 'email')->where(function ($query) {
                    // Проверяем только активные подписки
                    // Если пользователь отписался (is_active = false),
                    // он сможет подписаться снова
                    return $query->where('is_active', true);
                }),
            ],

            // Валидация имени подписчика
            // nullable - необязательное поле
            // string - строковое значение
            // max:255 - максимум 255 символов
            // regex - только буквы и пробелы
            'name' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[А-Яа-яЁёA-Za-z\s]+$/u', // Только буквы (русские/английские) и пробелы
            ],

            // Валидация интересующих категорий
            // nullable - необязательное поле
            // array - должен быть массивом ID категорий
            'categories' => [
                'nullable',
                'array',
            ],

            // Валидация каждой категории в массиве
            // integer - ID должен быть целым числом
            // exists - категория должна существовать в БД
            'categories.*' => [
                'integer',
                'exists:categories,id',
            ],
        ];
    }

    /**
     * Пользовательские сообщения об ошибках валидации
     * 
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Сообщения для email
            'email.required' => 'Пожалуйста, укажите ваш email адрес',
            'email.email' => 'Некорректный формат email адреса',
            'email.max' => 'Email не должен превышать :max символов',
            'email.unique' => 'Этот email уже подписан на рассылку',

            // Сообщения для имени
            'name.string' => 'Имя должно быть текстом',
            'name.max' => 'Имя не должно превышать :max символов',
            'name.regex' => 'Имя должно содержать только буквы',

            // Сообщения для категорий
            'categories.array' => 'Категории должны быть переданы в виде массива',
            'categories.*.integer' => 'ID категории должен быть числом',
            'categories.*.exists' => 'Одна из выбранных категорий не существует',
        ];
    }

    /**
     * Пользовательские названия атрибутов
     * 
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'email' => 'email адрес',
            'name' => 'имя',
            'categories' => 'категории',
        ];
    }

    /**
     * Получить валидированные данные с дополнительной обработкой
     * 
     * Этот метод возвращает валидированные данные и добавляет
     * дополнительную информацию о подписчике
     * 
     * @param string|null $key
     * @param mixed $default
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated();

        // Добавляем IP адрес подписчика для статистики
        $validated['ip_address'] = $this->ip();

        // Добавляем User Agent для определения источника подписки
        $validated['user_agent'] = $this->userAgent();

        // Дата и время подписки будут установлены автоматически через timestamps
        // Статус is_active будет true по умолчанию (установлен в миграции)

        return $validated;
    }
}
