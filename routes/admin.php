<?php

use App\Http\Controllers\Admin\BlogController as AdminBlogController;
use App\Http\Controllers\Admin\CategoriesController as AdminCategoriesController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\CustomersController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\NotificationsController;
use App\Http\Controllers\Admin\ProductOrderController as AdminProductOrderController;
use App\Http\Controllers\Admin\ReviewsController as AdminReviewsController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

// Admin routes
Route::middleware(['auth', 'role:admin'])->prefix(env('ADMIN_PREFIX', 'admin99'))->name('admin.')->group(function () {
    Route::get('/', fn() => redirect()->route('admin.dashboard'));
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Users
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [AdminUserController::class, 'index'])->name('index');
        Route::post('/', [AdminUserController::class, 'store'])->name('store');
        Route::put('/{user}', [AdminUserController::class, 'update'])->name('update');
        Route::post('/{user}/status', [AdminUserController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->name('reset-password');
        Route::delete('/{user}', [AdminUserController::class, 'destroy'])->name('destroy');
    });

    // Customers
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [CustomersController::class, 'index'])->name('index');
        Route::get('/{customer}', [CustomersController::class, 'show'])->name('show');
        Route::post('/{customer}/status', [CustomersController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{customer}', [CustomersController::class, 'destroy'])->name('destroy');
    });

    // Categories
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [AdminCategoriesController::class, 'index'])->name('index');
        Route::post('/', [AdminCategoriesController::class, 'store'])->name('store');
        Route::post('/{category}', [AdminCategoriesController::class, 'update'])->name('update');
        Route::put('/{category}', [AdminCategoriesController::class, 'update'])->name('update.put');
        Route::delete('/{category}', [AdminCategoriesController::class, 'destroy'])->name('destroy');
    });

    // Products
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/create', [ProductController::class, 'create'])->name('create');
        Route::post('/', [ProductController::class, 'store'])->name('store');
        Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit');
        Route::put('/{product}', [ProductController::class, 'update'])->name('update');
        Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');
        Route::post('/{product}/toggle-home', [ProductController::class, 'toggleHome'])->name('toggle-home');
        Route::post('/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{product}/toggle-approval', [ProductController::class, 'toggleApproval'])->name('toggle-approval');
    });

    // Orders
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [AdminProductOrderController::class, 'index'])->name('index');
        Route::get('/{order}', [AdminProductOrderController::class, 'show'])->name('show');
        Route::post('/{order}/status', [AdminProductOrderController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{order}', [AdminProductOrderController::class, 'destroy'])->name('destroy');
    });

    // Banners / Content
    Route::prefix('content')->name('content.')->group(function () {
        Route::get('/banners', [ContentController::class, 'banners'])->name('banners');
        Route::post('/banners', [ContentController::class, 'storeBanner'])->name('store-banner');
        Route::post('/banners/{banner}/update', [ContentController::class, 'updateBanner'])->name('update-banner-post');
        Route::put('/banners/{banner}', [ContentController::class, 'updateBanner'])->name('update-banner');
        Route::post('/banners/{banner}/toggle-status', [ContentController::class, 'toggleBannerStatus'])->name('toggle-banner-status');
        Route::delete('/banners/{banner}', [ContentController::class, 'destroyBanner'])->name('destroy-banner');
    });

    // Reviews
    Route::prefix('reviews')->name('reviews.')->group(function () {
        Route::get('/', [AdminReviewsController::class, 'index'])->name('index');
        Route::post('/{review}/approve', [AdminReviewsController::class, 'approve'])->name('approve');
        Route::post('/{review}/reject', [AdminReviewsController::class, 'reject'])->name('reject');
        Route::delete('/{review}', [AdminReviewsController::class, 'destroy'])->name('destroy');
    });

    // Blogs
    Route::prefix('blogs')->name('blogs.')->group(function () {
        Route::get('/', [AdminBlogController::class, 'index'])->name('index');
        Route::post('/', [AdminBlogController::class, 'store'])->name('store');
        Route::post('/{blog}', [AdminBlogController::class, 'update'])->name('update');
        Route::put('/{blog}', [AdminBlogController::class, 'update'])->name('update.put');
        Route::post('/{blog}/toggle-status', [AdminBlogController::class, 'toggleStatus'])->name('toggle-status');
        Route::delete('/{blog}', [AdminBlogController::class, 'destroy'])->name('destroy');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::put('/', [SettingsController::class, 'update'])->name('update');
    });

    // System Cache & Migration tools
    Route::prefix('system')->name('system.')->group(function () {
        Route::get('/migrate', function () {
            try {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                $output = \Illuminate\Support\Facades\Artisan::output();
                return response("<pre style='background:#111;color:#0f0;padding:20px;border-radius:10px;font-family:monospace;'>[SUCCESS] Migrations Executed:\n\n" . htmlspecialchars($output) . "\n\n<a href='" . route('admin.dashboard') . "' style='color:#fff;background:#e11d48;padding:8px 16px;text-decoration:none;border-radius:6px;display:inline-block;margin-top:15px;'>&larr; Return to Admin Dashboard</a></pre>");
            } catch (\Throwable $e) {
                return response("<pre style='background:#111;color:#f33;padding:20px;border-radius:10px;font-family:monospace;'>[ERROR] Migration Failed:\n\n" . htmlspecialchars($e->getMessage()) . "</pre>", 500);
            }
        })->name('migrate');

        Route::get('/optimize-clear', function () {
            try {
                \Illuminate\Support\Facades\Artisan::call('optimize:clear');
                $output = \Illuminate\Support\Facades\Artisan::output();
                return response("<pre style='background:#111;color:#0f0;padding:20px;border-radius:10px;font-family:monospace;'>[SUCCESS] Optimization Cache Cleared:\n\n" . htmlspecialchars($output) . "\n\n<a href='" . route('admin.dashboard') . "' style='color:#fff;background:#e11d48;padding:8px 16px;text-decoration:none;border-radius:6px;display:inline-block;margin-top:15px;'>&larr; Return to Admin Dashboard</a></pre>");
            } catch (\Throwable $e) {
                return response("<pre style='background:#111;color:#f33;padding:20px;border-radius:10px;font-family:monospace;'>[ERROR] Cache Clear Failed:\n\n" . htmlspecialchars($e->getMessage()) . "</pre>", 500);
            }
        })->name('optimize-clear');
    });
});
