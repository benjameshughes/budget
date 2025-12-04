<?php

declare(strict_types=1);

use App\Actions\Transaction\DeleteTransactionAction;
use App\Events\Transaction\TransactionDeleted;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Event;

test('user can delete their own transaction', function () {
    Event::fake();

    $user = User::factory()->create();
    $transaction = Transaction::factory()->forUser($user)->create();
    $transactionId = $transaction->id;
    $userId = $user->id;

    $this->actingAs($user);

    $action = new DeleteTransactionAction;
    $action->handle($transaction);

    expect(Transaction::find($transactionId))->toBeNull();

    Event::assertDispatched(TransactionDeleted::class, function ($event) use ($transactionId, $userId) {
        return $event->transactionId === $transactionId
            && $event->userId === $userId;
    });
});

test('user cannot delete another users transaction', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $transaction = Transaction::factory()->forUser($otherUser)->create();

    $this->actingAs($user);

    $action = new DeleteTransactionAction;

    expect(fn () => $action->handle($transaction))
        ->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);
});
