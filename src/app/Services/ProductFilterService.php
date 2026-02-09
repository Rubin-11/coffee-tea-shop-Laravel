<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Сервис для фильтрации и сортировки товаров
 * 
 * Этот сервис инкапсулирует всю бизнес-логику фильтрации товаров в каталоге:
 * - Фильтрация по категории
 * - Фильтрация по диапазону цен
 * - Фильтрация по тегам
 * - Фильтрация по наличию (в наличии/нет в наличии)
 * - Фильтрация по рейтингу
 * - Фильтрация по характеристикам (горчинка, кислинка)
 * - Сортировка по различным критериям
 * - Поиск по названию и описанию
 */
final readonly class ProductFilterService
{
    /**
     * Применить фильтры и вернуть отфильтрованные товары
     * 
     * Основной метод сервиса. Принимает массив фильтров из ProductFilterRequest
     * и возвращает отфильтрованные и отсортированные товары с пагинацией.
     * 
     * @param array $filters Массив фильтров
     * @return LengthAwarePaginator Товары с пагинацией
     */
    public function filter(array $filters): LengthAwarePaginator
    {
        // Начинаем с базового запроса - только доступные товары
        $query = Product::query()
            ->with(['category', 'primaryImage', 'tags'])
            ->available();

        // Применяем фильтры
        $query = $this->applyFilters($query, $filters);

        // Применяем сортировку
        $query = $this->applySorting($query, $filters['sort'] ?? 'popular');

        // Количество товаров на странице (по умолчанию 12)
        $perPage = $filters['per_page'] ?? 12;

        // Возвращаем результаты с пагинацией
        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Применить все фильтры к запросу
     * 
     * @param Builder $query Строитель запроса
     * @param array $filters Массив фильтров
     * @return Builder Обновленный строитель запроса
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        // Фильтр по поисковому запросу
        if (!empty($filters['search'])) {
            $query = $this->filterBySearch($query, $filters['search']);
        }

        // Фильтр по категории
        if (!empty($filters['category_id'])) {
            $query = $this->filterByCategory($query, (int) $filters['category_id']);
        }

        // Фильтр по диапазону цен
        if (!empty($filters['price_min']) || !empty($filters['price_max'])) {
            $query = $this->filterByPriceRange(
                $query,
                $filters['price_min'] ?? null,
                $filters['price_max'] ?? null
            );
        }

        // Фильтр по тегам
        if (!empty($filters['tags']) && is_array($filters['tags'])) {
            $query = $this->filterByTags($query, $filters['tags']);
        }

        // Фильтр по наличию
        if (isset($filters['in_stock']) && $filters['in_stock']) {
            $query = $this->filterByStock($query);
        }

        // Фильтр по рейтингу
        if (!empty($filters['min_rating'])) {
            $query = $this->filterByRating($query, (float) $filters['min_rating']);
        }

        // Фильтр по горчинке
        if (!empty($filters['bitterness_min']) || !empty($filters['bitterness_max'])) {
            $query = $this->filterByBitterness(
                $query,
                $filters['bitterness_min'] ?? null,
                $filters['bitterness_max'] ?? null
            );
        }

        // Фильтр по кислинке
        if (!empty($filters['acidity_min']) || !empty($filters['acidity_max'])) {
            $query = $this->filterByAcidity(
                $query,
                $filters['acidity_min'] ?? null,
                $filters['acidity_max'] ?? null
            );
        }

        // Фильтр по скидкам
        if (isset($filters['on_sale']) && $filters['on_sale']) {
            $query = $this->filterByDiscount($query);
        }

        // Фильтр по рекомендуемым товарам
        if (isset($filters['featured']) && $filters['featured']) {
            $query = $this->filterByFeatured($query);
        }

        return $query;
    }

    /**
     * Фильтр по поисковому запросу
     * 
     * Ищет в названии, описании и подробном описании товара
     * 
     * @param Builder $query Строитель запроса
     * @param string $search Поисковый запрос
     * @return Builder Обновленный строитель запроса
     */
    private function filterBySearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $searchTerm = '%' . $search . '%';
            $q->where('name', 'like', $searchTerm)
              ->orWhere('description', 'like', $searchTerm)
              ->orWhere('long_description', 'like', $searchTerm)
              ->orWhere('sku', 'like', $searchTerm);
        });
    }

    /**
     * Фильтр по категории
     * 
     * Включает товары из выбранной категории и всех её подкатегорий
     * 
     * @param Builder $query Строитель запроса
     * @param int $categoryId ID категории
     * @return Builder Обновленный строитель запроса
     */
    private function filterByCategory(Builder $query, int $categoryId): Builder
    {
        // Получаем ID всех подкатегорий выбранной категории
        $categoryIds = [$categoryId];
        
        // Здесь можно добавить логику для получения всех подкатегорий рекурсивно
        // Для простоты пока фильтруем только по основной категории
        
        return $query->whereIn('category_id', $categoryIds);
    }

    /**
     * Фильтр по диапазону цен
     * 
     * @param Builder $query Строитель запроса
     * @param float|null $minPrice Минимальная цена
     * @param float|null $maxPrice Максимальная цена
     * @return Builder Обновленный строитель запроса
     */
    private function filterByPriceRange(Builder $query, ?float $minPrice, ?float $maxPrice): Builder
    {
        if ($minPrice !== null) {
            $query->where('price', '>=', $minPrice);
        }

        if ($maxPrice !== null) {
            $query->where('price', '<=', $maxPrice);
        }

        return $query;
    }

    /**
     * Фильтр по тегам
     * 
     * Возвращает товары, имеющие хотя бы один из указанных тегов
     * 
     * @param Builder $query Строитель запроса
     * @param array<int> $tagIds Массив ID тегов
     * @return Builder Обновленный строитель запроса
     */
    private function filterByTags(Builder $query, array $tagIds): Builder
    {
        return $query->whereHas('tags', function (Builder $q) use ($tagIds) {
            $q->whereIn('tags.id', $tagIds);
        });
    }

    /**
     * Фильтр по наличию на складе
     * 
     * Показывает только товары, которые есть в наличии (stock > 0)
     * 
     * @param Builder $query Строитель запроса
     * @return Builder Обновленный строитель запроса
     */
    private function filterByStock(Builder $query): Builder
    {
        return $query->where('stock', '>', 0);
    }

    /**
     * Фильтр по минимальному рейтингу
     * 
     * Показывает товары с рейтингом не ниже указанного
     * 
     * @param Builder $query Строитель запроса
     * @param float $minRating Минимальный рейтинг (1-5)
     * @return Builder Обновленный строитель запроса
     */
    private function filterByRating(Builder $query, float $minRating): Builder
    {
        return $query->where('rating', '>=', $minRating);
    }

    /**
     * Фильтр по уровню горчинки
     * 
     * @param Builder $query Строитель запроса
     * @param int|null $min Минимальный уровень горчинки (0-10)
     * @param int|null $max Максимальный уровень горчинки (0-10)
     * @return Builder Обновленный строитель запроса
     */
    private function filterByBitterness(Builder $query, ?int $min, ?int $max): Builder
    {
        if ($min !== null) {
            $query->where('bitterness_percent', '>=', $min);
        }

        if ($max !== null) {
            $query->where('bitterness_percent', '<=', $max);
        }

        return $query;
    }

    /**
     * Фильтр по уровню кислинки
     * 
     * @param Builder $query Строитель запроса
     * @param int|null $min Минимальный уровень кислинки (0-10)
     * @param int|null $max Максимальный уровень кислинки (0-10)
     * @return Builder Обновленный строитель запроса
     */
    private function filterByAcidity(Builder $query, ?int $min, ?int $max): Builder
    {
        if ($min !== null) {
            $query->where('acidity_percent', '>=', $min);
        }

        if ($max !== null) {
            $query->where('acidity_percent', '<=', $max);
        }

        return $query;
    }

    /**
     * Фильтр по товарам со скидкой
     * 
     * Показывает только товары, у которых есть старая цена (old_price)
     * 
     * @param Builder $query Строитель запроса
     * @return Builder Обновленный строитель запроса
     */
    private function filterByDiscount(Builder $query): Builder
    {
        return $query->whereNotNull('old_price')
                     ->whereColumn('old_price', '>', 'price');
    }

    /**
     * Фильтр по рекомендуемым товарам
     * 
     * Показывает только товары, отмеченные как рекомендуемые
     * 
     * @param Builder $query Строитель запроса
     * @return Builder Обновленный строитель запроса
     */
    private function filterByFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Применить сортировку к запросу
     * 
     * Доступные варианты сортировки:
     * - popular (популярные): по рейтингу и количеству отзывов
     * - newest (новинки): по дате создания (новые первые)
     * - price_asc (цена по возрастанию): от дешевых к дорогим
     * - price_desc (цена по убыванию): от дорогих к дешевым
     * - rating (рейтинг): по рейтингу (высокий первый)
     * - name (название): по алфавиту
     * 
     * @param Builder $query Строитель запроса
     * @param string $sortBy Тип сортировки
     * @return Builder Обновленный строитель запроса
     */
    private function applySorting(Builder $query, string $sortBy): Builder
    {
        return match ($sortBy) {
            // Популярные: сначала по рейтингу, потом по количеству отзывов
            'popular' => $query->orderBy('rating', 'desc')
                              ->orderBy('reviews_count', 'desc'),

            // Новинки: по дате создания (новые первые)
            'newest' => $query->orderBy('created_at', 'desc'),

            // Цена: от дешевых к дорогим
            'price_asc' => $query->orderBy('price', 'asc'),

            // Цена: от дорогих к дешевым
            'price_desc' => $query->orderBy('price', 'desc'),

            // По рейтингу (высокий первый)
            'rating' => $query->orderBy('rating', 'desc')
                             ->orderBy('reviews_count', 'desc'),

            // По названию (алфавитный порядок)
            'name' => $query->orderBy('name', 'asc'),

            // По умолчанию - популярные
            default => $query->orderBy('rating', 'desc')
                            ->orderBy('reviews_count', 'desc'),
        };
    }

    /**
     * Получить минимальную и максимальную цену товаров
     * 
     * Используется для отображения ползунка диапазона цен в фильтрах
     * 
     * @param int|null $categoryId Опционально: ID категории для фильтрации
     * @return array{min: float, max: float} Минимальная и максимальная цена
     */
    public function getPriceRange(?int $categoryId = null): array
    {
        $query = Product::query()->available();

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $minPrice = $query->min('price') ?? 0;
        $maxPrice = $query->max('price') ?? 0;

        return [
            'min' => round((float) $minPrice, 2),
            'max' => round((float) $maxPrice, 2),
        ];
    }

    /**
     * Получить доступные значения фильтров для категории
     * 
     * Возвращает все возможные значения для фильтров:
     * - Теги товаров в категории
     * - Диапазон цен
     * - Диапазон горчинки
     * - Диапазон кислинки
     * 
     * @param int|null $categoryId ID категории (null для всех товаров)
     * @return array Массив доступных значений фильтров
     */
    public function getAvailableFilters(?int $categoryId = null): array
    {
        $query = Product::query()->available();

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        // Получаем все теги товаров в категории
        $tags = \App\Models\Tag::whereHas('products', function (Builder $q) use ($categoryId) {
            $q->available();
            if ($categoryId) {
                $q->where('category_id', $categoryId);
            }
        })->get();

        // Получаем диапазоны значений
        $priceRange = $this->getPriceRange($categoryId);
        
        return [
            'tags' => $tags,
            'price_range' => $priceRange,
            'bitterness_range' => [
                'min' => (int) ($query->min('bitterness_percent') ?? 0),
                'max' => (int) ($query->max('bitterness_percent') ?? 10),
            ],
            'acidity_range' => [
                'min' => (int) ($query->min('acidity_percent') ?? 0),
                'max' => (int) ($query->max('acidity_percent') ?? 10),
            ],
        ];
    }

    /**
     * Получить количество товаров для каждого фильтра
     * 
     * Используется для отображения количества товаров рядом с фильтром
     * Например: "В наличии (25)", "4+ звезды (18)"
     * 
     * @param int|null $categoryId ID категории
     * @return array Массив с количеством товаров для каждого фильтра
     */
    public function getFilterCounts(?int $categoryId = null): array
    {
        $query = Product::query()->available();

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        return [
            'total' => (clone $query)->count(),
            'in_stock' => (clone $query)->where('stock', '>', 0)->count(),
            'on_sale' => (clone $query)->whereNotNull('old_price')
                                       ->whereColumn('old_price', '>', 'price')
                                       ->count(),
            'featured' => (clone $query)->where('is_featured', true)->count(),
            'rating_4_plus' => (clone $query)->where('rating', '>=', 4)->count(),
        ];
    }
}
