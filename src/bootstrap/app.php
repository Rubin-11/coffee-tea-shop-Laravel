<?php

use App\Http\Middleware\CheckCartNotEmpty;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Неавторизованных пользователей отправляем на форму входа (роут auth.login)
        $middleware->redirectGuestsTo('/auth/login');

        // Регистрируем middleware-алиас для проверки непустой корзины
        // Использование в маршрутах: ->middleware('cart.not.empty')
        $middleware->alias([
            'cart.not.empty' => CheckCartNotEmpty::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
