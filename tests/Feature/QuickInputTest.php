<?php

declare(strict_types=1);

use App\Contracts\ExpenseParserInterface;
use App\DataTransferObjects\Actions\ParsedExpenseDto;
use App\Livewire\QuickInput;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ExpenseParserService;
use Livewire\Livewire;

class FakeQuickInputParser implements ExpenseParserInterface
{
    public function parse(string $input, int $userId): ParsedExpenseDto
    {
        return new ParsedExpenseDto(
            amount: 25.00,
            name: 'Tesco',
            type: 'expense',
            categoryId: null,
            categoryName: null,
            creditCardId: null,
            creditCardName: null,
            isCreditCardPayment: false,
            date: now()->toDateString(),
            confidence: 0.95,
            rawInput: $input,
        );
    }
}

function bindFakeParser(): void
{
    $fake = new FakeQuickInputParser;
    app()->instance(ExpenseParserService::class, $fake);
    app()->instance(ExpenseParserInterface::class, $fake);
}

test('quick input component can be rendered', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(QuickInput::class)
        ->assertStatus(200);
});

test('quick input can create a transaction', function () {
    $user = User::factory()->create();
    Category::factory()->create(['user_id' => $user->id, 'name' => 'Food']);

    bindFakeParser();

    Livewire::actingAs($user)
        ->test(QuickInput::class)
        ->set('input', '£25 at Tesco for groceries')
        ->call('submit')
        ->assertDispatched('transaction-added', fn ($name, $params) => isset($params['transactionId']))
        ->assertDispatched('close-quick-input');

    expect(Transaction::where('user_id', $user->id)->count())->toBe(1);
});

test('quick input clears after submission', function () {
    $user = User::factory()->create();

    bindFakeParser();

    Livewire::actingAs($user)
        ->test(QuickInput::class)
        ->set('input', '£10 coffee')
        ->call('submit')
        ->assertSet('input', '');
});

test('quick input does nothing with empty input', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(QuickInput::class)
        ->set('input', '')
        ->call('submit')
        ->assertNotDispatched('transaction-added');

    expect(Transaction::where('user_id', $user->id)->count())->toBe(0);
});

test('quick input can receive input via event', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(QuickInput::class)
        ->dispatch('quick-input-set', text: '£50 electricity bill')
        ->assertSet('input', '£50 electricity bill');
});
