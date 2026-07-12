<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Debt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Debt>
 */
class DebtFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['Car Loan', 'Student Loan', 'Personal Loan', 'Overdraft', 'Store Card']),
            'starting_balance' => fake()->randomFloat(2, 500, 10000),
            'minimum_payment' => fake()->randomFloat(2, 25, 200),
            'interest_rate' => fake()->randomFloat(2, 3, 30),
            'due_day' => fake()->numberBetween(1, 28),
            'cleared_at' => null,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function cleared(): static
    {
        return $this->state(fn (array $attributes) => [
            'cleared_at' => now(),
        ]);
    }

    public function interestFree(): static
    {
        return $this->state(fn (array $attributes) => [
            'interest_rate' => null,
        ]);
    }

    public function withBalance(float $balance): static
    {
        return $this->state(fn (array $attributes) => [
            'starting_balance' => $balance,
        ]);
    }

    public function withMinimum(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'minimum_payment' => $amount,
        ]);
    }

    public function withInterest(float $rate): static
    {
        return $this->state(fn (array $attributes) => [
            'interest_rate' => $rate,
        ]);
    }
}
