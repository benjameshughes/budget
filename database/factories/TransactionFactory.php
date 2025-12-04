<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'amount' => fake()->randomFloat(2, 10, 1000),
            'type' => fake()->randomElement(TransactionType::cases()),
            'is_savings' => false,
            'is_bill' => false,
            'payment_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'category_id' => null,
        ];
    }

    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TransactionType::Income,
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TransactionType::Expense,
        ]);
    }

    public function bill(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TransactionType::Expense,
            'is_bill' => true,
        ]);
    }

    public function withCategory(): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => Category::factory(),
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function onDate(\DateTimeInterface|string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_date' => $date,
        ]);
    }

    public function thisWeek(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_date' => fake()->dateTimeBetween('monday this week', 'sunday this week'),
        ]);
    }

    public function thisMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_date' => fake()->dateTimeBetween('first day of this month', 'last day of this month'),
        ]);
    }

    public function withAmount(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
        ]);
    }
}
