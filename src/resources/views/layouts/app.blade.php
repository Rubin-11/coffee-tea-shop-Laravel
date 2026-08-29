<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Coffee-Tea Shop — свежеобжаренный кофе и премиальный чай')</title>
    <meta name="description" content="@yield('meta_description', 'Интернет-магазин свежеобжаренного кофе и премиального чая.')">

    {{-- Шрифт: Gilroy в вебе недоступен, используем Onest (Google Fonts, максимально близкий аналог Gilroy) + системный стек --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Onest:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    @include('components.header')

    <main>
        @yield('content')
    </main>

    @include('components.footer')

    @stack('scripts')
</body>
</html>
