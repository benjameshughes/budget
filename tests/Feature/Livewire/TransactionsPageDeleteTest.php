<?php

declare(strict_types=1);

use App\Livewire\TransactionsPage;
use App\Models\Transaction;
use App\Models\User;
use Livewire\Livewire;

test('user can delete their own transaction via livewire', function () {
    $user = User::factory()->create();
    $transaction = Transaction::factory()->forUser($user)->create();

    Livewire::actingAs($user)
        ->test(TransactionsPage::class)
        ->call('confirmDelete', $transaction->id)
        ->assertSet('showDeleteModal', true)
        ->assertSet('transactionToDelete', $transaction->id)
        ->call('deleteTransaction')
        ->assertSet('showDeleteModal', false)
        ->assertHasNoErrors();

    expect(Transaction::find($transaction->id))->toBeNull();
});

test('user cannot delete another users transaction via livewire', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $transaction = Transaction::factory()->forUser($otherUser)->create();

    Livewire::actingAs($user)
        ->test(TransactionsPage::class)
        ->call('confirmDelete', $transaction->id)
        ->call('deleteTransaction')
        ->assertForbidden();

    expect(Transaction::find($transaction->id))->not->toBeNull();
});
