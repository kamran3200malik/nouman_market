<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\SitemapService;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    /**
     * Render dynamic XML sitemap for search engines.
     */
    public function sitemap(SitemapService $sitemapService): Response
    {
        $xml = $sitemapService->generate();

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * Render dynamic robots.txt directives.
     */
    public function robots(): Response
    {
        $isMaintenance = Setting::getValue('maintenance_mode', '0') === '1';

        $content = "User-agent: *\n";

        if ($isMaintenance) {
            $content .= "Disallow: /\n";
        } else {
            $content .= "Allow: /\n";
            $content .= "Disallow: /admin/\n";
            $content .= "Disallow: /artist/\n";
            $content .= "Disallow: /customer/\n";
            $content .= "Disallow: /profile\n";
            $content .= "Disallow: /dashboard\n";
            $content .= "Disallow: /login\n";
            $content .= "Disallow: /register\n";
            $content .= "\nSitemap: " . url('/sitemap.xml') . "\n";
        }

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
