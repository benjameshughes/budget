<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BnplInstallment>
 */
class BnplInstallmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'bnpl_purchase_id' => \App\Models\BnplPurchase::factory(),
            'installment_number' => fake()->numberBetween(1, 4),
            'amount' => fake()->randomFloat(2, 10, 150),
            'due_date' => fake()->dateTimeBetween('-2 months', '+2 months'),
            'is_paid' => false,
            'paid_date' => null,
        ];
    }
}
