{{-- Каталог товаров: фильтры, сортировка, пагинация --}}
@extends('layouts.app')

@section('title', 'Каталог товаров — Coffee-Tea Shop')

@section('content')
    <div class="catalog">
        <div class="container">
            <nav class="breadcrumbs" aria-label="Хлебные крошки">
                <a href="{{ route('home') }}">Главная</a>
                <span class="breadcrumbs__sep">→</span>
                <span class="breadcrumbs__current">Каталог товаров</span>
            </nav>

            <h1 class="catalog__title">Каталог товаров</h1>

            {{-- Фильтры --}}
            <div class="catalog-filters">
                {{-- Скидки --}}
                <a
                    href="{{ request('filter') === 'discount' ? request()->fullUrlWithQuery(['filter' => null, 'page' => null]) : request()->fullUrlWithQuery(['filter' => 'discount', 'page' => null]) }}"
                    class="filter-chip @if (request('filter') === 'discount') is-active @endif"
                >
                    Только со скидкой
                </a>

                {{-- Ценовой диапазон --}}
                <form action="{{ route('products.index') }}" method="GET" class="price-filter">
                    @if (request('sort')) <input type="hidden" name="sort" value="{{ request('sort') }}"> @endif
                    @if (request('filter')) <input type="hidden" name="filter" value="{{ request('filter') }}"> @endif
                    <input type="number" name="min_price" min="0" step="1" value="{{ request('min_price') }}" placeholder="Цена от, ₽">
                    <input type="number" name="max_price" min="0" step="1" value="{{ request('max_price') }}" placeholder="Цена до, ₽">
                    <button type="submit" class="btn btn--accent">Применить</button>
                </form>
            </div>

            {{-- Сортировка --}}
            <div class="catalog-toolbar">
                <span class="catalog-toolbar__label">Сортировка:</span>
                <a href="{{ request()->fullUrlWithQuery(['sort' => 'newest', 'page' => null]) }}" class="sort-link @if ($sort === 'newest') is-active @endif">Новинки</a>
                <a href="{{ request()->fullUrlWithQuery(['sort' => 'price_asc', 'page' => null]) }}" class="sort-link @if ($sort === 'price_asc') is-active @endif">Сначала дешевле</a>
                <a href="{{ request()->fullUrlWithQuery(['sort' => 'price_desc', 'page' => null]) }}" class="sort-link @if ($sort === 'price_desc') is-active @endif">Сначала дороже</a>
                <a href="{{ request()->fullUrlWithQuery(['sort' => 'rating', 'page' => null]) }}" class="sort-link @if ($sort === 'rating') is-active @endif">По рейтингу</a>
            </div>

            {{-- Товары --}}
            @if ($products->isNotEmpty())
                <div class="products-grid">
                    @foreach ($products as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>

                {{ $products->links('vendor.pagination.catalog') }}
            @else
                <p class="empty">По вашему запросу ничего не найдено. Попробуйте изменить фильтры.</p>
            @endif
        </div>
    </div>
@endsection
