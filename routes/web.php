<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\Customer\FavoritesController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductOrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

// SEO & Search Crawler Directives
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('seo.sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('seo.robots');

// Public Marketplace Routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/products', [ProductController::class, 'publicIndex'])->name('products.index');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::post('/products/{product}/review', [ProductController::class, 'storeReview'])->name('products.review.store');
Route::post('/products/order', [ProductOrderController::class, 'store'])->name('products.order.store');
Route::get('/order-success/{orderNumber}', [ProductOrderController::class, 'success'])->name('products.order.success');
Route::post('/wishlist/toggle/{product}', [FavoritesController::class, 'toggle'])->name('wishlist.toggle');

// Blogs & Editorial
Route::get('/blogs', [BlogController::class, 'index'])->name('blogs.index');
Route::get('/blogs/{slug}', [BlogController::class, 'show'])->name('blogs.show');

// Shared Dashboard redirect
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        $user = request()->user();

        if ($user?->hasRole('admin') || $user?->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('customer.dashboard');
    })->name('dashboard');
});

// Profile routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Notifications routes
Route::middleware('auth')->prefix('notifications')->name('notifications.')->group(function () {
    Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
    Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
    Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
});

// Modular route files
require __DIR__.'/customer.php';
require __DIR__.'/admin.php';
require __DIR__.'/auth.php';

// Storage file serving fallback for local server
Route::get('/storage/{path}', function (string $path) {
    if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
        abort(404);
    }
    return \Illuminate\Support\Facades\Storage::disk('public')->response($path);
})->where('path', '.*')->name('storage.fallback');
