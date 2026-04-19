<?php

use App\Http\Controllers\Admin\StoreManagementController as AdminStoreController;
use App\Http\Controllers\Admin\UserSubscriptionController;
use App\Http\Controllers\Admin\ProductManagementController;
use App\Http\Controllers\Admin\DiningTableManagementController;
use App\Http\Controllers\Admin\KitchenController;
use App\Http\Controllers\Admin\CashierController;
use App\Http\Controllers\Admin\AllBoardsController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController as AdminAuthenticatedSessionController;
use App\Http\Controllers\Admin\ChefManagementController;
use App\Http\Controllers\Customer\DineInMenuController;
use App\Http\Controllers\Customer\DineInOrderController;
use App\Http\Controllers\Customer\StoreReviewController;
use App\Http\Controllers\Customer\TakeoutOrderingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Merchant\FinancialReportController;
use App\Http\Controllers\Merchant\InvoiceCenterController;
use App\Http\Controllers\Merchant\LoyaltyController;
use App\Http\Controllers\Merchant\OrderHistoryController;
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

Route::middleware('guest')->prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminAuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AdminAuthenticatedSessionController::class, 'store'])->name('login.store');
});

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
            return redirect()->route('dashboard')->with('error', __('admin.error_store_not_found'));
        });

    Route::get('stores/{store}/kitchen/orders', [KitchenController::class, 'orders'])
        ->name('stores.kitchen.orders')
        ->missing(function (Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => __('admin.error_store_not_found')], 404);
            }

            return redirect()->route('dashboard')->with('error', __('admin.error_store_not_found'));
        });

    Route::patch('stores/{store}/kitchen/orders/{order:id}/status', [KitchenController::class, 'updateStatus'])
        ->name('stores.kitchen.orders.status')
        ->missing(function (Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => __('admin.error_store_not_found')], 404);
            }

            return redirect()->route('dashboard')->with('error', __('admin.error_store_not_found'));
        });

});

Route::middleware(['auth', 'verified', 'role:merchant,admin,cashier'])->prefix('admin')->name('admin.')->group(function () {
    // Cashier display
    Route::get('stores/{store}/cashier', [CashierController::class, 'index'])
        ->name('stores.cashier')
        ->missing(function () {
            return redirect()->route('dashboard')->with('error', __('admin.error_store_not_found'));
        });

    Route::get('stores/{store}/cashier/orders', [CashierController::class, 'orders'])
        ->name('stores.cashier.orders')
        ->missing(function (Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => __('admin.error_store_not_found')], 404);
            }

            return redirect()->route('dashboard')->with('error', __('admin.error_store_not_found'));
        });

    Route::patch('stores/{store}/cashier/orders/{order:id}/status', [CashierController::class, 'updateStatus'])
        ->name('stores.cashier.orders.status')
        ->missing(function (Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => __('admin.error_store_not_found')], 404);
            }

            return redirect()->route('dashboard')->with('error', __('admin.error_store_not_found'));
        });
});

Route::middleware(['auth', 'verified', 'role:merchant,admin,chef,cashier'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('stores/{store}/boards', [AllBoardsController::class, 'index'])
        ->name('stores.boards')
        ->missing(function () {
            return redirect()->route('dashboard')->with('error', __('admin.error_store_not_found'));
        });

    Route::get('stores/{store}/boards/orders', [AllBoardsController::class, 'orders'])
        ->name('stores.boards.orders')
        ->missing(function (Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => __('admin.error_store_not_found')], 404);
            }

            return redirect()->route('dashboard')->with('error', __('admin.error_store_not_found'));
        });
});

Route::middleware(['auth', 'verified', 'role:merchant'])->prefix('merchant')->name('merchant.')->group(function () {
    Route::get('/subscription', [MerchantSubscriptionController::class, 'index'])->name('subscription.index');
    Route::post('/subscription', [MerchantSubscriptionController::class, 'subscribe'])->name('subscription.subscribe');
    Route::post('/subscription/trial', [MerchantSubscriptionController::class, 'startTrial'])->name('subscription.trial');
    Route::get('/subscription/success', [MerchantSubscriptionController::class, 'success'])->name('subscription.success');
    Route::get('/orders', [OrderHistoryController::class, 'index'])->name('orders.index');
    Route::get('/invoices', [InvoiceCenterController::class, 'index'])->name('invoices.index');
    Route::post('/invoices/wizard', [InvoiceCenterController::class, 'updateWizard'])->name('invoices.wizard.update');
    Route::post('/invoices/test-issue', [InvoiceCenterController::class, 'runTestIssue'])->name('invoices.test-issue');
    Route::post('/invoices/orders/{order}/retry-issue', [InvoiceCenterController::class, 'retryOrderIssue'])->name('invoices.orders.retry-issue');
    Route::post('/invoices/{invoice}/retry-issue', [InvoiceCenterController::class, 'retryIssue'])->name('invoices.retry-issue');
    Route::post('/invoices/{invoice}/retry-upload', [InvoiceCenterController::class, 'retryUpload'])->name('invoices.retry-upload');
    Route::post('/invoices/{invoice}/retry-void', [InvoiceCenterController::class, 'retryVoid'])->name('invoices.retry-void');
    Route::post('/invoices/{invoice}/allowances', [InvoiceCenterController::class, 'createAllowance'])->name('invoices.allowances.store');
    Route::post('/invoice-allowances/{allowance}/retry', [InvoiceCenterController::class, 'retryAllowance'])->name('invoices.allowances.retry');
    Route::get('/reports/financial', [FinancialReportController::class, 'index'])->name('reports.financial');
    Route::post('/reports/financial/monthly-target', [FinancialReportController::class, 'updateMonthlyTarget'])->name('reports.financial.monthly-target');
    Route::get('/loyalty', [LoyaltyController::class, 'index'])->name('loyalty.index');
    Route::post('/loyalty/settings', [LoyaltyController::class, 'updateSettings'])->name('loyalty.settings.update');
    Route::post('/loyalty/coupons', [LoyaltyController::class, 'storeCoupon'])->name('loyalty.coupons.store');
    Route::put('/loyalty/coupons/{coupon}', [LoyaltyController::class, 'updateCoupon'])->name('loyalty.coupons.update');
    Route::delete('/loyalty/coupons/{coupon}', [LoyaltyController::class, 'destroyCoupon'])->name('loyalty.coupons.destroy');
    Route::patch('/loyalty/coupons/{coupon}/toggle', [LoyaltyController::class, 'toggleCoupon'])->name('loyalty.coupons.toggle');
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
Route::view('/product-intro', 'product-intro')->name('product.intro');
Route::view('/privacy-policy', 'privacy-policy')->name('privacy.policy');
Route::get('/stores', [HomeController::class, 'stores'])->name('stores.list');
Route::get('/stores/{store:slug}', [StoreController::class, 'enter'])->name('stores.enter');
Route::get('/sitemap.xml', function () {
    $latestStoreUpdateAt = \App\Models\Store::query()->max('updated_at');
    $siteLastmod = optional($latestStoreUpdateAt)->toAtomString() ?? now()->toAtomString();

    $urls = collect([
        [
            'loc' => route('home'),
            'priority' => '1.0',
            'changefreq' => 'daily',
            'lastmod' => $siteLastmod,
        ],
        [
            'loc' => route('product.intro'),
            'priority' => '0.8',
            'changefreq' => 'weekly',
            'lastmod' => $siteLastmod,
        ],
        [
            'loc' => route('stores.list'),
            'priority' => '0.9',
            'changefreq' => 'daily',
            'lastmod' => $siteLastmod,
        ],
        [
            'loc' => route('privacy.policy'),
            'priority' => '0.5',
            'changefreq' => 'monthly',
            'lastmod' => $siteLastmod,
        ],
    ]);

    $takeoutUrls = \App\Models\Store::query()
        ->where('is_active', true)
        ->where('takeout_qr_enabled', true)
        ->select(['slug', 'updated_at'])
        ->get()
        ->map(function ($store) {
            return [
                'loc' => route('customer.takeout.menu', ['store' => $store->slug]),
                'priority' => '0.7',
                'changefreq' => 'daily',
                'lastmod' => optional($store->updated_at)->toAtomString() ?? now()->toAtomString(),
            ];
        });

    $storeLandingUrls = \App\Models\Store::query()
        ->where('is_active', true)
        ->select(['slug', 'updated_at'])
        ->get()
        ->map(function ($store) {
            return [
                'loc' => route('stores.enter', ['store' => $store->slug]),
                'priority' => '0.8',
                'changefreq' => 'weekly',
                'lastmod' => optional($store->updated_at)->toAtomString() ?? now()->toAtomString(),
            ];
        });

    $xml = view('sitemap', [
        'urls' => $urls->concat($storeLandingUrls)->concat($takeoutUrls),
    ]);

    return response($xml, 200)->header('Content-Type', 'application/xml');
})->name('sitemap');

/*
|--------------------------------------------------------------------------
| Dine-in Ordering
|--------------------------------------------------------------------------
*/

Route::prefix('s/{store:slug}/t/{table:qr_token}')
    ->as('customer.dinein.')
    ->group(function () {
        Route::get('/menu', [DineInMenuController::class, 'index'])->name('menu');
        Route::get('/phone/registered', [DineInOrderController::class, 'checkPhoneRegistered'])->name('phone.registered');
        Route::post('/cart/items', [DineInOrderController::class, 'addToCart'])->name('cart.items.store');
        Route::patch('/cart/items/{lineKey}', [DineInOrderController::class, 'updateCartItem'])->name('cart.items.update');
        Route::delete('/cart/items/{lineKey}', [DineInOrderController::class, 'removeCartItem'])->name('cart.items.destroy');
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
        Route::get('/phone/registered', [TakeoutOrderingController::class, 'checkPhoneRegistered'])->name('phone.registered');
        Route::get('/coupon/check', [TakeoutOrderingController::class, 'checkCoupon'])->name('coupon.check');
        Route::post('/cart/items', [TakeoutOrderingController::class, 'addToCart'])->name('cart.items.store');
        Route::patch('/cart/items/{lineKey}', [TakeoutOrderingController::class, 'updateCartItem'])->name('cart.items.update');
        Route::delete('/cart/items/{lineKey}', [TakeoutOrderingController::class, 'removeCartItem'])->name('cart.items.destroy');
        Route::get('/cart', [TakeoutOrderingController::class, 'cart'])->name('cart.show');
        Route::post('/checkout', [TakeoutOrderingController::class, 'checkout'])->name('cart.checkout');
        Route::post('/customer-info/clear', [TakeoutOrderingController::class, 'clearRememberedCustomerInfo'])->name('customer-info.clear');
    });

Route::get('/s/{store:slug}/orders/{order}', [DineInOrderController::class, 'success'])
    ->name('customer.order.success');
Route::post('/s/{store:slug}/orders/{order}/review', [StoreReviewController::class, 'store'])
    ->name('customer.order.review.store');
Route::get('/orders/history', [DineInOrderController::class, 'history'])
    ->middleware(['auth', 'throttle:10,1'])
    ->name('customer.order.history');
Route::get('/s/{store:slug}/orders', function (Request $request) {
    return redirect()->route('customer.order.history', $request->query());
})->middleware(['auth', 'throttle:10,1']);
Route::get('/s/{store:slug}/orders/{order}/status', [DineInOrderController::class, 'orderStatus'])
    ->name('customer.order.status');

Route::get('/admin', function (Request $request) {
    $user = $request->user();

    if ($user === null) {
        return redirect()->route('admin.login');
    }

    if ($user->isMerchant()) {
        return redirect()->route('merchant.subscription.index');
    }

    if ($user->isAdmin()) {
        return redirect()->route('super-admin.subscriptions.index');
    }

    if (($user->isChef() || $user->isCashier()) && $user->store) {
        return redirect()->route('admin.stores.boards', $user->store);
    }

    return redirect()->route('home');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
