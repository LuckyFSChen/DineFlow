<?php

use App\Http\Controllers\Customer\MenuController;
use App\Http\Controllers\Customer\OrderController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/menu/{token}', [MenuController::class, 'index'])->name('customer.menu');

Route::prefix('cart')->group(function () {
    Route::post('/add', [OrderController::class, 'addToCart'])->name('customer.cart.add');
    Route::get('/{token}', [OrderController::class, 'cart'])->name('customer.cart');
    Route::post('/submit/{token}', [OrderController::class, 'submit'])->name('customer.cart.submit');
});

Route::get('/order/success/{order}', [OrderController::class, 'success'])->name('customer.order.success');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
