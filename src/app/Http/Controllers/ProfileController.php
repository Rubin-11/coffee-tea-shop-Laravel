<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Контроллер личного кабинета
 *
 * Сводка по пользователю: последние заказы, данные аккаунта.
 */
final class ProfileController extends Controller
{
    /**
     * Показать главную страницу личного кабинета
     */
    public function index(): View
    {
        $orders = Order::with(['items'])
            ->byUser(Auth::id())
            ->recent()
            ->limit(5)
            ->get();

        return view('profile.index', [
            'orders' => $orders,
        ]);
    }
}
