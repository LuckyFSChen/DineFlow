<?php

use App\Http\Controllers\Admin\StoreManagementController as AdminStoreController;
use App\Http\Controllers\Admin\UserSubscriptionController;
use App\Http\Controllers\Admin\ProductManagementController;
use App\Http\Controllers\Customer\DineInMenuController;
use App\Http\Controllers\Customer\DineInOrderController;
use App\Http\Controllers\Customer\TakeoutOrderingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Merchant\SubscriptionController as MerchantSubscriptionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Store
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'role:merchant,admin', 'merchant.subscription'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('stores', AdminStoreController::class)->except(['show']);
    Route::post('stores/{store}/products/reorder', [ProductManagementController::class, 'reorder'])->name('stores.products.reorder');
    Route::resource('stores.products', ProductManagementController::class)->except(['show']);
});

Route::middleware(['auth', 'verified', 'role:merchant'])->prefix('merchant')->name('merchant.')->group(function () {
    Route::get('/subscription', [MerchantSubscriptionController::class, 'index'])->name('subscription.index');
    Route::post('/subscription', [MerchantSubscriptionController::class, 'subscribe'])->name('subscription.subscribe');
    Route::get('/subscription/success', [MerchantSubscriptionController::class, 'success'])->name('subscription.success');
});

Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('super-admin')->name('super-admin.')->group(function () {
    Route::get('/subscriptions', [UserSubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::patch('/subscriptions/{user}', [UserSubscriptionController::class, 'update'])->name('subscriptions.update');
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
        Route::post('/customer-info/clear', [DineInOrderController::class, 'clearRememberedCustomerInfo'])->name('customer-info.clear');
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
        Route::post('/customer-info/clear', [TakeoutOrderingController::class, 'clearRememberedCustomerInfo'])->name('customer-info.clear');
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
