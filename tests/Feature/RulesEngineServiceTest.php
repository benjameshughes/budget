<?php

declare(strict_types=1);

use App\Enums\PotPurpose;
use App\Enums\RuleActionType;
use App\Enums\TriggerEvent;
use App\Models\AutomationRule;
use App\Models\AutomationRuleLog;
use App\Models\BankPot;
use App\Models\ConnectedAccount;
use App\Models\User;
use App\Services\RulesEngineService;
use Illuminate\Support\Facades\Http;

test('evaluate returns true when no conditions', function () {
    $rule = AutomationRule::factory()->create([
        'trigger_conditions' => [],
    ]);

    $service = app(RulesEngineService::class);

    expect($service->evaluate($rule, ['trigger' => ['amount' => 50000]]))->toBeTrue();
});

test('evaluate checks equality condition', function () {
    $rule = AutomationRule::factory()->create([
        'trigger_conditions' => [
            ['field' => 'trigger.is_credit', 'operator' => '==', 'value' => true],
        ],
    ]);

    $service = app(RulesEngineService::class);

    expect($service->evaluate($rule, ['trigger' => ['is_credit' => true]]))->toBeTrue();
    expect($service->evaluate($rule, ['trigger' => ['is_credit' => false]]))->toBeFalse();
});

test('evaluate checks numeric comparison conditions', function () {
    $rule = AutomationRule::factory()->create([
        'trigger_conditions' => [
            ['field' => 'trigger.amount', 'operator' => '>=', 'value' => 50000],
        ],
    ]);

    $service = app(RulesEngineService::class);

    expect($service->evaluate($rule, ['trigger' => ['amount' => 150000]]))->toBeTrue();
    expect($service->evaluate($rule, ['trigger' => ['amount' => 50000]]))->toBeTrue();
    expect($service->evaluate($rule, ['trigger' => ['amount' => 49999]]))->toBeFalse();
});

test('evaluate checks contains condition', function () {
    $rule = AutomationRule::factory()->create([
        'trigger_conditions' => [
            ['field' => 'trigger.description', 'operator' => 'contains', 'value' => 'salary'],
        ],
    ]);

    $service = app(RulesEngineService::class);

    expect($service->evaluate($rule, ['trigger' => ['description' => 'SALARY PAYMENT']]))->toBeTrue();
    expect($service->evaluate($rule, ['trigger' => ['description' => 'Monthly Salary']]))->toBeTrue();
    expect($service->evaluate($rule, ['trigger' => ['description' => 'Costa Coffee']]))->toBeFalse();
});

test('evaluate requires all conditions to be met', function () {
    $rule = AutomationRule::factory()->create([
        'trigger_conditions' => [
            ['field' => 'trigger.is_credit', 'operator' => '==', 'value' => true],
            ['field' => 'trigger.amount', 'operator' => '>=', 'value' => 100000],
        ],
    ]);

    $service = app(RulesEngineService::class);

    // Both met
    expect($service->evaluate($rule, ['trigger' => ['is_credit' => true, 'amount' => 150000]]))->toBeTrue();
    // Only first met
    expect($service->evaluate($rule, ['trigger' => ['is_credit' => true, 'amount' => 5000]]))->toBeFalse();
    // Only second met
    expect($service->evaluate($rule, ['trigger' => ['is_credit' => false, 'amount' => 150000]]))->toBeFalse();
});

test('interpolate replaces template variables', function () {
    $service = app(RulesEngineService::class);

    $result = $service->interpolate(
        'Received {{trigger.amount}} from {{trigger.merchant}}',
        ['trigger' => ['amount' => 150000, 'merchant' => 'Employer Ltd']],
    );

    expect($result)->toBe('Received 150000 from Employer Ltd');
});

test('interpolate handles missing variables gracefully', function () {
    $service = app(RulesEngineService::class);

    $result = $service->interpolate(
        'Amount: {{trigger.amount}}, Missing: {{trigger.nonexistent}}',
        ['trigger' => ['amount' => 5000]],
    );

    expect($result)->toBe('Amount: 5000, Missing: ');
});

test('execute creates a log entry on success', function () {
    $user = User::factory()->create();
    $rule = AutomationRule::factory()->create([
        'user_id' => $user->id,
        'actions' => [
            ['type' => RuleActionType::SendNotification->value, 'message' => 'Test notification'],
        ],
    ]);

    $service = app(RulesEngineService::class);
    $log = $service->execute($rule, ['trigger' => ['amount' => 50000]]);

    expect($log->status)->toBe('success');
    expect($log->automation_rule_id)->toBe($rule->id);
    expect($log->user_id)->toBe($user->id);
    expect($log->error_message)->toBeNull();
    expect(AutomationRuleLog::count())->toBe(1);
});

test('execute increments run count on success', function () {
    $user = User::factory()->create();
    $rule = AutomationRule::factory()->create([
        'user_id' => $user->id,
        'run_count' => 5,
        'actions' => [
            ['type' => RuleActionType::SendNotification->value, 'message' => 'Test'],
        ],
    ]);

    $service = app(RulesEngineService::class);
    $service->execute($rule, ['trigger' => ['amount' => 50000]]);

    expect($rule->fresh()->run_count)->toBe(6);
    expect($rule->fresh()->last_run_at)->not->toBeNull();
});

test('execute does not increment run count on dry run', function () {
    $user = User::factory()->create();
    $rule = AutomationRule::factory()->create([
        'user_id' => $user->id,
        'run_count' => 3,
        'actions' => [
            ['type' => RuleActionType::SendNotification->value, 'message' => 'Test'],
        ],
    ]);

    $service = app(RulesEngineService::class);
    $log = $service->execute($rule, ['trigger' => ['amount' => 50000]], dryRun: true);

    expect($log->status)->toBe('success');
    expect($rule->fresh()->run_count)->toBe(3);
    expect($log->actions_executed[0]['dry_run'] ?? false)->toBeTrue();
});

test('execute creates failed log on error', function () {
    $user = User::factory()->create();
    $rule = AutomationRule::factory()->create([
        'user_id' => $user->id,
        'actions' => [
            [
                'type' => RuleActionType::DepositToPot->value,
                'pot_id' => 99999,
                'amount' => '5000',
            ],
        ],
    ]);

    $service = app(RulesEngineService::class);
    $log = $service->execute($rule, ['trigger' => ['amount' => 50000]]);

    expect($log->status)->toBe('failed');
    expect($log->error_message)->not->toBeNull();
});

test('execute deposit to pot by purpose', function () {
    Http::fake([
        'api.monzo.com/*' => Http::response(['success' => true]),
    ]);

    $user = User::factory()->create();
    $account = ConnectedAccount::factory()->monzo()->create(['user_id' => $user->id]);
    $pot = BankPot::factory()->create([
        'user_id' => $user->id,
        'connected_account_id' => $account->id,
        'purpose' => PotPurpose::BillsFloat,
        'external_id' => 'pot_bills_123',
    ]);

    $rule = AutomationRule::factory()->create([
        'user_id' => $user->id,
        'actions' => [
            [
                'type' => RuleActionType::DepositToPot->value,
                'pot_purpose' => PotPurpose::BillsFloat->value,
                'amount' => '50000',
            ],
        ],
    ]);

    $service = app(RulesEngineService::class);
    $log = $service->execute($rule, ['trigger' => ['amount' => 150000]]);

    expect($log->status)->toBe('success');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'pot_bills_123/deposit')
            && $request['amount'] === 50000;
    });
});

test('evaluateAllForUser runs matching rules', function () {
    $user = User::factory()->create();

    AutomationRule::factory()->create([
        'user_id' => $user->id,
        'trigger_event' => TriggerEvent::SalaryDetected,
        'trigger_conditions' => [],
        'actions' => [
            ['type' => RuleActionType::SendNotification->value, 'message' => 'Salary detected!'],
        ],
    ]);

    AutomationRule::factory()->create([
        'user_id' => $user->id,
        'trigger_event' => TriggerEvent::TransactionReceived,
        'actions' => [
            ['type' => RuleActionType::SendNotification->value, 'message' => 'Transaction received'],
        ],
    ]);

    $service = app(RulesEngineService::class);
    $logs = $service->evaluateAllForUser(
        $user->id,
        TriggerEvent::SalaryDetected,
        ['trigger' => ['amount' => 150000, 'is_credit' => true]],
    );

    expect($logs)->toHaveCount(1);
    expect($logs[0]->status)->toBe('success');
});

test('evaluateAllForUser skips inactive rules', function () {
    $user = User::factory()->create();

    AutomationRule::factory()->inactive()->create([
        'user_id' => $user->id,
        'trigger_event' => TriggerEvent::SalaryDetected,
    ]);

    $service = app(RulesEngineService::class);
    $logs = $service->evaluateAllForUser(
        $user->id,
        TriggerEvent::SalaryDetected,
        ['trigger' => ['amount' => 150000]],
    );

    expect($logs)->toHaveCount(0);
});

test('evaluateAllForUser skips rules that fail conditions', function () {
    $user = User::factory()->create();

    AutomationRule::factory()->create([
        'user_id' => $user->id,
        'trigger_event' => TriggerEvent::TransactionReceived,
        'trigger_conditions' => [
            ['field' => 'trigger.amount', 'operator' => '>=', 'value' => 100000],
        ],
        'actions' => [
            ['type' => RuleActionType::SendNotification->value, 'message' => 'Big transaction!'],
        ],
    ]);

    $service = app(RulesEngineService::class);

    // Small transaction — should not trigger
    $logs = $service->evaluateAllForUser(
        $user->id,
        TriggerEvent::TransactionReceived,
        ['trigger' => ['amount' => 500]],
    );

    expect($logs)->toHaveCount(0);
    expect(AutomationRuleLog::count())->toBe(0);
});
