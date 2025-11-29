<?php

use App\Livewire\BnplSummary;
use App\Models\BnplInstallment;
use App\Models\BnplPurchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('displays purchases for authenticated user', function () {
    $user = User::factory()->create();
    $purchase = BnplPurchase::factory()->for($user)->create(['merchant' => 'Nike']);

    BnplInstallment::create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'installment_number' => 1,
        'amount' => 25.00,
        'due_date' => now()->addDays(10),
        'is_paid' => false,
    ]);

    $this->actingAs($user);

    Livewire::test(BnplSummary::class)
        ->assertSee('Nike')
        ->assertSee('Buy Now Pay Later');
});

test('shows total outstanding correctly', function () {
    $user = User::factory()->create();
    $purchase = BnplPurchase::factory()->for($user)->create();

    BnplInstallment::create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'installment_number' => 1,
        'amount' => 25.00,
        'due_date' => now(),
        'is_paid' => false,
    ]);
    BnplInstallment::create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'installment_number' => 2,
        'amount' => 30.00,
        'due_date' => now()->addWeeks(2),
        'is_paid' => false,
    ]);
    BnplInstallment::create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'installment_number' => 3,
        'amount' => 20.00,
        'due_date' => now()->addWeeks(4),
        'is_paid' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(BnplSummary::class)
        ->assertSee('55.00');
});

test('refreshes on bnpl-purchase-created event', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(BnplSummary::class)
        ->dispatch('bnpl-purchase-created')
        ->assertStatus(200);
});

test('refreshes on bnpl-installment-paid event', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(BnplSummary::class)
        ->dispatch('bnpl-installment-paid')
        ->assertStatus(200);
});
