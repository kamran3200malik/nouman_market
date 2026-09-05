<?php

namespace App\Console\Commands;

use App\Services\SitemapService;
use Illuminate\Console\Command;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';
    
    protected $description = 'Generate the XML sitemap for the website';
    
    public function handle(): int
    {
        $sitemapService = new SitemapService();
        $sitemapService->save();
        
        $this->info('Sitemap generated successfully.');
        
        return Command::SUCCESS;
    }
}
