<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DebtPayment>
 */
class DebtPaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'debt_id' => Debt::factory(),
            'amount' => fake()->randomFloat(2, 25, 500),
            'payment_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function forDebt(Debt $debt): static
    {
        return $this->state(fn (array $attributes) => [
            'debt_id' => $debt->id,
            'user_id' => $debt->user_id,
        ]);
    }

    public function withAmount(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
        ]);
    }

    public function onDate(\DateTimeInterface|string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_date' => $date,
        ]);
    }
}
