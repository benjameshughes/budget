<?php

declare(strict_types=1);

use App\Livewire\Settings\WeeklyBudget;
use App\Models\User;
use Livewire\Livewire;

test('weekly budget page is displayed', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/settings/weekly-budget')->assertOk();
});

test('weekly budget can be updated', function () {
    $user = User::factory()->create([
        'weekly_budget' => 100.00,
    ]);

    $this->actingAs($user);

    $response = Livewire::test(WeeklyBudget::class)
        ->set('weekly_budget', '250.50')
        ->call('updateWeeklyBudget');

    $response->assertHasNoErrors();

    $user->refresh();

    expect($user->weekly_budget)->toEqual('250.50');
});

test('weekly budget loads current value on mount', function () {
    $user = User::factory()->create([
        'weekly_budget' => 150.75,
    ]);

    $this->actingAs($user);

    $response = Livewire::test(WeeklyBudget::class);

    $response->assertSet('weekly_budget', '150.75');
});

test('weekly budget requires numeric value', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test(WeeklyBudget::class)
        ->set('weekly_budget', 'not-a-number')
        ->call('updateWeeklyBudget');

    $response->assertHasErrors(['weekly_budget']);
});

test('weekly budget requires non-negative value', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test(WeeklyBudget::class)
        ->set('weekly_budget', '-50.00')
        ->call('updateWeeklyBudget');

    $response->assertHasErrors(['weekly_budget']);
});

test('weekly budget accepts zero', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test(WeeklyBudget::class)
        ->set('weekly_budget', '0.00')
        ->call('updateWeeklyBudget');

    $response->assertHasNoErrors();

    $user->refresh();

    expect($user->weekly_budget)->toEqual('0.00');
});

test('weekly budget requires a value', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test(WeeklyBudget::class)
        ->set('weekly_budget', '')
        ->call('updateWeeklyBudget');

    $response->assertHasErrors(['weekly_budget']);
});

test('weekly budget handles decimal values correctly', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test(WeeklyBudget::class)
        ->set('weekly_budget', '99.99')
        ->call('updateWeeklyBudget');

    $response->assertHasNoErrors();

    $user->refresh();

    expect($user->weekly_budget)->toEqual('99.99');
});

test('weekly budget page requires authentication', function () {
    $this->get('/settings/weekly-budget')->assertRedirect('/login');
});
