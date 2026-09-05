<?php

namespace App\Services;

use App\Models\ArtistProfile;
use App\Models\Category;
use App\Models\Page;
use Illuminate\Support\Facades\Storage;

class SitemapService
{
    public function generate(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        
        // Homepage
        $xml .= $this->createUrlNode(url('/'), '1.0', 'daily');
        
        // Main Catalogues
        $xml .= $this->createUrlNode(url('/artists'), '0.9', 'daily');
        $xml .= $this->createUrlNode(url('/services'), '0.9', 'daily');
        $xml .= $this->createUrlNode(url('/products'), '0.9', 'daily');
        $xml .= $this->createUrlNode(url('/artist/register'), '0.7', 'monthly');
        
        // Artist profiles
        $artists = ArtistProfile::where('approval_status', 'approved')->get();
        foreach ($artists as $artist) {
            $xml .= $this->createUrlNode(
                url('/artists/' . $artist->slug),
                '0.8',
                'weekly',
                $artist->updated_at?->toIso8601String()
            );
        }
        
        // Products
        if (class_exists(\App\Models\Product::class)) {
            $products = \App\Models\Product::where('is_active', true)->get();
            foreach ($products as $product) {
                $xml .= $this->createUrlNode(
                    url('/products?highlight=' . $product->id),
                    '0.7',
                    'weekly',
                    $product->updated_at?->toIso8601String()
                );
            }
        }
        
        // Courses
        if (class_exists(\App\Models\Course::class)) {
            $xml .= $this->createUrlNode(url('/courses'), '0.8', 'weekly');
            $courses = \App\Models\Course::where('is_active', true)->get();
            foreach ($courses as $course) {
                $xml .= $this->createUrlNode(
                    url('/courses/' . $course->slug),
                    '0.8',
                    'weekly',
                    $course->updated_at?->toIso8601String()
                );
            }
        }

        // Blogs
        if (class_exists(\App\Models\Blog::class)) {
            $xml .= $this->createUrlNode(url('/blogs'), '0.8', 'weekly');
            $blogs = \App\Models\Blog::where('is_published', true)->get();
            foreach ($blogs as $blog) {
                $xml .= $this->createUrlNode(
                    url('/blogs/' . $blog->slug),
                    '0.7',
                    'weekly',
                    $blog->updated_at?->toIso8601String()
                );
            }
        }

        // CMS pages
        if (class_exists(Page::class)) {
            $pages = Page::where('is_published', true)->get();
            foreach ($pages as $page) {
                $xml .= $this->createUrlNode(
                    url('/' . $page->slug),
                    '0.6',
                    'monthly',
                    $page->updated_at?->toIso8601String()
                );
            }
        }
        
        $xml .= '</urlset>';
        
        return $xml;
    }
    
    protected function createUrlNode(string $url, string $priority, string $changeFreq, ?string $lastModified = null): string
    {
        $node = '<url>';
        $node .= '<loc>' . htmlspecialchars($url) . '</loc>';
        $node .= '<priority>' . $priority . '</priority>';
        $node .= '<changefreq>' . $changeFreq . '</changefreq>';
        
        if ($lastModified) {
            $node .= '<lastmod>' . date('Y-m-d', strtotime($lastModified)) . '</lastmod>';
        }
        
        $node .= '</url>';
        
        return $node;
    }
    
    public function save(): void
    {
        $sitemap = $this->generate();
        Storage::disk('public')->put('sitemap.xml', $sitemap);
    }
}
