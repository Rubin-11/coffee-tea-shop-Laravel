<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\Product;
use Illuminate\View\Component;
use Illuminate\Contracts\View\View;

/**
 * View Component для карточки товара
 * 
 * Отвечает за отображение карточки товара с характеристиками.
 * Вся логика (циклы, условия, форматирование) находится здесь,
 * а шаблон остается чистым и читаемым.
 * 
 * Использование в Blade:
 * <x-product-card :product="$product" />
 */
final class ProductCard extends Component
{
    /**
     * Товар для отображения
     * 
     * @var Product
     */
    public Product $product;

    /**
     * Конструктор компонента
     * 
     * @param Product $product Объект товара из базы данных
     */
    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * Получить массив флагов для отображения звезд рейтинга
     * 
     * Возвращает массив из 5 элементов (true/false)
     * true = заполненная звезда, false = пустая звезда
     * 
     * Пример: рейтинг 4.2 → [true, true, true, true, false]
     * 
     * @return array<int, bool>
     */
    public function ratingStars(): array
    {
        // Преобразуем в float (т.к. rating может быть строкой из-за decimal:2 cast)
        $ratingValue = floatval($this->product->rating ?? 0);
        $rating = (int) round($ratingValue);
        
        return array_map(
            fn($i) => $i <= $rating,
            range(1, 5)
        );
    }

    /**
     * Получить массив флагов для отображения зерен обжарки
     * 
     * Возвращает массив из 5 элементов (true/false)
     * true = активное зерно (черное), false = неактивное (серое)
     * 
     * @return array<int, bool>
     */
    public function roastBeans(): array
    {
        $level = $this->product->roast_level ?? 3;
        
        return array_map(
            fn($i) => $i <= $level,
            range(1, 5)
        );
    }

    /**
     * Получить массив флагов для уровня кислинки (1-7)
     * 
     * @return array<int, bool>
     */
    public function acidityLevels(): array
    {
        $acidity = $this->product->acidity ?? 0;
        
        return array_map(
            fn($i) => $i <= $acidity,
            range(1, 7)
        );
    }

    /**
     * Получить массив флагов для уровня горчинки (1-7)
     * 
     * @return array<int, bool>
     */
    public function bitternessLevels(): array
    {
        $bitterness = $this->product->bitterness ?? 0;
        
        return array_map(
            fn($i) => $i <= $bitterness,
            range(1, 7)
        );
    }

    /**
     * Получить массив флагов для уровня насыщенности (1-7)
     * 
     * @return array<int, bool>
     */
    public function saturationLevels(): array
    {
        $saturation = $this->product->saturation ?? 0;
        
        return array_map(
            fn($i) => $i <= $saturation,
            range(1, 7)
        );
    }

    /**
     * Проверить, есть ли скидка на товар
     * 
     * @return bool
     */
    public function hasDiscount(): bool
    {
        return isset($this->product->discount) && $this->product->discount > 0;
    }

    /**
     * Получить форматированную цену
     * 
     * Пример: 1500.00 → "1 500 ₽"
     * 
     * @return string
     */
    public function formattedPrice(): string
    {
        $price = floatval($this->product->price ?? 0);
        return number_format($price, 0, ',', ' ') . ' ₽';
    }

    /**
     * Получить форматированную цену со скидкой
     * 
     * @return string
     */
    public function formattedDiscountedPrice(): string
    {
        $price = floatval($this->product->discounted_price ?? 0);
        return number_format($price, 0, ',', ' ') . ' ₽';
    }

    /**
     * Получить форматированный рейтинг (с одним знаком после запятой)
     * 
     * Пример: 4.3
     * 
     * @return string
     */
    public function formattedRating(): string
    {
        $rating = floatval($this->product->rating ?? 0);
        return number_format($rating, 1);
    }

    /**
     * Получить количество отзывов
     * 
     * @return int
     */
    public function reviewsCount(): int
    {
        return $this->product->reviews_count ?? 0;
    }

    /**
     * Получить слово "отзыв" в правильном склонении для русского языка
     * 
     * 1, 21, 31... → "отзыв"
     * 2, 3, 4, 22, 23, 24... → "отзыва"
     * 0, 5-20, 25-30... → "отзывов"
     * 
     * @return string
     */
    public function reviewsCountLabel(): string
    {
        $n = $this->reviewsCount();
        $n10 = $n % 10;
        $n100 = $n % 100;

        if ($n10 === 1 && $n100 !== 11) {
            return 'отзыв';
        }
        if ($n10 >= 2 && $n10 <= 4 && ($n100 < 10 || $n100 >= 20)) {
            return 'отзыва';
        }
        return 'отзывов';
    }

    /**
     * Получить описание товара или значение по умолчанию
     * 
     * @return string
     */
    public function description(): string
    {
        return $this->product->description 
            ?? 'Свежеобжаренный кофе - описание товара, вкус, аромат';
    }

    /**
     * Получить варианты веса для селектора
     * 
     * @return array<int>
     */
    public function weightOptions(): array
    {
        return $this->product->weight_options ?? [250, 500, 1000];
    }

    /**
     * Рендеринг компонента
     * 
     * @return View
     */
    public function render(): View
    {
        return view('components.product-card');
    }
}
