<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RuleActionType;
use App\Enums\TriggerEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AutomationRule>
 */
class AutomationRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
            'trigger_event' => TriggerEvent::TransactionReceived,
            'trigger_conditions' => [],
            'actions' => [
                [
                    'type' => RuleActionType::SendNotification->value,
                    'message' => 'Transaction received',
                ],
            ],
            'last_run_at' => null,
            'run_count' => 0,
        ];
    }

    public function salaryTrigger(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Salary Automation',
            'trigger_event' => TriggerEvent::SalaryDetected,
            'trigger_conditions' => [
                ['field' => 'trigger.is_credit', 'operator' => '==', 'value' => true],
                ['field' => 'trigger.amount', 'operator' => '>=', 'value' => 50000],
            ],
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withDepositAction(int $potId, int $amountPence): static
    {
        return $this->state(fn (array $attributes) => [
            'actions' => [
                [
                    'type' => RuleActionType::DepositToPot->value,
                    'pot_id' => $potId,
                    'amount' => (string) $amountPence,
                ],
            ],
        ]);
    }

    public function withPurposeDepositAction(string $purpose, int $amountPence): static
    {
        return $this->state(fn (array $attributes) => [
            'actions' => [
                [
                    'type' => RuleActionType::DepositToPot->value,
                    'pot_purpose' => $purpose,
                    'amount' => (string) $amountPence,
                ],
            ],
        ]);
    }
}
