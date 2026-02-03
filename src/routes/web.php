<?php

use Illuminate\Support\Facades\Route;

/**
 * Главный маршрут приложения
 * Отображает стартовую страницу Laravel
 */
Route::get('/', function () {
    return view('welcome');
});
