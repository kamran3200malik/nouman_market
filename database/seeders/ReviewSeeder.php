<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $customer1 = User::where('email', 'customer@example.com')->first();
        $customer2 = User::where('email', 'fatima@example.com')->first();
        $customer3 = User::where('email', 'zainab@example.com')->first();

        $ordinary = Product::where('brand', 'The Ordinary')->first();
        $cerave = Product::where('brand', 'CeraVe')->first();
        $fenty = Product::where('brand', 'Fenty Beauty')->first();
        $olaplex = Product::where('brand', 'Olaplex')->first();

        $reviews = [
            [
                'product_id' => $ordinary?->id,
                'user_id' => $customer1?->id,
                'rating' => 5,
                'title' => '100% Genuine product, game changer for acne marks!',
                'comment' => 'I was hesitant to buy online because of so many fakes in Pakistan, but this was 100% authentic batch. My acne scars have faded significantly in just 3 weeks. Fast delivery to Karachi too!',
                'verified_purchase' => true,
                'is_approved' => true,
            ],
            [
                'product_id' => $cerave?->id,
                'user_id' => $customer2?->id,
                'rating' => 5,
                'title' => 'Best cleanser for sensitive skin',
                'comment' => 'Leaves skin super soft without that tight, stripped feeling. Absolutely love the pump bottle packaging and fresh texture.',
                'verified_purchase' => true,
                'is_approved' => true,
            ],
            [
                'product_id' => $fenty?->id,
                'user_id' => $customer3?->id,
                'rating' => 5,
                'title' => 'Fenty Glow is magic on Pakistani skin tones',
                'comment' => 'The shimmer is so fine and non-gritty. It smells like delicious peach vanilla! Worth every rupee.',
                'verified_purchase' => true,
                'is_approved' => true,
            ],
            [
                'product_id' => $olaplex?->id,
                'user_id' => $customer1?->id,
                'rating' => 5,
                'title' => 'Saved my bleached hair',
                'comment' => 'My hair was breaking after color treatment. Olaplex No. 3 completely restored the strength and elasticity. Highly recommended store!',
                'verified_purchase' => true,
                'is_approved' => true,
            ],
        ];

        foreach ($reviews as $rev) {
            if ($rev['product_id'] && $rev['user_id']) {
                Review::updateOrCreate(
                    [
                        'product_id' => $rev['product_id'],
                        'user_id' => $rev['user_id'],
                    ],
                    $rev
                );
            }
        }
    }
}
