<?php

namespace Database\Factories;

use App\Enums\TransferDirection;
use App\Models\SavingsAccount;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SavingsTransfer>
 */
class SavingsTransferFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'savings_account_id' => SavingsAccount::factory(),
            'transaction_id' => null,
            'amount' => fake()->randomFloat(2, 50, 500),
            'direction' => fake()->randomElement(TransferDirection::cases()),
            'transfer_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function deposit(): static
    {
        return $this->state(fn (array $attributes) => [
            'direction' => TransferDirection::Deposit,
        ]);
    }

    public function withdraw(): static
    {
        return $this->state(fn (array $attributes) => [
            'direction' => TransferDirection::Withdraw,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function forAccount(SavingsAccount $account): static
    {
        return $this->state(fn (array $attributes) => [
            'savings_account_id' => $account->id,
            'user_id' => $account->user_id,
        ]);
    }

    public function withTransaction(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_id' => Transaction::factory(),
        ]);
    }

    public function onDate(\DateTimeInterface|string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'transfer_date' => $date,
        ]);
    }
}
