<?php

use App\Http\Controllers\Customer\MenuController;
use App\Http\Controllers\Customer\OrderController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Website
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/stores/{store:slug}', [StoreController::class, 'show'])->name('stores.show');
Route::get('/stores/{store:slug}/menu', [StoreController::class, 'menu'])->name('stores.menu');

/*
|--------------------------------------------------------------------------
| QR Code / Dine-in Ordering
|--------------------------------------------------------------------------
*/

Route::prefix('s/{store:slug}/t/{table:qr_token}')
    ->as('customer.')
    ->group(function () {
        Route::get('/menu', [MenuController::class, 'index'])->name('menu');

        Route::prefix('cart')->group(function () {
            Route::post('/items', [OrderController::class, 'addToCart'])->name('cart.items.store');
            Route::get('/', [OrderController::class, 'cart'])->name('cart.show');
            Route::post('/checkout', [OrderController::class, 'submit'])->name('cart.checkout');
        });
    });

Route::get('/s/{store:slug}/orders/{order}', [OrderController::class, 'success'])
    ->name('customer.order.success');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';