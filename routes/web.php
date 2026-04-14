<?php

use App\Http\Controllers\Admin\StoreManagementController as AdminStoreController;
use App\Http\Controllers\Admin\UserSubscriptionController;
use App\Http\Controllers\Admin\ProductManagementController;
use App\Http\Controllers\Admin\DiningTableManagementController;
use App\Http\Controllers\Admin\KitchenController;
use App\Http\Controllers\Admin\CashierController;
use App\Http\Controllers\Admin\ChefManagementController;
use App\Http\Controllers\Customer\DineInMenuController;
use App\Http\Controllers\Customer\DineInOrderController;
use App\Http\Controllers\Customer\TakeoutOrderingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Merchant\FinancialReportController;
use App\Http\Controllers\Merchant\SubscriptionController as MerchantSubscriptionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StoreController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Store
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'role:merchant,admin', 'merchant.subscription'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('stores', AdminStoreController::class)->except(['show']);
    Route::post('stores/{store}/categories', [ProductManagementController::class, 'storeCategory'])->name('stores.categories.store');
    Route::get('stores/{store}/categories/{category}/edit', [ProductManagementController::class, 'editCategory'])->name('stores.categories.edit');
    Route::put('stores/{store}/categories/{category}', [ProductManagementController::class, 'updateCategory'])->name('stores.categories.update');
    Route::patch('stores/{store}/categories/{category}/disable', [ProductManagementController::class, 'disableCategory'])->name('stores.categories.disable');
    Route::patch('stores/{store}/categories/{category}/enable', [ProductManagementController::class, 'enableCategory'])->name('stores.categories.enable');
    Route::delete('stores/{store}/categories/{category}', [ProductManagementController::class, 'destroyCategory'])->name('stores.categories.destroy');
    Route::post('stores/{store}/products/reorder', [ProductManagementController::class, 'reorder'])->name('stores.products.reorder');
    Route::post('stores/{store}/products/move', [ProductManagementController::class, 'move'])->name('stores.products.move');
    Route::resource('stores.products', ProductManagementController::class)->except(['show']);
    Route::get('stores/{store}/tables', [DiningTableManagementController::class, 'index'])->name('stores.tables.index');
    Route::get('stores/{store}/tables/print', [DiningTableManagementController::class, 'print'])->name('stores.tables.print');
    Route::post('stores/{store}/tables', [DiningTableManagementController::class, 'store'])->name('stores.tables.store');
    Route::patch('stores/{store}/takeout-qr', [DiningTableManagementController::class, 'updateTakeoutQr'])->name('stores.takeout-qr.update');
    Route::patch('stores/{store}/tables/{table}/status', [DiningTableManagementController::class, 'updateStatus'])->name('stores.tables.status');
    Route::post('stores/{store}/tables/{table}/regenerate-qr', [DiningTableManagementController::class, 'regenerateQr'])->name('stores.tables.regenerate-qr');

    Route::get('stores/{store}/chefs', [ChefManagementController::class, 'index'])->name('stores.chefs.index');
    Route::post('stores/{store}/chefs', [ChefManagementController::class, 'store'])->name('stores.chefs.store');
    Route::delete('stores/{store}/chefs/{chef}', [ChefManagementController::class, 'destroy'])->name('stores.chefs.destroy');
});

Route::middleware(['auth', 'verified', 'role:merchant,admin,chef'])->prefix('admin')->name('admin.')->group(function () {
    // Kitchen display
    Route::get('stores/{store}/kitchen', [KitchenController::class, 'index'])
        ->name('stores.kitchen')
        ->missing(function () {
            return redirect()->route('dashboard')->with('error', '店家不存在或已刪除。');
        });

    Route::get('stores/{store}/kitchen/orders', [KitchenController::class, 'orders'])
        ->name('stores.kitchen.orders')
        ->missing(function (Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'Store not found'], 404);
            }

            return redirect()->route('dashboard')->with('error', '店家不存在或已刪除。');
        });

    Route::patch('stores/{store}/kitchen/orders/{order:id}/status', [KitchenController::class, 'updateStatus'])
        ->name('stores.kitchen.orders.status')
        ->missing(function (Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'Store not found'], 404);
            }

            return redirect()->route('dashboard')->with('error', '店家不存在或已刪除。');
        });
});

Route::middleware(['auth', 'verified', 'role:merchant,admin,cashier'])->prefix('admin')->name('admin.')->group(function () {
    // Cashier display
    Route::get('stores/{store}/cashier', [CashierController::class, 'index'])
        ->name('stores.cashier')
        ->missing(function () {
            return redirect()->route('dashboard')->with('error', '店家不存在或已刪除。');
        });

    Route::get('stores/{store}/cashier/orders', [CashierController::class, 'orders'])
        ->name('stores.cashier.orders')
        ->missing(function (Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'Store not found'], 404);
            }

            return redirect()->route('dashboard')->with('error', '店家不存在或已刪除。');
        });

    Route::patch('stores/{store}/cashier/orders/{order:id}/status', [CashierController::class, 'updateStatus'])
        ->name('stores.cashier.orders.status')
        ->missing(function (Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'Store not found'], 404);
            }

            return redirect()->route('dashboard')->with('error', '店家不存在或已刪除。');
        });
});

Route::middleware(['auth', 'verified', 'role:merchant'])->prefix('merchant')->name('merchant.')->group(function () {
    Route::get('/subscription', [MerchantSubscriptionController::class, 'index'])->name('subscription.index');
    Route::post('/subscription', [MerchantSubscriptionController::class, 'subscribe'])->name('subscription.subscribe');
    Route::get('/subscription/success', [MerchantSubscriptionController::class, 'success'])->name('subscription.success');
    Route::get('/reports/financial', [FinancialReportController::class, 'index'])->name('reports.financial');
});

Route::post('/ecpay/subscription/notify', [MerchantSubscriptionController::class, 'notify'])->name('ecpay.subscription.notify');
Route::post('/ecpay/subscription/result', [MerchantSubscriptionController::class, 'result'])->name('ecpay.subscription.result');

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('super-admin')->name('super-admin.')->group(function () {
    Route::get('/subscriptions', [UserSubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::patch('/subscriptions/{user}', [UserSubscriptionController::class, 'update'])->name('subscriptions.update');
});

/*
|--------------------------------------------------------------------------
| Public Website
|--------------------------------------------------------------------------
*/

Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');
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
Route::get('/s/{store:slug}/orders', [DineInOrderController::class, 'history'])
    ->name('customer.order.history');
Route::get('/s/{store:slug}/orders/{order}/status', [DineInOrderController::class, 'orderStatus'])
    ->name('customer.order.status');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
