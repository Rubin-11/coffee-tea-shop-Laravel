{{-- Футер: блок подписки + колонки с ссылками + нижняя полоса --}}
<footer class="footer">
    {{-- Блок подписки на новости и рассылку --}}
    <div class="newsletter">
        <div class="container newsletter__inner">
            <div>
                <h2 class="newsletter__title">Подписка на новости и рассылку</h2>
                <p class="newsletter__text">Узнавайте первыми о новых поступлениях, скидках и акциях.</p>
            </div>

            <form action="{{ route('newsletter.subscribe') }}" method="POST" class="newsletter__form">
                @csrf
                <div class="newsletter__row">
                    <input type="email" name="email" class="newsletter__input" placeholder="Ваш email" required>
                    <button type="submit" class="btn btn--accent">Подписаться</button>
                </div>
                <label class="newsletter__consent">
                    <input type="checkbox" name="agree" required>
                    <span>Согласен(на) на обработку персональных данных и <a href="{{ route('pages.privacy') }}">политикой конфиденциальности</a></span>
                </label>
            </form>
        </div>
    </div>

    <div class="container footer__inner">
        <div>
            <div class="footer__brand">Coffee-Tea <span>Shop</span></div>
            <p class="footer__desc">Интернет-магазин свежеобжаренного кофе и премиального чая. Обжариваем сами и доставляем по всей России.</p>
        </div>

        <div class="footer__col">
            <div class="footer__col-title">Каталог</div>
            <a href="{{ route('categories.index') }}">Каталог товаров</a>
            <a href="{{ route('products.index') }}">Все товары</a>
            <a href="{{ route('products.index', ['filter' => 'discount']) }}">Товары со скидкой</a>
        </div>

        <div class="footer__col">
            <div class="footer__col-title">Компания</div>
            <a href="{{ route('blog.index') }}">Блог</a>
            <a href="{{ route('pages.about') }}">О компании</a>
            <a href="{{ route('pages.contacts') }}">Контакты</a>
            <a href="{{ route('pages.delivery') }}">Доставка и оплата</a>
        </div>

        <div class="footer__col">
            <div class="footer__col-title">Контакты</div>
            <a href="tel:+74012375343">+7 (401) 237-53-43</a>
            <a href="mailto:Import@kldrefine.com">Import@kldrefine.com</a>
            <p>Калининградская обл., Гурьевский р-н, пос. Васильково</p>
        </div>
    </div>

    <div class="footer__bottom">
        <div class="container footer__bottom-inner">
            <span>© {{ date('Y') }} Coffee-Tea Shop</span>
            <span>Свежеобжаренный кофе и премиальный чай</span>
        </div>
    </div>
</footer>
