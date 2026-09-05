<?php

namespace Database\Seeders;

use App\Models\Blog;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        Blog::updateOrCreate(
            ['slug' => 'ultimate-glass-skin-routine-guide'],
            [
                'title' => 'The Ultimate Glass Skin Routine: 7 Expert Steps for Radiant Glow',
                'category' => 'Skincare Guide',
                'summary' => 'Discover the step-by-step beauty protocol used by top celebrity estheticians to achieve deeply hydrated, luminous, and pore-refined skin.',
                'content' => "## Achieving True Glass Skin\n\nGlass skin is all about intense hydration, gentle cell renewal, and lightweight layering.\n\n### Step 1: Double Cleansing\nAlways start with an oil-based balm to melt away SPF and impurities, followed by a gentle amino-acid foam cleanser.\n\n### Step 2: Essence and Hydrating Toners\nPat 3-5 layers of hyaluronic acid toner onto damp skin. This creates the signature plump bouncy texture.\n\n### Step 3: Targeted Serum and Barrier Cream\nLock in moisture with ceramide-rich barrier moisturizers and finish with lightweight squalane oil.",
                'is_published' => true,
                'published_at' => now(),
                'views_count' => 142,
                'tags' => 'Skincare,GlassSkin,BeautyTips,GlowRoutine',
                'image' => 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=800&auto=format&fit=crop&q=80',
            ]
        );

        Blog::updateOrCreate(
            ['slug' => 'how-to-choose-the-right-sunscreen'],
            [
                'title' => 'Mineral vs Chemical: How to Choose the Perfect Sunscreen for Your Skin Type',
                'category' => 'Sun Protection',
                'summary' => 'Understand the critical differences between physical UV blockers and chemical invisible filters to prevent premature aging and hyperpigmentation.',
                'content' => "## Understanding SPF in South Asian Weather\n\nWith high UV index conditions, broad-spectrum UVA and UVB defense is non-negotiable for maintaining healthy skin.\n\n### Physical / Mineral Blockers (Zinc Oxide, Titanium Dioxide)\nBest for sensitive, acne-prone, or rosacea skin. They sit on top of the skin reflecting UV rays like tiny mirrors.\n\n### Chemical Filters (Mexoryl 400, Tinosorb, Avobenzone)\nUltra-fluid, completely transparent without white cast. Ideal under makeup and for sports or daily outdoor commute.",
                'is_published' => true,
                'published_at' => now()->subDays(2),
                'views_count' => 210,
                'tags' => 'Sunscreen,SPF50,SkinCareGuide,AntiAging',
                'image' => 'https://images.unsplash.com/photo-1598440947619-2c35fc9aa908?w=800&auto=format&fit=crop&q=80',
            ]
        );
    }
}
