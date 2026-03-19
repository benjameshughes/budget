<?php

declare(strict_types=1);
use App\Livewire\SimpleDashboard;
use App\Models\ConnectedAccount;
use App\Models\Transaction;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get('/dashboard')
        ->assertStatus(200)
        ->assertSeeLivewire(SimpleDashboard::class);
});

test('dashboard shows budget breakdown', function () {
    $user = User::factory()->withBudget(200.00)->create();

    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertStatus(200);
    // Should see the budget breakdown pills and upcoming sections
    expect($response->getContent())
        ->toContain('spent')
        ->toContain('Bills Coming Up')
        ->toContain('BNPL Coming Up');
});

test('dashboard loads with transactions', function () {
    $user = User::factory()->create();

    Transaction::factory()
        ->count(3)
        ->state(['user_id' => $user->id])
        ->create();

    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertStatus(200)
        ->assertSeeLivewire(SimpleDashboard::class);
});

test('dashboard shows connected account balances as pills', function () {
    $user = User::factory()->create();

    ConnectedAccount::factory()->monzo()->create([
        'user_id' => $user->id,
        'balance_pence' => 150075,
        'is_active' => true,
    ]);

    ConnectedAccount::factory()->starling()->create([
        'user_id' => $user->id,
        'balance_pence' => 42000,
        'is_active' => true,
    ]);

    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertStatus(200);
    expect($response->getContent())
        ->toContain('1,500.75')
        ->toContain('Monzo')
        ->toContain('420.00')
        ->toContain('Starling');
});

test('dashboard hides account pill when balance is zero', function () {
    $user = User::factory()->create();

    ConnectedAccount::factory()->monzo()->create([
        'user_id' => $user->id,
        'balance_pence' => 0,
        'is_active' => true,
    ]);

    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertStatus(200);
    expect($response->getContent())->not->toContain('building-library');
});
