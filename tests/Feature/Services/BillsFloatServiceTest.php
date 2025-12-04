<?php

declare(strict_types=1);

use App\Enums\BillCadence;
use App\Enums\TransferDirection;
use App\Models\Bill;
use App\Models\SavingsAccount;
use App\Models\SavingsTransfer;
use App\Models\User;
use App\Services\BillsFloatService;

test('returns not configured when user has no bills', function () {
    $user = User::factory()->create();
    $service = app(BillsFloatService::class);

    $status = $service->status($user);

    expect($status['is_configured'])->toBeFalse()
        ->and($status['monthly_bills_total'])->toBe(0.0)
        ->and($status['weekly_contribution'])->toBe(0.0)
        ->and($status['target'])->toBe(0.0)
        ->and($status['message'])->toBe('Add bills to get started');
});

test('calculates monthly bills total from actual bills', function () {
    $user = User::factory()->create();

    // Create bills with different cadences
    Bill::factory()->create([
        'user_id' => $user->id,
        'amount' => 100,
        'cadence' => BillCadence::Weekly,
        'interval_every' => 1,
        'active' => true,
    ]);

    Bill::factory()->create([
        'user_id' => $user->id,
        'amount' => 500,
        'cadence' => BillCadence::Monthly,
        'interval_every' => 1,
        'active' => true,
    ]);

    Bill::factory()->create([
        'user_id' => $user->id,
        'amount' => 1200,
        'cadence' => BillCadence::Yearly,
        'interval_every' => 1,
        'active' => true,
    ]);

    $service = app(BillsFloatService::class);
    $status = $service->status($user);

    // Weekly: 100 * (52/12) = 433.33
    // Monthly: 500 * 1 = 500
    // Yearly: 1200 * (1/12) = 100
    // Total: 1033.33
    expect($status['monthly_bills_total'])->toBeGreaterThan(1033.0)
        ->and($status['monthly_bills_total'])->toBeLessThan(1034.0);
});

test('excludes inactive bills from calculation', function () {
    $user = User::factory()->create();

    Bill::factory()->create([
        'user_id' => $user->id,
        'amount' => 100,
        'cadence' => BillCadence::Monthly,
        'interval_every' => 1,
        'active' => true,
    ]);

    Bill::factory()->create([
        'user_id' => $user->id,
        'amount' => 200,
        'cadence' => BillCadence::Monthly,
        'interval_every' => 1,
        'active' => false, // Inactive
    ]);

    $service = app(BillsFloatService::class);
    $status = $service->status($user);

    expect($status['monthly_bills_total'])->toBe(100.0);
});

test('calculates weekly contribution correctly', function () {
    $user = User::factory()->create();

    Bill::factory()->create([
        'user_id' => $user->id,
        'amount' => 433,
        'cadence' => BillCadence::Monthly,
        'interval_every' => 1,
        'active' => true,
    ]);

    $service = app(BillsFloatService::class);
    $status = $service->status($user);

    // 433 / 4.33 = 100
    expect($status['weekly_contribution'])->toBeGreaterThan(99.9)
        ->and($status['weekly_contribution'])->toBeLessThan(100.1);
});

test('uses calculated monthly total as target when bills_float_target is null', function () {
    $user = User::factory()->create(['bills_float_target' => null]);
    $billsFloatAccount = SavingsAccount::factory()->create([
        'user_id' => $user->id,
        'is_bills_float' => true,
    ]);

    Bill::factory()->create([
        'user_id' => $user->id,
        'amount' => 500,
        'cadence' => BillCadence::Monthly,
        'interval_every' => 1,
        'active' => true,
    ]);

    $service = app(BillsFloatService::class);
    $status = $service->status($user);

    expect($status['target'])->toBe(500.0)
        ->and($status['monthly_bills_total'])->toBe(500.0);
});

test('uses bills_float_target override when set', function () {
    $user = User::factory()->create(['bills_float_target' => 1000]);
    $billsFloatAccount = SavingsAccount::factory()->create([
        'user_id' => $user->id,
        'is_bills_float' => true,
    ]);

    Bill::factory()->create([
        'user_id' => $user->id,
        'amount' => 500,
        'cadence' => BillCadence::Monthly,
        'interval_every' => 1,
        'active' => true,
    ]);

    $service = app(BillsFloatService::class);
    $status = $service->status($user);

    expect($status['target'])->toBe(1000.0)
        ->and($status['monthly_bills_total'])->toBe(500.0);
});

test('shows create account message when no bills float account exists', function () {
    $user = User::factory()->create();

    Bill::factory()->create([
        'user_id' => $user->id,
        'amount' => 100,
        'cadence' => BillCadence::Monthly,
        'interval_every' => 1,
        'active' => true,
    ]);

    $service = app(BillsFloatService::class);
    $status = $service->status($user);

    expect($status['message'])->toBe('Create a bills float savings account')
        ->and($status['is_configured'])->toBeTrue()
        ->and($status['is_healthy'])->toBeFalse();
});

test('calculates progress percentage correctly', function () {
    $user = User::factory()->create(['bills_float_target' => null]);

    Bill::factory()->create([
        'user_id' => $user->id,
        'amount' => 1000,
        'cadence' => BillCadence::Monthly,
        'interval_every' => 1,
        'active' => true,
    ]);

    $billsFloatAccount = SavingsAccount::factory()->create([
        'user_id' => $user->id,
        'is_bills_float' => true,
    ]);

    // Create a deposit to set balance to 500
    SavingsTransfer::factory()->create([
        'user_id' => $user->id,
        'savings_account_id' => $billsFloatAccount->id,
        'amount' => 500,
        'direction' => TransferDirection::Deposit,
    ]);

    $service = app(BillsFloatService::class);
    $status = $service->status($user);

    expect($status['progress_percentage'])->toBe(50.0)
        ->and($status['is_healthy'])->toBeFalse();
});

test('marks as healthy when current balance meets or exceeds target', function () {
    $user = User::factory()->create(['bills_float_target' => null]);

    Bill::factory()->create([
        'user_id' => $user->id,
        'amount' => 1000,
        'cadence' => BillCadence::Monthly,
        'interval_every' => 1,
        'active' => true,
    ]);

    $billsFloatAccount = SavingsAccount::factory()->create([
        'user_id' => $user->id,
        'is_bills_float' => true,
    ]);

    // Create a deposit to set balance to 1200 (120% of target)
    SavingsTransfer::factory()->create([
        'user_id' => $user->id,
        'savings_account_id' => $billsFloatAccount->id,
        'amount' => 1200,
        'direction' => TransferDirection::Deposit,
    ]);

    $service = app(BillsFloatService::class);
    $status = $service->status($user);

    expect($status['is_healthy'])->toBeTrue()
        ->and($status['progress_percentage'])->toBe(100.0); // Capped at 100
});

test('uses multiplier to calculate target when set', function () {
    $user = User::factory()->create([
        'bills_float_target' => null,
        'bills_float_multiplier' => 1.5,
    ]);

    Bill::factory()->create([
        'user_id' => $user->id,
        'amount' => 1000,
        'cadence' => BillCadence::Monthly,
        'interval_every' => 1,
        'active' => true,
    ]);

    $billsFloatAccount = SavingsAccount::factory()->create([
        'user_id' => $user->id,
        'is_bills_float' => true,
    ]);

    $service = app(BillsFloatService::class);
    $status = $service->status($user);

    // Monthly bills = 1000, multiplier = 1.5, target should be 1500
    expect($status['target'])->toBe(1500.0)
        ->and($status['monthly_bills_total'])->toBe(1000.0)
        ->and($status['multiplier'])->toBe(1.5);
});

test('defaults to multiplier of 1.0 when not set', function () {
    $user = User::factory()->create([
        'bills_float_target' => null,
        'bills_float_multiplier' => null,
    ]);

    Bill::factory()->create([
        'user_id' => $user->id,
        'amount' => 500,
        'cadence' => BillCadence::Monthly,
        'interval_every' => 1,
        'active' => true,
    ]);

    $billsFloatAccount = SavingsAccount::factory()->create([
        'user_id' => $user->id,
        'is_bills_float' => true,
    ]);

    $service = app(BillsFloatService::class);
    $status = $service->status($user);

    // Monthly bills = 500, no multiplier set, should default to 1.0
    expect($status['target'])->toBe(500.0)
        ->and($status['multiplier'])->toBe(1.0);
});

test('bills_float_target overrides multiplier calculation', function () {
    $user = User::factory()->create([
        'bills_float_target' => 2000,
        'bills_float_multiplier' => 1.5,
    ]);

    Bill::factory()->create([
        'user_id' => $user->id,
        'amount' => 1000,
        'cadence' => BillCadence::Monthly,
        'interval_every' => 1,
        'active' => true,
    ]);

    $billsFloatAccount = SavingsAccount::factory()->create([
        'user_id' => $user->id,
        'is_bills_float' => true,
    ]);

    $service = app(BillsFloatService::class);
    $status = $service->status($user);

    // Target should be the override value, not multiplier calculation
    expect($status['target'])->toBe(2000.0)
        ->and($status['monthly_bills_total'])->toBe(1000.0)
        ->and($status['multiplier'])->toBe(1.5);
});

test('multiplier of 2.0 doubles the monthly target', function () {
    $user = User::factory()->create([
        'bills_float_target' => null,
        'bills_float_multiplier' => 2.0,
    ]);

    Bill::factory()->create([
        'user_id' => $user->id,
        'amount' => 800,
        'cadence' => BillCadence::Monthly,
        'interval_every' => 1,
        'active' => true,
    ]);

    $billsFloatAccount = SavingsAccount::factory()->create([
        'user_id' => $user->id,
        'is_bills_float' => true,
    ]);

    $service = app(BillsFloatService::class);
    $status = $service->status($user);

    // Monthly bills = 800, multiplier = 2.0, target should be 1600
    expect($status['target'])->toBe(1600.0)
        ->and($status['monthly_bills_total'])->toBe(800.0)
        ->and($status['multiplier'])->toBe(2.0);
});

test('includes unpaid BNPL installments in monthly total', function () {
    $user = User::factory()->create();

    // Create a monthly bill
    Bill::factory()->create([
        'user_id' => $user->id,
        'amount' => 500,
        'cadence' => BillCadence::Monthly,
        'interval_every' => 1,
        'active' => true,
    ]);

    // Create unpaid BNPL installments
    $purchase = \App\Models\BnplPurchase::factory()->create([
        'user_id' => $user->id,
        'total_amount' => 300,
    ]);

    \App\Models\BnplInstallment::factory()->create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'amount' => 100,
        'is_paid' => false,
    ]);

    \App\Models\BnplInstallment::factory()->create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'amount' => 100,
        'is_paid' => false,
    ]);

    $service = app(BillsFloatService::class);
    $status = $service->status($user);

    // Monthly bills = 500, BNPL = 200, total = 700
    expect($status['monthly_bills_total'])->toBe(500.0)
        ->and($status['monthly_bnpl_total'])->toBe(200.0)
        ->and($status['monthly_total'])->toBe(700.0)
        ->and($status['weekly_contribution'])->toBeGreaterThan(161.6)
        ->and($status['weekly_contribution'])->toBeLessThan(161.8);
});

test('excludes paid BNPL installments from calculation', function () {
    $user = User::factory()->create();

    Bill::factory()->create([
        'user_id' => $user->id,
        'amount' => 500,
        'cadence' => BillCadence::Monthly,
        'interval_every' => 1,
        'active' => true,
    ]);

    $purchase = \App\Models\BnplPurchase::factory()->create([
        'user_id' => $user->id,
        'total_amount' => 300,
    ]);

    // Unpaid installment
    \App\Models\BnplInstallment::factory()->create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'amount' => 100,
        'is_paid' => false,
    ]);

    // Paid installment (should be excluded)
    \App\Models\BnplInstallment::factory()->create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'amount' => 200,
        'is_paid' => true,
    ]);

    $service = app(BillsFloatService::class);
    $status = $service->status($user);

    // Monthly bills = 500, BNPL = 100 (only unpaid), total = 600
    expect($status['monthly_bills_total'])->toBe(500.0)
        ->and($status['monthly_bnpl_total'])->toBe(100.0)
        ->and($status['monthly_total'])->toBe(600.0);
});

test('calculates target with BNPL and multiplier', function () {
    $user = User::factory()->create([
        'bills_float_target' => null,
        'bills_float_multiplier' => 1.5,
    ]);

    Bill::factory()->create([
        'user_id' => $user->id,
        'amount' => 600,
        'cadence' => BillCadence::Monthly,
        'interval_every' => 1,
        'active' => true,
    ]);

    $purchase = \App\Models\BnplPurchase::factory()->create([
        'user_id' => $user->id,
        'total_amount' => 200,
    ]);

    \App\Models\BnplInstallment::factory()->create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'amount' => 100,
        'is_paid' => false,
    ]);

    $billsFloatAccount = SavingsAccount::factory()->create([
        'user_id' => $user->id,
        'is_bills_float' => true,
    ]);

    $service = app(BillsFloatService::class);
    $status = $service->status($user);

    // Monthly bills = 600, BNPL = 100, total = 700
    // Target = 700 * 1.5 = 1050
    expect($status['monthly_bills_total'])->toBe(600.0)
        ->and($status['monthly_bnpl_total'])->toBe(100.0)
        ->and($status['monthly_total'])->toBe(700.0)
        ->and($status['target'])->toBe(1050.0)
        ->and($status['multiplier'])->toBe(1.5);
});

test('shows not configured when no bills or BNPL exist', function () {
    $user = User::factory()->create();
    $service = app(BillsFloatService::class);

    $status = $service->status($user);

    expect($status['is_configured'])->toBeFalse()
        ->and($status['monthly_bills_total'])->toBe(0.0)
        ->and($status['monthly_bnpl_total'])->toBe(0.0)
        ->and($status['monthly_total'])->toBe(0.0)
        ->and($status['weekly_contribution'])->toBe(0.0);
});

test('works with only BNPL and no bills', function () {
    $user = User::factory()->create();

    $purchase = \App\Models\BnplPurchase::factory()->create([
        'user_id' => $user->id,
        'total_amount' => 300,
    ]);

    \App\Models\BnplInstallment::factory()->create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $purchase->id,
        'amount' => 150,
        'is_paid' => false,
    ]);

    $billsFloatAccount = SavingsAccount::factory()->create([
        'user_id' => $user->id,
        'is_bills_float' => true,
    ]);

    $service = app(BillsFloatService::class);
    $status = $service->status($user);

    // No bills, but BNPL = 150
    expect($status['is_configured'])->toBeTrue()
        ->and($status['monthly_bills_total'])->toBe(0.0)
        ->and($status['monthly_bnpl_total'])->toBe(150.0)
        ->and($status['monthly_total'])->toBe(150.0)
        ->and($status['target'])->toBe(150.0)
        ->and($status['weekly_contribution'])->toBeGreaterThan(34.6)
        ->and($status['weekly_contribution'])->toBeLessThan(34.7);
});
