<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetDefaultSeo
{
    public function handle(Request $request, Closure $next): Response
    {
        View::share('defaultSeo', [
            'title' => config('app.name'),
            'description' => 'Find the best makeup artists, beauty salons, and beauty professionals near you. Book appointments for bridal makeup, hair styling, nails, facials, and more.',
            'keywords' => 'makeup artist, beauty salon, bridal makeup, hair stylist, nail artist, facial specialist, mehndi artist, lash artist, brow artist',
            'og_type' => 'website',
            'og_image' => asset('images/og-default.jpg'),
            'twitter_card' => 'summary_large_image',
        ]);

        return $next($request);
    }
}
