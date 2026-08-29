{{--
    Карточка товара (переиспользуемый компонент).
    Используется класс-компонент App\View\Components\ProductCard,
    поэтому здесь доступен объект $product.

    Отображаем только две шкалы (Кислинка / Горчинка) — в БД нет насыщенности.
--}}
@php
    $ratingValue = (int) round((float) ($product->rating ?? 0));
    $acidity = (int) ($product->acidity ?? 0);
    $bitterness = (int) ($product->bitterness ?? 0);
    $reviewsCount = (int) ($product->reviews_count ?? 0);

    // Склонение слова «отзыв»
    $reviewsLabel = 'отзывов';
    if ($reviewsCount % 10 === 1 && $reviewsCount % 100 !== 11) {
        $reviewsLabel = 'отзыв';
    } elseif ($reviewsCount % 10 >= 2 && $reviewsCount % 10 <= 4 && ($reviewsCount % 100 < 10 || $reviewsCount % 100 >= 20)) {
        $reviewsLabel = 'отзыва';
    }
@endphp

<article class="product-card">
    <a href="{{ route('products.show', $product->slug) }}" class="product-card__image">
        @if ($product->hasDiscount())
            <span class="product-card__badge">Скидки −{{ $product->discount_percent }}%</span>
        @endif
        <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" loading="lazy">
    </a>

    <div class="product-card__body">
        <h3 class="product-card__title">
            <a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a>
        </h3>

        <p class="product-card__desc">{{ $product->description }}</p>

        <div class="product-card__rating">
            <span class="stars" aria-hidden="true">
                @for ($i = 1; $i <= 5; $i++)
                    <span class="@if ($i <= $ratingValue) star--filled @endif">★</span>
                @endfor
            </span>
            <span class="product-card__rating-value">{{ number_format((float) ($product->rating ?? 0), 1) }}</span>
            <span class="product-card__rating-count">({{ $reviewsCount }} {{ $reviewsLabel }})</span>
        </div>

        <div class="product-card__scales">
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

        <div class="product-card__weight">{{ $product->weight }} г.</div>

        <div class="product-card__footer">
            <div class="product-card__price">
                <span class="product-card__price-current">{{ number_format((float) $product->price, 0, ',', ' ') }} ₽</span>
                @if ($product->hasDiscount())
                    <span class="product-card__price-old">{{ number_format((float) $product->old_price, 0, ',', ' ') }} ₽</span>
                @endif
            </div>

            <form action="{{ route('cart.add') }}" method="POST">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="quantity" value="1">
                <button type="submit" class="btn btn--accent product-card__buy">В корзину</button>
            </form>
        </div>
    </div>
</article>
