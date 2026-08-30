{{-- Форма входа в личный кабинет --}}
@extends('layouts.app')

@section('title', 'Вход — Coffee-Tea Shop')

@section('content')
    <div class="auth">
        <div class="container">
            <div class="auth__card">
                <h1 class="auth__title">Вход</h1>
                <p class="auth__subtitle">Войдите, чтобы видеть свои заказы и оставлять отзывы</p>

                <form action="{{ route('auth.login') }}" method="POST" class="auth__form">
                    @csrf

                    <div class="form-field">
                        <label class="form-label" for="email">Email *</label>
                        <input type="email" name="email" id="email" class="form-input" value="{{ old('email') }}" required autofocus>
                        @error('email') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="password">Пароль *</label>
                        <input type="password" name="password" id="password" class="form-input" required>
                        @error('password') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <label class="auth__remember">
                        <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                        <span>Запомнить меня</span>
                    </label>

                    <button type="submit" class="btn btn--accent btn--wide">Войти</button>
                </form>

                <div class="auth__footer">
                    <a href="{{ route('auth.forgot') }}">Забыли пароль?</a>
                    <span>·</span>
                    <a href="{{ route('auth.register') }}">Зарегистрироваться</a>
                </div>
            </div>
        </div>
    </div>
@endsection
