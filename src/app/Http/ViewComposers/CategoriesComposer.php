<?php

namespace App\Http\ViewComposers;

use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * View Composer для передачи списка категорий во все представления
 * 
 * Автоматически добавляет список активных категорий во все views,
 * где они нужны (обычно в меню навигации, футере, сайдбаре).
 * 
 * Передаваемые данные:
 * - $categories - коллекция главных категорий с подкатегориями
 * - $mainCategories - только главные категории (без родителя)
 */
class CategoriesComposer
{
    /**
     * Кеш категорий для оптимизации
     * 
     * Загружаем категории один раз за запрос и повторно используем
     * 
     * @var Collection|null
     */
    protected ?Collection $categories = null;

    /**
     * Связать данные с представлением
     * 
     * Этот метод вызывается автоматически перед рендерингом view.
     * Добавляет список категорий в переменные представления.
     *
     * @param View $view Объект представления
     * @return void
     */
    public function compose(View $view): void
    {
        // Если категории ещё не загружены, загружаем их
        if (is_null($this->categories)) {
            $this->loadCategories();
        }

        // Передаём данные в представление
        // Теперь в любом Blade-шаблоне доступны переменные:
        // {{ $categories }} - все главные категории с подкатегориями
        // {{ $mainCategories }} - только главные категории
        $view->with([
            'categories' => $this->categories,
            'mainCategories' => $this->categories, // Алиас для удобства
        ]);
    }

    /**
     * Загрузить категории из базы данных
     * 
     * Загружает только активные главные категории (без parent_id)
     * вместе с их активными подкатегориями.
     * Результат кешируется в свойстве $categories.
     *
     * @return void
     */
    protected function loadCategories(): void
    {
        // Загружаем только главные категории (parent_id = null)
        // с их активными дочерними категориями
        // Сортируем по полю sort_order для правильного порядка отображения
        $this->categories = Category::query()
            ->whereNull('parent_id')          // Только главные категории
            ->where('is_active', true)        // Только активные
            ->with([
                // Загружаем дочерние категории (для многоуровневого меню)
                'activeChildren' => function ($query) {
                    $query->orderBy('sort_order');
                }
            ])
            ->orderBy('sort_order')           // Сортировка по заданному порядку
            ->get();
    }
}
