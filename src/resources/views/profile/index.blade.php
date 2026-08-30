{{-- Личный кабинет: сводка по пользователю и последние заказы --}}
@extends('layouts.app')

@section('title', 'Личный кабинет — Coffee-Tea Shop')

@section('content')
    <div class="profile">
        <div class="container">
            <nav class="breadcrumbs" aria-label="Хлебные крошки">
                <a href="{{ route('home') }}">Главная</a>
                <span class="breadcrumbs__sep">→</span>
                <span class="breadcrumbs__current">Личный кабинет</span>
            </nav>

            <h1 class="profile__title">Личный кабинет</h1>

            <div class="profile__layout">
                {{-- Карточка пользователя --}}
                <aside class="profile__side">
                    <div class="profile__card">
                        <span class="profile__avatar">{{ mb_strtoupper(mb_substr(auth()->user()->first_name, 0, 1)) }}{{ mb_strtoupper(mb_substr(auth()->user()->last_name, 0, 1)) }}</span>
                        <p class="profile__name">{{ auth()->user()->full_name }}</p>
                        <p class="profile__email">{{ auth()->user()->email }}</p>

                        <form action="{{ route('auth.logout') }}" method="POST" class="profile__logout">
                            @csrf
                            <button type="submit" class="btn btn--outline btn--wide">Выйти</button>
                        </form>
                    </div>
                </aside>

                {{-- Последние заказы --}}
                <div class="profile__main">
                    <h2 class="profile__section-title">Мои заказы</h2>

                    @if ($orders->isNotEmpty())
                        <div class="orders-list">
                            @foreach ($orders as $order)
                                <a href="{{ route('orders.show', $order->id) }}" class="order-row">
                                    <span class="order-row__number">№ {{ $order->order_number }}</span>
                                    <span class="order-row__date">{{ $order->created_at->format('d.m.Y') }}</span>
                                    <span class="order-row__status">{{ $order->status_text }}</span>
                                    <span class="order-row__total">{{ number_format((float) $order->total, 0, ',', ' ') }} ₽</span>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="empty">У вас пока нет заказов.</p>
                    @endif

                    <div class="profile__actions">
                        <a href="{{ route('orders.index') }}" class="btn btn--accent">Все заказы</a>
                        <a href="{{ route('home') }}#catalog" class="btn btn--outline">Перейти в каталог</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
