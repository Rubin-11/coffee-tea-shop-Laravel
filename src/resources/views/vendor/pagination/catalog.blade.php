{{-- Кастомная пагинация в стиле каталога --}}
@if ($paginator->hasPages())
    <nav class="pagination" aria-label="Пагинация">
        {{-- Предыдущая страница --}}
        @if ($paginator->onFirstPage())
            <span class="pagination__item pagination__item--disabled">←</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="pagination__item" rel="prev">←</a>
        @endif

        {{-- Номера страниц --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="pagination__item pagination__item--dots">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="pagination__item pagination__item--active" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="pagination__item">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Следующая страница --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="pagination__item" rel="next">→</a>
        @else
            <span class="pagination__item pagination__item--disabled">→</span>
        @endif
    </nav>
@endif
