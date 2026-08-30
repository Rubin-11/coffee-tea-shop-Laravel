{{-- Гарантии и возврат: правила обмена и возврата товаров --}}
@extends('layouts.app')

@section('title', 'Гарантии и возврат — Coffee-Tea Shop')

@section('content')
    <div class="page">
        <div class="container">
            <nav class="breadcrumbs" aria-label="Хлебные крошки">
                <a href="{{ route('home') }}">Главная</a>
                <span class="breadcrumbs__sep">→</span>
                <span class="breadcrumbs__current">Гарантии и возврат</span>
            </nav>

            <h1 class="page__title">Гарантии и возврат</h1>

            <div class="page__content">
                <h2>Гарантия свежести</h2>
                <p>
                    Мы гарантируем, что кофе обжарен не более 30 дней назад на момент отправки,
                    а чай собран в текущем сезоне. Если вы получили товар ненадлежащего качества —
                    вернём деньги или заменим товар.
                </p>

                <h2>Возврат</h2>
                <ul>
                    <li>Товар надлежащего качества можно вернуть в течение 7 дней;</li>
                    <li>Для возврата напишите нам на <a href="mailto:Import@kldrefine.com">Import@kldrefine.com</a>;</li>
                    <li>Средства возвращаются в течение 3–5 рабочих дней.</li>
                </ul>
            </div>
        </div>
    </div>
@endsection
