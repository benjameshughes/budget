<?php

declare(strict_types=1);

use App\Enums\BillCadence;
use App\Livewire\Components\AddBill;
use App\Models\Bill;
use App\Models\Category;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('it can create a new bill', function () {
    $category = Category::factory()->for($this->user)->create();

    Livewire::test(AddBill::class)
        ->set('form.name', 'Test Bill')
        ->set('form.amount', '100.00')
        ->set('form.cadence', BillCadence::Monthly->value)
        ->set('form.next_due_date', '2025-01-15')
        ->set('form.interval_every', 1)
        ->set('form.category_id', (string) $category->id)
        ->set('form.notes', 'Test notes')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('bill-saved');

    expect(Bill::where('name', 'Test Bill')->exists())->toBeTrue();

    $bill = Bill::where('name', 'Test Bill')->first();
    expect($bill->user_id)->toBe($this->user->id)
        ->and((float) $bill->amount)->toBe(100.00)
        ->and($bill->cadence)->toBe(BillCadence::Monthly)
        ->and($bill->day_of_month)->toBe(15)
        ->and($bill->next_due_date->format('Y-m-d'))->toBe('2025-01-15')
        ->and($bill->category_id)->toBe($category->id)
        ->and($bill->notes)->toBe('Test notes');
});

test('it can edit an existing bill', function () {
    $category = Category::factory()->for($this->user)->create();
    $bill = Bill::factory()->for($this->user)->monthly()->create([
        'name' => 'Original Bill',
        'amount' => 50.00,
        'day_of_month' => 10,
    ]);

    Livewire::test(AddBill::class)
        ->call('editBill', $bill->id)
        ->set('form.name', 'Updated Bill')
        ->set('form.amount', '75.50')
        ->set('form.category_id', (string) $category->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('bill-saved');

    $bill->refresh();
    expect($bill->name)->toBe('Updated Bill')
        ->and((float) $bill->amount)->toBe(75.50)
        ->and($bill->category_id)->toBe($category->id);
});

test('it validates required fields when creating', function () {
    Livewire::test(AddBill::class)
        ->set('form.name', '')
        ->set('form.amount', '')
        ->set('form.cadence', '')
        ->set('form.next_due_date', '')
        ->call('save')
        ->assertHasErrors([
            'form.name',
            'form.amount',
            'form.cadence',
            'form.next_due_date',
        ]);
});

test('it validates required fields when editing', function () {
    $bill = Bill::factory()->for($this->user)->create();

    Livewire::test(AddBill::class)
        ->call('editBill', $bill->id)
        ->set('form.name', '')
        ->set('form.amount', '')
        ->call('save')
        ->assertHasErrors([
            'form.name',
            'form.amount',
        ]);
});

test('it prevents unauthorized users from editing bills', function () {
    $otherUser = User::factory()->create();
    $bill = Bill::factory()->for($otherUser)->create();

    Livewire::test(AddBill::class)
        ->call('editBill', $bill->id)
        ->assertForbidden();
});

test('it loads bill data correctly when editing', function () {
    $category = Category::factory()->for($this->user)->create();
    $bill = Bill::factory()->for($this->user)->for($category)->monthly()->create([
        'name' => 'My Bill',
        'amount' => 99.99,
        'day_of_month' => 20,
        'next_due_date' => '2025-01-20',
        'interval_every' => 2,
        'notes' => 'Some notes',
    ]);

    Livewire::test(AddBill::class)
        ->call('editBill', $bill->id)
        ->assertSet('form.name', 'My Bill')
        ->assertSet('form.amount', '99.99')
        ->assertSet('form.cadence', BillCadence::Monthly->value)
        ->assertSet('form.next_due_date', '2025-01-20')
        ->assertSet('form.interval_every', 2)
        ->assertSet('form.category_id', (string) $category->id)
        ->assertSet('form.notes', 'Some notes')
        ->assertSet('bill.id', $bill->id);
});

test('it resets form after successful creation', function () {
    Livewire::test(AddBill::class)
        ->set('form.name', 'Test Bill')
        ->set('form.amount', '100.00')
        ->set('form.cadence', BillCadence::Monthly->value)
        ->set('form.next_due_date', '2025-01-15')
        ->call('save')
        ->assertSet('form.name', '')
        ->assertSet('form.amount', '')
        ->assertSet('bill', null);
});

test('it validates amount is positive', function () {
    Livewire::test(AddBill::class)
        ->set('form.name', 'Test Bill')
        ->set('form.amount', '-10.00')
        ->set('form.cadence', BillCadence::Monthly->value)
        ->set('form.next_due_date', '2025-01-15')
        ->call('save')
        ->assertHasErrors(['form.amount']);
});

test('it can update bill cadence from monthly to weekly', function () {
    $bill = Bill::factory()->for($this->user)->monthly()->create([
        'day_of_month' => 15,
        'weekday' => null,
        'next_due_date' => '2025-01-20', // Monday
    ]);

    Livewire::test(AddBill::class)
        ->call('editBill', $bill->id)
        ->set('form.cadence', BillCadence::Weekly->value)
        ->set('form.next_due_date', '2025-01-20') // Monday
        ->call('save')
        ->assertHasNoErrors();

    $bill->refresh();
    expect($bill->cadence)->toBe(BillCadence::Weekly)
        ->and($bill->weekday)->toBe(1) // Monday
        ->and($bill->day_of_month)->toBeNull();
});
