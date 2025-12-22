<?php

declare(strict_types=1);

use App\Livewire\BillsManagement;
use App\Livewire\UpcomingPayments;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get('/bills')->assertRedirect('/login');
});

test('authenticated users can visit the bills page', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get('/bills')
        ->assertStatus(200)
        ->assertSee('Bills')
        ->assertSee('Manage your recurring bills and payments');
});

test('bills page contains all required livewire components', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get('/bills')
        ->assertStatus(200)
        ->assertSeeLivewire(BillsManagement::class)
        ->assertSeeLivewire(UpcomingPayments::class);
});
