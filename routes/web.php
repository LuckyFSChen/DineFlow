<?php

use App\Http\Controllers\Admin\StoreManagementController as AdminStoreController;
use App\Http\Controllers\Customer\DineInMenuController;
use App\Http\Controllers\Customer\DineInOrderController;
use App\Http\Controllers\Customer\TakeoutOrderingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Store
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
        Route::resource('stores', AdminStoreController::class)->except(['show']);
});

/*
|--------------------------------------------------------------------------
| Public Website
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/stores/{store:slug}', [StoreController::class, 'enter'])->name('stores.enter');

/*
|--------------------------------------------------------------------------
| Dine-in Ordering
|--------------------------------------------------------------------------
*/

Route::prefix('s/{store:slug}/t/{table:qr_token}')
    ->as('customer.dinein.')
    ->group(function () {
        Route::get('/menu', [DineInMenuController::class, 'index'])->name('menu');
        Route::post('/cart/items', [DineInOrderController::class, 'addToCart'])->name('cart.items.store');
        Route::get('/cart', [DineInOrderController::class, 'cart'])->name('cart.show');
        Route::post('/checkout', [DineInOrderController::class, 'submit'])->name('cart.checkout');
    });

/*
|--------------------------------------------------------------------------
| Takeout Ordering
|--------------------------------------------------------------------------
*/

Route::prefix('s/{store:slug}/takeout')
    ->as('customer.takeout.')
    ->group(function () {
        Route::get('/menu', [TakeoutOrderingController::class, 'menu'])->name('menu');
        Route::post('/cart/items', [TakeoutOrderingController::class, 'addToCart'])->name('cart.items.store');
        Route::get('/cart', [TakeoutOrderingController::class, 'cart'])->name('cart.show');
        Route::post('/checkout', [TakeoutOrderingController::class, 'checkout'])->name('cart.checkout');
    });

Route::get('/s/{store:slug}/orders/{order}', [DineInOrderController::class, 'success'])
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
