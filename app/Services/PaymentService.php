<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Booking;
use App\Models\Commission;
use App\PaymentStatus;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function processPayment(Booking $booking, string $paymentMethod, array $paymentData = []): Payment
    {
        return DB::transaction(function () use ($booking, $paymentMethod, $paymentData) {
            $totalAmount = $booking->total_amount ?? $booking->total_price;

            // Calculate commission
            $commission = $this->calculateCommission($booking);

            $netAmount = $totalAmount - $commission;

            $payment = Payment::create([
                'booking_id' => $booking->id,
                'amount' => $totalAmount,
                'commission_amount' => $commission,
                'net_amount' => $netAmount,
                'status' => PaymentStatus::PAID->value,
                'payment_method' => $paymentMethod,
                'payment_data' => json_encode($paymentData),
                'paid_at' => now(),
            ]);

            return $payment;
        });
    }

    public function refundPayment(Payment $payment, string $reason): Payment
    {
        return DB::transaction(function () use ($payment, $reason) {
            $payment->update([
                'status' => PaymentStatus::REFUNDED->value,
                'refund_reason' => $reason,
                'refunded_at' => now(),
            ]);

            return $payment;
        });
    }

    private function calculateCommission(Booking $booking): float
    {
        $percentage = $this->getCommissionPercentage($booking);
        $amount = (float) ($booking->total_amount ?? $booking->total_price ?? 0);
        return round(($amount * $percentage) / 100, 2);
    }

    private function getCommissionPercentage(Booking $booking): float
    {
        $artist = $booking->artistProfile ?? \App\Models\ArtistProfile::find($booking->artist_profile_id);
        
        if ($artist) {
            return $artist->getEffectiveCommissionRate();
        }

        // Check for category-specific commission
        $service = $booking->service;
        if ($service && $service->category) {
            $categoryCommission = \App\Models\Commission::where('category_id', $service->category_id)
                ->where('is_active', true)
                ->value('commission_rate');
            if ($categoryCommission !== null) {
                return (float) $categoryCommission;
            }
        }

        // Return global commission
        return (float) config('platform.commission_percentage', 10.0);
    }

    public function createPayout(int $artistProfileId, float $amount): \App\Models\Payout
    {
        return DB::transaction(function () use ($artistProfileId, $amount) {
            $referenceNumber = 'PAYOUT-' . strtoupper(uniqid());

            return \App\Models\Payout::create([
                'artist_profile_id' => $artistProfileId,
                'reference_number' => $referenceNumber,
                'amount' => $amount,
                'status' => 'pending',
            ]);
        });
    }

    public function processPayout(\App\Models\Payout $payout, string $paymentMethod, array $paymentDetails): \App\Models\Payout
    {
        return DB::transaction(function () use ($payout, $paymentMethod, $paymentDetails) {
            $payout->update([
                'status' => 'processing',
                'payment_method' => $paymentMethod,
                'payment_details' => json_encode($paymentDetails),
            ]);

            // Here you would integrate with payment gateway
            // For now, we'll mark as completed
            $payout->update([
                'status' => 'completed',
                'processed_at' => now(),
            ]);

            return $payout;
        });
    }
}
