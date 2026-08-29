{{-- Страница категории: товары + подкатегории + сортировка --}}
@extends('layouts.app')

@section('title', $category->name.' — Coffee-Tea Shop')
@section('meta_description', $category->description)

@section('content')
    <div class="catalog">
        <div class="container">
            <nav class="breadcrumbs" aria-label="Хлебные крошки">
                <a href="{{ route('home') }}">Главная</a>
                <span class="breadcrumbs__sep">→</span>
                <a href="{{ route('home') }}#catalog">Каталог</a>
                @foreach ($breadcrumbs as $crumb)
                    <span class="breadcrumbs__sep">→</span>
                    @if ($crumb['url'])
                        <a href="{{ $crumb['url'] }}">{{ $crumb['name'] }}</a>
                    @else
                        <span class="breadcrumbs__current">{{ $crumb['name'] }}</span>
                    @endif
                @endforeach
            </nav>

            <div class="category-page__header">
                <h1 class="catalog__title">{{ $category->name }}</h1>
                @if ($category->description)
                    <p class="category-page__desc">{{ $category->description }}</p>
                @endif
            </div>

            {{-- Подкатегории --}}
            @if ($category->activeChildren->isNotEmpty())
                <div class="subcategories" style="margin-bottom: 24px;">
                    @foreach ($category->activeChildren as $child)
                        <a href="{{ route('categories.show', $child->slug) }}" class="subcategory-pill">
                            {{ $child->name }}
                            <span class="subcategory-pill__count">{{ $child->available_products_count }}</span>
                        </a>
                    @endforeach
                </div>
            @endif

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
            @elseif ($category->activeChildren->isNotEmpty())
                <p class="empty">Выберите подкатегорию, чтобы посмотреть товары.</p>
            @else
                <p class="empty">В этой категории пока нет доступных товаров.</p>
            @endif
        </div>
    </div>
@endsection
