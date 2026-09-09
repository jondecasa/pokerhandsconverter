<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $starters = [
            [
                'name' => 'Monthly',
                'slug' => 'monthly',
                'description' => 'Unlimited conversions, billed monthly. Cancel anytime.',
                'price' => (float) env('PLAN_MONTHLY_AMOUNT', 9),
                'interval' => 'month',
                'stripe_price_id' => env('STRIPE_PRICE_MONTHLY'),
                'stakes_cap' => null,
                'features' => [
                    'Unlimited CoinPoker → PokerTracker 4 conversions',
                    'Cash & tournament hand histories',
                    'Conversion history & re-downloads',
                    'Cancel anytime, self-serve',
                ],
                'is_highlighted' => false,
                'sort_order' => 10,
            ],
            [
                'name' => 'Yearly',
                'slug' => 'yearly',
                'description' => 'Two months free versus monthly. Unlimited conversions.',
                'price' => (float) env('PLAN_YEARLY_AMOUNT', 90),
                'interval' => 'year',
                'stripe_price_id' => env('STRIPE_PRICE_YEARLY'),
                'stakes_cap' => null,
                'features' => [
                    'Unlimited CoinPoker → PokerTracker 4 conversions',
                    'Cash & tournament hand histories',
                    'Conversion history & re-downloads',
                    'Cancel anytime, self-serve',
                ],
                'is_highlighted' => true,
                'sort_order' => 20,
            ],
        ];

        foreach ($starters as $data) {
            Plan::updateOrCreate(
                ['slug' => $data['slug']],
                $data + ['currency' => 'USD', 'is_visible' => true, 'is_active' => true],
            );
        }
    }
}
