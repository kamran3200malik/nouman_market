<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Commission;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentsController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with([
            'booking.customer',
            'booking.artistProfile.user',
            'booking.artistProfile.city',
            'booking.service.category'
        ]);

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by payment method
        if ($request->filled('method') && $request->method !== 'all') {
            $query->where('payment_method', $request->method);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Search
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%")
                  ->orWhereHas('booking', function ($bq) use ($search) {
                      $bq->where('booking_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($cq) use ($search) {
                            $cq->where('name', 'like', "%{$search}%")
                              ->orWhere('email', 'like', "%{$search}%")
                              ->orWhere('phone', 'like', "%{$search}%");
                        })
                        ->orWhereHas('artistProfile', function ($aq) use ($search) {
                            $aq->where('business_name', 'like', "%{$search}%")
                              ->orWhereHas('user', function ($uq) use ($search) {
                                  $uq->where('name', 'like', "%{$search}%");
                              });
                        })
                        ->orWhereHas('service', function ($sq) use ($search) {
                            $sq->where('name', 'like', "%{$search}%");
                        });
                  });
            });
        }

        $payments = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total_volume' => (float) Payment::where('status', 'completed')->sum('amount'),
            'platform_commission' => (float) Payment::where('status', 'completed')->sum('commission_amount'),
            'artist_net' => (float) Payment::where('status', 'completed')->sum('net_amount'),
            'pending_volume' => (float) Payment::where('status', 'pending')->sum('amount'),
            'total_count' => Payment::count(),
            'completed_count' => Payment::where('status', 'completed')->count(),
            'pending_count' => Payment::where('status', 'pending')->count(),
            'failed_count' => Payment::where('status', 'failed')->count(),
            'refunded_count' => Payment::where('status', 'refunded')->count(),
        ];

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments,
            'stats' => $stats,
            'filters' => $request->only(['status', 'method', 'search', 'from_date', 'to_date']),
        ]);
    }

    public function markPaid(Payment $payment)
    {
        $payment->update([
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        if ($payment->booking) {
            $payment->booking->update([
                'payment_status' => 'paid',
            ]);
        }

        return redirect()->route('admin.payments.index')->with('success', 'Transaction marked as completed/paid successfully.');
    }

    public function refund(Request $request, Payment $payment)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $payment->update([
            'status' => 'refunded',
            'notes' => ($payment->notes ? $payment->notes . "\n" : '') . "Refund reason: " . $request->reason,
        ]);

        if ($payment->booking) {
            $payment->booking->update([
                'payment_status' => 'refunded',
            ]);
        }

        return redirect()->route('admin.payments.index')->with('success', 'Payment marked as refunded.');
    }

    public function payouts(Request $request)
    {
        $query = Payout::with(['artistProfile.user', 'artistProfile.city']);

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhereHas('artistProfile', function ($aq) use ($search) {
                      $aq->where('business_name', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($uq) use ($search) {
                            $uq->where('name', 'like', "%{$search}%");
                        });
                  });
            });
        }

        $payouts = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total_disbursed' => (float) Payout::where('status', 'completed')->sum('amount'),
            'pending_disbursement' => (float) Payout::where('status', 'pending')->sum('amount'),
            'pending_count' => Payout::where('status', 'pending')->count(),
            'completed_count' => Payout::where('status', 'completed')->count(),
            'rejected_count' => Payout::where('status', 'rejected')->count(),
        ];

        return Inertia::render('Admin/Payments/Payouts', [
            'payouts' => $payouts,
            'stats' => $stats,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function approvePayout(Payout $payout)
    {
        $payout->update([
            'status' => 'completed',
            'processed_at' => now(),
        ]);

        return redirect()->route('admin.payments.payouts')->with('success', 'Payout approved and marked as disbursed.');
    }

    public function rejectPayout(Request $request, Payout $payout)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $payout->update([
            'status' => 'rejected',
            'notes' => ($payout->notes ? $payout->notes . "\n" : '') . "Rejection reason: " . $request->reason,
        ]);

        return redirect()->route('admin.payments.payouts')->with('success', 'Payout request rejected.');
    }


    public function commissions(Request $request)
    {
        $query = Commission::with(['artistProfile.user', 'category']);

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('commission_type', $request->type);
        }

        $commissions = $query->latest()->paginate(20)->withQueryString();
        $categories = Category::select('id', 'name')->get();

        // Query artists and their billing model
        $artistsQuery = \App\Models\ArtistProfile::with(['user', 'city'])
            ->select([
                'id', 'user_id', 'city_id', 'business_name', 'profile_image',
                'billing_model', 'commission_rate', 'subscription_plan_name',
                'subscription_monthly_fee', 'subscription_status',
                'subscription_started_at', 'subscription_expires_at', 'subscription_auto_renew'
            ]);

        if ($request->filled('artist_search')) {
            $search = trim($request->artist_search);
            $artistsQuery->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('billing_model') && $request->billing_model !== 'all') {
            $artistsQuery->where('billing_model', $request->billing_model);
        }

        $artists = $artistsQuery->latest()->paginate(15, ['*'], 'artist_page')->withQueryString();

        $stats = [
            'commission_artists' => \App\Models\ArtistProfile::where('billing_model', 'commission')->count(),
            'subscription_artists' => \App\Models\ArtistProfile::where('billing_model', 'subscription')->count(),
            'hybrid_artists' => \App\Models\ArtistProfile::where('billing_model', 'hybrid')->count(),
            'active_subscriptions' => \App\Models\ArtistProfile::where('billing_model', 'subscription')
                ->whereIn('subscription_status', ['active', 'trial'])
                ->count(),
            'mrr' => (float) \App\Models\ArtistProfile::where('billing_model', 'subscription')
                ->whereIn('subscription_status', ['active', 'trial'])
                ->sum('subscription_monthly_fee'),
        ];

        return Inertia::render('Admin/Payments/Commissions', [
            'commissions' => $commissions,
            'categories' => $categories,
            'artists' => $artists,
            'stats' => $stats,
            'filters' => $request->only(['type', 'artist_search', 'billing_model']),
        ]);
    }
}
