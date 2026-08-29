{{-- Страница «Каталог нашей продукции»: все главные категории --}}
@extends('layouts.app')

@section('title', 'Каталог нашей продукции — Coffee-Tea Shop')

@section('content')
    <div class="catalog">
        <div class="container">
            <nav class="breadcrumbs" aria-label="Хлебные крошки">
                <a href="{{ route('home') }}">Главная</a>
                <span class="breadcrumbs__sep">→</span>
                <span class="breadcrumbs__current">Каталог</span>
            </nav>

            <h1 class="catalog__title">Каталог нашей продукции</h1>

            <div class="categories-grid">
                @foreach ($categories as $category)
                    <div class="category-card category-card--list">
                        <a href="{{ route('categories.show', $category->slug) }}" class="category-card__image">
                            <img src="{{ $category->image_url }}" alt="{{ $category->name }}" loading="lazy">
                        </a>

                        <div class="category-card__body">
                            <h2 class="category-card__title">
                                <a href="{{ route('categories.show', $category->slug) }}">{{ $category->name }}</a>
                            </h2>
                            <span class="category-card__count">{{ $category->available_products_count }} товаров</span>

                            @if ($category->activeChildren->isNotEmpty())
                                <ul class="subcategories">
                                    @foreach ($category->activeChildren as $child)
                                        <li>
                                            <a href="{{ route('categories.show', $child->slug) }}">{{ $child->name }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
