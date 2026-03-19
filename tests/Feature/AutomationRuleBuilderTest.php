<?php

declare(strict_types=1);

use App\Enums\RuleActionType;
use App\Enums\TriggerEvent;
use App\Livewire\AutomationRuleBuilder;
use App\Models\AutomationRule;
use App\Models\AutomationRuleLog;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

test('automations page renders with the builder component', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('automations'))
        ->assertOk()
        ->assertSeeLivewire(AutomationRuleBuilder::class);
});

test('builder shows empty state when no rules exist', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(AutomationRuleBuilder::class)
        ->assertSee('No automation rules yet')
        ->assertSee('Create First Rule');
});

test('user can create a new automation rule', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(AutomationRuleBuilder::class)
        ->call('createRule')
        ->set('name', 'Salary Bills Deposit')
        ->set('triggerEvent', TriggerEvent::SalaryDetected->value)
        ->set('actions', [
            [
                'type' => RuleActionType::SendNotification->value,
                'message' => 'Salary received!',
            ],
        ])
        ->call('saveRule')
        ->assertHasNoErrors();

    expect(AutomationRule::count())->toBe(1);

    $rule = AutomationRule::first();
    expect($rule->name)->toBe('Salary Bills Deposit');
    expect($rule->trigger_event)->toBe(TriggerEvent::SalaryDetected);
    expect($rule->is_active)->toBeTrue();
    expect($rule->actions)->toHaveCount(1);
});

test('user can edit an existing rule', function () {
    $user = User::factory()->create();
    $rule = AutomationRule::factory()->create([
        'user_id' => $user->id,
        'name' => 'Original Name',
    ]);

    Livewire::actingAs($user)
        ->test(AutomationRuleBuilder::class)
        ->call('editRule', $rule->id)
        ->set('name', 'Updated Name')
        ->call('saveRule')
        ->assertHasNoErrors();

    expect($rule->fresh()->name)->toBe('Updated Name');
});

test('user can delete a rule', function () {
    $user = User::factory()->create();
    $rule = AutomationRule::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(AutomationRuleBuilder::class)
        ->call('deleteRule', $rule->id);

    expect(AutomationRule::count())->toBe(0);
});

test('user can toggle a rule active state', function () {
    $user = User::factory()->create();
    $rule = AutomationRule::factory()->create([
        'user_id' => $user->id,
        'is_active' => true,
    ]);

    Livewire::actingAs($user)
        ->test(AutomationRuleBuilder::class)
        ->call('toggleRule', $rule->id);

    expect($rule->fresh()->is_active)->toBeFalse();
});

test('user cannot edit another users rule', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $rule = AutomationRule::factory()->create(['user_id' => $otherUser->id]);

    expect(fn () => Livewire::actingAs($user)
        ->test(AutomationRuleBuilder::class)
        ->call('editRule', $rule->id)
    )->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

test('creating a rule requires a name', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(AutomationRuleBuilder::class)
        ->call('createRule')
        ->set('name', '')
        ->set('actions', [['type' => RuleActionType::SendNotification->value, 'message' => 'test']])
        ->call('saveRule')
        ->assertHasErrors('name');
});

test('creating a rule requires at least one action', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(AutomationRuleBuilder::class)
        ->call('createRule')
        ->set('name', 'Test Rule')
        ->set('actions', [])
        ->call('saveRule')
        ->assertHasErrors('actions');
});

test('add and remove conditions', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(AutomationRuleBuilder::class)
        ->call('createRule')
        ->call('addCondition');

    expect($component->get('conditions'))->toHaveCount(1);

    $component->call('removeCondition', 0);

    expect($component->get('conditions'))->toHaveCount(0);
});

test('add and remove actions', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(AutomationRuleBuilder::class)
        ->call('createRule')
        ->call('addAction');

    expect($component->get('actions'))->toHaveCount(1);

    $component->call('removeAction', 0);

    expect($component->get('actions'))->toHaveCount(0);
});

test('dry run creates a log entry without incrementing run count', function () {
    $user = User::factory()->create();
    $rule = AutomationRule::factory()->create([
        'user_id' => $user->id,
        'run_count' => 0,
        'actions' => [
            ['type' => RuleActionType::SendNotification->value, 'message' => 'Dry run test'],
        ],
    ]);

    Livewire::actingAs($user)
        ->test(AutomationRuleBuilder::class)
        ->call('dryRun', $rule->id);

    expect(AutomationRuleLog::count())->toBe(1);
    expect(AutomationRuleLog::first()->status)->toBe('success');
    expect($rule->fresh()->run_count)->toBe(0);
});

test('rules list shows existing rules', function () {
    $user = User::factory()->create();
    AutomationRule::factory()->create([
        'user_id' => $user->id,
        'name' => 'My Salary Rule',
        'trigger_event' => TriggerEvent::SalaryDetected,
    ]);

    Livewire::actingAs($user)
        ->test(AutomationRuleBuilder::class)
        ->assertSee('My Salary Rule')
        ->assertSee('Salary Detected');
});

test('recent logs are displayed', function () {
    $user = User::factory()->create();
    $rule = AutomationRule::factory()->create([
        'user_id' => $user->id,
        'name' => 'Logged Rule',
    ]);
    AutomationRuleLog::factory()->create([
        'user_id' => $user->id,
        'automation_rule_id' => $rule->id,
        'status' => 'success',
    ]);

    Livewire::actingAs($user)
        ->test(AutomationRuleBuilder::class)
        ->assertSee('Recent Activity')
        ->assertSee('Logged Rule');
});

test('manual trigger executes rule and increments run count', function () {
    $user = User::factory()->create();
    $rule = AutomationRule::factory()->create([
        'user_id' => $user->id,
        'run_count' => 0,
        'actions' => [
            ['type' => RuleActionType::SendNotification->value, 'message' => 'Manual test'],
        ],
    ]);

    Livewire::actingAs($user)
        ->test(AutomationRuleBuilder::class)
        ->call('manualTrigger', $rule->id);

    expect($rule->fresh()->run_count)->toBe(1);
    expect(AutomationRuleLog::count())->toBe(1);
    expect(AutomationRuleLog::first()->status)->toBe('success');
});

test('stats bar shows active rule count', function () {
    $user = User::factory()->create();
    AutomationRule::factory()->count(3)->create(['user_id' => $user->id, 'is_active' => true]);
    AutomationRule::factory()->inactive()->create(['user_id' => $user->id]);

    $component = Livewire::actingAs($user)
        ->test(AutomationRuleBuilder::class);

    expect($component->get('activeRuleCount'))->toBe(3);
});

test('stats bar shows today run counts', function () {
    $user = User::factory()->create();
    $rule = AutomationRule::factory()->create(['user_id' => $user->id]);

    // Today's logs
    AutomationRuleLog::factory()->count(2)->create([
        'user_id' => $user->id,
        'automation_rule_id' => $rule->id,
        'status' => 'success',
        'ran_at' => now(),
    ]);
    AutomationRuleLog::factory()->failed()->create([
        'user_id' => $user->id,
        'automation_rule_id' => $rule->id,
        'ran_at' => now(),
    ]);

    // Yesterday's log (should not count)
    AutomationRuleLog::factory()->create([
        'user_id' => $user->id,
        'automation_rule_id' => $rule->id,
        'ran_at' => now()->subDay(),
    ]);

    $component = Livewire::actingAs($user)
        ->test(AutomationRuleBuilder::class);

    expect($component->get('todayRunCount'))->toBe(3);
    expect($component->get('todaySuccessCount'))->toBe(2);
    expect($component->get('todayFailCount'))->toBe(1);
});
