{{-- Запрос ссылки на сброс пароля (шаг 1) --}}
@extends('layouts.app')

@section('title', 'Восстановление пароля — Coffee-Tea Shop')

@section('content')
    <div class="auth">
        <div class="container">
            <div class="auth__card">
                <h1 class="auth__title">Восстановление пароля</h1>
                <p class="auth__subtitle">Укажите email — мы отправим ссылку для сброса пароля</p>

                @if (session('status'))
                    <div class="auth__status">{{ session('status') }}</div>
                @endif

                <form action="{{ route('auth.forgot.send') }}" method="POST" class="auth__form">
                    @csrf

                    <div class="form-field">
                        <label class="form-label" for="email">Email *</label>
                        <input type="email" name="email" id="email" class="form-input" value="{{ old('email') }}" required autofocus>
                        @error('email') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" class="btn btn--accent btn--wide">Отправить ссылку</button>
                </form>

                <div class="auth__footer">
                    <a href="{{ route('auth.login') }}">← Вернуться ко входу</a>
                </div>
            </div>
        </div>
    </div>
@endsection
