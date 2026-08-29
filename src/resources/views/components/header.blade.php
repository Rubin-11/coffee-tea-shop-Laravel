{{-- Хедер: логотип, навигация, поиск, иконки ЛК и корзины --}}
<header class="header">
    <div class="container header__inner">
        <a href="{{ route('home') }}" class="header__logo">Coffee-Tea <span>Shop</span></a>

        <nav class="header__nav">
            <a href="{{ route('categories.index') }}">Каталог товаров</a>
            <a href="{{ route('blog.index') }}">Блог</a>
            <a href="{{ route('pages.contacts') }}">Контакты</a>
        </nav>

        <form action="{{ route('products.index') }}" method="GET" class="header__search" role="search">
            <input type="search" name="search" placeholder="Поиск по товарам" aria-label="Поиск по товарам">
        </form>

        <div class="header__actions">
            <a href="{{ route('profile.index') }}" class="header__icon" aria-label="Личный кабинет" title="Личный кабинет">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </a>
            <a href="{{ route('cart.index') }}" class="header__icon" aria-label="Корзина" title="Корзина">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
            </a>
        </div>
    </div>
</header>
