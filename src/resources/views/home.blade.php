{{-- Главная страница по макету Pixso --}}
@extends('layouts.app')

@section('title', 'Coffee-Tea Shop — свежеобжаренный кофе и премиальный чай')

@section('content')
    @php
        // Товары со скидкой — берём из рекомендованных те, у которых есть старая цена
        $discountedProducts = $featuredProducts->filter(fn ($product) => $product->hasDiscount());
    @endphp

    {{-- ============ 1. HERO ============ --}}
    <section class="hero">
        <div class="container hero__inner">
            <div class="hero__content">
                <h1 class="hero__title">Свежеобжаренный кофе</h1>
                <p class="hero__text">
                    Кофе Калининградской обжарки из разных стран произрастания с доставкой на дом. Мы обжариваем кофе каждые выходные.
                </p>
                <div class="hero__actions">
                    <a href="{{ route('products.index') }}" class="btn btn--accent">Посмотреть каталог</a>
                </div>
            </div>
            <div class="hero__media">
                <img src="{{ asset('images/pages/main/main_pages__container1__image/coffee-bean.png') }}" alt="" class="hero__beans" aria-hidden="true">
                <img src="{{ asset('images/pages/main/main_pages__container1__image/cappuccino-coffee.png') }}" alt="Свежеобжаренный кофе" class="hero__cup">
            </div>
        </div>
    </section>

    {{-- ============ 2. КАТАЛОГИ НАШЕЙ ПРОДУКЦИИ ============ --}}
    <section class="section">
        <div class="container">
            <div class="section__head">
                <h2 class="section__title">Каталоги нашей продукции</h2>
            </div>

            <div class="categories-grid">
                @foreach ($categories as $category)
                    <a href="{{ route('categories.show', $category->slug) }}" class="category-card">
                        <div class="category-card__image">
                            <img src="{{ $category->image_url }}" alt="{{ $category->name }}" loading="lazy">
                        </div>
                        <div class="category-card__body">
                            <h3 class="category-card__title">{{ $category->name }}</h3>
                            <span class="category-card__count">{{ $category->available_products_count }} товаров</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ 3. ТОВАРЫ СО СКИДКОЙ ============ --}}
    @if ($discountedProducts->isNotEmpty())
        <section class="section section--soft">
            <div class="container">
                <div class="section__head">
                    <h2 class="section__title">Товары со скидкой</h2>
                    <a href="{{ route('products.index', ['filter' => 'discount']) }}" class="section__link">Все товары</a>
                </div>

                <div class="products-grid">
                    @foreach ($discountedProducts as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ============ 4. ПОЧЕМУ СТОИТ РАБОТАТЬ ИМЕННО С НАМИ ============ --}}
    <section class="section">
        <div class="container">
            <div class="section__head">
                <h2 class="section__title">Почему стоит работать именно с нами?</h2>
            </div>

            <div class="features-grid">
                <div class="feature">
                    <span class="feature__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                    </span>
                    <h3 class="feature__title">Лучшие цены</h3>
                    <p class="feature__text">Прямые поставки с плантаций и собственная обжарка позволяют держать честные цены без лишних наценок.</p>
                </div>

                <div class="feature">
                    <span class="feature__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
                            <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
                            <line x1="6" y1="1" x2="6" y2="4"></line>
                            <line x1="10" y1="1" x2="10" y2="4"></line>
                            <line x1="14" y1="1" x2="14" y2="4"></line>
                        </svg>
                    </span>
                    <h3 class="feature__title">Всегда свежая обжарка</h3>
                    <p class="feature__text">Обжариваем кофе небольшими партиями сразу после заказа, чтобы вы получали максимально свежий продукт.</p>
                </div>

                <div class="feature">
                    <span class="feature__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                        </svg>
                    </span>
                    <h3 class="feature__title">Консультации 24/7</h3>
                    <p class="feature__text">Поможем подобрать сорт под ваш вкус и способ приготовления — в любое время дня и ночи.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ 5. КАК МЫ ОБЖАРИВАЕМ НАШ КОФЕ ============ --}}
    <section class="section section--soft">
        <div class="container roast">
            <div class="roast__image">
                <img src="{{ asset('images/coffee-beans-decor.png') }}" alt="Обжарка кофе">
            </div>
            <div>
                <h2 class="roast__title">Как мы обжариваем наш кофе</h2>
                <p class="roast__text">
                    Мы обжариваем кофе в Калининграде на современном ростере, небольшими партиями и под строгим контролем профиля обжарки.
                </p>
                <p class="roast__text">
                    Каждый сорт раскрывается по-своему: от светлой обжарки с яркой кислинкой до тёмной — с плотным телом и горчинкой. Благодаря этому кофе сохраняет аромат и свежесть в каждой чашке.
                </p>
            </div>
        </div>
    </section>

    {{-- ============ 6. НОВОСТИ КОМПАНИИ ============ --}}
    @if ($blogPosts->isNotEmpty())
        <section class="section">
            <div class="container">
                <div class="section__head">
                    <h2 class="section__title">Новости компании</h2>
                    <a href="{{ route('blog.index') }}" class="section__link">Читать все</a>
                </div>

                <div class="news-grid">
                    @foreach ($blogPosts as $post)
                        <article class="news-card">
                            <a href="{{ route('blog.show', $post->slug) }}" class="news-card__image">
                                @if ($post->image_url)
                                    <img src="{{ $post->image_url }}" alt="{{ $post->title }}" loading="lazy">
                                @endif
                            </a>
                            <div class="news-card__body">
                                <span class="news-card__date">{{ $post->published_at?->format('d.m.Y') }}</span>
                                <h3 class="news-card__title">{{ $post->title }}</h3>
                                <p class="news-card__excerpt">{{ $post->excerpt }}</p>
                                <a href="{{ route('blog.show', $post->slug) }}" class="news-card__more">Читать далее →</a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ============ 7. МЫ В INSTAGRAM ============ --}}
    <section class="section">
        <div class="container">
            <div class="section__head">
                <h2 class="section__title">Мы в Instagram</h2>
            </div>

            <div class="instagram-grid">
                @foreach (['coffee-beans-decor.png', 'hero-coffee.png', 'logo.png'] as $image)
                    <div class="instagram-grid__item">
                        <img src="{{ asset('images/' . $image) }}" alt="Coffee-Tea Shop в Instagram" loading="lazy">
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
