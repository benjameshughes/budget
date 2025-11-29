<?php

use App\Livewire\BudgetSummary;
use App\Livewire\CategoryBreakdown;
use App\Livewire\Components\TotalMoney;
use App\Livewire\SavingsAccountsSummary;
use App\Livewire\SpendingChart;
use App\Livewire\TransactionTable;
use App\Livewire\UpcomingPayments;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get('/analytics')->assertRedirect('/login');
});

test('authenticated users can visit the analytics page', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get('/analytics')
        ->assertStatus(200)
        ->assertSee('Analytics')
        ->assertSee('Deep dive into your spending patterns');
});

test('analytics page contains all required livewire components', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get('/analytics')
        ->assertStatus(200)
        ->assertSeeLivewire(TotalMoney::class)
        ->assertSeeLivewire(BudgetSummary::class)
        ->assertSeeLivewire(SavingsAccountsSummary::class)
        ->assertSeeLivewire(SpendingChart::class)
        ->assertSeeLivewire(CategoryBreakdown::class)
        ->assertSeeLivewire(TransactionTable::class)
        ->assertSeeLivewire(UpcomingPayments::class);
});
