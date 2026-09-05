<?php

use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Customer\FavoritesController;
use App\Http\Controllers\Customer\OrdersController as CustomerOrdersController;
use Illuminate\Support\Facades\Route;

// Customer routes
Route::middleware(['auth'])->prefix('customer')->name('customer.')->group(function () {
    Route::get('/dashboard', [CustomerDashboardController::class, 'index'])->name('dashboard');

    // Orders
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [CustomerOrdersController::class, 'index'])->name('index');
        Route::get('/{order}', [CustomerOrdersController::class, 'show'])->name('show');
        Route::post('/{order}/cancel', [CustomerOrdersController::class, 'cancel'])->name('cancel');
    });

    // Wishlist / Favorites
    Route::prefix('wishlist')->name('wishlist.')->group(function () {
        Route::get('/', [CustomerDashboardController::class, 'wishlist'])->name('index');
        Route::post('/toggle/{product}', [FavoritesController::class, 'toggle'])->name('toggle');
        Route::delete('/{product}', [FavoritesController::class, 'destroy'])->name('destroy');
    });
    Route::get('/favorites', fn() => redirect()->route('customer.wishlist.index'))->name('favorites.index');

    // Reviews
    Route::prefix('reviews')->name('reviews.')->group(function () {
        Route::get('/', [CustomerDashboardController::class, 'reviews'])->name('index');
    });

    // Profile
    Route::get('/profile', [CustomerDashboardController::class, 'profile'])->name('profile');
    Route::post('/profile', [CustomerDashboardController::class, 'updateProfile'])->name('profile.update');
});
