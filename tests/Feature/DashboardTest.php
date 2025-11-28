<?php

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

test('dashboard shows status message', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertStatus(200);
    // Should see the status message - when no transactions exist, it will show overspent by the initial amount
    expect($response->getContent())
        ->toContain('this week');
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
