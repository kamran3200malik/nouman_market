<?php

namespace App\Services;

use App\Models\User;
use App\Models\Booking;
use App\Models\Review;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\ArtistProfile;
use App\Notifications\GeneralAppNotification;
use Illuminate\Support\Facades\Log;

class AppNotificationService
{
    /**
     * Get all active admin users.
     */
    public static function getAdmins()
    {
        return User::role('admin')->get();
    }

    /**
     * Triggered when a new booking is created.
     */
    public static function notifyBookingCreated(Booking $booking): void
    {
        try {
            $booking->loadMissing(['customer', 'artistProfile.user', 'service']);
            $customer = $booking->customer;
            $artistUser = $booking->artistProfile?->user;
            $artistName = $booking->artistProfile?->business_name ?? 'Salon';
            $serviceName = $booking->service?->name ?? 'Beauty Service';
            $dateFormatted = $booking->booking_date ? \Carbon\Carbon::parse($booking->booking_date)->format('M d, Y') : 'Scheduled Date';
            $timeFormatted = $booking->booking_time ? substr($booking->booking_time, 0, 5) : '';

            // 1. Notify Artist
            if ($artistUser) {
                $artistUser->notify(new GeneralAppNotification(
                    title: '🗓️ New Booking Request',
                    message: "{$customer->name} requested '{$serviceName}' on {$dateFormatted} at {$timeFormatted}. (Ref #{$booking->booking_number})",
                    type: 'booking',
                    actionUrl: route('artist.bookings.show', $booking->id),
                    extraData: ['booking_id' => $booking->id]
                ));
            }

            // 2. Notify Customer
            if ($customer) {
                $customer->notify(new GeneralAppNotification(
                    title: '✨ Appointment Placed',
                    message: "Your appointment #{$booking->booking_number} for '{$serviceName}' with {$artistName} has been submitted.",
                    type: 'booking',
                    actionUrl: route('customer.bookings.show', $booking->id),
                    extraData: ['booking_id' => $booking->id]
                ));
            }

            // 3. Notify Admin
            foreach (self::getAdmins() as $admin) {
                $admin->notify(new GeneralAppNotification(
                    title: '👑 New Booking Placed',
                    message: "Booking #{$booking->booking_number} by {$customer->name} for {$artistName} ({$serviceName}).",
                    type: 'booking',
                    actionUrl: route('admin.bookings.index'),
                    extraData: ['booking_id' => $booking->id]
                ));
            }
        } catch (\Throwable $e) {
            Log::error('Error sending BookingCreated notification: ' . $e->getMessage());
        }
    }

    /**
     * Triggered when booking status changes (confirmed, completed, rejected, cancelled, rescheduled).
     */
    public static function notifyBookingStatusChanged(Booking $booking, string $status, ?string $notes = null): void
    {
        try {
            $booking->loadMissing(['customer', 'artistProfile.user', 'service']);
            $customer = $booking->customer;
            $artistUser = $booking->artistProfile?->user;
            $artistName = $booking->artistProfile?->business_name ?? 'Salon';
            $serviceName = $booking->service?->name ?? 'Beauty Service';

            $statusLabels = [
                'confirmed' => ['title' => '✓ Appointment Confirmed!', 'desc' => "{$artistName} confirmed your appointment for '{$serviceName}'."],
                'completed' => ['title' => '★ Appointment Completed!', 'desc' => "Your makeover session with {$artistName} is marked complete. Leave a review!"],
                'cancelled' => ['title' => '✕ Appointment Cancelled', 'desc' => "Booking #{$booking->booking_number} has been cancelled." . ($notes ? " Reason: {$notes}" : "")],
                'rejected' => ['title' => '✕ Booking Request Declined', 'desc' => "Booking #{$booking->booking_number} was declined by {$artistName}." . ($notes ? " Reason: {$notes}" : "")],
                'rescheduled' => ['title' => '🗓️ Appointment Rescheduled', 'desc' => "Booking #{$booking->booking_number} date/time was updated." . ($notes ? " Note: {$notes}" : "")],
            ];

            $info = $statusLabels[$status] ?? ['title' => "Booking {$status}", 'desc' => "Status updated for booking #{$booking->booking_number}."];

            // Notify Customer
            if ($customer) {
                $customer->notify(new GeneralAppNotification(
                    title: $info['title'],
                    message: $info['desc'],
                    type: 'booking',
                    actionUrl: route('customer.bookings.show', $booking->id),
                    extraData: ['booking_id' => $booking->id, 'status' => $status]
                ));
            }

            // Notify Artist (if status is cancelled or rescheduled by customer)
            if ($artistUser && in_array($status, ['cancelled', 'rescheduled'])) {
                $artistUser->notify(new GeneralAppNotification(
                    title: $info['title'],
                    message: "Booking #{$booking->booking_number} ({$customer->name}) has been {$status}." . ($notes ? " Reason: {$notes}" : ""),
                    type: 'booking',
                    actionUrl: route('artist.bookings.show', $booking->id),
                    extraData: ['booking_id' => $booking->id, 'status' => $status]
                ));
            }

            // Notify Admin
            foreach (self::getAdmins() as $admin) {
                $admin->notify(new GeneralAppNotification(
                    title: "Booking #{$booking->booking_number} " . ucfirst($status),
                    message: "Booking between {$customer->name} and {$artistName} marked as {$status}.",
                    type: 'booking',
                    actionUrl: route('admin.bookings.index'),
                    extraData: ['booking_id' => $booking->id, 'status' => $status]
                ));
            }
        } catch (\Throwable $e) {
            Log::error('Error sending BookingStatusChanged notification: ' . $e->getMessage());
        }
    }

    /**
     * Triggered when a new chat message is sent.
     */
    public static function notifyNewMessage(Conversation $conversation, Message $message, User $sender): void
    {
        try {
            $conversation->loadMissing(['customer', 'artistProfile.user']);
            $customer = $conversation->customer;
            $artistUser = $conversation->artistProfile?->user;
            $salonName = $conversation->artistProfile?->business_name ?? 'Salon';
            $preview = mb_strimwidth($message->message, 0, 70, '...');

            // If sender is Customer, notify Artist
            if ($sender->id === $customer?->id && $artistUser) {
                $artistUser->notify(new GeneralAppNotification(
                    title: "💬 Message from {$customer->name}",
                    message: "{$customer->name}: \"{$preview}\"",
                    type: 'message',
                    actionUrl: route('artist.messages.show', $conversation->id),
                    extraData: ['conversation_id' => $conversation->id]
                ));
            }

            // If sender is Artist, notify Customer
            if ($sender->id === $artistUser?->id && $customer) {
                $customer->notify(new GeneralAppNotification(
                    title: "💬 Message from {$salonName}",
                    message: "{$salonName}: \"{$preview}\"",
                    type: 'message',
                    actionUrl: route('customer.messages.show', $conversation->id),
                    extraData: ['conversation_id' => $conversation->id]
                ));
            }

            // Notify Admin for communications audit
            foreach (self::getAdmins() as $admin) {
                // Don't notify if admin was the sender
                if ($admin->id !== $sender->id) {
                    $admin->notify(new GeneralAppNotification(
                        title: "💬 New Chat: {$customer->name} & {$salonName}",
                        message: "{$sender->name}: \"{$preview}\"",
                        type: 'message',
                        actionUrl: route('admin.messages.show', $conversation->id),
                        extraData: ['conversation_id' => $conversation->id]
                    ));
                }
            }
        } catch (\Throwable $e) {
            Log::error('Error sending NewMessage notification: ' . $e->getMessage());
        }
    }

    /**
     * Triggered when a new review is submitted.
     */
    public static function notifyNewReview(Review $review): void
    {
        try {
            $review->loadMissing(['customer', 'artistProfile.user']);
            $customer = $review->customer;
            $artistUser = $review->artistProfile?->user;
            $salonName = $review->artistProfile?->business_name ?? 'Salon';
            $preview = mb_strimwidth($review->review, 0, 70, '...');

            // Notify Artist
            if ($artistUser) {
                $artistUser->notify(new GeneralAppNotification(
                    title: "⭐ New {$review->rating}-Star Review!",
                    message: "{$customer->name} rated your salon {$review->rating}/5 stars: \"{$preview}\"",
                    type: 'review',
                    actionUrl: route('artist.reviews.index'),
                    extraData: ['review_id' => $review->id]
                ));
            }

            // Notify Admin
            foreach (self::getAdmins() as $admin) {
                $admin->notify(new GeneralAppNotification(
                    title: "⭐ Review Posted on {$salonName}",
                    message: "{$customer->name} gave {$review->rating}★ to {$salonName}.",
                    type: 'review',
                    actionUrl: route('admin.reviews.index'),
                    extraData: ['review_id' => $review->id]
                ));
            }
        } catch (\Throwable $e) {
            Log::error('Error sending NewReview notification: ' . $e->getMessage());
        }
    }

    /**
     * Triggered when a new artist profile registers.
     */
    public static function notifyNewArtistApplication(ArtistProfile $artist): void
    {
        try {
            $artist->loadMissing(['user', 'city']);
            $cityName = $artist->city?->name ?? 'Pakistan';

            foreach (self::getAdmins() as $admin) {
                $admin->notify(new GeneralAppNotification(
                    title: "👑 New Salon Registration: {$artist->business_name}",
                    message: "A new beauty salon application from {$artist->business_name} in {$cityName} is awaiting verification.",
                    type: 'system',
                    actionUrl: route('admin.artists.index'),
                    extraData: ['artist_id' => $artist->id]
                ));
            }
        } catch (\Throwable $e) {
            Log::error('Error sending NewArtistApplication notification: ' . $e->getMessage());
        }
    }

    /**
     * Triggered when a customer places a new product marketplace order.
     */
    public static function notifyProductOrderCreated(\App\Models\ProductOrder $order): void
    {
        try {
            $order->loadMissing(['items', 'user']);
            $itemsCount = $order->items->sum('quantity');
            $totalFormatted = 'PKR ' . number_format($order->total_amount, 0);

            // 1. Notify Admins
            foreach (self::getAdmins() as $admin) {
                $admin->notify(new GeneralAppNotification(
                    title: "🛍️ New Store Order #{$order->order_number}",
                    message: "{$order->customer_name} ({$order->city}) ordered {$itemsCount} item(s) totaling {$totalFormatted}.",
                    type: 'booking',
                    actionUrl: route('admin.products.orders'),
                    extraData: ['order_id' => $order->id, 'order_number' => $order->order_number]
                ));
            }

            // 2. Notify Customer (either from user relation or matching email)
            $customerUser = $order->user ?? ($order->customer_email ? User::where('email', $order->customer_email)->first() : null);
            if ($customerUser) {
                $customerUser->notify(new GeneralAppNotification(
                    title: "✨ Beauty Order Placed (#{$order->order_number})",
                    message: "Thank you {$order->customer_name}! Your order for {$itemsCount} item(s) totaling {$totalFormatted} has been received and is pending dispatch.",
                    type: 'booking',
                    actionUrl: route('products.index'),
                    extraData: ['order_id' => $order->id, 'order_number' => $order->order_number]
                ));
            }

            // 3. Notify individual Artists whose products were ordered
            $artistItemGroups = $order->items->whereNotNull('artist_profile_id')->groupBy('artist_profile_id');
            foreach ($artistItemGroups as $artistProfileId => $items) {
                $artistProfile = \App\Models\ArtistProfile::with('user')->find($artistProfileId);
                if ($artistProfile && $artistProfile->user) {
                    $artistItemsCount = $items->sum('quantity');
                    $artistNetTotal = $items->sum('artist_net_amount');
                    $artistNetFormatted = 'PKR ' . number_format($artistNetTotal, 0);

                    $artistProfile->user->notify(new GeneralAppNotification(
                        title: "🛍️ New Salon Product Sale! (#{$order->order_number})",
                        message: "Customer in {$order->city} ordered {$artistItemsCount} of your salon product(s). Your net payout: {$artistNetFormatted}.",
                        type: 'booking',
                        actionUrl: route('artist.products.orders'),
                        extraData: ['order_id' => $order->id, 'order_number' => $order->order_number]
                    ));
                }
            }
        } catch (\Throwable $e) {
            Log::error('Error sending notifyProductOrderCreated notification: ' . $e->getMessage());
        }
    }

    /**
     * Triggered when an artist applies to sell products and signs the commission agreement.
     */
    public static function notifyArtistSellerApplication(\App\Models\ArtistProfile $artist): void
    {
        try {
            $salonName = $artist->seller_store_name ?: $artist->business_name;
            foreach (self::getAdmins() as $admin) {
                $admin->notify(new GeneralAppNotification(
                    title: "📝 New Product Seller Request: {$salonName}",
                    message: "{$salonName} has signed the marketplace seller agreement and requested authorization to list salon products.",
                    type: 'system',
                    actionUrl: route('admin.products.sellers'),
                    extraData: ['artist_id' => $artist->id]
                ));
            }
        } catch (\Throwable $e) {
            Log::error('Error sending notifyArtistSellerApplication: ' . $e->getMessage());
        }
    }

    /**
     * Triggered when admin authorizes an artist to sell products.
     */
    public static function notifyArtistSellerApproved(\App\Models\ArtistProfile $artist): void
    {
        try {
            if ($artist->user) {
                $rate = $artist->product_commission_rate ?? 10;
                $artist->user->notify(new GeneralAppNotification(
                    title: "🎉 Product Seller Authorization Approved!",
                    message: "Congratulations! Your store is now authorized on BeautyBook Luxe with a {$rate}% platform commission. You can now add and manage your products.",
                    type: 'system',
                    actionUrl: route('artist.products.index'),
                    extraData: ['artist_id' => $artist->id]
                ));
            }
        } catch (\Throwable $e) {
            Log::error('Error sending notifyArtistSellerApproved: ' . $e->getMessage());
        }
    }

    /**
     * Triggered when admin rejects an artist seller application.
     */
    public static function notifyArtistSellerRejected(\App\Models\ArtistProfile $artist, string $reason): void
    {
        try {
            if ($artist->user) {
                $artist->user->notify(new GeneralAppNotification(
                    title: "✕ Product Seller Request Update",
                    message: "Your seller application was not approved. Reason: {$reason}",
                    type: 'system',
                    actionUrl: route('artist.products.index'),
                    extraData: ['artist_id' => $artist->id, 'reason' => $reason]
                ));
            }
        } catch (\Throwable $e) {
            Log::error('Error sending notifyArtistSellerRejected: ' . $e->getMessage());
        }
    }

    /**
     * Triggered when product order status is updated (dispatched, delivered, etc.)
     */
    public static function notifyProductOrderStatusChanged(\App\Models\ProductOrder $order): void
    {
        try {
            $customerUser = $order->user ?? ($order->customer_email ? User::where('email', $order->customer_email)->first() : null);
            if (!$customerUser) {
                return;
            }

            $orderNumber = $order->order_number;
            $city = $order->city;
            $status = $order->order_status;

            [$title, $message] = match ($status) {
                'confirmed' => [
                    "✨ Order #{$orderNumber} Confirmed",
                    "Your beauty order has been verified and approved by the fulfillment team."
                ],
                'processing' => [
                    "📦 Order #{$orderNumber} Being Packed",
                    "Your salon products are currently being packed and prepared for shipping."
                ],
                'dispatched' => [
                    "🚚 Order #{$orderNumber} Dispatched / On the Way!",
                    "Your order has been handed to courier for delivery to {$city}." . ($order->admin_notes ? " Courier details: {$order->admin_notes}" : "")
                ],
                'delivered' => [
                    "🎉 Order #{$orderNumber} Delivered!",
                    "Your beauty parcel has been marked as delivered. Thank you for shopping with BeautyBook Luxe!"
                ],
                'cancelled' => [
                    "✕ Order #{$orderNumber} Cancelled",
                    "Your order has been cancelled." . ($order->cancellation_reason ? " Reason: {$order->cancellation_reason}" : "")
                ],
                default => [
                    "📦 Order #{$orderNumber} Updated",
                    "Your beauty marketplace order status is now " . ucfirst($status) . "."
                ]
            };

            $actionUrl = \Illuminate\Support\Facades\Route::has('customer.orders.show')
                ? route('customer.orders.show', $order->id)
                : route('products.index');

            $customerUser->notify(new GeneralAppNotification(
                title: $title,
                message: $message,
                type: 'booking',
                actionUrl: $actionUrl,
                extraData: ['order_id' => $order->id, 'order_number' => $order->order_number, 'status' => $status]
            ));
        } catch (\Throwable $e) {
            Log::error('Error sending notifyProductOrderStatusChanged notification: ' . $e->getMessage());
        }
    }
}
