{{-- Доставка и оплата: способы, сроки, стоимость --}}
@extends('layouts.app')

@section('title', 'Доставка и оплата — Coffee-Tea Shop')

@section('content')
    <div class="page">
        <div class="container">
            <nav class="breadcrumbs" aria-label="Хлебные крошки">
                <a href="{{ route('home') }}">Главная</a>
                <span class="breadcrumbs__sep">→</span>
                <span class="breadcrumbs__current">Доставка и оплата</span>
            </nav>

            <h1 class="page__title">Доставка и оплата</h1>

            <div class="page__content">
                <h2>Доставка</h2>
                <ul>
                    <li><strong>Почта России</strong> — от 300 ₽, по всей России;</li>
                    <li><strong>СДЭК</strong> — от 390 ₽, курьером или в пункт выдачи;</li>
                    <li><strong>DPD</strong> — от 427 ₽;</li>
                    <li><strong>Самовывоз</strong> — из наших магазинов в Калининграде и Балашихе.</li>
                </ul>
                <p>Точную стоимость доставки можно рассчитать в корзине перед оформлением заказа.</p>

                <h2>Оплата</h2>
                <ul>
                    <li>Банковской картой онлайн;</li>
                    <li>Картой или наличными при получении;</li>
                    <li>По счёту для юридических лиц.</li>
                </ul>
            </div>
        </div>
    </div>
@endsection
