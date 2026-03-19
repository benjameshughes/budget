<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ConnectedAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BankPot>
 */
class BankPotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'connected_account_id' => ConnectedAccount::factory(),
            'external_id' => 'pot_'.fake()->uuid(),
            'name' => fake()->randomElement(['Bills', 'Holiday', 'Emergency Fund', 'Rainy Day', 'Kids']),
            'balance_pence' => fake()->numberBetween(0, 100000),
            'goal_amount_pence' => fake()->optional(0.7)->numberBetween(10000, 500000),
            'is_locked' => false,
            'type' => 'pot',
            'is_active' => true,
            'last_synced_at' => null,
        ];
    }

    public function space(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'space',
            'external_id' => 'space_'.fake()->uuid(),
        ]);
    }

    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_locked' => true,
        ]);
    }
}
