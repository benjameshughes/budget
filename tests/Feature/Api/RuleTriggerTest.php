<?php

declare(strict_types=1);

use App\Enums\RuleActionType;
use App\Enums\TriggerEvent;
use App\Models\AutomationRule;
use App\Models\User;

test('trigger endpoint requires authentication', function () {
    $this->postJson(route('api.rules.trigger'))
        ->assertUnauthorized();
});

test('trigger fires all manual_trigger rules for user', function () {
    $user = User::factory()->create();

    AutomationRule::factory()->create([
        'user_id' => $user->id,
        'trigger_event' => TriggerEvent::ManualTrigger,
        'actions' => [
            ['type' => RuleActionType::SendNotification->value, 'message' => 'Sort those spaces!'],
        ],
    ]);

    // This one should NOT fire — different trigger
    AutomationRule::factory()->create([
        'user_id' => $user->id,
        'trigger_event' => TriggerEvent::TransactionReceived,
        'actions' => [
            ['type' => RuleActionType::SendNotification->value, 'message' => 'Nope'],
        ],
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson(route('api.rules.trigger'));

    $response->assertOk()
        ->assertJsonPath('triggered', 1)
        ->assertJsonPath('results.0.status', 'success');
});

test('trigger specific rule by id', function () {
    $user = User::factory()->create();

    $rule = AutomationRule::factory()->create([
        'user_id' => $user->id,
        'trigger_event' => TriggerEvent::ManualTrigger,
        'actions' => [
            ['type' => RuleActionType::SendNotification->value, 'message' => 'Fired!'],
        ],
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson(route('api.rules.trigger.specific', $rule->id));

    $response->assertOk()
        ->assertJsonPath('status', 'success');
});

test('trigger accepts context in request body', function () {
    $user = User::factory()->create();

    AutomationRule::factory()->create([
        'user_id' => $user->id,
        'trigger_event' => TriggerEvent::ManualTrigger,
        'trigger_conditions' => [
            ['field' => 'trigger.amount', 'operator' => '>=', 'value' => 50000],
        ],
        'actions' => [
            ['type' => RuleActionType::SendNotification->value, 'message' => 'Big amount!'],
        ],
    ]);

    // Should fire — amount meets condition
    $response = $this->actingAs($user, 'sanctum')
        ->postJson(route('api.rules.trigger'), ['amount' => 150000]);

    $response->assertOk()
        ->assertJsonPath('triggered', 1);

    // Should NOT fire — amount too low
    $response = $this->actingAs($user, 'sanctum')
        ->postJson(route('api.rules.trigger'), ['amount' => 1000]);

    $response->assertOk()
        ->assertJsonPath('triggered', 0);
});

test('user cannot trigger another users rule', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $rule = AutomationRule::factory()->create([
        'user_id' => $otherUser->id,
        'trigger_event' => TriggerEvent::ManualTrigger,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson(route('api.rules.trigger.specific', $rule->id))
        ->assertNotFound();
});
