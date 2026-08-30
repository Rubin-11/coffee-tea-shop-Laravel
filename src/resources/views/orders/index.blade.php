{{-- Список заказов пользователя с фильтрами по статусу --}}
@extends('layouts.app')

@section('title', 'Мои заказы — Coffee-Tea Shop')

@section('content')
    <div class="orders">
        <div class="container">
            <nav class="breadcrumbs" aria-label="Хлебные крошки">
                <a href="{{ route('home') }}">Главная</a>
                <span class="breadcrumbs__sep">→</span>
                <a href="{{ route('profile.index') }}">Личный кабинет</a>
                <span class="breadcrumbs__sep">→</span>
                <span class="breadcrumbs__current">Заказы</span>
            </nav>

            <h1 class="orders__title">Мои заказы</h1>

            {{-- Фильтры по статусу --}}
            <div class="orders-filters">
                <a href="{{ route('orders.index') }}"
                   class="filter-chip @if (! $currentStatus) is-active @endif">Все ({{ $statusCounts['all'] }})</a>
                <a href="{{ route('orders.index', ['status' => 'pending']) }}"
                   class="filter-chip @if ($currentStatus === 'pending') is-active @endif">Активные ({{ $statusCounts['pending'] }})</a>
                <a href="{{ route('orders.index', ['status' => 'paid']) }}"
                   class="filter-chip @if ($currentStatus === 'paid') is-active @endif">Оплаченные ({{ $statusCounts['paid'] }})</a>
                <a href="{{ route('orders.index', ['status' => 'delivered']) }}"
                   class="filter-chip @if ($currentStatus === 'delivered') is-active @endif">Завершённые ({{ $statusCounts['delivered'] }})</a>
                <a href="{{ route('orders.index', ['status' => 'cancelled']) }}"
                   class="filter-chip @if ($currentStatus === 'cancelled') is-active @endif">Отменённые ({{ $statusCounts['cancelled'] }})</a>
            </div>

            @if ($orders->isNotEmpty())
                <div class="orders-list">
                    @foreach ($orders as $order)
                        <a href="{{ route('orders.show', $order->id) }}" class="order-row">
                            <span class="order-row__number">№ {{ $order->order_number }}</span>
                            <span class="order-row__date">{{ $order->created_at->format('d.m.Y') }}</span>
                            <span class="order-row__status">{{ $order->status_text }}</span>
                            <span class="order-row__total">{{ number_format((float) $order->total, 0, ',', ' ') }} ₽</span>
                        </a>
                    @endforeach
                </div>

                {{ $orders->links('vendor.pagination.catalog') }}
            @else
                <p class="empty">В этой категории заказов пока нет.</p>
            @endif
        </div>
    </div>
@endsection
