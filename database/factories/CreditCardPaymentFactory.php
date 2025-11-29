<?php

namespace Database\Factories;

use App\Models\CreditCard;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CreditCardPayment>
 */
class CreditCardPaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'credit_card_id' => CreditCard::factory(),
            'transaction_id' => null,
            'amount' => fake()->randomFloat(2, 25, 500),
            'payment_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function forCard(CreditCard $card): static
    {
        return $this->state(fn (array $attributes) => [
            'credit_card_id' => $card->id,
            'user_id' => $card->user_id,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
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
            'payment_date' => $date,
        ]);
    }
}
