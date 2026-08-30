{{-- Политика конфиденциальности --}}
@extends('layouts.app')

@section('title', 'Политика конфиденциальности — Coffee-Tea Shop')

@section('content')
    <div class="page">
        <div class="container">
            <nav class="breadcrumbs" aria-label="Хлебные крошки">
                <a href="{{ route('home') }}">Главная</a>
                <span class="breadcrumbs__sep">→</span>
                <span class="breadcrumbs__current">Политика конфиденциальности</span>
            </nav>

            <h1 class="page__title">Политика конфиденциальности</h1>

            <div class="page__content">
                <p>
                    Мы уважаем вашу конфиденциальность и обрабатываем персональные данные
                    (имя, email, телефон, адрес доставки) только для выполнения заказов
                    и информирования о статусе заказа.
                </p>
                <ul>
                    <li>Данные не передаются третьим лицам, кроме служб доставки;</li>
                    <li>Отписаться от рассылки можно в любой момент по ссылке в письме;</li>
                    <li>Данные хранятся до тех пор, пока действует ваш аккаунт.</li>
                </ul>
            </div>
        </div>
    </div>
@endsection
