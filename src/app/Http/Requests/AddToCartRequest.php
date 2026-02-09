<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Запрос для валидации добавления товара в корзину
 * 
 * Этот класс проверяет корректность данных при добавлении товара:
 * - ID товара должен существовать и товар должен быть доступен
 * - Количество должно быть положительным и не превышать остаток на складе
 * - Вес (опционально) для товаров, продающихся на развес
 */
final class AddToCartRequest extends FormRequest
{
    /**
     * Определить, авторизован ли пользователь для выполнения этого запроса
     * 
     * @return bool Возвращает true, так как добавлять в корзину могут все
     */
    public function authorize(): bool
    {
        // Добавление в корзину доступно всем пользователям (включая гостей)
        // Для гостей корзина будет храниться в сессии
        return true;
    }

    /**
     * Подготовить данные для валидации
     * 
     * Этот метод вызывается до валидации и позволяет
     * преобразовать или очистить входные данные
     * 
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Приводим quantity к целому числу, если оно передано
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
     * - product_id: ID товара из каталога (обязательное поле)
     * - quantity: Количество единиц товара (по умолчанию 1)
     * - weight: Вес товара в граммах (для товаров на развес)
     * 
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Валидация ID товара
            // required - обязательное поле
            // integer - должно быть целым числом
            // exists - товар должен существовать в БД
            // Rule::exists - дополнительная проверка доступности товара
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where(function ($query) {
                    // Проверяем, что товар доступен для заказа и не удален
                    $query->where('is_available', true)
                          ->whereNull('deleted_at');
                }),
            ],

            // Валидация количества
            // required - обязательное поле
            // integer - целое число
            // min:1 - минимум 1 единица товара
            // max:100 - максимум 100 единиц за раз (защита от злоупотреблений)
            'quantity' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],

            // Валидация веса (опционально, для товаров на развес)
            // nullable - поле необязательное
            // integer - вес в граммах (целое число)
            // in - только определенные значения веса: 250г, 500г, 1000г
            'weight' => [
                'nullable',
                'integer',
                'in:250,500,1000', // Доступные варианты фасовки
            ],
        ];
    }

    /**
     * Дополнительная валидация с использованием валидатора
     * 
     * Этот метод позволяет добавить более сложные правила валидации,
     * которые требуют обращения к базе данных
     * 
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        // Добавляем проверку после основной валидации
        $validator->after(function ($validator) {
            // Проверяем наличие товара на складе
            $product = \App\Models\Product::find($this->product_id);

            if ($product) {
                // Проверяем, достаточно ли товара на складе
                if ($product->stock < $this->quantity) {
                    $validator->errors()->add(
                        'quantity',
                        "В наличии только {$product->stock} ед. товара"
                    );
                }

                // Проверяем, что товар в наличии
                if ($product->stock === 0) {
                    $validator->errors()->add(
                        'product_id',
                        'К сожалению, этот товар закончился'
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
            // Сообщения для product_id
            'product_id.required' => 'Не указан товар для добавления в корзину',
            'product_id.integer' => 'Некорректный идентификатор товара',
            'product_id.exists' => 'Товар не найден или недоступен для заказа',

            // Сообщения для quantity
            'quantity.required' => 'Укажите количество товара',
            'quantity.integer' => 'Количество должно быть целым числом',
            'quantity.min' => 'Минимальное количество товара - :min',
            'quantity.max' => 'Максимальное количество товара - :max',

            // Сообщения для weight
            'weight.integer' => 'Вес должен быть указан в граммах',
            'weight.in' => 'Доступные варианты фасовки: 250г, 500г, 1000г',
        ];
    }

    /**
     * Пользовательские названия атрибутов для сообщений об ошибках
     * 
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'product_id' => 'товар',
            'quantity' => 'количество',
            'weight' => 'вес',
        ];
    }
}
