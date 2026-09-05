<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BlogController extends Controller
{
    /**
     * Display a listing of published blogs.
     */
    public function index(Request $request)
    {
        $query = Blog::published();

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('category') && $request->category !== 'all') {
            $query->byCategory($request->category);
        }

        $featured = Blog::published()->featured()->latest()->take(3)->get();
        $blogs = $query->latest('published_at')->paginate(6)->withQueryString();

        $categories = Blog::published()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category');

        return Inertia::render('Blogs/Index', [
            'blogs' => $blogs,
            'featured' => $featured,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category']),
        ]);
    }

    /**
     * Display the specified blog post.
     */
    public function show(string $slug)
    {
        $blog = Blog::published()->where('slug', $slug)->firstOrFail();
        $blog->recordView();

        $relatedBlogs = Blog::published()
            ->where('id', '!=', $blog->id)
            ->when($blog->category, fn($q) => $q->where('category', $blog->category))
            ->latest('published_at')
            ->take(3)
            ->get();

        return Inertia::render('Blogs/Show', [
            'blog' => $blog,
            'relatedBlogs' => $relatedBlogs,
        ]);
    }
}
