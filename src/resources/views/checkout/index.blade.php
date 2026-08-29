{{-- Оформление заказа: контактные данные, адрес, доставка, оплата + сводка --}}
@extends('layouts.app')

@section('title', 'Оформление заказа — Coffee-Tea Shop')

@php
    $subtotal = $cartItems->sum(fn ($item) => $item->getSubtotal());
    $hasAddresses = $addresses->isNotEmpty();
@endphp

@section('content')
    <div class="checkout">
        <div class="container">
            <nav class="breadcrumbs" aria-label="Хлебные крошки">
                <a href="{{ route('home') }}">Главная</a>
                <span class="breadcrumbs__sep">→</span>
                <a href="{{ route('cart.index') }}">Корзина</a>
                <span class="breadcrumbs__sep">→</span>
                <span class="breadcrumbs__current">Оформление</span>
            </nav>

            <h1 class="checkout__title">Оформление заказа</h1>

            <form action="{{ route('checkout.store') }}" method="POST" class="checkout__layout">
                @csrf

                {{-- Левая колонка: форма --}}
                <div class="checkout__form">
                    {{-- Контактные данные --}}
                    <section class="checkout__section">
                        <h2 class="checkout__section-title">Контактные данные</h2>

                        <div class="form-field">
                            <label class="form-label" for="name">Имя *</label>
                            <input type="text" name="name" id="name" class="form-input" value="{{ old('name') }}" required>
                            @error('name') <span class="form-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="email">Email *</label>
                            <input type="email" name="email" id="email" class="form-input" value="{{ old('email') }}" required>
                            @error('email') <span class="form-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="phone">Телефон *</label>
                            <input type="tel" name="phone" id="phone" class="form-input" value="{{ old('phone') }}" placeholder="+7 (___) ___-__-__" required>
                            @error('phone') <span class="form-error">{{ $message }}</span> @enderror
                        </div>
                    </section>

                    {{-- Адрес доставки --}}
                    <section class="checkout__section">
                        <h2 class="checkout__section-title">Адрес доставки</h2>

                        @if ($hasAddresses)
                            <div class="checkout__radio-list">
                                @foreach ($addresses as $address)
                                    <label class="radio-card">
                                        <input type="radio" name="address_id" value="{{ $address->id }}" class="js-address-radio" {{ $loop->first ? 'checked' : '' }}>
                                        <span>
                                            <strong>{{ $address->name }}</strong>
                                            <span class="radio-card__desc">{{ $address->getFullAddress() }}</span>
                                        </span>
                                    </label>
                                @endforeach
                                <label class="radio-card">
                                    <input type="radio" name="address_id" value="" class="js-address-radio js-new-address">
                                    <span><strong>Новый адрес</strong></span>
                                </label>
                            </div>
                        @endif

                        <div class="checkout__new-address @if ($hasAddresses) is-hidden @endif" id="new-address-block">
                            <div class="checkout__row">
                                <div class="form-field">
                                    <label class="form-label" for="city">Город *</label>
                                    <input type="text" name="new_address[city]" id="city" class="form-input" value="{{ old('new_address.city') }}">
                                    @error('new_address.city') <span class="form-error">{{ $message }}</span> @enderror
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="postal_code">Индекс *</label>
                                    <input type="text" name="new_address[postal_code]" id="postal_code" class="form-input" value="{{ old('new_address.postal_code') }}">
                                    @error('new_address.postal_code') <span class="form-error">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="checkout__row">
                                <div class="form-field">
                                    <label class="form-label" for="street">Улица *</label>
                                    <input type="text" name="new_address[street]" id="street" class="form-input" value="{{ old('new_address.street') }}">
                                    @error('new_address.street') <span class="form-error">{{ $message }}</span> @enderror
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="house">Дом *</label>
                                    <input type="text" name="new_address[house]" id="house" class="form-input" value="{{ old('new_address.house') }}">
                                    @error('new_address.house') <span class="form-error">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="checkout__row">
                                <div class="form-field">
                                    <label class="form-label" for="apartment">Квартира</label>
                                    <input type="text" name="new_address[apartment]" id="apartment" class="form-input" value="{{ old('new_address.apartment') }}">
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="entrance">Подъезд</label>
                                    <input type="text" name="new_address[entrance]" id="entrance" class="form-input" value="{{ old('new_address.entrance') }}">
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="floor">Этаж</label>
                                    <input type="number" name="new_address[floor]" id="floor" class="form-input" value="{{ old('new_address.floor') }}">
                                </div>
                            </div>

                            @error('new_address') <span class="form-error">{{ $message }}</span> @enderror
                        </div>
                    </section>

                    {{-- Доставка --}}
                    <section class="checkout__section">
                        <h2 class="checkout__section-title">Способ доставки</h2>

                        <div class="checkout__radio-list">
                            @foreach ($deliveryMethods as $key => $method)
                                @if ($method['available'])
                                    <label class="radio-card">
                                        <input type="radio" name="delivery_method" value="{{ $key }}" {{ $loop->first ? 'checked' : '' }}>
                                        <span>
                                            <strong>{{ $method['name'] }}</strong>
                                            <span class="radio-card__desc">{{ $method['description'] }}</span>
                                            <span class="radio-card__cost">
                                                @php $deliveryCost = (float) ($costEstimates[$key]['delivery_cost'] ?? 0); @endphp
                                                {{ $deliveryCost > 0 ? number_format($deliveryCost, 0, ',', ' ').' ₽' : 'Бесплатно' }}
                                            </span>
                                        </span>
                                    </label>
                                @endif
                            @endforeach
                        </div>
                        @error('delivery_method') <span class="form-error">{{ $message }}</span> @enderror

                        <div class="checkout__row checkout__delivery-extra">
                            <div class="form-field">
                                <label class="form-label" for="delivery_date">Дата доставки</label>
                                <input type="date" name="delivery_date" id="delivery_date" class="form-input" value="{{ old('delivery_date') }}" min="{{ now()->addDay()->toDateString() }}">
                                @error('delivery_date') <span class="form-error">{{ $message }}</span> @enderror
                            </div>
                            <div class="form-field">
                                <label class="form-label" for="delivery_time">Время доставки</label>
                                <input type="time" name="delivery_time" id="delivery_time" class="form-input" value="{{ old('delivery_time') }}">
                                @error('delivery_time') <span class="form-error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </section>

                    {{-- Оплата --}}
                    <section class="checkout__section">
                        <h2 class="checkout__section-title">Способ оплаты</h2>

                        <div class="checkout__radio-list">
                            @foreach ($paymentMethods as $key => $method)
                                @if ($method['available'])
                                    <label class="radio-card">
                                        <input type="radio" name="payment_method" value="{{ $key }}" {{ $loop->first ? 'checked' : '' }}>
                                        <span>
                                            <strong>{{ $method['name'] }}</strong>
                                            <span class="radio-card__desc">{{ $method['description'] }}</span>
                                        </span>
                                    </label>
                                @endif
                            @endforeach
                        </div>
                        @error('payment_method') <span class="form-error">{{ $message }}</span> @enderror
                    </section>

                    {{-- Комментарий + промокод --}}
                    <section class="checkout__section">
                        <h2 class="checkout__section-title">Дополнительно</h2>

                        <div class="form-field">
                            <label class="form-label" for="comment">Комментарий к заказу</label>
                            <textarea name="comment" id="comment" class="form-textarea" rows="3" placeholder="Например: позвонить за час до доставки">{{ old('comment') }}</textarea>
                            @error('comment') <span class="form-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="promocode">Промокод</label>
                            <input type="text" name="promocode" id="promocode" class="form-input" value="{{ old('promocode') }}" placeholder="Введите промокод">
                            @error('promocode') <span class="form-error">{{ $message }}</span> @enderror
                        </div>
                    </section>
                </div>

                {{-- Правая колонка: сводка --}}
                <aside class="checkout__summary">
                    <div class="checkout-summary">
                        <h2 class="checkout-summary__title">Ваш заказ</h2>

                        <div class="checkout-summary__items">
                            @foreach ($cartItems as $item)
                                <div class="checkout-summary__item">
                                    <span class="checkout-summary__image">
                                        @if ($item->product)
                                            <img src="{{ $item->product->primary_image_url }}" alt="{{ $item->product->name }}" loading="lazy">
                                        @endif
                                    </span>
                                    <span class="checkout-summary__info">
                                        <span class="checkout-summary__name">{{ $item->product?->name ?? 'Товар' }}</span>
                                        <span class="checkout-summary__meta">{{ $item->quantity }} × {{ number_format((float) $item->price, 0, ',', ' ') }} ₽</span>
                                    </span>
                                    <span class="checkout-summary__sum">{{ number_format($item->getSubtotal(), 0, ',', ' ') }} ₽</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="checkout-summary__total">
                            <span>Итого</span>
                            <span class="checkout-summary__total-value">{{ number_format((float) $subtotal, 0, ',', ' ') }} ₽</span>
                        </div>

                        <button type="submit" class="btn btn--accent checkout-summary__submit">Оформить заказ</button>
                    </div>
                </aside>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var block = document.getElementById('new-address-block');
            var radios = document.querySelectorAll('.js-address-radio');
            var newAddressRadio = document.querySelector('.js-new-address');

            function syncNewAddress() {
                if (!block) return;

                // Если есть переключатели адреса — показываем новый адрес только при выборе «Новый адрес»
                var showNew = radios.length === 0 || (newAddressRadio && newAddressRadio.checked);

                block.classList.toggle('is-hidden', !showNew);
                block.querySelectorAll('input').forEach(function (input) {
                    input.disabled = !showNew;
                });
            }

            if (radios.length > 0) {
                radios.forEach(function (radio) {
                    radio.addEventListener('change', syncNewAddress);
                });
            }

            syncNewAddress();
        });
    </script>
@endpush
