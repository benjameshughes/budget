<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CreditCard>
 */
class CreditCardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['Visa', 'Mastercard', 'American Express', 'Chase Sapphire', 'Discover']),
            'starting_balance' => fake()->randomFloat(2, 100, 5000),
            'credit_limit' => fake()->optional()->randomFloat(2, 5000, 25000),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }

    public function withLimit(float $limit): static
    {
        return $this->state(fn (array $attributes) => [
            'credit_limit' => $limit,
        ]);
    }

    public function withoutLimit(): static
    {
        return $this->state(fn (array $attributes) => [
            'credit_limit' => null,
        ]);
    }
}
