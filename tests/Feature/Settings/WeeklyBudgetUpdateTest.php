<?php

declare(strict_types=1);

use App\Livewire\Settings\PayBudget;
use App\Models\User;
use Livewire\Livewire;

test('pay & budget page is displayed', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/settings/pay-budget')->assertOk();
});

test('weekly budget can be updated', function () {
    $user = User::factory()->create([
        'weekly_budget' => 100.00,
    ]);

    $this->actingAs($user);

    Livewire::test(PayBudget::class)
        ->set('weekly_budget', '250.50')
        ->call('save')
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->weekly_budget)->toEqual('250.50');
});

test('weekly budget loads current value on mount', function () {
    $user = User::factory()->create([
        'weekly_budget' => 150.75,
    ]);

    $this->actingAs($user);

    Livewire::test(PayBudget::class)
        ->assertSet('weekly_budget', '150.75');
});

test('weekly budget requires numeric value', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(PayBudget::class)
        ->set('weekly_budget', 'not-a-number')
        ->call('save')
        ->assertHasErrors(['weekly_budget']);
});

test('weekly budget requires non-negative value', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(PayBudget::class)
        ->set('weekly_budget', '-50.00')
        ->call('save')
        ->assertHasErrors(['weekly_budget']);
});

test('weekly budget accepts zero', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(PayBudget::class)
        ->set('weekly_budget', '0.00')
        ->call('save')
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->weekly_budget)->toEqual('0.00');
});

test('weekly budget requires a value', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(PayBudget::class)
        ->set('weekly_budget', '')
        ->call('save')
        ->assertHasErrors(['weekly_budget']);
});

test('weekly budget handles decimal values correctly', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(PayBudget::class)
        ->set('weekly_budget', '99.99')
        ->call('save')
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->weekly_budget)->toEqual('99.99');
});

test('pay & budget page requires authentication', function () {
    $this->get('/settings/pay-budget')->assertRedirect('/login');
});

test('pay cadence and pay day can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(PayBudget::class)
        ->set('pay_cadence', 'monthly')
        ->set('pay_day', 25)
        ->call('save')
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->pay_cadence->value)->toBe('monthly')
        ->and($user->pay_day)->toBe(25);
});

test('weekly savings goal can be cleared', function () {
    $user = User::factory()->create(['weekly_savings_goal' => 50.00]);

    $this->actingAs($user);

    Livewire::test(PayBudget::class)
        ->set('weekly_savings_goal', '')
        ->call('save')
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->weekly_savings_goal)->toBeNull();
});
