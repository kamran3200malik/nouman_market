<?php

namespace App\Console\Commands;

use App\Models\ArtistProfile;
use App\ApprovalStatus;
use App\Notifications\SubscriptionExpired;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:suspend-expired-subscriptions')]
#[Description('Automatically suspend artists whose monthly subscription has expired')]
class SuspendExpiredSubscriptions extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredArtists = ArtistProfile::with('user')
            ->where('billing_model', 'subscription')
            ->whereNotNull('subscription_expires_at')
            ->where('subscription_expires_at', '<', now())
            ->where(function ($q) {
                $q->where('subscription_status', '!=', 'expired')
                  ->orWhere('is_active', true)
                  ->orWhere('approval_status', '!=', ApprovalStatus::SUSPENDED->value);
            })
            ->get();

        $count = 0;

        foreach ($expiredArtists as $artist) {
            $formattedDate = $artist->subscription_expires_at ? $artist->subscription_expires_at->format('M d, Y') : 'recently';
            $planName = $artist->subscription_plan_name ?: 'Monthly Subscription';

            $artist->update([
                'subscription_status' => 'expired',
                'is_active' => false,
                'approval_status' => ApprovalStatus::SUSPENDED->value,
                'rejection_reason' => "Your {$planName} expired on {$formattedDate}. Please renew your subscription to reactivate your studio profile and treatment listings.",
            ]);

            if ($artist->user) {
                $artist->user->notify(new SubscriptionExpired($artist));
            }

            $this->line("Suspended expired salon: {$artist->business_name} (ID: {$artist->id})");
            $count++;
        }

        $this->info("Processed and suspended {$count} expired subscription salon(s).");

        return Command::SUCCESS;
    }
}
