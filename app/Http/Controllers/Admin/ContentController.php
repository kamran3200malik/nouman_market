<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ContentController extends Controller
{
    public function banners(Request $request)
    {
        $query = Banner::with(['product', 'category']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('subtitle', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $banners = $query->orderBy('sort_order', 'asc')->latest()->paginate(12)->withQueryString();

        $stats = [
            'total_banners' => Banner::count(),
            'active_banners' => Banner::where('is_active', true)->count(),
        ];

        // Load active products for banner target picker
        $products = Product::where('is_active', true)
            ->select('id', 'name', 'price', 'brand', 'image')
            ->orderBy('name', 'asc')
            ->get();

        // Load active categories for banner target picker
        $categories = Category::where('is_active', true)
            ->select('id', 'name', 'slug', 'image')
            ->orderBy('name', 'asc')
            ->get();

        return Inertia::render('Admin/Content/Banners', [
            'banners' => $banners,
            'stats' => $stats,
            'products' => $products,
            'categories' => $categories,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function toggleBannerStatus(Banner $banner)
    {
        $banner->update([
            'is_active' => !$banner->is_active,
        ]);

        return back()->with('success', "Banner status changed to " . ($banner->is_active ? 'Active' : 'Inactive') . ".");
    }

    public function storeBanner(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'tag' => 'nullable|string|max:100',
            'image' => 'nullable|string',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'position' => 'nullable|string',
            'product_id' => 'nullable|exists:products,id',
            'category_id' => 'nullable|exists:categories,id',
            'link_url' => 'nullable|string|max:1000',
            'button_text' => 'nullable|string|max:100',
            'price' => 'nullable|numeric|min:0',
        ]);

        $imagePath = $validated['image'] ?? 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=1600&q=80';
        if ($request->hasFile('image_file')) {
            $imagePath = $request->file('image_file')->store('banners', 'public');
        }

        $validated['image'] = $imagePath;
        $validated['is_active'] = $request->boolean('is_active', true);

        Banner::create($validated);

        return redirect()->route('admin.content.banners')->with('success', 'Banner created successfully.');
    }

    public function updateBanner(Request $request, Banner $banner)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'tag' => 'nullable|string|max:100',
            'image' => 'nullable|string',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'position' => 'nullable|string',
            'product_id' => 'nullable|exists:products,id',
            'category_id' => 'nullable|exists:categories,id',
            'link_url' => 'nullable|string|max:1000',
            'button_text' => 'nullable|string|max:100',
            'price' => 'nullable|numeric|min:0',
        ]);

        if ($request->hasFile('image_file')) {
            if (!empty($banner->image) && !str_starts_with($banner->image, 'http') && Storage::disk('public')->exists($banner->image)) {
                Storage::disk('public')->delete($banner->image);
            }
            $validated['image'] = $request->file('image_file')->store('banners', 'public');
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $banner->update($validated);

        return redirect()->route('admin.content.banners')->with('success', 'Banner updated successfully.');
    }

    public function destroyBanner(Banner $banner)
    {
        if (!empty($banner->image) && !str_starts_with($banner->image, 'http') && Storage::disk('public')->exists($banner->image)) {
            Storage::disk('public')->delete($banner->image);
        }
        $banner->delete();

        return redirect()->route('admin.content.banners')->with('success', 'Banner deleted successfully.');
    }
}
