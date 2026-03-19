<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BankProvider;
use App\Models\ConnectedAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BankTransaction>
 */
class BankTransactionFactory extends Factory
{
    public function definition(): array
    {
        $provider = fake()->randomElement(BankProvider::cases());

        return [
            'user_id' => User::factory(),
            'connected_account_id' => ConnectedAccount::factory(),
            'external_id' => 'tx_'.fake()->uuid(),
            'provider' => $provider,
            'amount' => fake()->randomFloat(2, -500, 500),
            'currency' => 'GBP',
            'description' => fake()->sentence(3),
            'merchant_name' => fake()->company(),
            'category' => fake()->randomElement(['groceries', 'eating_out', 'transport', 'shopping', 'bills', null]),
            'notes' => null,
            'transacted_at' => fake()->dateTimeBetween('-30 days'),
            'imported_at' => now(),
            'metadata' => [],
        ];
    }

    public function monzo(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => BankProvider::Monzo,
        ]);
    }

    public function starling(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => BankProvider::Starling,
        ]);
    }

    public function credit(float $amount = 1500.00): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => abs($amount),
            'description' => 'Salary Payment',
            'category' => 'income',
        ]);
    }

    public function debit(float $amount = 25.50): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => -abs($amount),
        ]);
    }
}
