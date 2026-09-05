<?php

namespace App\Console\Commands;

use App\Models\Banner;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:disable-expired-banners')]
#[Description('Disable banners whose end date has passed')]
class DisableExpiredBanners extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = Banner::where('is_active', true)
            ->where('end_date', '<', now())
            ->update(['is_active' => false]);

        $this->info("Disabled {$count} expired banner(s).");

        return Command::SUCCESS;
    }
}
