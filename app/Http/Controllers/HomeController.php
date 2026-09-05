<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Blog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        // 1. Hero Banners
        $banners = Banner::active()
            ->with(['product', 'category'])
            ->orderBy('sort_order', 'asc')
            ->get();

        // 2. Featured Categories with Product counts
        $categories = Category::active()
            ->withCount(['products' => function ($q) {
                $q->active();
            }])
            ->orderBy('sort_order', 'asc')
            ->get();

        // 3. Trending / Flash Deals Products
        $trendingProducts = Product::active()
            ->trending()
            ->with(['categoryRelation', 'user'])
            ->orderBy('sort_order', 'asc')
            ->take(8)
            ->get();

        // 4. Featured & Bestsellers
        $featuredProducts = Product::active()
            ->featured()
            ->with(['categoryRelation', 'user'])
            ->orderBy('rating', 'desc')
            ->take(8)
            ->get();

        // 5. New Arrivals
        $newArrivals = Product::active()
            ->with(['categoryRelation', 'user'])
            ->latest()
            ->take(8)
            ->get();

        // 6. Verified Customer Reviews
        $reviews = Review::approved()
            ->with(['user', 'product'])
            ->latest()
            ->take(6)
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'author_name' => $r->reviewer_name,
                    'rating' => (int) $r->rating,
                    'title' => $r->title,
                    'comment' => $r->comment,
                    'product_name' => $r->product?->name ?? 'Beauty Product',
                    'product_image' => $r->product?->image_url ?? null,
                    'time_ago' => $r->created_at?->diffForHumans(),
                ];
            });

        // 7. Editorial / Beauty Guides
        $latestBlogs = Blog::where('is_published', true)
            ->latest()
            ->take(3)
            ->get();

        // 8. Unique Brands
        $brands = Product::active()
            ->whereNotNull('brand')
            ->distinct()
            ->pluck('brand')
            ->take(10);

        // 9. Marketplace Stats
        $stats = [
            'total_products' => Product::active()->count(),
            'happy_customers' => '25,000+',
            'original_guarantee' => '100% Genuine & Authentic',
            'average_rating' => '4.9★',
        ];

        return Inertia::render('Home', [
            'banners' => $banners,
            'categories' => $categories,
            'trendingProducts' => $trendingProducts,
            'featuredProducts' => $featuredProducts,
            'newArrivals' => $newArrivals,
            'reviews' => $reviews,
            'latestBlogs' => $latestBlogs,
            'brands' => $brands,
            'stats' => $stats,
        ]);
    }
}
