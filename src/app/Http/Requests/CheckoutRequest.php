<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Запрос для валидации оформления заказа
 * 
 * Этот класс проверяет все данные при оформлении заказа:
 * - Контактные данные покупателя (имя, email, телефон)
 * - Адрес доставки (выбор существующего или создание нового)
 * - Способ оплаты и доставки
 * - Дополнительные параметры (комментарий, промокод)
 */
final class CheckoutRequest extends FormRequest
{
    /**
     * Определить, авторизован ли пользователь для выполнения этого запроса
     * 
     * @return bool Возвращает true, так как оформлять заказы могут все
     */
    public function authorize(): bool
    {
        // Оформление заказа доступно всем (включая гостей)
        return true;
    }

    /**
     * Подготовить данные для валидации
     * 
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Очищаем телефон от лишних символов
        if ($this->has('phone')) {
            $phone = preg_replace('/[^0-9]/', '', $this->phone);
            $this->merge(['phone' => $phone]);
        }
    }

    /**
     * Правила валидации для запроса
     * 
     * Описание полей:
     * - name: ФИО покупателя
     * - email: Email для уведомлений
     * - phone: Телефон для связи
     * - address_id: ID существующего адреса (для авторизованных)
     * - new_address: Данные нового адреса доставки
     * - payment_method: Способ оплаты (наличные, карта, онлайн)
     * - delivery_method: Способ доставки (самовывоз, курьер, почта)
     * - comment: Комментарий к заказу
     * - promocode: Промокод на скидку
     * 
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // ===== Контактные данные =====
            
            // Валидация ФИО
            // required - обязательное поле
            // string - строковое значение
            // max:255 - максимум 255 символов
            // regex - только буквы и пробелы
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[А-Яа-яЁёA-Za-z\s]+$/u', // Только буквы (русские/английские) и пробелы
            ],

            // Валидация Email
            // required - обязательное поле
            // email - должен быть корректным email адресом
            // max:255 - максимум 255 символов
            'email' => [
                'required',
                'email',
                'max:255',
            ],

            // Валидация телефона
            // required - обязательное поле
            // string - строка
            // regex - от 10 до 15 цифр (после очистки в prepareForValidation)
            'phone' => [
                'required',
                'string',
                'regex:/^[0-9]{10,15}$/', // От 10 до 15 цифр
            ],

            // ===== Адрес доставки =====
            
            // Валидация выбора существующего адреса
            // nullable - можно не указывать (если создается новый)
            // integer - целое число
            // exists - адрес должен существовать и принадлежать пользователю
            'address_id' => [
                'nullable',
                'integer',
                'exists:addresses,id',
                // Если пользователь авторизован, проверяем что адрес принадлежит ему
                function ($attribute, $value, $fail) {
                    if (auth()->check() && $value) {
                        $address = \App\Models\Address::find($value);
                        if ($address && $address->user_id !== auth()->id()) {
                            $fail('Выбранный адрес не принадлежит вам');
                        }
                    }
                },
            ],

            // Валидация создания нового адреса
            // required_without:address_id - обязательно если не указан address_id
            // array - должен быть массивом с данными адреса
            'new_address' => [
                'required_without:address_id',
                'array',
            ],

            // Поля нового адреса
            'new_address.city' => [
                'required_with:new_address',
                'string',
                'max:100',
            ],
            'new_address.street' => [
                'required_with:new_address',
                'string',
                'max:255',
            ],
            'new_address.house' => [
                'required_with:new_address',
                'string',
                'max:20',
            ],
            'new_address.apartment' => [
                'nullable',
                'string',
                'max:20',
            ],
            'new_address.postal_code' => [
                'required_with:new_address',
                'string',
                'regex:/^[0-9]{6}$/', // Российский индекс - 6 цифр
            ],
            'new_address.entrance' => [
                'nullable',
                'string',
                'max:10',
            ],
            'new_address.floor' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
            'new_address.intercom' => [
                'nullable',
                'string',
                'max:20',
            ],

            // ===== Способы оплаты и доставки =====
            
            // Валидация способа оплаты
            // required - обязательное поле
            // in - только из списка разрешенных значений
            'payment_method' => [
                'required',
                'string',
                'in:cash,card,online', // Наличные, картой курьеру, онлайн-оплата
            ],

            // Валидация способа доставки
            // required - обязательное поле
            // in - только из списка разрешенных значений
            'delivery_method' => [
                'required',
                'string',
                'in:pickup,courier,post', // Самовывоз, курьер, почта
            ],

            // ===== Дополнительные поля =====
            
            // Валидация комментария к заказу
            // nullable - необязательное поле
            // string - строковое значение
            // max:500 - максимум 500 символов
            'comment' => [
                'nullable',
                'string',
                'max:500',
            ],

            // Валидация промокода
            // nullable - необязательное поле
            // string - строка
            // alpha_num - только буквы и цифры
            // max:50 - максимум 50 символов
            'promocode' => [
                'nullable',
                'string',
                'alpha_num',
                'max:50',
            ],

            // Валидация желаемой даты доставки
            // nullable - необязательное поле
            // date - должна быть корректной датой
            // after:today - дата должна быть не раньше завтрашнего дня
            'delivery_date' => [
                'nullable',
                'date',
                'after:today',
            ],

            // Валидация желаемого времени доставки
            // nullable - необязательное поле
            // date_format - формат HH:MM
            'delivery_time' => [
                'nullable',
                'date_format:H:i',
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
            // Сообщения для контактных данных
            'name.required' => 'Укажите ваше имя',
            'name.regex' => 'Имя должно содержать только буквы',
            'name.max' => 'Имя не должно превышать :max символов',

            'email.required' => 'Укажите ваш email',
            'email.email' => 'Некорректный формат email адреса',
            'email.max' => 'Email не должен превышать :max символов',

            'phone.required' => 'Укажите ваш телефон',
            'phone.regex' => 'Номер телефона должен содержать от 10 до 15 цифр',

            // Сообщения для адреса
            'address_id.exists' => 'Выбранный адрес не найден',
            'new_address.required_without' => 'Укажите адрес доставки или выберите существующий',
            'new_address.array' => 'Некорректный формат данных адреса',

            'new_address.city.required_with' => 'Укажите город',
            'new_address.city.max' => 'Название города не должно превышать :max символов',

            'new_address.street.required_with' => 'Укажите улицу',
            'new_address.street.max' => 'Название улицы не должно превышать :max символов',

            'new_address.house.required_with' => 'Укажите номер дома',
            'new_address.house.max' => 'Номер дома не должен превышать :max символов',

            'new_address.apartment.max' => 'Номер квартиры не должен превышать :max символов',

            'new_address.postal_code.required_with' => 'Укажите почтовый индекс',
            'new_address.postal_code.regex' => 'Индекс должен содержать 6 цифр',

            'new_address.floor.integer' => 'Этаж должен быть числом',
            'new_address.floor.min' => 'Минимальный этаж - :min',
            'new_address.floor.max' => 'Максимальный этаж - :max',

            // Сообщения для способов оплаты и доставки
            'payment_method.required' => 'Выберите способ оплаты',
            'payment_method.in' => 'Выбран недопустимый способ оплаты',

            'delivery_method.required' => 'Выберите способ доставки',
            'delivery_method.in' => 'Выбран недопустимый способ доставки',

            // Сообщения для дополнительных полей
            'comment.max' => 'Комментарий не должен превышать :max символов',

            'promocode.alpha_num' => 'Промокод должен содержать только буквы и цифры',
            'promocode.max' => 'Промокод не должен превышать :max символов',

            'delivery_date.date' => 'Некорректная дата доставки',
            'delivery_date.after' => 'Дата доставки должна быть не раньше завтрашнего дня',

            'delivery_time.date_format' => 'Время доставки должно быть в формате ЧЧ:ММ',
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
            'name' => 'имя',
            'email' => 'email',
            'phone' => 'телефон',
            'address_id' => 'адрес',
            'payment_method' => 'способ оплаты',
            'delivery_method' => 'способ доставки',
            'comment' => 'комментарий',
            'promocode' => 'промокод',
            'delivery_date' => 'дата доставки',
            'delivery_time' => 'время доставки',
        ];
    }
}
