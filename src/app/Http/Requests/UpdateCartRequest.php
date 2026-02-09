<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Запрос для валидации обновления количества товара в корзине
 * 
 * Этот класс проверяет корректность данных при изменении количества:
 * - Количество должно быть положительным числом
 * - Не должно превышать доступный остаток на складе
 */
final class UpdateCartRequest extends FormRequest
{
    /**
     * Определить, авторизован ли пользователь для выполнения этого запроса
     * 
     * @return bool Возвращает true, так как обновлять корзину могут все
     */
    public function authorize(): bool
    {
        // Обновление корзины доступно всем пользователям
        return true;
    }

    /**
     * Подготовить данные для валидации
     * 
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Приводим quantity к целому числу
        if ($this->has('quantity')) {
            $this->merge([
                'quantity' => (int) $this->quantity,
            ]);
        }
    }

    /**
     * Правила валидации для запроса
     * 
     * Описание полей:
     * - quantity: Новое количество товара (от 1 до 100)
     * 
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Валидация количества
            // required - обязательное поле
            // integer - целое число
            // min:1 - минимум 1 единица (если 0, то товар должен быть удален)
            // max:100 - максимум 100 единиц
            'quantity' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }

    /**
     * Дополнительная валидация с использованием валидатора
     * 
     * Проверяем, что запрашиваемое количество не превышает остаток на складе
     * 
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Получаем ID товара из позиции корзины (передается в маршруте)
            $cartItemId = $this->route('id');

            // Находим позицию в корзине
            $cartItem = \App\Models\CartItem::find($cartItemId);

            if ($cartItem) {
                // Получаем товар
                $product = $cartItem->product;

                // Проверяем наличие на складе
                if ($product && $product->stock < $this->quantity) {
                    $validator->errors()->add(
                        'quantity',
                        "В наличии только {$product->stock} ед. товара"
                    );
                }

                // Проверяем, что товар еще доступен
                if ($product && !$product->is_available) {
                    $validator->errors()->add(
                        'quantity',
                        'Этот товар больше недоступен для заказа'
                    );
                }
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
            'quantity.required' => 'Укажите количество товара',
            'quantity.integer' => 'Количество должно быть целым числом',
            'quantity.min' => 'Минимальное количество - :min. Для удаления используйте кнопку "Удалить"',
            'quantity.max' => 'Максимальное количество товара - :max',
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
            'quantity' => 'количество',
        ];
    }
}
