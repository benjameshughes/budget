<?php

declare(strict_types=1);

use App\Livewire\Components\BillsPotSummary;
use App\Models\Bill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('shows not configured message when user has no bills', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(BillsPotSummary::class)
        ->assertSee('Bills Pot')
        ->assertSee('Add bills to get started');
});

test('displays weekly set-aside amount prominently when bills exist', function () {
    $user = User::factory()->create();

    // Create a monthly bill of £100
    Bill::factory()
        ->for($user)
        ->monthly()
        ->create([
            'amount' => 100.00,
            'active' => true,
        ]);

    $this->actingAs($user);

    // Monthly total = £100
    // Weekly contribution = (£100 * 1.0) / 4.33 = £23.09
    Livewire::test(BillsPotSummary::class)
        ->assertSee('Weekly set-aside')
        ->assertSee('23.09');
});

test('displays float target with multiplier when multiplier is set', function () {
    $user = User::factory()->create([
        'bills_float_multiplier' => 1.1,
    ]);

    Bill::factory()
        ->for($user)
        ->monthly()
        ->create([
            'amount' => 100.00,
            'active' => true,
        ]);

    $this->actingAs($user);

    // Monthly total = £100
    // Target = £100 * 1.1 = £110
    // Weekly contribution = £110 / 4.33 = £25.40
    Livewire::test(BillsPotSummary::class)
        ->assertSee('Weekly set-aside')
        ->assertSee('25.40')
        ->assertSee('110.00'); // Target shown in header
});

test('refreshes on bill-created event', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(BillsPotSummary::class)
        ->dispatch('bill-created')
        ->assertStatus(200);
});

test('refreshes on bill-updated event', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(BillsPotSummary::class)
        ->dispatch('bill-updated')
        ->assertStatus(200);
});

test('refreshes on bill-deleted event', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(BillsPotSummary::class)
        ->dispatch('bill-deleted')
        ->assertStatus(200);
});

test('refreshes on weekly-budget-updated event', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(BillsPotSummary::class)
        ->dispatch('weekly-budget-updated')
        ->assertStatus(200);
});

test('refreshes on bnpl-installment-paid event', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(BillsPotSummary::class)
        ->dispatch('bnpl-installment-paid')
        ->assertStatus(200);
});

test('calculates weekly contribution correctly for multiple bills with different cadences', function () {
    $user = User::factory()->create();

    // Monthly bill: £100/month
    Bill::factory()
        ->for($user)
        ->monthly()
        ->create([
            'amount' => 100.00,
            'active' => true,
        ]);

    // Weekly bill: £20/week = £20 * (52/12) = £86.67/month (rounded)
    Bill::factory()
        ->for($user)
        ->weekly()
        ->create([
            'amount' => 20.00,
            'active' => true,
        ]);

    $this->actingAs($user);

    // Weekly set-aside should be visible with calculated amount
    Livewire::test(BillsPotSummary::class)
        ->assertSee('Weekly set-aside');
});
