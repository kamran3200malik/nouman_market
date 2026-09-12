<script setup>
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const seo = page.props.seo || page.props.defaultSeo || {};
</script>

<template>
    <Head>
        <title>{{ seo.title || 'Luxe Beauty Market - 100% Genuine Cosmetics & Skincare' }}</title>
        <meta name="description" :content="seo.description" />
        <meta name="keywords" :content="seo.keywords" />
        
        <!-- Open Graph -->
        <meta property="og:title" :content="seo.title" />
        <meta property="og:description" :content="seo.description" />
        <meta property="og:type" :content="seo.og_type || 'website'" />
        <meta property="og:url" :content="page.url" />
        <meta property="og:image" :content="seo.og_image || '/images/og-default.jpg'" />
        <meta property="og:site_name" content="Luxe Beauty Market" />
        
        <!-- Twitter Card -->
        <meta name="twitter:card" :content="seo.twitter_card || 'summary_large_image'" />
        <meta name="twitter:title" :content="seo.title" />
        <meta name="twitter:description" :content="seo.description" />
        <meta name="twitter:image" :content="seo.og_image || asset('images/og-default.jpg')" />
        
        <!-- Canonical URL -->
        <link rel="canonical" :href="page.url" />
    </Head>
</template>
