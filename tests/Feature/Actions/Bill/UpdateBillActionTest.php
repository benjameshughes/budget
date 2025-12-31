<?php

declare(strict_types=1);

use App\Actions\Bill\UpdateBillAction;
use App\DataTransferObjects\Actions\UpdateBillData;
use App\Enums\BillCadence;
use App\Models\Bill;
use App\Models\Category;
use App\Models\User;
use Carbon\Carbon;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('it updates a bill successfully', function () {
    $category = Category::factory()->for($this->user)->create();
    $bill = Bill::factory()->for($this->user)->monthly()->create([
        'name' => 'Old Name',
        'amount' => 50.00,
        'day_of_month' => 15,
        'next_due_date' => Carbon::parse('2025-01-15'),
        'interval_every' => 1,
    ]);

    $updateData = new UpdateBillData(
        name: 'New Name',
        amount: 75.50,
        cadence: BillCadence::Monthly,
        nextDueDate: Carbon::parse('2025-01-15'),
        categoryId: $category->id,
        intervalEvery: 1,
        notes: 'Updated notes',
    );

    $action = app(UpdateBillAction::class);
    $updatedBill = $action->handle($bill, $updateData);

    expect($updatedBill->name)->toBe('New Name')
        ->and((float) $updatedBill->amount)->toBe(75.50)
        ->and($updatedBill->category_id)->toBe($category->id)
        ->and($updatedBill->notes)->toBe('Updated notes');
});

test('it recalculates next_due_date when cadence changes', function () {
    $bill = Bill::factory()->for($this->user)->monthly()->create([
        'day_of_month' => 15,
        'start_date' => Carbon::parse('2025-01-15'),
        'next_due_date' => Carbon::parse('2025-02-15'),
    ]);

    $updateData = new UpdateBillData(
        name: $bill->name,
        amount: (float) $bill->amount,
        cadence: BillCadence::Weekly,
        nextDueDate: Carbon::parse('2025-01-20'), // Monday
        intervalEvery: 1,
    );

    $action = app(UpdateBillAction::class);
    $updatedBill = $action->handle($bill, $updateData);

    expect($updatedBill->cadence)->toBe(BillCadence::Weekly)
        ->and($updatedBill->weekday)->toBe(1) // Monday
        ->and($updatedBill->next_due_date->format('Y-m-d'))->toBe('2025-01-20');
});

test('it recalculates next_due_date when next_due_date changes', function () {
    $bill = Bill::factory()->for($this->user)->monthly()->create([
        'day_of_month' => 15,
        'start_date' => Carbon::parse('2025-01-15'),
        'next_due_date' => Carbon::parse('2025-02-15'),
    ]);

    $updateData = new UpdateBillData(
        name: $bill->name,
        amount: (float) $bill->amount,
        cadence: BillCadence::Monthly,
        nextDueDate: Carbon::parse('2025-01-05'),
        intervalEvery: 1,
    );

    $action = app(UpdateBillAction::class);
    $updatedBill = $action->handle($bill, $updateData);

    expect($updatedBill->day_of_month)->toBe(5)
        ->and($updatedBill->next_due_date->format('Y-m-d'))->toBe('2025-01-05');
});

test('it does not recalculate next_due_date when only non-scheduling fields change', function () {
    $originalNextDueDate = Carbon::parse('2025-02-15');
    $bill = Bill::factory()->for($this->user)->monthly()->create([
        'name' => 'Old Name',
        'amount' => 50.00,
        'day_of_month' => 15,
        'interval_every' => 1,
        'start_date' => Carbon::parse('2025-01-15'),
        'next_due_date' => $originalNextDueDate,
    ]);

    $updateData = new UpdateBillData(
        name: 'New Name',
        amount: 75.00,
        cadence: $bill->cadence,
        nextDueDate: $bill->next_due_date,
        intervalEvery: $bill->interval_every,
        notes: 'New notes',
    );

    $action = app(UpdateBillAction::class);
    $updatedBill = $action->handle($bill, $updateData);

    expect($updatedBill->name)->toBe('New Name')
        ->and((float) $updatedBill->amount)->toBe(75.00)
        ->and($updatedBill->next_due_date->format('Y-m-d'))->toBe($originalNextDueDate->format('Y-m-d'));
});

test('it authorizes the user can update the bill', function () {
    $otherUser = User::factory()->create();
    $bill = Bill::factory()->for($otherUser)->create();

    $updateData = new UpdateBillData(
        name: 'Hacked Name',
        amount: 100.00,
        cadence: BillCadence::Monthly,
        nextDueDate: Carbon::now(),
        intervalEvery: 1,
    );

    $action = app(UpdateBillAction::class);

    expect(fn () => $action->handle($bill, $updateData))
        ->toThrow(Illuminate\Auth\Access\AuthorizationException::class);
});

test('it updates interval_every correctly', function () {
    $bill = Bill::factory()->for($this->user)->monthly()->create([
        'interval_every' => 1,
        'next_due_date' => Carbon::parse('2025-01-15'),
    ]);

    $updateData = new UpdateBillData(
        name: $bill->name,
        amount: (float) $bill->amount,
        cadence: BillCadence::Monthly,
        nextDueDate: $bill->next_due_date,
        intervalEvery: 3,
    );

    $action = app(UpdateBillAction::class);
    $updatedBill = $action->handle($bill, $updateData);

    expect($updatedBill->interval_every)->toBe(3);
});

test('it updates autopay flag', function () {
    $bill = Bill::factory()->for($this->user)->create([
        'autopay' => false,
        'next_due_date' => Carbon::parse('2025-01-15'),
    ]);

    $updateData = new UpdateBillData(
        name: $bill->name,
        amount: (float) $bill->amount,
        cadence: $bill->cadence,
        nextDueDate: $bill->next_due_date,
        intervalEvery: $bill->interval_every,
        autopay: true,
    );

    $action = app(UpdateBillAction::class);
    $updatedBill = $action->handle($bill, $updateData);

    expect($updatedBill->autopay)->toBeTrue();
});

test('it can remove category from bill', function () {
    $category = Category::factory()->for($this->user)->create();
    $bill = Bill::factory()->for($this->user)->for($category)->create([
        'next_due_date' => Carbon::parse('2025-01-15'),
    ]);

    $updateData = new UpdateBillData(
        name: $bill->name,
        amount: (float) $bill->amount,
        cadence: $bill->cadence,
        nextDueDate: $bill->next_due_date,
        intervalEvery: $bill->interval_every,
        categoryId: null,
    );

    $action = app(UpdateBillAction::class);
    $updatedBill = $action->handle($bill, $updateData);

    expect($updatedBill->category_id)->toBeNull();
});

test('it updates start_date when next_due_date changes', function () {
    $bill = Bill::factory()->for($this->user)->monthly()->create([
        'day_of_month' => 10,
        'start_date' => Carbon::parse('2025-01-10'),
        'next_due_date' => Carbon::parse('2025-02-10'),
    ]);

    $updateData = new UpdateBillData(
        name: $bill->name,
        amount: (float) $bill->amount,
        cadence: BillCadence::Monthly,
        nextDueDate: Carbon::parse('2025-01-20'),
        intervalEvery: 1,
    );

    $action = app(UpdateBillAction::class);
    $updatedBill = $action->handle($bill, $updateData);

    expect($updatedBill->start_date->format('Y-m-d'))->toBe('2025-01-20')
        ->and($updatedBill->day_of_month)->toBe(20)
        ->and($updatedBill->next_due_date->format('Y-m-d'))->toBe('2025-01-20');
});
