{{-- Страница успешного оформления заказа --}}
@extends('layouts.app')

@section('title', 'Заказ оформлен — Coffee-Tea Shop')

@section('content')
    <div class="checkout-success">
        <div class="container">
            <div class="checkout-success__card">
                <span class="checkout-success__icon">✓</span>
                <h1 class="checkout-success__title">Заказ оформлен!</h1>
                <p class="checkout-success__number">Заказ № {{ $order->order_number }}</p>

                <div class="checkout-success__details">
                    <div class="checkout-success__row">
                        <span>Сумма заказа</span>
                        <strong>{{ number_format((float) $order->total, 0, ',', ' ') }} ₽</strong>
                    </div>
                    <div class="checkout-success__row">
                        <span>Способ доставки</span>
                        <strong>{{ $order->delivery_method_text }}</strong>
                    </div>
                    <div class="checkout-success__row">
                        <span>Способ оплаты</span>
                        <strong>{{ $order->payment_method_text }}</strong>
                    </div>
                    <div class="checkout-success__row">
                        <span>Статус заказа</span>
                        <strong>{{ $order->status_text }}</strong>
                    </div>
                    @if ($order->delivery_address)
                        <div class="checkout-success__row">
                            <span>Адрес доставки</span>
                            <strong>{{ $order->delivery_address }}</strong>
                        </div>
                    @endif
                </div>

                <div class="checkout-success__actions">
                    <a href="{{ route('home') }}#catalog" class="btn btn--accent">Продолжить покупки</a>
                    @auth
                        <a href="{{ route('profile.index') }}" class="btn btn--outline">В личный кабинет</a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
@endsection
