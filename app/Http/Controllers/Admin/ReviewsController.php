<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReviewsController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::with(['user', 'product']);

        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'approved') {
                $query->where('is_approved', true);
            } elseif ($request->status === 'pending') {
                $query->where('is_approved', false);
            }
        }

        if ($request->filled('rating') && $request->rating !== 'all') {
            $query->where('rating', (int) $request->rating);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('comment', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('author_name', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('product', fn($pq) => $pq->where('name', 'like', "%{$search}%"));
            });
        }

        $reviews = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total' => Review::count(),
            'pending' => Review::where('is_approved', false)->count(),
            'approved' => Review::where('is_approved', true)->count(),
            'average_rating' => round(Review::avg('rating') ?? 5.0, 1),
        ];

        return Inertia::render('Admin/Reviews/Index', [
            'reviews' => $reviews,
            'stats' => $stats,
            'filters' => $request->only(['status', 'rating', 'search']),
        ]);
    }

    public function approve(Review $review)
    {
        $review->update(['is_approved' => true]);
        $this->updateProductRating($review->product_id);

        return back()->with('success', 'Review approved successfully.');
    }

    public function reject(Review $review)
    {
        $review->update(['is_approved' => false]);
        $this->updateProductRating($review->product_id);

        return back()->with('success', 'Review unapproved.');
    }

    public function destroy(Review $review)
    {
        $productId = $review->product_id;
        $review->delete();
        $this->updateProductRating($productId);

        return back()->with('success', 'Review deleted successfully.');
    }

    private function updateProductRating($productId)
    {
        if (!$productId) return;
        $product = Product::find($productId);
        if ($product) {
            $avg = Review::where('product_id', $productId)->where('is_approved', true)->avg('rating');
            $count = Review::where('product_id', $productId)->where('is_approved', true)->count();
            $product->update([
                'rating' => round($avg ?? 5.0, 2),
                'reviews_count' => $count,
            ]);
        }
    }
}
