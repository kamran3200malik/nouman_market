<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

        <title inertia>{{ config('app.name', 'BeautyBook Luxe') }}</title>

        <!-- Primary SEO Meta Tags -->
        <meta name="title" content="{{ config('app.name', 'Luxe Beauty Market') }} - 100% Genuine Cosmetics & Skincare Marketplace">
        <meta name="description" content="Discover 100% original cosmetics, clinical skincare, French fragrances, and haircare essentials in Pakistan with fast nationwide delivery.">
        <meta name="keywords" content="cosmetics pakistan, original skincare lahore, makeup karachi, luxury perfumes islamabad, beauty products online, original cosmetics buy">
        <meta name="author" content="{{ config('app.name', 'Luxe Beauty Market') }}">
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">

        <!-- Open Graph / Facebook / WhatsApp Meta Tags -->
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:title" content="{{ config('app.name', 'Luxe Beauty Market') }} - 100% Genuine Cosmetics & Skincare Marketplace">
        <meta property="og:description" content="Discover 100% original cosmetics, clinical skincare, French fragrances, and haircare essentials in Pakistan with fast nationwide delivery.">
        <meta property="og:image" content="{{ asset('images/og-share-card.jpg') }}">
        <meta property="og:site_name" content="{{ config('app.name', 'Luxe Beauty Market') }}">
        <meta property="og:locale" content="en_US">

        <!-- Twitter Card Meta Tags -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:url" content="{{ url()->current() }}">
        <meta name="twitter:title" content="{{ config('app.name', 'Luxe Beauty Market') }} - 100% Genuine Cosmetics & Skincare Marketplace">
        <meta name="twitter:description" content="Discover 100% original cosmetics, clinical skincare, French fragrances, and haircare essentials in Pakistan with fast nationwide delivery.">
        <meta name="twitter:image" content="{{ asset('images/og-share-card.jpg') }}">

        <!-- Favicon & App Icons -->
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
        <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

        <!-- Canonical URL -->
        <link rel="canonical" href="{{ url()->current() }}" />

        <!-- CSRF Token & Referrer Policy -->
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="referrer" content="no-referrer-when-downgrade">

        <!-- Google Fonts: Plus Jakarta Sans (Unified Dashboard & Modern UI) -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">

        <!-- Schema.org JSON-LD Structured Data for Search Engines -->
        <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'WebSite',
                    '@id' => url('/') . '/#website',
                    'url' => url('/'),
                    'name' => config('app.name', 'Luxe Beauty Market'),
                    'description' => "Pakistan's premier multi-brand luxury cosmetics and skincare marketplace delivering original, verified beauty essentials to your doorstep.",
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => [
                            '@type' => 'EntryPoint',
                            'urlTemplate' => url('/products') . '?search={search_term_string}',
                        ],
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
                [
                    '@type' => 'Organization',
                    '@id' => url('/') . '/#organization',
                    'name' => config('app.name', 'BeautyBook Luxe'),
                    'url' => url('/'),
                    'logo' => asset('images/logo.png'),
                    'contactPoint' => [
                        '@type' => 'ContactPoint',
                        'telephone' => '+923001234567',
                        'contactType' => 'customer service',
                        'areaServed' => 'PK',
                        'availableLanguage' => ['English', 'Urdu'],
                    ],
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
        </script>

        <!-- Scripts -->
        <script>
            window.__STORAGE_URL__ = "{{ asset('storage') }}";
            window.__ASSET_URL__ = "{{ asset('') }}";
        </script>
        @routes
        @vite(['resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
