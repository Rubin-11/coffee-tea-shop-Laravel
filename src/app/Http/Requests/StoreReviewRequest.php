<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Запрос для валидации создания отзыва на товар
 * 
 * Этот класс проверяет данные при добавлении отзыва:
 * - Рейтинг товара (от 1 до 5 звезд)
 * - Текст отзыва (обязательный, минимум 10 символов)
 * - Достоинства товара (необязательное поле)
 * - Недостатки товара (необязательное поле)
 */
final class StoreReviewRequest extends FormRequest
{
    /**
     * Определить, авторизован ли пользователь для выполнения этого запроса
     * 
     * @return bool Возвращает true только для авторизованных пользователей
     */
    public function authorize(): bool
    {
        // Оставлять отзывы могут только авторизованные пользователи
        // Это предотвращает спам и обеспечивает ответственность за отзывы
        return auth()->check();
    }

    /**
     * Правила валидации для запроса
     * 
     * Описание полей:
     * - rating: Оценка товара от 1 до 5 звезд
     * - comment: Основной текст отзыва (обязательный)
     * - pros: Достоинства товара (необязательное поле)
     * - cons: Недостатки товара (необязательное поле)
     * 
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Валидация рейтинга
            // required - обязательное поле (пользователь должен поставить оценку)
            // integer - целое число
            // between:1,5 - от 1 до 5 звезд включительно
            'rating' => [
                'required',
                'integer',
                'between:1,5',
            ],

            // Валидация текста отзыва
            // required - обязательное поле
            // string - строковое значение
            // min:10 - минимум 10 символов (чтобы отзыв был содержательным)
            // max:1000 - максимум 1000 символов (для читабельности)
            'comment' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],

            // Валидация достоинств товара
            // nullable - необязательное поле
            // string - строковое значение
            // max:500 - максимум 500 символов
            'pros' => [
                'nullable',
                'string',
                'max:500',
            ],

            // Валидация недостатков товара
            // nullable - необязательное поле
            // string - строковое значение
            // max:500 - максимум 500 символов
            'cons' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * Дополнительная валидация с использованием валидатора
     * 
     * Проверяем, что пользователь еще не оставлял отзыв на этот товар
     * 
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Получаем ID товара из маршрута
            $productId = $this->route('product');

            // Проверяем, существует ли уже отзыв от этого пользователя
            $existingReview = \App\Models\Review::where('user_id', auth()->id())
                ->where('product_id', $productId)
                ->exists();

            if ($existingReview) {
                $validator->errors()->add(
                    'rating',
                    'Вы уже оставили отзыв на этот товар'
                );
            }

            // Проверяем, существует ли товар
            $product = \App\Models\Product::find($productId);
            if (!$product) {
                $validator->errors()->add(
                    'product_id',
                    'Товар не найден'
                );
            }
        });
    }

    /**
     * Пользовательские сообщения об ошибках валидации
     * 
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Сообщения для рейтинга
            'rating.required' => 'Пожалуйста, поставьте оценку товару',
            'rating.integer' => 'Оценка должна быть целым числом',
            'rating.between' => 'Оценка должна быть от :min до :max звезд',

            // Сообщения для текста отзыва
            'comment.required' => 'Пожалуйста, напишите отзыв о товаре',
            'comment.string' => 'Отзыв должен быть текстом',
            'comment.min' => 'Отзыв должен содержать минимум :min символов',
            'comment.max' => 'Отзыв не должен превышать :max символов',

            // Сообщения для достоинств
            'pros.string' => 'Достоинства должны быть текстом',
            'pros.max' => 'Достоинства не должны превышать :max символов',

            // Сообщения для недостатков
            'cons.string' => 'Недостатки должны быть текстом',
            'cons.max' => 'Недостатки не должны превышать :max символов',
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
            'rating' => 'оценка',
            'comment' => 'отзыв',
            'pros' => 'достоинства',
            'cons' => 'недостатки',
        ];
    }

    /**
     * Обработка неудачной авторизации
     * 
     * Этот метод вызывается, когда authorize() возвращает false
     * 
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function failedAuthorization(): void
    {
        throw new \Illuminate\Auth\Access\AuthorizationException(
            'Для добавления отзыва необходимо войти в систему'
        );
    }
}
