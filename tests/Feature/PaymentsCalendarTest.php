<?php

declare(strict_types=1);

use App\Livewire\PaymentsCalendar;
use App\Models\Bill;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected to login', function () {
    $this->get('/calendar')->assertRedirect('/login');
});

test('authenticated users can visit the calendar', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/calendar')
        ->assertOk()
        ->assertSeeLivewire(PaymentsCalendar::class);
});

test('calendar shows bills due this month', function () {
    $user = User::factory()->create();

    Bill::factory()->for($user)->create([
        'name' => 'Netflix',
        'amount' => 17.99,
        'next_due_date' => now()->startOfMonth()->addDays(5),
        'active' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(PaymentsCalendar::class)
        ->assertSee('Netflix')
        ->assertSee('17.99');
});

test('calendar does not show bills from other months', function () {
    $user = User::factory()->create();

    Bill::factory()->for($user)->create([
        'name' => 'Future Bill',
        'amount' => 50.00,
        'next_due_date' => now()->addMonths(2),
        'active' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(PaymentsCalendar::class)
        ->assertDontSee('Future Bill');
});

test('calendar month navigation works', function () {
    $this->actingAs(User::factory()->create());

    $nextMonthLabel = now()->addMonth()->format('F Y');

    Livewire::test(PaymentsCalendar::class)
        ->call('nextMonth')
        ->assertSee($nextMonthLabel);
});

test('monthly total sums bills for the month', function () {
    $user = User::factory()->create();

    Bill::factory()->for($user)->create([
        'name' => 'Rent',
        'amount' => 800.00,
        'next_due_date' => now()->startOfMonth()->addDays(1),
        'active' => true,
    ]);

    $this->actingAs($user);

    expect(
        Livewire::test(PaymentsCalendar::class)->instance()->monthlyTotal
    )->toBe(800.0);
});
