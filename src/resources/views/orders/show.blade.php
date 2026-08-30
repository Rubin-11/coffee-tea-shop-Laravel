{{-- Детальная страница заказа: товары, доставка, оплата, статус --}}
@extends('layouts.app')

@section('title', 'Заказ № ' . $order->order_number . ' — Coffee-Tea Shop')

@section('content')
    <div class="order-page">
        <div class="container">
            <nav class="breadcrumbs" aria-label="Хлебные крошки">
                <a href="{{ route('home') }}">Главная</a>
                <span class="breadcrumbs__sep">→</span>
                <a href="{{ route('profile.index') }}">Личный кабинет</a>
                <span class="breadcrumbs__sep">→</span>
                <a href="{{ route('orders.index') }}">Заказы</a>
                <span class="breadcrumbs__sep">→</span>
                <span class="breadcrumbs__current">№ {{ $order->order_number }}</span>
            </nav>

            <h1 class="order-page__title">Заказ № {{ $order->order_number }}</h1>

            {{-- Статусы --}}
            <div class="order-page__statuses">
                <span class="order-page__badge">Статус: {{ $order->status_text }}</span>
                <span class="order-page__badge order-page__badge--payment">Оплата: {{ $order->payment_status_text }}</span>
            </div>

            <div class="order-page__layout">
                {{-- Состав заказа --}}
                <div class="order-page__items">
                    <h2 class="order-page__section-title">Состав заказа</h2>

                    @foreach ($order->items as $item)
                        <div class="order-item">
                            <div class="order-item__info">
                                <span class="order-item__name">{{ $item->product_name }}</span>
                                <span class="order-item__qty">{{ $item->quantity }} × {{ number_format((float) $item->price, 0, ',', ' ') }} ₽</span>
                            </div>
                            <span class="order-item__total">{{ number_format((float) $item->total, 0, ',', ' ') }} ₽</span>
                        </div>
                    @endforeach
                </div>

                {{-- Сводка --}}
                <aside class="order-page__summary">
                    <h2 class="order-page__section-title">Сводка</h2>

                    <div class="order-page__row">
                        <span>Товары</span>
                        <strong>{{ number_format((float) $order->subtotal, 0, ',', ' ') }} ₽</strong>
                    </div>
                    @if ((float) $order->discount > 0)
                        <div class="order-page__row">
                            <span>Скидка</span>
                            <strong>−{{ number_format((float) $order->discount, 0, ',', ' ') }} ₽</strong>
                        </div>
                    @endif
                    <div class="order-page__row">
                        <span>Доставка</span>
                        <strong>{{ number_format((float) $order->delivery_cost, 0, ',', ' ') }} ₽</strong>
                    </div>
                    <div class="order-page__row order-page__row--total">
                        <span>Итого</span>
                        <strong>{{ number_format((float) $order->total, 0, ',', ' ') }} ₽</strong>
                    </div>

                    <div class="order-page__row">
                        <span>Доставка</span>
                        <strong>{{ $order->delivery_method_text }}</strong>
                    </div>
                    <div class="order-page__row">
                        <span>Оплата</span>
                        <strong>{{ $order->payment_method_text }}</strong>
                    </div>
                    @if ($order->delivery_address)
                        <div class="order-page__row">
                            <span>Адрес</span>
                            <strong>{{ $order->delivery_address }}</strong>
                        </div>
                    @endif

                    {{-- Действия --}}
                    <div class="order-page__actions">
                        <form action="{{ route('orders.reorder', $order->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn--accent btn--wide">Повторить заказ</button>
                        </form>

                        @if ($order->canBeCancelled())
                            <form action="{{ route('orders.cancel', $order->id) }}" method="POST"
                                  onsubmit="return confirm('Отменить заказ?')">
                                @csrf
                                <button type="submit" class="btn btn--outline btn--wide">Отменить заказ</button>
                            </form>
                        @endif
                    </div>
                </aside>
            </div>
        </div>
    </div>
@endsection
