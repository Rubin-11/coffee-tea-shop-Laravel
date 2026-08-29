{{-- Страница статьи блога --}}
@extends('layouts.app')

@section('title', $post->title.' — Блог Coffee-Tea Shop')
@section('meta_description', $post->excerpt)

@section('content')
    <div class="blog-post">
        <div class="container">
            <nav class="breadcrumbs" aria-label="Хлебные крошки">
                <a href="{{ route('home') }}">Главная</a>
                <span class="breadcrumbs__sep">→</span>
                <a href="{{ route('blog.index') }}">Блог</a>
                <span class="breadcrumbs__sep">→</span>
                <span class="breadcrumbs__current">{{ $post->title }}</span>
            </nav>

            <article class="blog-post__article">
                <h1 class="blog-post__title">{{ $post->title }}</h1>

                <div class="blog-post__meta">
                    <span class="blog-post__category">{{ $post->category }}</span>
                    <span>{{ $post->published_at?->format('d.m.Y') }}</span>
                    <span>{{ $post->reading_time }} мин чтения</span>
                    @if ($post->author)
                        <span>{{ $post->author->full_name }}</span>
                    @endif
                </div>

                @if ($post->image_url)
                    <div class="blog-post__image">
                        <img src="{{ $post->image_url }}" alt="{{ $post->title }}" onerror="this.remove()">
                    </div>
                @endif

                <div class="blog-post__content">
                    {!! $post->content !!}
                </div>
            </article>

            {{-- Похожие статьи --}}
            @if ($relatedPosts->isNotEmpty())
                <section class="section blog-post__related">
                    <div class="section__head">
                        <h2 class="section__title">Похожие статьи</h2>
                    </div>
                    <div class="blog-grid">
                        @foreach ($relatedPosts as $related)
                            <article class="blog-card">
                                <a href="{{ route('blog.show', $related->slug) }}" class="blog-card__image">
                                    @if ($related->image_url)
                                        <img src="{{ $related->image_url }}" alt="{{ $related->title }}" loading="lazy" onerror="this.remove()">
                                    @endif
                                    <span class="blog-card__category">{{ $related->category }}</span>
                                </a>
                                <div class="blog-card__body">
                                    <span class="blog-card__date">{{ $related->published_at?->format('d.m.Y') }}</span>
                                    <h3 class="blog-card__title">
                                        <a href="{{ route('blog.show', $related->slug) }}">{{ $related->title }}</a>
                                    </h3>
                                    <p class="blog-card__excerpt">{{ $related->excerpt }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
@endsection
