<?php

declare(strict_types=1);

use App\Livewire\BnplManagement;
use App\Models\BnplInstallment;
use App\Models\BnplPurchase;
use App\Models\User;
use Livewire\Livewire;

uses()->group('livewire');

test('can delete a purchase with no payments', function () {
    $user = User::factory()->create();
    $purchase = BnplPurchase::factory()->create(['user_id' => $user->id]);
    BnplInstallment::factory()->create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'is_paid' => false,
    ]);

    Livewire::actingAs($user)
        ->test(BnplManagement::class)
        ->call('deletePurchase', $purchase->id)
        ->assertOk();

    expect(BnplPurchase::find($purchase->id))->toBeNull();
    expect(BnplInstallment::where('bnpl_purchase_id', $purchase->id)->count())->toBe(0);
});

test('cannot delete a purchase that has paid installments', function () {
    $user = User::factory()->create();
    $purchase = BnplPurchase::factory()->create(['user_id' => $user->id]);
    BnplInstallment::factory()->create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'is_paid' => true,
    ]);

    Livewire::actingAs($user)
        ->test(BnplManagement::class)
        ->call('deletePurchase', $purchase->id)
        ->assertOk();

    expect(BnplPurchase::find($purchase->id))->not->toBeNull();
});

test('cannot delete another users purchase', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $purchase = BnplPurchase::factory()->create(['user_id' => $other->id]);

    expect(fn () => Livewire::actingAs($user)
        ->test(BnplManagement::class)
        ->call('deletePurchase', $purchase->id)
    )->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect(BnplPurchase::find($purchase->id))->not->toBeNull();
});
