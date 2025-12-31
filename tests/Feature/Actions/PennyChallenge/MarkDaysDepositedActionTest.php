<?php

declare(strict_types=1);

use App\Actions\PennyChallenge\MarkDaysDepositedAction;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\PennyChallenge;
use App\Models\PennyChallengeDay;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->challenge = PennyChallenge::factory()
        ->forUser($this->user)
        ->create([
            'name' => 'Test Challenge',
            'start_date' => Carbon::create(2026, 1, 1),
            'end_date' => Carbon::create(2026, 1, 10),
        ]);

    // Create 10 days manually
    for ($i = 1; $i <= 10; $i++) {
        PennyChallengeDay::factory()
            ->forChallenge($this->challenge)
            ->dayNumber($i)
            ->create();
    }
});

test('it marks days as deposited', function () {
    $action = app(MarkDaysDepositedAction::class);

    $days = $this->challenge->days()->take(3)->get();
    $dayIds = $days->pluck('id')->toArray();

    $action->handle($this->challenge, $dayIds);

    foreach ($days as $day) {
        $day->refresh();
        expect($day->deposited_at)->not->toBeNull();
    }
});

test('it creates an expense transaction', function () {
    $action = app(MarkDaysDepositedAction::class);

    $days = $this->challenge->days()->whereIn('day_number', [1, 2, 3])->get();
    $dayIds = $days->pluck('id')->toArray();

    $transaction = $action->handle($this->challenge, $dayIds);

    expect($transaction)->toBeInstanceOf(Transaction::class)
        ->and($transaction->user_id)->toBe($this->user->id)
        ->and($transaction->type)->toBe(TransactionType::Expense)
        // Days 1+2+3 = 6 pence = £0.06
        ->and((float) $transaction->amount)->toBe(0.06);
});

test('it links transaction to deposited days', function () {
    $action = app(MarkDaysDepositedAction::class);

    $days = $this->challenge->days()->take(2)->get();
    $dayIds = $days->pluck('id')->toArray();

    $transaction = $action->handle($this->challenge, $dayIds);

    foreach ($days as $day) {
        $day->refresh();
        expect($day->transaction_id)->toBe($transaction->id);
    }
});

test('it creates penny challenge category on demand', function () {
    $action = app(MarkDaysDepositedAction::class);

    $days = $this->challenge->days()->take(1)->get();
    $dayIds = $days->pluck('id')->toArray();

    expect(Category::where('user_id', $this->user->id)->where('name', 'Penny Challenge')->exists())->toBeFalse();

    $transaction = $action->handle($this->challenge, $dayIds);

    expect(Category::where('user_id', $this->user->id)->where('name', 'Penny Challenge')->exists())->toBeTrue()
        ->and($transaction->category->name)->toBe('Penny Challenge');
});

test('it reuses existing penny challenge category', function () {
    $existingCategory = Category::factory()->for($this->user)->create(['name' => 'Penny Challenge']);

    $action = app(MarkDaysDepositedAction::class);

    $days = $this->challenge->days()->take(1)->get();
    $dayIds = $days->pluck('id')->toArray();

    $transaction = $action->handle($this->challenge, $dayIds);

    expect($transaction->category_id)->toBe($existingCategory->id)
        ->and(Category::where('user_id', $this->user->id)->where('name', 'Penny Challenge')->count())->toBe(1);
});

test('it throws exception for already deposited days', function () {
    $action = app(MarkDaysDepositedAction::class);

    // Mark first day as deposited
    $day = $this->challenge->days()->first();
    $day->update(['deposited_at' => now()]);

    $action->handle($this->challenge, [$day->id]);
})->throws(InvalidArgumentException::class);

test('it throws exception for empty day selection', function () {
    $action = app(MarkDaysDepositedAction::class);

    $action->handle($this->challenge, []);
})->throws(InvalidArgumentException::class);

test('it calculates correct amount for multiple days', function () {
    $action = app(MarkDaysDepositedAction::class);

    // Days 5, 6, 7, 8 = 26 pence = £0.26
    $days = $this->challenge->days()->whereIn('day_number', [5, 6, 7, 8])->get();
    $dayIds = $days->pluck('id')->toArray();

    $transaction = $action->handle($this->challenge, $dayIds);

    expect((float) $transaction->amount)->toBe(0.26);
});

test('it generates correct transaction name for single day', function () {
    $action = app(MarkDaysDepositedAction::class);

    $day = $this->challenge->days()->where('day_number', 5)->first();

    $transaction = $action->handle($this->challenge, [$day->id]);

    expect($transaction->name)->toBe('Test Challenge - Day 5');
});

test('it generates correct transaction name for multiple days', function () {
    $action = app(MarkDaysDepositedAction::class);

    $days = $this->challenge->days()->take(3)->get();
    $dayIds = $days->pluck('id')->toArray();

    $transaction = $action->handle($this->challenge, $dayIds);

    expect($transaction->name)->toBe('Test Challenge - 3 days');
});

test('it updates challenge stats after deposit', function () {
    $action = app(MarkDaysDepositedAction::class);

    $days = $this->challenge->days()->whereIn('day_number', [1, 2, 3])->get();
    $dayIds = $days->pluck('id')->toArray();

    expect($this->challenge->depositedCount())->toBe(0)
        ->and($this->challenge->totalDeposited())->toBe(0.0);

    $action->handle($this->challenge, $dayIds);

    $this->challenge->refresh();

    expect($this->challenge->depositedCount())->toBe(3)
        ->and($this->challenge->totalDeposited())->toBe(0.06);
});
