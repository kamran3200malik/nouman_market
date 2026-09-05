<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Review;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function publicIndex(Request $request)
    {
        $query = Product::active()->with(['categoryRelation', 'user']);

        // 1. Search Query
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('badge', 'like', "%{$search}%");
            });
        }

        // 2. Category Filter
        if ($categorySlug = $request->input('category')) {
            if ($categorySlug !== 'all' && $categorySlug !== '') {
                $query->where(function ($q) use ($categorySlug) {
                    $q->where('category', $categorySlug)
                      ->orWhereHas('categoryRelation', function ($cq) use ($categorySlug) {
                          $cq->where('slug', $categorySlug)->orWhere('name', $categorySlug);
                      });
                });
            }
        }

        // 3. Brand Filter
        if ($brand = $request->input('brand')) {
            if ($brand !== 'all' && $brand !== '') {
                $query->where('brand', $brand);
            }
        }

        // 4. Trending Filter
        if ($request->boolean('trending')) {
            $query->trending();
        }

        // 5. In Stock Only
        if ($request->boolean('in_stock')) {
            $query->inStock();
        }

        // 6. Rating Filter
        if ($minRating = $request->input('min_rating')) {
            $query->where('rating', '>=', (float) $minRating);
        }

        // 7. Price Filter
        if ($minPrice = $request->input('min_price')) {
            $query->where('price', '>=', (float) $minPrice);
        }
        if ($maxPrice = $request->input('max_price')) {
            $query->where('price', '<=', (float) $maxPrice);
        }

        // 8. Sorting
        $sortBy = $request->input('sort_by', 'recommended');
        switch ($sortBy) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'rating':
            case 'rating_desc':
                $query->orderBy('rating', 'desc')->orderBy('reviews_count', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'discount':
            case 'discount_desc':
                $query->orderByRaw('((original_price - price) / original_price) DESC');
                break;
            case 'trending':
                $query->orderBy('is_trending', 'desc')->orderBy('rating', 'desc');
                break;
            case 'recommended':
            default:
                $query->orderBy('is_trending', 'desc')
                      ->orderBy('sort_order', 'asc')
                      ->orderBy('rating', 'desc');
                break;
        }

        // Paginated results (12 per page)
        $products = $query->paginate(12)->withQueryString();

        // Categories with counts
        $categories = Category::active()
            ->withCount(['products' => function ($q) {
                $q->active();
            }])
            ->orderBy('sort_order', 'asc')
            ->get();

        // Available brands
        $brands = Product::active()
            ->whereNotNull('brand')
            ->distinct()
            ->pluck('brand')
            ->sort()
            ->values();

        // Price range stats
        $priceStats = [
            'min' => (int) (Product::active()->min('price') ?? 500),
            'max' => (int) (Product::active()->max('price') ?? 50000),
        ];

        // Spotlight / Trending products
        $trendingProducts = Product::active()
            ->trending()
            ->orderBy('sort_order', 'asc')
            ->limit(4)
            ->get();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
            'priceStats' => $priceStats,
            'trendingProducts' => $trendingProducts,
            'totalProductsCount' => Product::active()->count(),
            'shipping_settings' => [
                'standard_fee' => (float) Setting::getValue('shipping_fee_standard', 250),
                'free_threshold' => (float) Setting::getValue('shipping_free_threshold', 5000),
                'free_enabled' => Setting::getValue('shipping_free_enabled', '1') === '1',
                'carrier_name' => Setting::getValue('shipping_carrier_name', 'TCS Express'),
                'estimated_days' => Setting::getValue('shipping_estimated_days', '2 - 4 Business Days'),
            ],
            'filters' => [
                'search' => $request->input('search', ''),
                'category' => $request->input('category', ''),
                'brand' => $request->input('brand', ''),
                'sort_by' => $sortBy,
                'min_price' => $request->input('min_price', ''),
                'max_price' => $request->input('max_price', ''),
                'min_rating' => $request->input('min_rating', ''),
                'trending' => $request->boolean('trending'),
                'in_stock' => $request->boolean('in_stock'),
            ],
        ]);
    }

    public function show(string $slug)
    {
        $product = Product::active()
            ->where('slug', $slug)
            ->with(['categoryRelation', 'images', 'user', 'reviews.user'])
            ->firstOrFail();

        $relatedProducts = Product::active()
            ->where('id', '!=', $product->id)
            ->where(function ($q) use ($product) {
                if ($product->category_id) {
                    $q->where('category_id', $product->category_id);
                }
                if ($product->brand) {
                    $q->orWhere('brand', $product->brand);
                }
            })
            ->take(4)
            ->get();

        return Inertia::render('Products/Show', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
            'shipping_settings' => [
                'standard_fee' => (float) Setting::getValue('shipping_fee_standard', 250),
                'free_threshold' => (float) Setting::getValue('shipping_free_threshold', 5000),
                'estimated_days' => Setting::getValue('shipping_estimated_days', '2 - 4 Business Days'),
            ],
        ]);
    }

    public function storeReview(Request $request, Product $product)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'required|string|min:5|max:2000',
            'author_name' => 'nullable|string|max:255',
            'author_email' => 'nullable|email|max:255',
        ]);

        $user = Auth::user();

        Review::create([
            'product_id' => $product->id,
            'user_id' => $user?->id,
            'author_name' => $user?->name ?? $validated['author_name'] ?? 'Verified Customer',
            'author_email' => $user?->email ?? $validated['author_email'] ?? null,
            'rating' => $validated['rating'],
            'title' => $validated['title'] ?? null,
            'comment' => $validated['comment'],
            'verified_purchase' => $user ? true : false,
            'is_approved' => true,
        ]);

        // Recalculate product rating
        $avgRating = Review::where('product_id', $product->id)->where('is_approved', true)->avg('rating');
        $reviewsCount = Review::where('product_id', $product->id)->where('is_approved', true)->count();

        $product->update([
            'rating' => round($avgRating, 2),
            'reviews_count' => $reviewsCount,
        ]);

        return back()->with('success', 'Thank you for your review! It has been posted successfully.');
    }

    // --- ADMIN METHODS ---

    public function index()
    {
        $products = Product::with(['categoryRelation', 'user'])
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
        ]);
    }

    public function create()
    {
        $categories = Category::active()->orderBy('name')->get();
        return Inertia::render('Admin/Products/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products',
            'sku' => 'nullable|string|max:255|unique:products',
            'category_id' => 'nullable|exists:categories,id',
            'category' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'image' => 'nullable',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'rating' => 'nullable|numeric|min:1|max:5',
            'stock_quantity' => 'nullable|integer|min:0',
            'in_stock' => 'boolean',
            'badge' => 'nullable|string|max:255',
            'short_features' => 'nullable|array',
            'usage_instructions' => 'nullable|string',
            'ingredients' => 'nullable|string',
            'is_featured' => 'boolean',
            'is_trending' => 'boolean',
            'is_active' => 'boolean',
            'approval_status' => 'nullable|in:pending,approved,rejected',
            'sort_order' => 'integer|min:0',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']) . '-' . rand(100, 999);
        }

        if (empty($validated['sku'])) {
            $validated['sku'] = 'SKU-' . strtoupper(Str::random(6));
        }

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
            $validated['image'] = $imagePath;
        }

        $validated['user_id'] = Auth::id();
        $validated['approval_status'] = 'approved';

        $product = Product::create($validated);

        if (!empty($product->image)) {
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $product->image,
                'is_primary' => true,
                'sort_order' => 1,
            ]);
        }

        return redirect()->route('admin.products.index')
            ->with('success', 'Product created successfully.');
    }

    public function edit(Product $product)
    {
        $categories = Category::active()->orderBy('name')->get();
        $product->load(['images', 'categoryRelation']);

        return Inertia::render('Admin/Products/Edit', [
            'product' => $product,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug,' . $product->id,
            'sku' => 'nullable|string|max:255|unique:products,sku,' . $product->id,
            'category_id' => 'nullable|exists:categories,id',
            'category' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'image' => 'nullable',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'rating' => 'nullable|numeric|min:1|max:5',
            'stock_quantity' => 'nullable|integer|min:0',
            'in_stock' => 'boolean',
            'badge' => 'nullable|string|max:255',
            'short_features' => 'nullable|array',
            'usage_instructions' => 'nullable|string',
            'ingredients' => 'nullable|string',
            'is_featured' => 'boolean',
            'is_trending' => 'boolean',
            'is_active' => 'boolean',
            'approval_status' => 'nullable|in:pending,approved,rejected',
            'sort_order' => 'integer|min:0',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        if ($request->hasFile('image')) {
            if (!empty($product->image) && !str_starts_with($product->image, 'http') && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $imagePath = $request->file('image')->store('products', 'public');
            $validated['image'] = $imagePath;
        } elseif (!isset($validated['image']) || is_null($validated['image']) || $validated['image'] === '') {
            unset($validated['image']);
        }

        $product->update($validated);

        return redirect()->route('admin.products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        if (!empty($product->image) && !str_starts_with($product->image, 'http') && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }

    public function toggleHome(Product $product)
    {
        $newState = !$product->is_trending;
        $product->update([
            'is_trending' => $newState,
            'approval_status' => $newState ? 'approved' : $product->approval_status,
        ]);

        return back()->with('success', $newState 
            ? "Product '{$product->name}' is now featured on the Home page." 
            : "Product '{$product->name}' is removed from Home page spotlight."
        );
    }

    public function toggleApproval(Product $product)
    {
        $isCurrentlyApproved = $product->approval_status === 'approved';
        $newStatus = $isCurrentlyApproved ? 'pending' : 'approved';
        
        $product->update([
            'approval_status' => $newStatus,
        ]);

        return back()->with('success', "Product approval status changed to {$newStatus}.");
    }

    public function toggleStatus(Product $product)
    {
        $newStatus = !$product->is_active;
        $product->update([
            'is_active' => $newStatus,
        ]);

        return back()->with('success', $newStatus 
            ? "Product '{$product->name}' is now Active." 
            : "Product '{$product->name}' is now Inactive."
        );
    }
}
