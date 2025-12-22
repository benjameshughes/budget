<?php

declare(strict_types=1);

use App\Livewire\BillsManagement;
use App\Models\Bill;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;

uses()->group('livewire');

test('authenticated users can view bills management component', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BillsManagement::class)
        ->assertStatus(200);
});

test('can pay a bill', function () {
    Carbon::setTestNow('2024-01-15');
    $user = User::factory()->create();
    $bill = Bill::factory()->create([
        'user_id' => $user->id,
        'active' => true,
        'next_due_date' => Carbon::today(),
        'amount' => 50.00,
    ]);

    Livewire::actingAs($user)
        ->test(BillsManagement::class)
        ->call('pay', $bill->id)
        ->assertDispatched('bill-paid')
        ->assertDispatched('transaction-added');

    expect(Transaction::where('user_id', $user->id)->count())->toBe(1);
    expect(Transaction::where('user_id', $user->id)->first()->amount)->toBe('50.00');
});

test('cannot pay another users bill', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $bill = Bill::factory()->create([
        'user_id' => $otherUser->id,
        'active' => true,
    ]);

    // The findOrFail will throw ModelNotFoundException because the bill
    // doesn't belong to the authenticated user
    expect(fn () => Livewire::actingAs($user)
        ->test(BillsManagement::class)
        ->call('pay', $bill->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

test('bills are filtered by active status', function () {
    $user = User::factory()->create();
    $activeBill = Bill::factory()->create(['user_id' => $user->id, 'active' => true]);
    $inactiveBill = Bill::factory()->create(['user_id' => $user->id, 'active' => false]);

    Livewire::actingAs($user)
        ->test(BillsManagement::class)
        ->assertSee($activeBill->name)
        ->assertDontSee($inactiveBill->name);
});

test('can toggle bill active status', function () {
    $user = User::factory()->create();
    $bill = Bill::factory()->create(['user_id' => $user->id, 'active' => true]);

    Livewire::actingAs($user)
        ->test(BillsManagement::class)
        ->call('toggleActive', $bill->id);

    expect($bill->fresh()->active)->toBeFalse();
});

test('can delete a bill', function () {
    $user = User::factory()->create();
    $bill = Bill::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(BillsManagement::class)
        ->call('deleteBill', $bill->id);

    expect(Bill::find($bill->id))->toBeNull();
});

test('can sort bills by name', function () {
    $user = User::factory()->create();
    Bill::factory()->create(['user_id' => $user->id, 'name' => 'Zebra Bill']);
    Bill::factory()->create(['user_id' => $user->id, 'name' => 'Apple Bill']);

    $component = Livewire::actingAs($user)
        ->test(BillsManagement::class)
        ->call('sort', 'name');

    expect($component->get('sortBy'))->toBe('name');
    expect($component->get('sortDirection'))->toBe('desc');
});
