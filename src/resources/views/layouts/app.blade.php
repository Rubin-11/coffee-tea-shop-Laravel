<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Мir.co - Магазин кофе и чая')</title>
    <meta name="description" content="@yield('meta_description', 'Интернет-магазин свежеобжаренного кофе и премиального чая. Мir.co.')">
   
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
