<?php

declare(strict_types=1);

use App\Enums\PayCadence;
use App\Models\Bill;
use App\Models\BnplInstallment;
use App\Models\BnplPurchase;
use App\Models\Transaction;
use App\Models\User;
use App\Queries\PayPeriodForecastQueries;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->queries = app(PayPeriodForecastQueries::class);
});

test('forecast returns correct period boundaries for weekly pay', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-14')); // Tuesday

    $user = User::factory()->create([
        'pay_cadence' => PayCadence::Weekly,
        'pay_day' => 3, // Wednesday
    ]);

    $forecast = $this->queries->forecast($user);

    expect($forecast['period_start']->toDateString())->toBe('2026-07-08') // last Wednesday
        ->and($forecast['period_end']->toDateString())->toBe('2026-07-15') // next Wednesday
        ->and($forecast['days_left'])->toBe(1);

    Carbon::setTestNow();
});

test('forecast calculates income from transactions', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-14'));

    $user = User::factory()->create([
        'pay_cadence' => PayCadence::Weekly,
        'pay_day' => 3,
    ]);

    Transaction::factory()->forUser($user)->income()->create([
        'amount' => 700.00,
        'payment_date' => '2026-07-09',
    ]);

    Transaction::factory()->forUser($user)->income()->create([
        'amount' => 50.00,
        'payment_date' => '2026-07-10',
    ]);

    $forecast = $this->queries->forecast($user);

    expect($forecast['income'])->toBe(750.0);

    Carbon::setTestNow();
});

test('forecast classifies bills as gone when due date has passed', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-14'));

    $user = User::factory()->create([
        'pay_cadence' => PayCadence::Weekly,
        'pay_day' => 3,
    ]);

    Bill::factory()->forUser($user)->monthly()->dueOn('2026-07-10')->create([
        'name' => 'EDF',
        'amount' => 141.00,
    ]);

    $forecast = $this->queries->forecast($user);
    $edf = $forecast['outgoings']->firstWhere('name', 'EDF');

    expect($edf['status'])->toBe('gone')
        ->and($edf['amount'])->toBe(141.0);

    Carbon::setTestNow();
});

test('forecast classifies bills as upcoming when due in current period', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-14'));

    $user = User::factory()->create([
        'pay_cadence' => PayCadence::Weekly,
        'pay_day' => 3,
    ]);

    Bill::factory()->forUser($user)->weekly()->dueOn('2026-07-15')->create([
        'name' => 'Ted',
        'amount' => 70.00,
    ]);

    $forecast = $this->queries->forecast($user);
    $ted = $forecast['outgoings']->firstWhere('name', 'Ted');

    expect($ted['status'])->toBe('upcoming')
        ->and($ted['amount'])->toBe(70.0);

    Carbon::setTestNow();
});

test('forecast classifies bills as next_period when due after pay window', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-14'));

    $user = User::factory()->create([
        'pay_cadence' => PayCadence::Weekly,
        'pay_day' => 3,
    ]);

    Bill::factory()->forUser($user)->monthly()->dueOn('2026-07-21')->create([
        'name' => 'Bruce groom',
        'amount' => 45.00,
    ]);

    $forecast = $this->queries->forecast($user);
    $groom = $forecast['outgoings']->firstWhere('name', 'Bruce groom');

    expect($groom['status'])->toBe('next_period')
        ->and($groom['amount'])->toBe(45.0);

    Carbon::setTestNow();
});

test('committed total only includes items in current pay period', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-14'));

    $user = User::factory()->create([
        'pay_cadence' => PayCadence::Weekly,
        'pay_day' => 3,
    ]);

    // In-window bills
    Bill::factory()->forUser($user)->monthly()->dueOn('2026-07-10')->create(['amount' => 141.00]);
    Bill::factory()->forUser($user)->weekly()->dueOn('2026-07-15')->create(['amount' => 70.00]);

    // After window
    Bill::factory()->forUser($user)->monthly()->dueOn('2026-07-21')->create(['amount' => 45.00]);

    $forecast = $this->queries->forecast($user);

    expect($forecast['committed'])->toBe(211.0)
        ->and($forecast['outgoings'])->toHaveCount(3);

    Carbon::setTestNow();
});

test('remaining and daily_budget calculate correctly', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-14'));

    $user = User::factory()->create([
        'pay_cadence' => PayCadence::Weekly,
        'pay_day' => 3,
    ]);

    Transaction::factory()->forUser($user)->income()->create([
        'amount' => 700.00,
        'payment_date' => '2026-07-09',
    ]);

    Bill::factory()->forUser($user)->monthly()->dueOn('2026-07-10')->create(['amount' => 141.00]);
    Bill::factory()->forUser($user)->weekly()->dueOn('2026-07-15')->create(['amount' => 70.00]);

    $forecast = $this->queries->forecast($user);

    expect($forecast['income'])->toBe(700.0)
        ->and($forecast['committed'])->toBe(211.0)
        ->and($forecast['remaining'])->toBe(489.0)
        ->and($forecast['daily_budget'])->toBe(489.0); // 1 day left

    Carbon::setTestNow();
});

test('forecast includes bnpl installments', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-14'));

    $user = User::factory()->create([
        'pay_cadence' => PayCadence::Weekly,
        'pay_day' => 3,
    ]);

    $purchase = BnplPurchase::factory()->create([
        'user_id' => $user->id,
        'merchant' => 'Nike',
    ]);

    BnplInstallment::factory()->create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'amount' => 25.00,
        'due_date' => '2026-07-12',
        'is_paid' => false,
    ]);

    $forecast = $this->queries->forecast($user);
    $nike = $forecast['outgoings']->firstWhere('name', 'Nike');

    expect($nike)->not->toBeNull()
        ->and($nike['type'])->toBe('bnpl')
        ->and($nike['amount'])->toBe(25.0)
        ->and($nike['status'])->toBe('gone');

    Carbon::setTestNow();
});

test('forecast excludes paid bnpl installments', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-14'));

    $user = User::factory()->create([
        'pay_cadence' => PayCadence::Weekly,
        'pay_day' => 3,
    ]);

    $purchase = BnplPurchase::factory()->create([
        'user_id' => $user->id,
        'merchant' => 'ASOS',
    ]);

    BnplInstallment::factory()->create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'amount' => 30.00,
        'due_date' => '2026-07-12',
        'is_paid' => true,
    ]);

    $forecast = $this->queries->forecast($user);

    expect($forecast['outgoings']->firstWhere('name', 'ASOS'))->toBeNull();

    Carbon::setTestNow();
});

test('forecast works with no outgoings', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-14'));

    $user = User::factory()->create([
        'pay_cadence' => PayCadence::Weekly,
        'pay_day' => 3,
    ]);

    Transaction::factory()->forUser($user)->income()->create([
        'amount' => 700.00,
        'payment_date' => '2026-07-09',
    ]);

    $forecast = $this->queries->forecast($user);

    expect($forecast['outgoings'])->toHaveCount(0)
        ->and($forecast['committed'])->toBe(0.0)
        ->and($forecast['remaining'])->toBe(700.0);

    Carbon::setTestNow();
});

test('forecast outgoings are sorted by due date', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-14'));

    $user = User::factory()->create([
        'pay_cadence' => PayCadence::Weekly,
        'pay_day' => 3,
    ]);

    Bill::factory()->forUser($user)->monthly()->dueOn('2026-07-15')->create(['name' => 'Later']);
    Bill::factory()->forUser($user)->monthly()->dueOn('2026-07-10')->create(['name' => 'Earlier']);

    $forecast = $this->queries->forecast($user);

    expect($forecast['outgoings']->first()['name'])->toBe('Earlier')
        ->and($forecast['outgoings']->last()['name'])->toBe('Later');

    Carbon::setTestNow();
});
