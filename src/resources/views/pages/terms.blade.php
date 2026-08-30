{{-- Пользовательское соглашение --}}
@extends('layouts.app')

@section('title', 'Пользовательское соглашение — Coffee-Tea Shop')

@section('content')
    <div class="page">
        <div class="container">
            <nav class="breadcrumbs" aria-label="Хлебные крошки">
                <a href="{{ route('home') }}">Главная</a>
                <span class="breadcrumbs__sep">→</span>
                <span class="breadcrumbs__current">Пользовательское соглашение</span>
            </nav>

            <h1 class="page__title">Пользовательское соглашение</h1>

            <div class="page__content">
                <p>
                    Используя сайт Coffee-Tea Shop, вы соглашаетесь с условиями настоящего соглашения.
                </p>
                <ul>
                    <li>Оформляя заказ, вы подтверждаете достоверность указанных контактных данных;</li>
                    <li>Цены на сайте указаны в рублях и могут быть изменены без предварительного уведомления;</li>
                    <li>Администрация вправе отказать в обслуживании при нарушении правил.</li>
                </ul>
            </div>
        </div>
    </div>
@endsection
