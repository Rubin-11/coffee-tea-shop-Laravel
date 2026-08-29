{{-- Страница товара: галерея, цена, характеристики, вкладки, отзывы, похожие --}}
@extends('layouts.app')

@section('title', $product->name.' — Coffee-Tea Shop')
@section('meta_description', $product->description)

@php
    // Цепочка предков категории (от дальней к ближней) для хлебных крошек
    $categoryAncestors = $product->category
        ? $product->category->getAllAncestors()->reverse()
        : collect();

    $reviewsCount = $product->approvedReviews->count();
    $ratingValue = (int) round((float) ($product->rating ?? 0));
    $acidity = (int) ($product->acidity ?? 0);
    $bitterness = (int) ($product->bitterness ?? 0);

    // Склонение слова «отзыв»
    $reviewsLabel = 'отзывов';
    if ($reviewsCount % 10 === 1 && $reviewsCount % 100 !== 11) {
        $reviewsLabel = 'отзыв';
    } elseif ($reviewsCount % 10 >= 2 && $reviewsCount % 10 <= 4 && ($reviewsCount % 100 < 10 || $reviewsCount % 100 >= 20)) {
        $reviewsLabel = 'отзыва';
    }
@endphp

@section('content')
    <div class="product-page">
        <div class="container">
            {{-- Хлебные крошки --}}
            <nav class="breadcrumbs" aria-label="Хлебные крошки">
                <a href="{{ route('home') }}">Главная</a>
                <span class="breadcrumbs__sep">→</span>
                <a href="{{ route('home') }}#catalog">Каталог</a>
                @foreach ($categoryAncestors as $ancestor)
                    <span class="breadcrumbs__sep">→</span>
                    <a href="{{ route('categories.show', $ancestor->slug) }}">{{ $ancestor->name }}</a>
                @endforeach
                @if ($product->category)
                    <span class="breadcrumbs__sep">→</span>
                    <a href="{{ route('categories.show', $product->category->slug) }}">{{ $product->category->name }}</a>
                @endif
                <span class="breadcrumbs__sep">→</span>
                <span class="breadcrumbs__current">{{ $product->name }}</span>
            </nav>

            {{-- Верхняя часть: галерея + информация --}}
            <div class="product-page__top">
                {{-- Галерея --}}
                <div class="product-page__gallery">
                    @if ($product->images->isNotEmpty())
                        <div class="gallery">
                            <div class="gallery__main">
                                <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}">
                            </div>
                            @if ($product->images->count() > 1)
                                <div class="gallery__thumbs">
                                    @foreach ($product->images as $image)
                                        <img src="{{ $image->url }}" alt="{{ $image->alt_text ?: $product->name }}" loading="lazy">
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="gallery gallery--empty">
                            <span>Нет изображения</span>
                        </div>
                    @endif
                </div>

                {{-- Информация о товаре --}}
                <div class="product-page__info">
                    <h1 class="product-page__title">{{ $product->name }}</h1>

                    {{-- Рейтинг --}}
                    <div class="product-page__rating">
                        <span class="stars" aria-hidden="true">
                            @for ($i = 1; $i <= 5; $i++)
                                <span class="@if ($i <= $ratingValue) star--filled @endif">★</span>
                            @endfor
                        </span>
                        <span class="product-page__rating-value">{{ number_format((float) ($product->rating ?? 0), 1) }}</span>
                        <a href="#reviews" class="product-page__rating-count">{{ $reviewsCount }} {{ $reviewsLabel }}</a>
                    </div>

                    {{-- Цена + скидка --}}
                    <div class="product-page__price">
                        <span class="product-page__price-current">{{ number_format((float) $product->price, 0, ',', ' ') }} ₽</span>
                        @if ($product->hasDiscount())
                            <span class="product-page__price-old">{{ number_format((float) $product->old_price, 0, ',', ' ') }} ₽</span>
                            <span class="product-page__badge">Скидки −{{ $product->discount_percent }}%</span>
                        @endif
                    </div>

                    {{-- Вес (варианты, декоративно) --}}
                    <div class="weight-options">
                        <span class="weight-options__label">Вес:</span>
                        @foreach ($product->weight_options as $option)
                            <button type="button" class="weight-option @if ($option == $product->weight) weight-option--active @endif">{{ $option }} г</button>
                        @endforeach
                    </div>

                    {{-- Шкалы кислинки и горчинки --}}
                    <div class="product-page__scales">
                        <div class="scale">
                            <span class="scale__label">Кислинка</span>
                            <span class="scale__dots">
                                @for ($i = 1; $i <= 7; $i++)
                                    <span class="scale__dot @if ($i <= $acidity) scale__dot--active @endif"></span>
                                @endfor
                            </span>
                        </div>
                        <div class="scale">
                            <span class="scale__label">Горчинка</span>
                            <span class="scale__dots">
                                @for ($i = 1; $i <= 7; $i++)
                                    <span class="scale__dot @if ($i <= $bitterness) scale__dot--active @endif"></span>
                                @endfor
                            </span>
                        </div>
                    </div>

                    {{-- В корзину --}}
                    <form action="{{ route('cart.add') }}" method="POST" class="product-page__cart">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <input type="hidden" name="quantity" value="1">
                        <button type="submit" class="btn btn--accent product-page__buy">В корзину</button>
                    </form>

                    {{-- Теги --}}
                    @if ($product->tags->isNotEmpty())
                        <div class="product-tags">
                            @foreach ($product->tags as $tag)
                                <span class="tag-chip">{{ $tag->name }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Вкладки --}}
            <div class="tabs">
                <div class="tabs__nav">
                    <button type="button" class="tabs__btn is-active" data-tab="tab-description">Описание</button>
                    <button type="button" class="tabs__btn" data-tab="tab-specs">Характеристики</button>
                    <button type="button" class="tabs__btn" data-tab="tab-reviews">Отзывы ({{ $reviewsCount }})</button>
                </div>

                {{-- Описание --}}
                <div class="tab-pane is-active" id="tab-description">
                    @if ($product->long_description)
                        <p>{{ $product->long_description }}</p>
                    @elseif ($product->description)
                        <p>{{ $product->description }}</p>
                    @else
                        <p>Описание товара скоро появится.</p>
                    @endif
                </div>

                {{-- Характеристики --}}
                <div class="tab-pane" id="tab-specs">
                    <dl class="specs">
                        <div class="specs__row">
                            <dt>Артикул</dt>
                            <dd>{{ $product->sku }}</dd>
                        </div>
                        <div class="specs__row">
                            <dt>Вес</dt>
                            <dd>{{ $product->weight }} г</dd>
                        </div>
                        <div class="specs__row">
                            <dt>Категория</dt>
                            <dd>
                                @if ($product->category)
                                    <a href="{{ route('categories.show', $product->category->slug) }}">{{ $product->category->name }}</a>
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                        <div class="specs__row">
                            <dt>Наличие</dt>
                            <dd>{{ $product->inStock() ? 'В наличии' : 'Нет в наличии' }}</dd>
                        </div>
                        @if (! is_null($product->bitterness_percent))
                            <div class="specs__row">
                                <dt>Горчинка</dt>
                                <dd>{{ $product->bitterness_percent }}%</dd>
                            </div>
                        @endif
                        @if (! is_null($product->acidity_percent))
                            <div class="specs__row">
                                <dt>Кислинка</dt>
                                <dd>{{ $product->acidity_percent }}%</dd>
                            </div>
                        @endif
                        @if ($product->tags->isNotEmpty())
                            <div class="specs__row">
                                <dt>Теги</dt>
                                <dd>{{ $product->tags->pluck('name')->implode(', ') }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                {{-- Отзывы --}}
                <div class="tab-pane" id="tab-reviews">
                    <div class="reviews">
                        {{-- Сводка по рейтингу --}}
                        <div class="reviews__summary">
                            <div class="reviews__score">
                                <span class="reviews__score-value">{{ number_format((float) ($product->rating ?? 0), 1) }}</span>
                                <span class="stars" aria-hidden="true">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <span class="@if ($i <= $ratingValue) star--filled @endif">★</span>
                                    @endfor
                                </span>
                                <span class="reviews__score-count">{{ $reviewsCount }} {{ $reviewsLabel }}</span>
                            </div>

                            @if ($ratingDistribution->isNotEmpty())
                                <div class="rating-bars">
                                    @foreach ($ratingDistribution as $rating => $count)
                                        <div class="rating-bar">
                                            <span class="rating-bar__label">{{ $rating }} ★</span>
                                            <span class="rating-bar__track">
                                                <span class="rating-bar__fill" style="width: {{ $reviewsCount > 0 ? round($count / $reviewsCount * 100) : 0 }}%"></span>
                                            </span>
                                            <span class="rating-bar__count">{{ $count }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Список отзывов --}}
                        @if ($product->approvedReviews->isNotEmpty())
                            <div class="reviews__list">
                                @foreach ($product->approvedReviews as $review)
                                    <article class="review">
                                        <div class="review__header">
                                            <span class="review__author">{{ $review->user?->full_name ?? 'Покупатель' }}</span>
                                            @if ($review->is_verified_purchase)
                                                <span class="review__verified">✓ покупка подтверждена</span>
                                            @endif
                                        </div>
                                        <div class="review__rating">
                                            <span class="stars" aria-hidden="true">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    <span class="@if ($i <= $review->rating) star--filled @endif">★</span>
                                                @endfor
                                            </span>
                                            <span class="review__rating-text">{{ $review->rating_text }}</span>
                                        </div>
                                        @if ($review->comment)
                                            <p class="review__comment">{{ $review->comment }}</p>
                                        @endif
                                        @if ($review->pros)
                                            <div class="review__pros">
                                                <span class="review__pros-label">Достоинства:</span>
                                                <span>{{ $review->pros }}</span>
                                            </div>
                                        @endif
                                        @if ($review->cons)
                                            <div class="review__cons">
                                                <span class="review__cons-label">Недостатки:</span>
                                                <span>{{ $review->cons }}</span>
                                            </div>
                                        @endif
                                    </article>
                                @endforeach
                            </div>
                        @else
                            <p class="empty">Отзывов пока нет — станьте первым!</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Похожие товары --}}
            @if ($relatedProducts->isNotEmpty())
                <section class="section product-page__related">
                    <div class="section__head">
                        <h2 class="section__title">Похожие товары</h2>
                    </div>
                    <div class="products-grid">
                        @foreach ($relatedProducts as $related)
                            <x-product-card :product="$related" />
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var buttons = document.querySelectorAll('.tabs__btn');
            var panes = document.querySelectorAll('.tab-pane');

            buttons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var target = this.getAttribute('data-tab');

                    buttons.forEach(function (b) {
                        b.classList.toggle('is-active', b === btn);
                    });

                    panes.forEach(function (pane) {
                        pane.classList.toggle('is-active', pane.getAttribute('id') === target);
                    });
                });
            });
        });
    </script>
@endpush
