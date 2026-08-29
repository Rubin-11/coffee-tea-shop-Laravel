{{-- Страница блога: фильтр по категориям, сортировка, сетка статей --}}
@extends('layouts.app')

@section('title', 'Блог — Coffee-Tea Shop')

@php
    $currentSort = request('sort', 'latest');
    $currentCategory = request('category');
@endphp

@section('content')
    <div class="blog">
        <div class="container">
            <nav class="breadcrumbs" aria-label="Хлебные крошки">
                <a href="{{ route('home') }}">Главная</a>
                <span class="breadcrumbs__sep">→</span>
                <span class="breadcrumbs__current">Блог</span>
            </nav>

            <h1 class="blog__title">Блог</h1>

            {{-- Фильтр по категориям --}}
            <div class="blog-filters">
                <a href="{{ request()->fullUrlWithQuery(['category' => null, 'page' => null]) }}"
                   class="filter-chip @if (! $currentCategory) is-active @endif">Все</a>
                @foreach ($blogCategories as $cat)
                    <a href="{{ request()->fullUrlWithQuery(['category' => $cat, 'page' => null]) }}"
                       class="filter-chip @if ($currentCategory === $cat) is-active @endif">{{ $cat }}</a>
                @endforeach
            </div>

            {{-- Сортировка --}}
            <div class="catalog-toolbar">
                <span class="catalog-toolbar__label">Сортировка:</span>
                <a href="{{ request()->fullUrlWithQuery(['sort' => 'latest', 'page' => null]) }}"
                   class="sort-link @if ($currentSort === 'latest') is-active @endif">Сначала новые</a>
                <a href="{{ request()->fullUrlWithQuery(['sort' => 'popular', 'page' => null]) }}"
                   class="sort-link @if ($currentSort === 'popular') is-active @endif">Популярные</a>
            </div>

            {{-- Сетка статей --}}
            @if ($posts->isNotEmpty())
                <div class="blog-grid">
                    @foreach ($posts as $post)
                        <article class="blog-card">
                            <a href="{{ route('blog.show', $post->slug) }}" class="blog-card__image">
                                @if ($post->image_url)
                                    <img src="{{ $post->image_url }}" alt="{{ $post->title }}" loading="lazy" onerror="this.remove()">
                                @endif
                                <span class="blog-card__category">{{ $post->category }}</span>
                            </a>

                            <div class="blog-card__body">
                                <span class="blog-card__date">{{ $post->published_at?->format('d.m.Y') }}</span>
                                <h2 class="blog-card__title">
                                    <a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a>
                                </h2>
                                <p class="blog-card__excerpt">{{ $post->excerpt }}</p>
                                <div class="blog-card__meta">
                                    <span>{{ $post->reading_time }} мин чтения</span>
                                    @if ($post->author)
                                        <span>{{ $post->author->full_name }}</span>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                {{ $posts->links('vendor.pagination.catalog') }}
            @else
                <p class="empty">В этой категории пока нет статей.</p>
            @endif
        </div>
    </div>
@endsection
