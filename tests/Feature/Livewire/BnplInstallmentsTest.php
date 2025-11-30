<?php

use App\Livewire\Components\BnplInstallments;
use App\Models\BnplInstallment;
use App\Models\BnplPurchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('shows purchases with unpaid installments', function () {
    $user = User::factory()->create();
    $purchase = BnplPurchase::factory()->for($user)->create([
        'merchant' => 'Nike',
    ]);

    BnplInstallment::create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'installment_number' => 1,
        'amount' => 25.00,
        'due_date' => now(),
        'is_paid' => false,
    ]);

    $this->actingAs($user);

    Livewire::test(BnplInstallments::class)
        ->assertSee('Nike');
});

test('hides fully paid purchases', function () {
    $user = User::factory()->create();
    $purchase = BnplPurchase::factory()->for($user)->create([
        'merchant' => 'FullyPaidStore',
    ]);

    for ($i = 1; $i <= 4; $i++) {
        BnplInstallment::create([
            'user_id' => $user->id,
            'bnpl_purchase_id' => $purchase->id,
            'installment_number' => $i,
            'amount' => 25.00,
            'due_date' => now()->addWeeks(($i - 1) * 2),
            'is_paid' => true,
            'paid_date' => now(),
        ]);
    }

    $this->actingAs($user);

    Livewire::test(BnplInstallments::class)
        ->assertDontSee('FullyPaidStore');
});

test('shows purchase with partially paid installments', function () {
    $user = User::factory()->create();
    $purchase = BnplPurchase::factory()->for($user)->create([
        'merchant' => 'PartialStore',
    ]);

    // 3 paid, 1 unpaid - should STILL show in the list
    for ($i = 1; $i <= 4; $i++) {
        BnplInstallment::create([
            'user_id' => $user->id,
            'bnpl_purchase_id' => $purchase->id,
            'installment_number' => $i,
            'amount' => 25.00,
            'due_date' => now()->addWeeks(($i - 1) * 2),
            'is_paid' => $i <= 3, // First 3 are paid
            'paid_date' => $i <= 3 ? now() : null,
        ]);
    }

    $this->actingAs($user);

    Livewire::test(BnplInstallments::class)
        ->assertSee('PartialStore')
        ->assertSee('1 of 4 remaining');
});

test('still shows purchase after marking one installment paid', function () {
    $user = User::factory()->create();
    $purchase = BnplPurchase::factory()->for($user)->create([
        'merchant' => 'TestStore',
    ]);

    // Create 4 unpaid installments
    for ($i = 1; $i <= 4; $i++) {
        BnplInstallment::create([
            'user_id' => $user->id,
            'bnpl_purchase_id' => $purchase->id,
            'installment_number' => $i,
            'amount' => 25.00,
            'due_date' => now()->addWeeks(($i - 1) * 2),
            'is_paid' => false,
        ]);
    }

    $this->actingAs($user);

    // Initially shows 4 remaining
    Livewire::test(BnplInstallments::class)
        ->assertSee('TestStore')
        ->assertSee('4 of 4 remaining');

    // Now mark one installment as paid
    $firstInstallment = BnplInstallment::where('bnpl_purchase_id', $purchase->id)
        ->where('installment_number', 1)
        ->first();
    $firstInstallment->update(['is_paid' => true, 'paid_date' => now()]);

    // Refresh should still show the purchase with 3 remaining
    Livewire::test(BnplInstallments::class)
        ->call('refreshPurchases')
        ->assertSee('TestStore')
        ->assertSee('3 of 4 remaining');
});

test('dispatches show-bnpl-purchase-detail event when clicking purchase', function () {
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

    $this->actingAs($user);

    Livewire::test(BnplInstallments::class)
        ->call('showPurchaseDetail', $purchase->id)
        ->assertDispatched('show-bnpl-purchase-detail', purchaseId: $purchase->id);
});

test('refreshes when bnpl-installment-paid event is received', function () {
    $user = User::factory()->create();
    $purchase = BnplPurchase::factory()->for($user)->create([
        'merchant' => 'RefreshTestStore',
    ]);

    for ($i = 1; $i <= 4; $i++) {
        BnplInstallment::create([
            'user_id' => $user->id,
            'bnpl_purchase_id' => $purchase->id,
            'installment_number' => $i,
            'amount' => 25.00,
            'due_date' => now()->addWeeks(($i - 1) * 2),
            'is_paid' => false,
        ]);
    }

    $this->actingAs($user);

    $component = Livewire::test(BnplInstallments::class)
        ->assertSee('RefreshTestStore')
        ->assertSee('4 of 4 remaining');

    // Mark first installment as paid in database
    BnplInstallment::where('bnpl_purchase_id', $purchase->id)
        ->where('installment_number', 1)
        ->update(['is_paid' => true, 'paid_date' => now()]);

    // Dispatch event to trigger refresh
    $component->dispatch('bnpl-installment-paid')
        ->assertSee('RefreshTestStore')
        ->assertSee('3 of 4 remaining');
});
