<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\ReviewImage;
use App\Models\Booking;
use App\Models\ArtistProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ReviewsController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Review::where('customer_id', $user->id)
            ->with([
                'artistProfile.user',
                'artistProfile.city',
                'booking.service',
                'images'
            ]);

        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('review', 'like', "%{$search}%")
                  ->orWhereHas('artistProfile', fn($a) => $a->where('business_name', 'like', "%{$search}%"))
                  ->orWhereHas('booking.service', fn($s) => $s->where('name', 'like', "%{$search}%"));
            });
        }

        $reviews = $query->latest()->paginate(10)->withQueryString();

        // Get completed bookings that have not been reviewed yet
        $reviewedBookingIds = Review::where('customer_id', $user->id)->pluck('booking_id')->toArray();

        $pendingReviewBookings = Booking::where('customer_id', $user->id)
            ->where('status', 'completed')
            ->whereNotIn('id', $reviewedBookingIds)
            ->with(['artistProfile.user', 'service'])
            ->latest()
            ->get()
            ->map(function ($b) {
                return [
                    'id' => $b->id,
                    'booking_number' => $b->booking_number,
                    'service_name' => $b->service?->name ?? 'Salon Service',
                    'booking_date' => $b->booking_date,
                    'artist' => [
                        'id' => $b->artistProfile?->id,
                        'business_name' => $b->artistProfile?->business_name ?? 'Beauty Artist',
                        'city' => $b->artistProfile?->city?->name,
                    ],
                ];
            });

        // Compute review stats
        $allReviews = Review::where('customer_id', $user->id)->get();
        $totalReviews = $allReviews->count();
        $avgRating = $totalReviews > 0 ? round($allReviews->avg('rating'), 1) : 5.0;
        $fiveStarCount = $allReviews->where('rating', 5)->count();

        return Inertia::render('Customer/Reviews/Index', [
            'reviews' => $reviews,
            'pendingReviewBookings' => $pendingReviewBookings,
            'stats' => [
                'total_reviews' => $totalReviews,
                'avg_rating' => $avgRating,
                'five_star_count' => $fiveStarCount,
                'pending_count' => $pendingReviewBookings->count(),
            ],
            'filters' => [
                'search' => $request->search ?? '',
                'rating' => $request->rating ?? '',
            ],
        ]);
    }

    public function create(Booking $booking)
    {
        if ($booking->customer_id !== Auth::id()) {
            abort(403);
        }

        if ($booking->status !== 'completed') {
            return redirect()->route('customer.reviews.index')->with('error', 'You can only review completed appointments.');
        }

        $existing = Review::where('booking_id', $booking->id)->where('customer_id', Auth::id())->first();
        if ($existing) {
            return redirect()->route('customer.reviews.index')->with('error', 'You have already reviewed this booking.');
        }

        $booking->load(['artistProfile.user', 'service']);

        return Inertia::render('Customer/Reviews/Create', [
            'booking' => $booking,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'required|string|min:5|max:2000',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072',
        ]);

        $booking = Booking::findOrFail($request->booking_id);

        if ($booking->customer_id !== Auth::id()) {
            return back()->with('error', 'Unauthorized action.');
        }

        if ($booking->status !== 'completed') {
            return back()->with('error', 'You can only review completed bookings.');
        }

        $existing = Review::where('booking_id', $booking->id)->where('customer_id', Auth::id())->first();
        if ($existing) {
            return back()->with('error', 'You have already reviewed this appointment.');
        }

        $review = Review::create([
            'customer_id' => Auth::id(),
            'artist_profile_id' => $booking->artist_profile_id,
            'booking_id' => $booking->id,
            'rating' => $request->rating,
            'review' => $request->review,
            'is_verified' => true,
            'is_approved' => true,
            'approved_at' => now(),
        ]);

        // Upload photos
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('reviews', 'public');
                ReviewImage::create([
                    'review_id' => $review->id,
                    'image_path' => $path,
                ]);
            }
        }

        // Recalculate artist rating avg & count
        $artist = ArtistProfile::find($booking->artist_profile_id);
        if ($artist) {
            $avg = Review::where('artist_profile_id', $artist->id)->where('is_approved', true)->avg('rating');
            $cnt = Review::where('artist_profile_id', $artist->id)->where('is_approved', true)->count();
            $artist->update([
                'rating_avg' => round($avg ?: 5.0, 2),
                'review_count' => $cnt,
            ]);
        }

        // Dispatch notification to artist and admin
        \App\Services\AppNotificationService::notifyNewReview($review);

        return redirect()->route('customer.reviews.index')
            ->with('success', 'Your review and rating have been posted successfully! Thank you for your feedback.');
    }

    public function edit(Review $review)
    {
        if ($review->customer_id !== Auth::id()) {
            abort(403);
        }

        $review->load(['artistProfile.user', 'booking.service', 'images']);

        return Inertia::render('Customer/Reviews/Edit', [
            'review' => $review,
        ]);
    }

    public function update(Request $request, Review $review)
    {
        if ($review->customer_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'required|string|min:5|max:2000',
        ]);

        $review->update([
            'rating' => $request->rating,
            'review' => $request->review,
        ]);

        // Recalculate artist rating
        $artist = ArtistProfile::find($review->artist_profile_id);
        if ($artist) {
            $avg = Review::where('artist_profile_id', $artist->id)->where('is_approved', true)->avg('rating');
            $artist->update([
                'rating_avg' => round($avg ?: 5.0, 2),
            ]);
        }

        return redirect()->route('customer.reviews.index')
            ->with('success', 'Review updated successfully.');
    }

    public function destroy(Review $review)
    {
        if ($review->customer_id !== Auth::id()) {
            abort(403);
        }

        $artistId = $review->artist_profile_id;

        foreach ($review->images as $image) {
            if ($image->image_path) {
                Storage::disk('public')->delete($image->image_path);
            }
            $image->delete();
        }

        $review->delete();

        // Recalculate artist rating
        $artist = ArtistProfile::find($artistId);
        if ($artist) {
            $avg = Review::where('artist_profile_id', $artist->id)->where('is_approved', true)->avg('rating');
            $cnt = Review::where('artist_profile_id', $artist->id)->where('is_approved', true)->count();
            $artist->update([
                'rating_avg' => round($avg ?: 5.0, 2),
                'review_count' => $cnt,
            ]);
        }

        return redirect()->route('customer.reviews.index')
            ->with('success', 'Review removed successfully.');
    }
}
