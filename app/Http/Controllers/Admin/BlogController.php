<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class BlogController extends Controller
{
    /**
     * Display a listing of blog posts.
     */
    public function index(Request $request)
    {
        $query = Blog::query();

        // Search term
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Category filter
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        // Status filter
        if ($request->filled('status')) {
            if ($request->status === 'published') {
                $query->where('is_published', true);
            } elseif ($request->status === 'draft') {
                $query->where('is_published', false);
            } elseif ($request->status === 'featured') {
                $query->where('is_featured', true);
            }
        }

        // Sorting
        $sort = $request->input('sort', 'newest');
        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'views':
                $query->orderBy('views_count', 'desc');
                break;
            case 'title':
                $query->orderBy('title', 'asc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $blogs = $query->paginate(12)->withQueryString();

        // Distinct categories for filter
        $categories = Blog::whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category');

        $stats = [
            'total_blogs' => Blog::count(),
            'published_blogs' => Blog::where('is_published', true)->count(),
            'draft_blogs' => Blog::where('is_published', false)->count(),
            'featured_blogs' => Blog::where('is_featured', true)->count(),
            'total_views' => (int) Blog::sum('views_count'),
        ];

        return Inertia::render('Admin/Blogs/Index', [
            'blogs' => $blogs,
            'stats' => $stats,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category', 'status', 'sort']),
        ]);
    }

    /**
     * Store a newly created blog post.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blogs,slug',
            'category' => 'nullable|string|max:100',
            'excerpt' => 'nullable|string|max:1500',
            'content' => 'required|string',
            'author_name' => 'nullable|string|max:255',
            'tags' => 'nullable',
            'read_time' => 'nullable|integer|min:1|max:180',
            'is_published' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:1500',
            'meta_keywords' => 'nullable|string|max:500',
            'image' => 'nullable',
            'existing_gallery' => 'nullable|array',
            'gallery_files.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:8192',
            'gallery_areas' => 'nullable|array',
            'gallery_captions' => 'nullable|array',
        ]);

        // Auto generate slug
        if (empty($validated['slug'])) {
            $baseSlug = Str::slug($validated['title']);
            $slug = $baseSlug;
            $counter = 1;
            while (Blog::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            $validated['slug'] = $slug;
        }

        // Auto calculate read time if omitted
        if (empty($validated['read_time'])) {
            $validated['read_time'] = Blog::estimateReadingTime($validated['content']);
        }

        // Process tags
        if (isset($validated['tags']) && is_string($validated['tags'])) {
            $validated['tags'] = array_values(array_filter(array_map('trim', explode(',', $validated['tags']))));
        }

        // Primary cover image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('blogs/covers', 'public');
            $validated['image'] = $imagePath;
        }

        // Process multi-area gallery images
        $galleryImages = [];

        // Upload new area gallery files
        if ($request->hasFile('gallery_files')) {
            $files = $request->file('gallery_files');
            $areas = $request->input('gallery_areas', []);
            $captions = $request->input('gallery_captions', []);

            foreach ($files as $index => $file) {
                if ($file && $file->isValid()) {
                    $storedPath = $file->store('blogs/gallery', 'public');
                    $galleryImages[] = [
                        'path' => $storedPath,
                        'url' => Storage::url($storedPath),
                        'area' => $areas[$index] ?? 'gallery',
                        'caption' => $captions[$index] ?? '',
                    ];
                }
            }
        }

        $validated['gallery_images'] = $galleryImages;

        // Publication date
        $isPublished = (bool) ($validated['is_published'] ?? true);
        $validated['is_published'] = $isPublished;
        $validated['is_featured'] = (bool) ($validated['is_featured'] ?? false);
        $validated['published_at'] = $isPublished ? now() : null;
        $validated['author_name'] = $validated['author_name'] ?: ($request->user()->name ?? 'BeautyBook Editor');

        Blog::create($validated);

        return redirect()->route('admin.blogs.index')
            ->with('success', 'Blog article "' . $validated['title'] . '" created successfully.');
    }

    /**
     * Update an existing blog post.
     */
    public function update(Request $request, Blog $blog)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blogs,slug,' . $blog->id,
            'category' => 'nullable|string|max:100',
            'excerpt' => 'nullable|string|max:1500',
            'content' => 'required|string',
            'author_name' => 'nullable|string|max:255',
            'tags' => 'nullable',
            'read_time' => 'nullable|integer|min:1|max:180',
            'is_published' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:1500',
            'meta_keywords' => 'nullable|string|max:500',
            'image' => 'nullable',
            'remove_image' => 'nullable|boolean',
            'existing_gallery' => 'nullable|array',
            'gallery_files.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:8192',
            'gallery_areas' => 'nullable|array',
            'gallery_captions' => 'nullable|array',
        ]);

        if (empty($validated['slug'])) {
            $baseSlug = Str::slug($validated['title']);
            $slug = $baseSlug;
            $counter = 1;
            while (Blog::where('slug', $slug)->where('id', '!=', $blog->id)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            $validated['slug'] = $slug;
        }

        if (empty($validated['read_time'])) {
            $validated['read_time'] = Blog::estimateReadingTime($validated['content']);
        }

        if (isset($validated['tags']) && is_string($validated['tags'])) {
            $validated['tags'] = array_values(array_filter(array_map('trim', explode(',', $validated['tags']))));
        }

        // Primary cover image handle
        if ($request->boolean('remove_image')) {
            if ($blog->image && Storage::disk('public')->exists($blog->image)) {
                Storage::disk('public')->delete($blog->image);
            }
            $validated['image'] = null;
        } elseif ($request->hasFile('image')) {
            if ($blog->image && Storage::disk('public')->exists($blog->image)) {
                Storage::disk('public')->delete($blog->image);
            }
            $imagePath = $request->file('image')->store('blogs/covers', 'public');
            $validated['image'] = $imagePath;
        } else {
            unset($validated['image']); // Retain existing
        }

        // Process Area Gallery Images
        $galleryImages = [];

        // 1. Existing retained gallery items
        if ($request->filled('existing_gallery')) {
            $existing = $request->input('existing_gallery');
            if (is_array($existing)) {
                foreach ($existing as $item) {
                    if (is_array($item) && (!empty($item['path']) || !empty($item['url']))) {
                        $path = $item['path'] ?? $item['url'];
                        $galleryImages[] = [
                            'path' => $path,
                            'url' => (!empty($item['url']) && str_starts_with($item['url'], 'http')) ? $item['url'] : Storage::url($path),
                            'area' => $item['area'] ?? 'gallery',
                            'caption' => $item['caption'] ?? '',
                        ];
                    }
                }
            }
        }

        // 2. Newly uploaded area gallery files
        if ($request->hasFile('gallery_files')) {
            $files = $request->file('gallery_files');
            $areas = $request->input('gallery_areas', []);
            $captions = $request->input('gallery_captions', []);

            foreach ($files as $index => $file) {
                if ($file && $file->isValid()) {
                    $storedPath = $file->store('blogs/gallery', 'public');
                    $galleryImages[] = [
                        'path' => $storedPath,
                        'url' => Storage::url($storedPath),
                        'area' => $areas[$index] ?? 'gallery',
                        'caption' => $captions[$index] ?? '',
                    ];
                }
            }
        }

        $validated['gallery_images'] = $galleryImages;

        // Publication status logic
        $isPublished = (bool) ($validated['is_published'] ?? $blog->is_published);
        $validated['is_published'] = $isPublished;
        $validated['is_featured'] = (bool) ($validated['is_featured'] ?? $blog->is_featured);

        if ($isPublished && !$blog->published_at) {
            $validated['published_at'] = now();
        }

        $blog->update($validated);

        return redirect()->route('admin.blogs.index')
            ->with('success', 'Blog article "' . $blog->title . '" updated successfully.');
    }

    /**
     * Remove the specified blog post.
     */
    public function destroy(Blog $blog)
    {
        // Remove primary image
        if ($blog->image && Storage::disk('public')->exists($blog->image)) {
            Storage::disk('public')->delete($blog->image);
        }

        // Remove gallery images
        if (is_array($blog->gallery_images)) {
            foreach ($blog->gallery_images as $img) {
                $path = is_array($img) ? ($img['path'] ?? null) : $img;
                if ($path && Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
        }

        $title = $blog->title;
        $blog->delete();

        return redirect()->route('admin.blogs.index')
            ->with('success', 'Blog article "' . $title . '" was deleted permanently.');
    }

    /**
     * Toggle the publication status of a blog post.
     */
    public function toggleStatus(Blog $blog)
    {
        $newStatus = !$blog->is_published;
        $blog->update([
            'is_published' => $newStatus,
            'published_at' => ($newStatus && !$blog->published_at) ? now() : $blog->published_at,
        ]);

        return redirect()->route('admin.blogs.index')
            ->with('success', 'Blog status changed to ' . ($newStatus ? 'Published' : 'Draft') . '.');
    }

    /**
     * Toggle featured status of a blog post.
     */
    public function toggleFeatured(Blog $blog)
    {
        $newFeatured = !$blog->is_featured;
        $blog->update([
            'is_featured' => $newFeatured,
        ]);

        return redirect()->route('admin.blogs.index')
            ->with('success', 'Blog article ' . ($newFeatured ? 'marked as Featured Spotlight' : 'removed from Featured') . '.');
    }

    /**
     * Direct image uploader for rich-content in-editor placement
     */
    public function uploadInlineImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:8192',
        ]);

        $path = $request->file('image')->store('blogs/inline', 'public');

        return response()->json([
            'url' => Storage::url($path),
            'path' => $path,
        ]);
    }
}
