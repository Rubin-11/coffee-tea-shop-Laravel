{{-- Установка нового пароля по токену (шаг 2) --}}
@extends('layouts.app')

@section('title', 'Новый пароль — Coffee-Tea Shop')

@section('content')
    <div class="auth">
        <div class="container">
            <div class="auth__card">
                <h1 class="auth__title">Новый пароль</h1>
                <p class="auth__subtitle">Придумайте новый пароль для входа</p>

                <form action="{{ route('auth.reset') }}" method="POST" class="auth__form">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="form-field">
                        <label class="form-label" for="email">Email *</label>
                        <input type="email" name="email" id="email" class="form-input" value="{{ old('email') }}" required autofocus>
                        @error('email') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="password">Новый пароль *</label>
                        <input type="password" name="password" id="password" class="form-input" required>
                        @error('password') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="password_confirmation">Повторите пароль *</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-input" required>
                    </div>

                    <button type="submit" class="btn btn--accent btn--wide">Сохранить пароль</button>
                </form>
            </div>
        </div>
    </div>
@endsection
