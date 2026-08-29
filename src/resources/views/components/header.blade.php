{{-- Хедер по макету Pixso (17124:12): логотип-картинка, навигация, 3 иконки --}}
<header class="header">
    <div class="container header__inner">
        <a href="{{ route('home') }}" class="header__logo" aria-label="На главную">
            <img src="{{ asset('images/logo.png') }}" alt="Coffee-Tea Shop" class="header__logo-img">
        </a>

        <nav class="header__nav">
            <a href="{{ route('categories.index') }}">Каталог товаров</a>
            <a href="{{ route('blog.index') }}">Блог</a>
            <a href="{{ route('pages.contacts') }}">Контакты</a>
        </nav>

        <div class="header__actions">
            <a href="{{ route('products.index') }}" class="header__icon header__icon--search" aria-label="Поиск по товарам" title="Поиск">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
            </a>
            <a href="{{ route('cart.index') }}" class="header__icon header__icon--cart" aria-label="Корзина" title="Корзина">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
            </a>
            <a href="{{ route('profile.index') }}" class="header__icon header__icon--account" aria-label="Личный кабинет" title="Личный кабинет">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </a>
        </div>
    </div>
</header>
