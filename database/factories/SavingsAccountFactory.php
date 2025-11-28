<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SavingsAccount>
 */
class SavingsAccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['Emergency Fund', 'Holiday', 'New Car', 'House Deposit', 'Rainy Day']),
            'target_amount' => fake()->optional()->randomFloat(2, 1000, 50000),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function withTarget(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'target_amount' => $amount,
        ]);
    }

    public function withoutTarget(): static
    {
        return $this->state(fn (array $attributes) => [
            'target_amount' => null,
        ]);
    }

    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }
}
