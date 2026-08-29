{{-- Страница корзины: позиции, итого, доставка, промокод, действия --}}
@extends('layouts.app')

@section('title', 'Корзина — Coffee-Tea Shop')

@section('content')
    <div class="cart">
        <div class="container">
            <nav class="breadcrumbs" aria-label="Хлебные крошки">
                <a href="{{ route('home') }}">Главная</a>
                <span class="breadcrumbs__sep">→</span>
                <span class="breadcrumbs__current">Корзина</span>
            </nav>

            <h1 class="cart__title">Корзина</h1>

            @if ($cartItems->isNotEmpty())
                {{-- Предупреждение о недоступных товарах --}}
                @if (! $availability['available'])
                    <div class="cart-warning">
                        <strong>Некоторые товары недоступны для заказа:</strong>
                        <ul>
                            @foreach ($availability['unavailable_items'] as $unavailable)
                                <li>
                                    «{{ $unavailable['product_name'] }}» —
                                    @if (! $unavailable['is_available'])
                                        товар снят с продажи
                                    @else
                                        доступно {{ $unavailable['available_quantity'] }} шт. (в корзине {{ $unavailable['requested_quantity'] }})
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="cart__layout">
                    {{-- Список позиций --}}
                    <div class="cart__items">
                        <div class="cart__items-head">
                            <span>Товар</span>
                            <span>Цена</span>
                            <span>Количество</span>
                            <span>Сумма</span>
                            <span></span>
                        </div>

                        @foreach ($cartItems as $item)
                            <div class="cart-item">
                                <div class="cart-item__product">
                                    <a href="{{ $item->product ? route('products.show', $item->product->slug) : '#' }}" class="cart-item__image">
                                        @if ($item->product)
                                            <img src="{{ $item->product->primary_image_url }}" alt="{{ $item->product->name }}" loading="lazy">
                                        @endif
                                    </a>
                                    @if ($item->product)
                                        <a href="{{ route('products.show', $item->product->slug) }}" class="cart-item__title">{{ $item->product->name }}</a>
                                    @else
                                        <span class="cart-item__title">Товар удалён</span>
                                    @endif
                                </div>

                                <div class="cart-item__price">
                                    {{ number_format((float) $item->price, 0, ',', ' ') }} ₽
                                </div>

                                <div class="cart-item__qty">
                                    <form action="{{ route('cart.update', $item->id) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="quantity" value="{{ $item->quantity - 1 }}">
                                        <button type="submit" class="qty-btn" @if ($item->quantity <= 1) disabled @endif aria-label="Уменьшить количество">−</button>
                                    </form>

                                    <span class="cart-item__qty-value">{{ $item->quantity }}</span>

                                    <form action="{{ route('cart.update', $item->id) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="quantity" value="{{ $item->quantity + 1 }}">
                                        <button type="submit" class="qty-btn" aria-label="Увеличить количество">+</button>
                                    </form>
                                </div>

                                <div class="cart-item__subtotal">
                                    {{ number_format($item->getSubtotal(), 0, ',', ' ') }} ₽
                                </div>

                                <form action="{{ route('cart.remove', $item->id) }}" method="POST" class="cart-item__remove">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" aria-label="Удалить товар" title="Удалить">✕</button>
                                </form>
                            </div>
                        @endforeach

                        <div class="cart__items-foot">
                            <form action="{{ route('cart.clear') }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn--outline cart__clear">Удалить все</button>
                            </form>
                        </div>
                    </div>

                    {{-- Итоговая панель --}}
                    <aside class="cart__summary">
                        <div class="cart-summary">
                            <h2 class="cart-summary__title">Ваш заказ</h2>

                            <div class="cart-summary__row">
                                <span>Товары ({{ $itemsCount }})</span>
                                <span>{{ number_format((float) $total, 0, ',', ' ') }} ₽</span>
                            </div>

                            <div class="cart-summary__total">
                                <span>Итого</span>
                                <span class="cart-summary__total-value">{{ number_format((float) $total, 0, ',', ' ') }} ₽</span>
                            </div>

                            {{-- Промокод (декоративно) --}}
                            <div class="promo">
                                <input type="text" class="promo__input" placeholder="Ввести промокод">
                                <button type="button" class="btn btn--outline promo__btn">Применить</button>
                            </div>

                            {{-- Доставка (декоративно) --}}
                            <div class="delivery">
                                <div class="delivery__title">Способ доставки</div>
                                <label class="delivery__option">
                                    <input type="radio" name="delivery" checked>
                                    <span>Почта России — 300 ₽</span>
                                </label>
                                <label class="delivery__option">
                                    <input type="radio" name="delivery">
                                    <span>СДЭК — до двери — 390 ₽</span>
                                </label>
                                <label class="delivery__option">
                                    <input type="radio" name="delivery">
                                    <span>DPD — курьер — 427 ₽</span>
                                </label>
                                <button type="button" class="btn btn--outline delivery__calc">Рассчитать доставку</button>
                            </div>

                            <a href="{{ route('checkout.index') }}" class="btn btn--accent cart-summary__checkout">Оплатить заказ</a>
                        </div>
                    </aside>
                </div>
            @else
                <div class="cart-empty">
                    <h2 class="cart-empty__title">Корзина пуста</h2>
                    <p class="cart-empty__text">Добавьте свежеобжаренный кофе или чай — и возвращайтесь.</p>
                    <a href="{{ route('home') }}#catalog" class="btn btn--accent">Перейти в каталог</a>
                </div>
            @endif
        </div>
    </div>
@endsection
