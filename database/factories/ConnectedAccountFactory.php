<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BankProvider;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ConnectedAccount>
 */
class ConnectedAccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => fake()->randomElement(BankProvider::cases()),
            'external_account_id' => fake()->uuid(),
            'access_token' => fake()->sha256(),
            'refresh_token' => fake()->sha256(),
            'token_expires_at' => now()->addHour(),
            'webhook_secret' => fake()->sha256(),
            'display_name' => fake()->company().' Account',
            'currency' => 'GBP',
            'balance_pence' => fake()->numberBetween(0, 500000),
            'last_synced_at' => null,
            'is_active' => true,
        ];
    }

    public function monzo(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => BankProvider::Monzo,
            'display_name' => 'Monzo Current Account',
        ]);
    }

    public function starling(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => BankProvider::Starling,
            'display_name' => 'Starling Personal Account',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
