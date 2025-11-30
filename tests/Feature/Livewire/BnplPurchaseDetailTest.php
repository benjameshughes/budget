<?php

use App\Livewire\Components\BnplPurchaseDetail;
use App\Models\BnplInstallment;
use App\Models\BnplPurchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('shows purchase details when purchase ID is provided', function () {
    $user = User::factory()->create();
    $purchase = BnplPurchase::factory()->for($user)->create([
        'merchant' => 'Nike',
        'total_amount' => 100.00,
        'fee' => 0,
    ]);

    BnplInstallment::create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'installment_number' => 1,
        'amount' => 25.00,
        'due_date' => now()->addDays(10),
        'is_paid' => false,
    ]);

    $this->actingAs($user);

    Livewire::test(BnplPurchaseDetail::class)
        ->call('showPurchase', $purchase->id)
        ->assertSee('Nike')
        ->assertSee('100.00')
        ->assertSee('25.00');
});

test('shows all four installments for a purchase', function () {
    $user = User::factory()->create();
    $purchase = BnplPurchase::factory()->for($user)->create();

    for ($i = 1; $i <= 4; $i++) {
        BnplInstallment::create([
            'user_id' => $user->id,
            'bnpl_purchase_id' => $purchase->id,
            'installment_number' => $i,
            'amount' => 25.00,
            'due_date' => now()->addWeeks($i * 2),
            'is_paid' => false,
        ]);
    }

    $this->actingAs($user);

    Livewire::test(BnplPurchaseDetail::class)
        ->call('showPurchase', $purchase->id)
        ->assertSee('1')
        ->assertSee('2')
        ->assertSee('3')
        ->assertSee('4');
});

test('can mark installment as paid with checkbox click', function () {
    $user = User::factory()->create();
    $purchase = BnplPurchase::factory()->for($user)->create();

    $installment = BnplInstallment::create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'installment_number' => 1,
        'amount' => 25.00,
        'due_date' => now(),
        'is_paid' => false,
    ]);

    $this->actingAs($user);

    Livewire::test(BnplPurchaseDetail::class)
        ->set('purchaseId', $purchase->id)
        ->call('markPaid', $installment->id)
        ->assertDispatched('bnpl-installment-paid');

    expect($installment->fresh()->is_paid)->toBeTrue();
});

test('displays paid and unpaid status correctly', function () {
    $user = User::factory()->create();
    $purchase = BnplPurchase::factory()->for($user)->create();

    BnplInstallment::create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'installment_number' => 1,
        'amount' => 25.00,
        'due_date' => now(),
        'is_paid' => true,
        'paid_date' => now(),
    ]);

    BnplInstallment::create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'installment_number' => 2,
        'amount' => 25.00,
        'due_date' => now()->addWeeks(2),
        'is_paid' => false,
    ]);

    $this->actingAs($user);

    Livewire::test(BnplPurchaseDetail::class)
        ->call('showPurchase', $purchase->id)
        ->assertSee('Paid')
        ->assertSee('Unpaid');
});

test('responds to show-bnpl-purchase-detail event', function () {
    $user = User::factory()->create();
    $purchase = BnplPurchase::factory()->for($user)->create();

    $this->actingAs($user);

    Livewire::test(BnplPurchaseDetail::class)
        ->dispatch('show-bnpl-purchase-detail', purchaseId: $purchase->id)
        ->assertSet('purchaseId', $purchase->id);
});

test('cannot mark already paid installment', function () {
    $user = User::factory()->create();
    $purchase = BnplPurchase::factory()->for($user)->create();

    $installment = BnplInstallment::create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'installment_number' => 1,
        'amount' => 25.00,
        'due_date' => now(),
        'is_paid' => true,
        'paid_date' => now()->subDay(),
    ]);

    $this->actingAs($user);

    Livewire::test(BnplPurchaseDetail::class)
        ->set('purchaseId', $purchase->id)
        ->call('markPaid', $installment->id)
        ->assertNotDispatched('bnpl-installment-paid');
});

test('shows total paid and remaining amounts', function () {
    $user = User::factory()->create();
    $purchase = BnplPurchase::factory()->for($user)->create();

    BnplInstallment::create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'installment_number' => 1,
        'amount' => 25.00,
        'due_date' => now(),
        'is_paid' => true,
    ]);

    BnplInstallment::create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'installment_number' => 2,
        'amount' => 25.00,
        'due_date' => now()->addWeeks(2),
        'is_paid' => false,
    ]);

    $this->actingAs($user);

    Livewire::test(BnplPurchaseDetail::class)
        ->call('showPurchase', $purchase->id)
        ->assertSee('Total Paid')
        ->assertSee('Total Remaining')
        ->assertSee('25.00');
});

test('refreshes purchase details when installment is marked as paid', function () {
    $user = User::factory()->create();
    $purchase = BnplPurchase::factory()->for($user)->create();

    $installment = BnplInstallment::create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'installment_number' => 1,
        'amount' => 25.00,
        'due_date' => now(),
        'is_paid' => false,
    ]);

    $this->actingAs($user);

    Livewire::test(BnplPurchaseDetail::class)
        ->call('showPurchase', $purchase->id)
        ->assertSee('Unpaid')
        ->call('markPaid', $installment->id)
        ->assertDispatched('bnpl-installment-paid')
        ->assertSee('Paid');
});
