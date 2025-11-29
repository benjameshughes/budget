<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BnplPurchase>
 */
class BnplPurchaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $provider = fake()->randomElement(\App\Enums\BnplProvider::cases());
        $totalAmount = fake()->randomFloat(2, 50, 500);
        $fee = $provider === \App\Enums\BnplProvider::Zilch ? fake()->randomFloat(2, 0, 5) : 0;

        return [
            'user_id' => \App\Models\User::factory(),
            'merchant' => fake()->randomElement([
                'Nike',
                'ASOS',
                'Amazon',
                'Zara',
                'H&M',
                'Apple',
                'John Lewis',
                'Currys',
                'Argos',
                'Next',
            ]),
            'total_amount' => $totalAmount,
            'provider' => $provider,
            'fee' => $fee,
            'purchase_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'notes' => fake()->boolean(30) ? fake()->sentence() : null,
        ];
    }
}
