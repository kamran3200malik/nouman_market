<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        $skincare = Category::where('name', 'Skincare')->first();
        $fenty = Product::where('brand', 'Fenty Beauty')->first();
        $mfk = Product::where('brand', 'Maison Francis Kurkdjian')->first();

        $banners = [
            [
                'title' => 'Radiant Skin Starts Here',
                'subtitle' => 'Explore 100% genuine clinical skincare & viral K-beauty essentials with up to 25% OFF.',
                'tag' => 'SUMMER GLOW SALE',
                'image' => 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=1600&q=85',
                'button_text' => 'Shop Skincare',
                'position' => 'main_hero',
                'category_id' => $skincare?->id,
                'price' => null,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'title' => 'Explosive Shine & Color',
                'subtitle' => 'Iconic lip glosses, velvety liquid mattes, and multi-finish eyeshadows from world-class cosmetics brands.',
                'tag' => 'BESTSELLERS',
                'image' => 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?auto=format&fit=crop&w=1600&q=85',
                'button_text' => 'Discover Makeup',
                'position' => 'main_hero',
                'product_id' => $fenty?->id,
                'price' => 7400.00,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'title' => 'Signature Luxury Fragrances',
                'subtitle' => 'Unforgettable long-lasting Eau de Parfum and niche Arabian blends crafted to captivate.',
                'tag' => 'LUXURY PERFUMERY',
                'image' => 'https://images.unsplash.com/photo-1594035910387-fea47794261f?auto=format&fit=crop&w=1600&q=85',
                'button_text' => 'Explore Perfumes',
                'position' => 'main_hero',
                'product_id' => $mfk?->id,
                'price' => 78000.00,
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($banners as $b) {
            Banner::updateOrCreate(
                ['title' => $b['title']],
                $b
            );
        }
    }
}
