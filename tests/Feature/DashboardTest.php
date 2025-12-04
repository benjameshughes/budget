<?php

declare(strict_types=1);
use App\Livewire\SimpleDashboard;
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
    // Should see the budget breakdown section
    expect($response->getContent())
        ->toContain('Spent this week')
        ->toContain('Bills Pot');
});

test('dashboard shows recent transactions', function () {
    $user = User::factory()->create();

    Transaction::factory()
        ->count(3)
        ->state(['user_id' => $user->id])
        ->create();

    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertStatus(200)
        ->assertSee('Recent Transactions');
});
