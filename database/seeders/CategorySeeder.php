<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Skincare',
                'description' => 'Targeted serums, clinical sunscreens, anti-aging creams, and deeply hydrating moisturizers.',
                'image' => 'https://images.unsplash.com/photo-1556228720-195a672e8a03?auto=format&fit=crop&w=600&q=80',
                'icon' => '✨',
                'is_featured' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Makeup & Cosmetics',
                'description' => 'Flawless foundations, matte & velvet lipsticks, high-pigment eyeshadow palettes, and waterproof mascaras.',
                'image' => 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=600&q=80',
                'icon' => '💄',
                'is_featured' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Haircare & Styling',
                'description' => 'Sulfate-free shampoos, keratin hair masks, bond repair treatments, and organic hair growth oils.',
                'image' => 'https://images.unsplash.com/photo-1527799820374-dcf8d9d4a388?auto=format&fit=crop&w=600&q=80',
                'icon' => '💇‍♀️',
                'is_featured' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Fragrances & Perfumes',
                'description' => 'Long-lasting Eau de Parfum, luxury Arabian oud, fresh floral mists, and designer scents.',
                'image' => 'https://images.unsplash.com/photo-1594035910387-fea47794261f?auto=format&fit=crop&w=600&q=80',
                'icon' => '🌸',
                'is_featured' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Bath & Body Care',
                'description' => 'Exfoliating body scrubs, nourishing body butters, aromatic body washes, and hand creams.',
                'image' => 'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?auto=format&fit=crop&w=600&q=80',
                'icon' => '🧴',
                'is_featured' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'Beauty Tools & Accessories',
                'description' => 'Professional brush sets, jade rollers, gua sha, high-speed hair stylers, and precision tweezers.',
                'image' => 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?auto=format&fit=crop&w=600&q=80',
                'icon' => '🪞',
                'is_featured' => true,
                'sort_order' => 6,
            ],
            [
                'name' => 'Organic & Herbal',
                'description' => 'Pure rose water, cold-pressed castor oils, organic ubtan, and chemical-free glow packs.',
                'image' => 'https://images.unsplash.com/photo-1608248597359-00f7e44a953e?auto=format&fit=crop&w=600&q=80',
                'icon' => '🌿',
                'is_featured' => true,
                'sort_order' => 7,
            ],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['slug' => Str::slug($cat['name'])],
                [
                    'name' => $cat['name'],
                    'description' => $cat['description'],
                    'image' => $cat['image'],
                    'icon' => $cat['icon'],
                    'is_featured' => $cat['is_featured'],
                    'sort_order' => $cat['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
