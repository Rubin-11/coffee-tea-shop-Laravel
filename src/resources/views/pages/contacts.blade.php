{{-- Страница контактов: телефоны, email, адреса, карта магазинов --}}
@extends('layouts.app')

@section('title', 'Контакты — Coffee-Tea Shop')

@section('content')
    <div class="page">
        <div class="container">
            <nav class="breadcrumbs" aria-label="Хлебные крошки">
                <a href="{{ route('home') }}">Главная</a>
                <span class="breadcrumbs__sep">→</span>
                <span class="breadcrumbs__current">Контакты</span>
            </nav>

            <h1 class="page__title">Контакты</h1>

            <div class="contacts">
                {{-- Левая колонка: связь и адреса --}}
                <div class="contacts__info">
                    <section class="contacts__section">
                        <h2 class="contacts__section-title">Связаться с нами</h2>
                        <ul class="contacts__list">
                            <li class="contacts__item">
                                <span class="contacts__label">Телефон</span>
                                <a href="tel:+74012375343" class="contacts__value">+7 (401) 237 53 43</a>
                            </li>
                            <li class="contacts__item">
                                <span class="contacts__label">Email</span>
                                <a href="mailto:Import@kldrefine.com" class="contacts__value">Import@kldrefine.com</a>
                            </li>
                        </ul>
                    </section>

                    <section class="contacts__section">
                        <h2 class="contacts__section-title">Юридический адрес</h2>
                        <p class="contacts__text">Калининградская обл., Гурьевский р-н, пос. Васильково</p>
                    </section>

                    <section class="contacts__section">
                        <h2 class="contacts__section-title">Адрес склада</h2>
                        <p class="contacts__text">Балашиха, Шоссе энтузиастов 1</p>
                    </section>
                </div>

                {{-- Правая колонка: наши магазины + карта --}}
                <div class="contacts__map-side">
                    <section class="contacts__section">
                        <h2 class="contacts__section-title">Наши магазины</h2>
                        <p class="contacts__text">
                            У нас два магазина: в Калининграде (склад-магазин) и в Балашихе.
                            Точные адреса точек — на карте ниже.
                        </p>
                    </section>

                    <div class="contacts__map">
                        {{-- Карта-заглушка: если нет API-ключа, показываем блок с адресами --}}
                        <iframe
                            src="https://yandex.ru/map-widget/v1/?text=Балашиха%2C%20Шоссе%20энтузиастов%201&z=12"
                            width="100%"
                            height="380"
                            style="border: 0; border-radius: 16px;"
                            allowfullscreen
                            loading="lazy"
                            title="Карта магазинов Coffee-Tea Shop"
                        ></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
