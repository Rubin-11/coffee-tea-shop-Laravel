{{-- О компании: история, ценности, география --}}
@extends('layouts.app')

@section('title', 'О компании — Coffee-Tea Shop')

@section('content')
    <div class="page">
        <div class="container">
            <nav class="breadcrumbs" aria-label="Хлебные крошки">
                <a href="{{ route('home') }}">Главная</a>
                <span class="breadcrumbs__sep">→</span>
                <span class="breadcrumbs__current">О компании</span>
            </nav>

            <h1 class="page__title">О компании</h1>

            <div class="page__content">
                <p>
                    Coffee-Tea Shop — магазин свежеобжаренного кофе и премиального чая.
                    Мы работаем напрямую с плантациями и обжариваем кофе небольшими партиями,
                    чтобы к вам попадал максимально свежий продукт.
                </p>

                <h2>Почему стоит работать именно с нами?</h2>
                <ul>
                    <li><strong>Лучшие цены</strong> — работаем без посредников;</li>
                    <li><strong>Всегда свежая обжарка</strong> — обжариваем под заказ;</li>
                    <li><strong>Консультации 24/7</strong> — поможем выбрать кофе и чай под ваш вкус.</li>
                </ul>

                <h2>География</h2>
                <p>
                    Наше производство и обжарка находятся в Калининграде,
                    склад и доставка по России — в Балашихе (Московская область).
                </p>
            </div>
        </div>
    </div>
@endsection
