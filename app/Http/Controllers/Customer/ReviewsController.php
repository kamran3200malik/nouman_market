<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Product;
use App\Models\ProductOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ReviewsController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Review::where('user_id', $user->id)
            ->with(['product.categoryRelation']);

        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('comment', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhereHas('product', fn($p) => $p->where('name', 'like', "%{$search}%"));
            });
        }

        $reviews = $query->latest()->paginate(10)->withQueryString();

        // Compute review stats
        $allReviews = Review::where('user_id', $user->id)->get();
        $totalReviews = $allReviews->count();
        $avgRating = $totalReviews > 0 ? round($allReviews->avg('rating'), 1) : 5.0;
        $fiveStarCount = $allReviews->where('rating', 5)->count();

        return Inertia::render('Customer/Reviews/Index', [
            'reviews' => $reviews,
            'pendingReviewBookings' => [],
            'stats' => [
                'total_reviews' => $totalReviews,
                'avg_rating' => $avgRating,
                'five_star_count' => $fiveStarCount,
                'pending_count' => 0,
            ],
            'filters' => [
                'search' => $request->search ?? '',
                'rating' => $request->rating ?? '',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'required|string|min:5|max:2000',
        ]);

        $product = Product::findOrFail($request->product_id);

        $review = Review::create([
            'user_id' => Auth::id(),
            'product_id' => $product->id,
            'rating' => $request->rating,
            'title' => $request->title,
            'comment' => $request->comment,
            'reviewer_name' => Auth::user()->name,
            'reviewer_email' => Auth::user()->email,
            'is_approved' => true,
            'is_verified_purchase' => true,
        ]);

        // Recalculate product rating avg & count
        $avg = Review::where('product_id', $product->id)->where('is_approved', true)->avg('rating');
        $cnt = Review::where('product_id', $product->id)->where('is_approved', true)->count();
        $product->update([
            'rating' => round($avg ?: 5.0, 1),
            'reviews_count' => $cnt,
        ]);

        return redirect()->back()
            ->with('success', 'Your product review and rating have been posted successfully!');
    }

    public function destroy(Review $review)
    {
        if ($review->user_id !== Auth::id()) {
            abort(403);
        }

        $productId = $review->product_id;
        $review->delete();

        if ($productId) {
            $product = Product::find($productId);
            if ($product) {
                $avg = Review::where('product_id', $product->id)->where('is_approved', true)->avg('rating');
                $cnt = Review::where('product_id', $product->id)->where('is_approved', true)->count();
                $product->update([
                    'rating' => round($avg ?: 5.0, 1),
                    'reviews_count' => $cnt,
                ]);
            }
        }

        return redirect()->route('customer.reviews.index')
            ->with('success', 'Review removed successfully.');
    }
}
