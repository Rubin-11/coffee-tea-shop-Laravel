{{-- Форма регистрации нового пользователя --}}
@extends('layouts.app')

@section('title', 'Регистрация — Coffee-Tea Shop')

@section('content')
    <div class="auth">
        <div class="container">
            <div class="auth__card">
                <h1 class="auth__title">Регистрация</h1>
                <p class="auth__subtitle">Создайте аккаунт, чтобы оформлять заказы быстрее</p>

                <form action="{{ route('auth.register') }}" method="POST" class="auth__form">
                    @csrf

                    <div class="auth__row">
                        <div class="form-field">
                            <label class="form-label" for="first_name">Имя *</label>
                            <input type="text" name="first_name" id="first_name" class="form-input" value="{{ old('first_name') }}" required>
                            @error('first_name') <span class="form-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="last_name">Фамилия *</label>
                            <input type="text" name="last_name" id="last_name" class="form-input" value="{{ old('last_name') }}" required>
                            @error('last_name') <span class="form-error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="email">Email *</label>
                        <input type="email" name="email" id="email" class="form-input" value="{{ old('email') }}" required>
                        @error('email') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="phone">Телефон</label>
                        <input type="tel" name="phone" id="phone" class="form-input" value="{{ old('phone') }}" placeholder="+7 (___) ___-__-__">
                        @error('phone') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="password">Придумайте пароль *</label>
                        <input type="password" name="password" id="password" class="form-input" required>
                        @error('password') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="password_confirmation">Повторите пароль *</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-input" required>
                    </div>

                    <button type="submit" class="btn btn--accent btn--wide">Зарегистрироваться</button>
                </form>

                <div class="auth__footer">
                    <span>Уже есть аккаунт?</span>
                    <a href="{{ route('auth.login') }}">Войти</a>
                </div>
            </div>
        </div>
    </div>
@endsection
