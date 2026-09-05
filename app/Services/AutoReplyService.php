<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\ArtistProfile;
use App\Models\Setting;
use Carbon\Carbon;

class AutoReplyService
{
    /**
     * Trigger an automated reply to customer inquiry.
     */
    public static function handleCustomerMessage(Conversation $conversation, string $customerMessageText = ''): ?Message
    {
        $conversation->loadMissing(['artistProfile.user', 'customer', 'booking.service']);

        $artistProfile = $conversation->artistProfile;
        if (!$artistProfile || !$artistProfile->user) {
            return null;
        }

        $artistUser = $artistProfile->user;
        $customerUser = $conversation->customer;

        // Check if an auto-reply or artist message was already sent within the last 30 minutes
        $lastArtistMessage = Message::where('conversation_id', $conversation->id)
            ->where('sender_id', $artistUser->id)
            ->latest()
            ->first();

        if ($lastArtistMessage && $lastArtistMessage->created_at->diffInMinutes(Carbon::now()) < 30) {
            return null; // Already replied recently, avoid duplicate automated bursts
        }

        // Generate personalized auto-reply text
        $customerName = $customerUser?->name ? explode(' ', $customerUser->name)[0] : 'there';
        $salonName = $artistProfile->business_name ?: ($artistUser->name ?: 'our salon');

        // Check custom artist auto-reply setting
        $customAutoReplyKey = "artist_autoreply_{$artistProfile->id}";
        $customSetting = Setting::where('key', $customAutoReplyKey)->first();

        if ($customSetting && !empty($customSetting->value)) {
            $replyTemplate = $customSetting->value;
            $replyText = str_replace(
                ['{customer_name}', '{salon_name}'],
                [$customerName, $salonName],
                $replyTemplate
            );
        } else {
            // Default smart concierge auto-reply
            $replyText = "Hi {$customerName}! ✨ Thank you for messaging {$salonName}.\n\nWe have received your inquiry and our team will get back to you shortly.";

            if ($conversation->booking && $conversation->booking->service) {
                $replyText .= "\n\n📋 We see your inquiry is regarding Booking #{$conversation->booking->booking_number} ({$conversation->booking->service->name}). We are reviewing your schedule details.";
            } else {
                $replyText .= "\n\n💄 In the meantime, feel free to view our service list, rates, and signature makeover portfolio on our profile!";
            }
        }

        // Create the automated message from the artist
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $artistUser->id,
            'message' => $replyText,
            'is_read' => false,
        ]);

        $conversation->update(['last_message_at' => Carbon::now()]);

        return $message;
    }
}
