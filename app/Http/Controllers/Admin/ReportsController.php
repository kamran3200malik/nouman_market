<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ArtistProfile;
use App\Models\Booking;
use App\Models\Service;
use App\Models\Review;
use App\Models\Category;
use App\Models\Payment;
use App\Models\City;
use App\Models\Payout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->period ?? '30';
        $isAllTime = ($period === 'all' || $period === 'all_time');

        if ($isAllTime) {
            $startDate = now()->subYears(10)->startOfDay();
            $endDate = now()->endOfDay();
            $prevStartDate = now()->subYears(20)->startOfDay();
            $prevEndDate = now()->subYears(10)->endOfDay();
        } else {
            $days = is_numeric($period) ? (int)$period : 30;
            if ($days <= 0) $days = 30;
            $startDate = now()->subDays($days)->startOfDay();
            $endDate = now()->endOfDay();
            $prevStartDate = now()->subDays($days * 2)->startOfDay();
            $prevEndDate = now()->subDays($days)->endOfDay();
        }

        // ==========================================
        // 1. FINANCIAL & REVENUE INTELLIGENCE
        // ==========================================
        $currentRevenue = (float) Booking::where('status', 'completed')
            ->when(!$isAllTime, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]))
            ->sum('total_amount');

        $prevRevenue = (float) Booking::where('status', 'completed')
            ->whereBetween('created_at', [$prevStartDate, $prevEndDate])
            ->sum('total_amount');

        $revenueGrowth = $prevRevenue > 0 
            ? round((($currentRevenue - $prevRevenue) / $prevRevenue) * 100, 1) 
            : ($currentRevenue > 0 ? 100 : 0);

        $currentCommission = (float) Booking::where('status', 'completed')
            ->when(!$isAllTime, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]))
            ->sum('commission_amount');

        $subscriptionMrr = (float) ArtistProfile::where('billing_model', 'subscription')
            ->whereIn('subscription_status', ['active', 'trial'])
            ->sum('subscription_monthly_fee');

        $netSalonPayouts = (float) Booking::where('status', 'completed')
            ->when(!$isAllTime, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]))
            ->sum(DB::raw('total_amount - commission_amount'));

        $totalCompletedBookings = Booking::where('status', 'completed')
            ->when(!$isAllTime, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]))
            ->count();

        $avgOrderValue = $totalCompletedBookings > 0 ? round($currentRevenue / $totalCompletedBookings, 0) : 0;

        $financials = [
            'total_gmv' => $currentRevenue,
            'prev_gmv' => $prevRevenue,
            'revenue_growth' => $revenueGrowth,
            'commission_collected' => $currentCommission,
            'net_salon_payouts' => $netSalonPayouts,
            'subscription_mrr' => $subscriptionMrr,
            'subscription_arr' => $subscriptionMrr * 12,
            'avg_order_value' => $avgOrderValue,
            'total_disbursed_payouts' => (float) Payout::where('status', 'completed')->sum('amount'),
            'pending_payouts' => (float) Payout::where('status', 'pending')->sum('amount'),
        ];

        // ==========================================
        // 2. BOOKING INTELLIGENCE & STATUS FLOW
        // ==========================================
        $bookingBase = Booking::query()
            ->when(!$isAllTime, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]));

        $totalBookings = (clone $bookingBase)->count();
        $confirmedBookings = (clone $bookingBase)->where('status', 'confirmed')->count();
        $pendingBookings = (clone $bookingBase)->where('status', 'pending')->count();
        $cancelledBookings = (clone $bookingBase)->where('status', 'cancelled')->count();
        $rescheduledBookings = (clone $bookingBase)->where('status', 'rescheduled')->count();

        $completionRate = $totalBookings > 0 ? round(($totalCompletedBookings / $totalBookings) * 100, 1) : 0;
        $cancellationRate = $totalBookings > 0 ? round(($cancelledBookings / $totalBookings) * 100, 1) : 0;

        $bookings = [
            'total' => $totalBookings,
            'completed' => $totalCompletedBookings,
            'confirmed' => $confirmedBookings,
            'pending' => $pendingBookings,
            'cancelled' => $cancelledBookings,
            'rescheduled' => $rescheduledBookings,
            'completion_rate' => $completionRate,
            'cancellation_rate' => $cancellationRate,
        ];

        // ==========================================
        // 3. TIME-SERIES TRAJECTORY (DAILY / RECENT)
        // ==========================================
        $dailyData = Booking::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total_bookings'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN total_amount ELSE 0 END) as revenue'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN commission_amount ELSE 0 END) as commission')
            )
            ->when(!$isAllTime, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]))
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get()
            ->reverse()
            ->values();

        // ==========================================
        // 4. TOP SALON STUDIOS LEADERBOARD
        // ==========================================
        $topArtistsQuery = ArtistProfile::select(
                'artist_profiles.id',
                'artist_profiles.user_id',
                'artist_profiles.city_id',
                'artist_profiles.business_name',
                'artist_profiles.profile_image',
                'artist_profiles.billing_model',
                'artist_profiles.rating_avg',
                'artist_profiles.review_count',
                DB::raw('COUNT(bookings.id) as booking_count'),
                DB::raw('COALESCE(SUM(bookings.total_amount), 0) as total_revenue'),
                DB::raw('COALESCE(SUM(bookings.commission_amount), 0) as total_commission')
            )
            ->join('bookings', 'artist_profiles.id', '=', 'bookings.artist_profile_id')
            ->where('bookings.status', 'completed');

        if (!$isAllTime) {
            $topArtistsQuery->whereBetween('bookings.created_at', [$startDate, $endDate]);
        }

        $topArtists = $topArtistsQuery
            ->groupBy('artist_profiles.id', 'artist_profiles.user_id', 'artist_profiles.city_id', 'artist_profiles.business_name', 'artist_profiles.profile_image', 'artist_profiles.billing_model', 'artist_profiles.rating_avg', 'artist_profiles.review_count')
            ->orderBy('total_revenue', 'desc')
            ->limit(12)
            ->with(['user:id,name,email,phone', 'city:id,name'])
            ->get();

        // ==========================================
        // 5. TOP TREATMENTS & GLAM PACKAGES
        // ==========================================
        $topServicesQuery = Service::select(
                'services.id',
                'services.category_id',
                'services.artist_profile_id',
                'services.name',
                'services.price',
                'services.discount_price',
                'services.duration_minutes',
                DB::raw('COUNT(bookings.id) as booking_count'),
                DB::raw('COALESCE(SUM(bookings.total_amount), 0) as total_revenue')
            )
            ->leftJoin('bookings', 'services.id', '=', 'bookings.service_id')
            ->where('bookings.status', 'completed');

        if (!$isAllTime) {
            $topServicesQuery->whereBetween('bookings.created_at', [$startDate, $endDate]);
        }

        $topServices = $topServicesQuery
            ->groupBy('services.id', 'services.category_id', 'services.artist_profile_id', 'services.name', 'services.price', 'services.discount_price', 'services.duration_minutes')
            ->orderBy('booking_count', 'desc')
            ->limit(12)
            ->with(['category:id,name,image', 'artistProfile:id,business_name'])
            ->get();

        // ==========================================
        // 6. CATEGORY MARKET SHARE
        // ==========================================
        $categoryBreakdown = Category::select('categories.id', 'categories.name', 'categories.image')
            ->withCount(['services'])
            ->get()
            ->map(function ($cat) use ($startDate, $endDate, $isAllTime) {
                $revQuery = Booking::whereHas('service', fn($q) => $q->where('category_id', $cat->id))
                    ->where('status', 'completed');
                
                $bookQuery = Booking::whereHas('service', fn($q) => $q->where('category_id', $cat->id));

                if (!$isAllTime) {
                    $revQuery->whereBetween('created_at', [$startDate, $endDate]);
                    $bookQuery->whereBetween('created_at', [$startDate, $endDate]);
                }

                $rev = (float) $revQuery->sum('total_amount');
                $bookingsCount = $bookQuery->count();

                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'image' => $cat->image,
                    'services_count' => $cat->services_count,
                    'revenue' => $rev,
                    'bookings' => $bookingsCount,
                ];
            })
            ->sortByDesc('revenue')
            ->values();

        // ==========================================
        // 7. CITY & REGIONAL MARKET PENETRATION
        // ==========================================
        $cityBreakdown = City::select('cities.id', 'cities.name')
            ->withCount(['artistProfiles as active_salons' => fn($q) => $q->where('approval_status', 'approved')->where('is_active', true)])
            ->get()
            ->map(function ($city) use ($startDate, $endDate, $isAllTime) {
                $revQuery = Booking::whereHas('artistProfile', fn($q) => $q->where('city_id', $city->id))
                    ->where('status', 'completed');
                $bookQuery = Booking::whereHas('artistProfile', fn($q) => $q->where('city_id', $city->id));

                if (!$isAllTime) {
                    $revQuery->whereBetween('created_at', [$startDate, $endDate]);
                    $bookQuery->whereBetween('created_at', [$startDate, $endDate]);
                }

                return [
                    'id' => $city->id,
                    'name' => $city->name,
                    'active_salons' => $city->active_salons,
                    'revenue' => (float) $revQuery->sum('total_amount'),
                    'bookings' => $bookQuery->count(),
                ];
            })
            ->filter(fn($c) => $c['active_salons'] > 0 || $c['bookings'] > 0)
            ->sortByDesc('revenue')
            ->values();

        // ==========================================
        // 8. PAYMENT METHODS DISTRIBUTION
        // ==========================================
        $paymentMethods = [
            [
                'name' => 'Cash on Appointment',
                'count' => Booking::when(!$isAllTime, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]))
                    ->where(fn($q) => $q->where('payment_method', 'cash')->orWhereHas('payments', fn($p) => $p->where('payment_method', 'cash')))
                    ->count(),
                'amount' => (float) Booking::where('status', 'completed')
                    ->when(!$isAllTime, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]))
                    ->where(fn($q) => $q->where('payment_method', 'cash')->orWhereHas('payments', fn($p) => $p->where('payment_method', 'cash')))
                    ->sum('total_amount'),
                'icon' => '💵',
                'color' => 'emerald',
            ],
            [
                'name' => 'Online Card / Stripe',
                'count' => Booking::when(!$isAllTime, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]))
                    ->where(fn($q) => $q->whereIn('payment_method', ['card', 'stripe'])->orWhereHas('payments', fn($p) => $p->whereIn('payment_method', ['card', 'stripe'])))
                    ->count(),
                'amount' => (float) Booking::where('status', 'completed')
                    ->when(!$isAllTime, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]))
                    ->where(fn($q) => $q->whereIn('payment_method', ['card', 'stripe'])->orWhereHas('payments', fn($p) => $p->whereIn('payment_method', ['card', 'stripe'])))
                    ->sum('total_amount'),
                'icon' => '💳',
                'color' => 'purple',
            ],
            [
                'name' => 'JazzCash / EasyPaisa',
                'count' => Booking::when(!$isAllTime, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]))
                    ->where(fn($q) => $q->whereIn('payment_method', ['jazzcash', 'easypaisa', 'wallet'])->orWhereHas('payments', fn($p) => $p->whereIn('payment_method', ['jazzcash', 'easypaisa', 'wallet'])))
                    ->count(),
                'amount' => (float) Booking::where('status', 'completed')
                    ->when(!$isAllTime, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]))
                    ->where(fn($q) => $q->whereIn('payment_method', ['jazzcash', 'easypaisa', 'wallet'])->orWhereHas('payments', fn($p) => $p->whereIn('payment_method', ['jazzcash', 'easypaisa', 'wallet'])))
                    ->sum('total_amount'),
                'icon' => '📱',
                'color' => 'blue',
            ],
        ];

        // ==========================================
        // 9. CLIENT & NETWORK SATISFACTION MATRIX
        // ==========================================
        $totalCustomers = User::when(!$isAllTime, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]))->count();
        $repeatCustomers = Booking::select('customer_id')
            ->where('status', 'completed')
            ->groupBy('customer_id')
            ->havingRaw('COUNT(id) > 1')
            ->count();

        $repeatRate = $totalCustomers > 0 ? round(($repeatCustomers / $totalCustomers) * 100, 1) : 0;

        $reviewsQuery = Review::when(!$isAllTime, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]));
        $totalReviews = (clone $reviewsQuery)->count();
        $avgRating = round((float) (clone $reviewsQuery)->avg('rating'), 1) ?: 4.9;

        $ratingDistribution = [
            '5_star' => (clone $reviewsQuery)->where('rating', 5)->count(),
            '4_star' => (clone $reviewsQuery)->where('rating', 4)->count(),
            '3_star' => (clone $reviewsQuery)->where('rating', 3)->count(),
            '2_star' => (clone $reviewsQuery)->where('rating', 2)->count(),
            '1_star' => (clone $reviewsQuery)->where('rating', 1)->count(),
        ];

        $network = [
            'total_customers' => $totalCustomers,
            'repeat_customers' => $repeatCustomers,
            'repeat_rate' => $repeatRate,
            'total_artists' => ArtistProfile::count(),
            'active_artists' => ArtistProfile::where('approval_status', 'approved')->where('is_active', true)->count(),
            'subscription_artists' => ArtistProfile::where('billing_model', 'subscription')->whereIn('subscription_status', ['active', 'trial'])->count(),
            'commission_artists' => ArtistProfile::where('billing_model', 'commission')->count(),
            'pending_artists' => ArtistProfile::where('approval_status', 'pending')->count(),
            'total_services' => Service::where('is_active', true)->count(),
            'total_reviews' => $totalReviews,
            'avg_rating' => $avgRating,
            'rating_distribution' => $ratingDistribution,
        ];

        return Inertia::render('Admin/Reports/Index', [
            'financials' => $financials,
            'bookings' => $bookings,
            'dailyData' => $dailyData,
            'topArtists' => $topArtists,
            'topServices' => $topServices,
            'categoryBreakdown' => $categoryBreakdown,
            'cityBreakdown' => $cityBreakdown,
            'paymentMethods' => $paymentMethods,
            'network' => $network,
            'period' => (string) $period,
        ]);
    }
}
