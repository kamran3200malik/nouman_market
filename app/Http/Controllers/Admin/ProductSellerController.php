<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArtistProfile;
use App\Services\AppNotificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductSellerController extends Controller
{
    /**
     * Display artist product seller applications and commission authorizations.
     */
    public function index(Request $request)
    {
        $query = ArtistProfile::with(['user', 'city'])
            ->whereNotNull('seller_agreement_signed_at')
            ->orWhere('product_seller_status', '!=', 'none');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                  ->orWhere('seller_store_name', 'like', "%{$search}%")
                  ->orWhere('seller_bank_title', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        if ($status = $request->input('status')) {
            if ($status !== 'all') {
                $query->where('product_seller_status', $status);
            }
        }

        $sellers = $query->latest('seller_agreement_signed_at')->paginate(12)->withQueryString();

        $stats = [
            'total_requests' => ArtistProfile::where('product_seller_status', '!=', 'none')->count(),
            'pending_requests' => ArtistProfile::where('product_seller_status', 'pending')->count(),
            'approved_sellers' => ArtistProfile::where('product_seller_status', 'approved')->count(),
            'rejected_sellers' => ArtistProfile::where('product_seller_status', 'rejected')->count(),
        ];

        return Inertia::render('Admin/Products/Sellers', [
            'sellers' => $sellers,
            'stats' => $stats,
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $request->input('status', 'all'),
            ],
        ]);
    }

    /**
     * Authorize an artist to sell products on the marketplace with signed commission rate.
     */
    public function authorizeSeller(Request $request, ArtistProfile $artist)
    {
        $validated = $request->validate([
            'commission_rate' => 'required|numeric|min:0|max:100',
        ]);

        $artist->update([
            'product_seller_status' => 'approved',
            'product_commission_rate' => $validated['commission_rate'],
            'seller_rejection_reason' => null,
        ]);

        // Send instant notification to the artist
        AppNotificationService::notifyArtistSellerApproved($artist);

        return redirect()->route('admin.products.sellers')->with('success', "Salon '{$artist->business_name}' is now authorized to sell products with {$validated['commission_rate']}% platform commission.");
    }

    /**
     * Reject or suspend an artist's seller application.
     */
    public function reject(Request $request, ArtistProfile $artist)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $artist->update([
            'product_seller_status' => 'rejected',
            'seller_rejection_reason' => $validated['rejection_reason'],
        ]);

        AppNotificationService::notifyArtistSellerRejected($artist, $validated['rejection_reason']);

        return redirect()->route('admin.products.sellers')->with('success', "Seller request for '{$artist->business_name}' has been rejected.");
    }
}

