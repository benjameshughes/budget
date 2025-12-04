<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\User;
use App\Services\ExpenseParserService;
use Carbon\Carbon;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;
use Prism\Prism\ValueObjects\Usage;

beforeEach(function () {
    $this->service = app(ExpenseParserService::class);
});

function fakePrismResponse(array $data): void
{
    Prism::fake([
        TextResponseFake::make()
            ->withText(json_encode($data))
            ->withUsage(new Usage(10, 20)),
    ]);
}

test('parse extracts expense data from natural language input', function () {
    $user = User::factory()->create();
    Category::factory()->create([
        'user_id' => $user->id,
        'name' => 'Food & Drink',
    ]);

    fakePrismResponse([
        'amount' => 4.50,
        'name' => 'Starbucks coffee',
        'type' => 'expense',
        'category' => 'Food & Drink',
        'date' => Carbon::today()->toDateString(),
        'confidence' => 0.95,
    ]);

    $result = $this->service->parse('Spent £4.50 at Starbucks', $user->id);

    expect($result)
        ->toBeInstanceOf(\App\DataTransferObjects\Actions\ParsedExpenseDto::class)
        ->and($result->amount)->toBe(4.50)
        ->and($result->name)->toBe('Starbucks coffee')
        ->and($result->type)->toBe('expense')
        ->and($result->categoryId)->not->toBeNull()
        ->and($result->date)->toBe(Carbon::today()->toDateString())
        ->and($result->confidence)->toBe(0.95)
        ->and($result->rawInput)->toBe('Spent £4.50 at Starbucks');
});

test('parse extracts income data from natural language input', function () {
    $user = User::factory()->create();

    fakePrismResponse([
        'amount' => 500.00,
        'name' => 'Freelance payment',
        'type' => 'income',
        'category' => null,
        'date' => Carbon::today()->toDateString(),
        'confidence' => 0.88,
    ]);

    $result = $this->service->parse('Got paid £500 for freelance work', $user->id);

    expect($result)
        ->toBeInstanceOf(\App\DataTransferObjects\Actions\ParsedExpenseDto::class)
        ->and($result->amount)->toBe(500.00)
        ->and($result->name)->toBe('Freelance payment')
        ->and($result->type)->toBe('income')
        ->and($result->categoryId)->toBeNull()
        ->and($result->confidence)->toBe(0.88);
});

test('parse handles category matching by name', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create([
        'user_id' => $user->id,
        'name' => 'Groceries',
    ]);

    fakePrismResponse([
        'amount' => 45.50,
        'name' => 'Tesco shopping',
        'type' => 'expense',
        'category' => 'Groceries',
        'date' => Carbon::today()->toDateString(),
        'confidence' => 0.92,
    ]);

    $result = $this->service->parse('£45.50 at Tesco', $user->id);

    expect($result->categoryId)->toBe($category->id)
        ->and($result->categoryName)->toBe('Groceries');
});

test('parse sets category_id to null when category does not match', function () {
    $user = User::factory()->create();
    Category::factory()->create([
        'user_id' => $user->id,
        'name' => 'Transport',
    ]);

    fakePrismResponse([
        'amount' => 10.00,
        'name' => 'Coffee',
        'type' => 'expense',
        'category' => 'Food & Drink',
        'date' => Carbon::today()->toDateString(),
        'confidence' => 0.85,
    ]);

    $result = $this->service->parse('£10 at Costa', $user->id);

    expect($result->categoryId)->toBeNull()
        ->and($result->categoryName)->toBe('Food & Drink');
});

test('parse defaults to expense when type is invalid', function () {
    $user = User::factory()->create();

    fakePrismResponse([
        'amount' => 20.00,
        'name' => 'Something',
        'type' => 'invalid_type',
        'category' => null,
        'date' => Carbon::today()->toDateString(),
        'confidence' => 0.60,
    ]);

    $result = $this->service->parse('£20 for something', $user->id);

    expect($result->type)->toBe('expense');
});

test('parse uses today as fallback date when date is null', function () {
    $user = User::factory()->create();

    fakePrismResponse([
        'amount' => 15.00,
        'name' => 'Lunch',
        'type' => 'expense',
        'category' => null,
        'date' => null,
        'confidence' => 0.75,
    ]);

    $result = $this->service->parse('£15 for lunch', $user->id);

    expect($result->date)->toBe(Carbon::today()->toDateString());
});

test('parse throws exception when API fails', function () {
    $user = User::factory()->create();

    Prism::fake([
        TextResponseFake::make()
            ->withText('Invalid JSON response without braces')
            ->withUsage(new Usage(10, 20)),
    ]);

    $this->service->parse('£10 at store', $user->id);
})->throws(\RuntimeException::class, 'Unable to parse transaction');

test('parse detects credit card payment', function () {
    $user = User::factory()->create();
    $creditCard = \App\Models\CreditCard::factory()->create([
        'user_id' => $user->id,
        'name' => 'Barclaycard',
    ]);

    fakePrismResponse([
        'amount' => 50.00,
        'name' => 'Credit card payment',
        'type' => 'expense',
        'category' => null,
        'date' => Carbon::today()->toDateString(),
        'credit_card' => 'Barclaycard',
        'is_credit_card_payment' => true,
        'payment_type' => 'credit_card_payment',
        'bill' => null,
        'bnpl_purchase' => null,
        'confidence' => 0.95,
    ]);

    $result = $this->service->parse('Paid £50 off Barclaycard', $user->id);

    expect($result)
        ->toBeInstanceOf(\App\DataTransferObjects\Actions\ParsedExpenseDto::class)
        ->and($result->amount)->toBe(50.00)
        ->and($result->paymentType)->toBe('credit_card_payment')
        ->and($result->isCreditCardPayment)->toBeTrue()
        ->and($result->creditCardId)->toBe($creditCard->id)
        ->and($result->billId)->toBeNull()
        ->and($result->bnplInstallmentId)->toBeNull();
});

test('parse detects bill payment with exact match', function () {
    $user = User::factory()->create();
    $bill = \App\Models\Bill::factory()->create([
        'user_id' => $user->id,
        'name' => 'Electricity',
        'active' => true,
    ]);

    fakePrismResponse([
        'amount' => 75.00,
        'name' => 'Electricity bill payment',
        'type' => 'expense',
        'category' => null,
        'date' => Carbon::today()->toDateString(),
        'credit_card' => null,
        'is_credit_card_payment' => false,
        'payment_type' => 'bill_payment',
        'bill' => 'Electricity',
        'bnpl_purchase' => null,
        'confidence' => 0.92,
    ]);

    $result = $this->service->parse('Paid electricity bill', $user->id);

    expect($result)
        ->toBeInstanceOf(\App\DataTransferObjects\Actions\ParsedExpenseDto::class)
        ->and($result->amount)->toBe(75.00)
        ->and($result->paymentType)->toBe('bill_payment')
        ->and($result->billId)->toBe($bill->id)
        ->and($result->billName)->toBe('Electricity')
        ->and($result->isCreditCardPayment)->toBeFalse()
        ->and($result->bnplInstallmentId)->toBeNull();
});

test('parse detects bill payment with fuzzy matching', function () {
    $user = User::factory()->create();
    $bill = \App\Models\Bill::factory()->create([
        'user_id' => $user->id,
        'name' => 'Gas Bill',
        'active' => true,
    ]);

    fakePrismResponse([
        'amount' => 60.00,
        'name' => 'Gas payment',
        'type' => 'expense',
        'category' => null,
        'date' => Carbon::today()->toDateString(),
        'credit_card' => null,
        'is_credit_card_payment' => false,
        'payment_type' => 'bill_payment',
        'bill' => 'gas',
        'bnpl_purchase' => null,
        'confidence' => 0.88,
    ]);

    $result = $this->service->parse('Paid gas', $user->id);

    expect($result)
        ->toBeInstanceOf(\App\DataTransferObjects\Actions\ParsedExpenseDto::class)
        ->and($result->amount)->toBe(60.00)
        ->and($result->paymentType)->toBe('bill_payment')
        ->and($result->billId)->toBe($bill->id)
        ->and($result->billName)->toBe('Gas Bill');
});

test('parse detects BNPL installment payment', function () {
    $user = User::factory()->create();
    $bnplPurchase = \App\Models\BnplPurchase::factory()->create([
        'user_id' => $user->id,
        'merchant' => 'Amazon',
        'provider' => \App\Enums\BnplProvider::Zilch,
    ]);

    $installment = \App\Models\BnplInstallment::factory()->create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $bnplPurchase->id,
        'amount' => 25.00,
        'is_paid' => false,
        'due_date' => Carbon::today()->addDays(5),
    ]);

    fakePrismResponse([
        'amount' => 25.00,
        'name' => 'Zilch installment payment',
        'type' => 'expense',
        'category' => null,
        'date' => Carbon::today()->toDateString(),
        'credit_card' => null,
        'is_credit_card_payment' => false,
        'payment_type' => 'bnpl_payment',
        'bill' => null,
        'bnpl_purchase' => 'Amazon (Zilch)',
        'confidence' => 0.90,
    ]);

    $result = $this->service->parse('Paid Zilch installment', $user->id);

    expect($result)
        ->toBeInstanceOf(\App\DataTransferObjects\Actions\ParsedExpenseDto::class)
        ->and($result->amount)->toBe(25.00)
        ->and($result->paymentType)->toBe('bnpl_payment')
        ->and($result->bnplInstallmentId)->toBe($installment->id)
        ->and($result->bnplPurchaseName)->toBe('Amazon (Zilch)')
        ->and($result->isCreditCardPayment)->toBeFalse()
        ->and($result->billId)->toBeNull();
});

test('parse skips inactive bills', function () {
    $user = User::factory()->create();
    \App\Models\Bill::factory()->create([
        'user_id' => $user->id,
        'name' => 'Old Internet Bill',
        'active' => false,
    ]);

    fakePrismResponse([
        'amount' => 30.00,
        'name' => 'Internet bill payment',
        'type' => 'expense',
        'category' => null,
        'date' => Carbon::today()->toDateString(),
        'credit_card' => null,
        'is_credit_card_payment' => false,
        'payment_type' => 'bill_payment',
        'bill' => 'Old Internet Bill',
        'bnpl_purchase' => null,
        'confidence' => 0.85,
    ]);

    $result = $this->service->parse('Paid internet bill', $user->id);

    expect($result->billId)->toBeNull()
        ->and($result->billName)->toBeNull();
});

test('parse skips BNPL purchases with no unpaid installments', function () {
    $user = User::factory()->create();
    $bnplPurchase = \App\Models\BnplPurchase::factory()->create([
        'user_id' => $user->id,
        'merchant' => 'Amazon',
        'provider' => \App\Enums\BnplProvider::Zilch,
    ]);

    // All installments are paid
    \App\Models\BnplInstallment::factory()->create([
        'user_id' => $user->id,
        'bnpl_purchase_id' => $bnplPurchase->id,
        'amount' => 25.00,
        'is_paid' => true,
        'paid_date' => Carbon::today()->subDays(5),
    ]);

    fakePrismResponse([
        'amount' => 25.00,
        'name' => 'Zilch installment payment',
        'type' => 'expense',
        'category' => null,
        'date' => Carbon::today()->toDateString(),
        'credit_card' => null,
        'is_credit_card_payment' => false,
        'payment_type' => 'bnpl_payment',
        'bill' => null,
        'bnpl_purchase' => 'Amazon (Zilch)',
        'confidence' => 0.90,
    ]);

    $result = $this->service->parse('Paid Zilch installment', $user->id);

    expect($result->bnplInstallmentId)->toBeNull()
        ->and($result->bnplPurchaseName)->toBeNull();
});

test('parse regular transaction still works correctly', function () {
    $user = User::factory()->create();
    Category::factory()->create([
        'user_id' => $user->id,
        'name' => 'Food & Drink',
    ]);

    fakePrismResponse([
        'amount' => 4.50,
        'name' => 'Starbucks coffee',
        'type' => 'expense',
        'category' => 'Food & Drink',
        'date' => Carbon::today()->toDateString(),
        'credit_card' => null,
        'is_credit_card_payment' => false,
        'payment_type' => 'regular',
        'bill' => null,
        'bnpl_purchase' => null,
        'confidence' => 0.95,
    ]);

    $result = $this->service->parse('Spent £4.50 at Starbucks', $user->id);

    expect($result)
        ->toBeInstanceOf(\App\DataTransferObjects\Actions\ParsedExpenseDto::class)
        ->and($result->amount)->toBe(4.50)
        ->and($result->paymentType)->toBe('regular')
        ->and($result->billId)->toBeNull()
        ->and($result->bnplInstallmentId)->toBeNull()
        ->and($result->isCreditCardPayment)->toBeFalse();
});

test('parse defaults to regular payment type when invalid type provided', function () {
    $user = User::factory()->create();

    fakePrismResponse([
        'amount' => 10.00,
        'name' => 'Something',
        'type' => 'expense',
        'category' => null,
        'date' => Carbon::today()->toDateString(),
        'credit_card' => null,
        'is_credit_card_payment' => false,
        'payment_type' => 'invalid_type',
        'bill' => null,
        'bnpl_purchase' => null,
        'confidence' => 0.70,
    ]);

    $result = $this->service->parse('£10 for something', $user->id);

    expect($result->paymentType)->toBe('regular');
});

test('parse detects savings transfer deposit', function () {
    $user = User::factory()->create();
    $savingsAccount = \App\Models\SavingsAccount::factory()->create([
        'user_id' => $user->id,
        'name' => 'Bills Pot',
        'is_bills_float' => true,
    ]);

    fakePrismResponse([
        'amount' => 216.71,
        'name' => 'Transfer to Bills Pot',
        'type' => 'expense',
        'category' => null,
        'date' => Carbon::today()->toDateString(),
        'credit_card' => null,
        'is_credit_card_payment' => false,
        'payment_type' => 'savings_transfer',
        'bill' => null,
        'bnpl_purchase' => null,
        'savings_account' => 'Bills Pot (Bills Pot)',
        'transfer_direction' => 'deposit',
        'bnpl_provider' => null,
        'bnpl_merchant' => null,
        'bnpl_fee' => null,
        'confidence' => 0.95,
    ]);

    $result = $this->service->parse('Transfer £216.71 to bills pot', $user->id);

    expect($result)
        ->toBeInstanceOf(\App\DataTransferObjects\Actions\ParsedExpenseDto::class)
        ->and($result->amount)->toBe(216.71)
        ->and($result->paymentType)->toBe('savings_transfer')
        ->and($result->savingsAccountId)->toBe($savingsAccount->id)
        ->and($result->transferDirection)->toBe('deposit');
});

test('parse detects savings transfer withdrawal', function () {
    $user = User::factory()->create();
    $savingsAccount = \App\Models\SavingsAccount::factory()->create([
        'user_id' => $user->id,
        'name' => 'Emergency Fund',
        'is_bills_float' => false,
    ]);

    fakePrismResponse([
        'amount' => 100.00,
        'name' => 'Withdrawal from Emergency Fund',
        'type' => 'expense',
        'category' => null,
        'date' => Carbon::today()->toDateString(),
        'credit_card' => null,
        'is_credit_card_payment' => false,
        'payment_type' => 'savings_transfer',
        'bill' => null,
        'bnpl_purchase' => null,
        'savings_account' => 'Emergency Fund',
        'transfer_direction' => 'withdraw',
        'bnpl_provider' => null,
        'bnpl_merchant' => null,
        'bnpl_fee' => null,
        'confidence' => 0.92,
    ]);

    $result = $this->service->parse('Withdraw £100 from emergency fund', $user->id);

    expect($result)
        ->toBeInstanceOf(\App\DataTransferObjects\Actions\ParsedExpenseDto::class)
        ->and($result->amount)->toBe(100.00)
        ->and($result->paymentType)->toBe('savings_transfer')
        ->and($result->savingsAccountId)->toBe($savingsAccount->id)
        ->and($result->transferDirection)->toBe('withdraw');
});

test('parse detects new BNPL purchase with Zilch', function () {
    $user = User::factory()->create();

    fakePrismResponse([
        'amount' => 200.00,
        'name' => 'Zilch purchase at Amazon',
        'type' => 'expense',
        'category' => 'Shopping',
        'date' => Carbon::today()->toDateString(),
        'credit_card' => null,
        'is_credit_card_payment' => false,
        'payment_type' => 'bnpl_purchase',
        'bill' => null,
        'bnpl_purchase' => null,
        'savings_account' => null,
        'transfer_direction' => null,
        'bnpl_provider' => 'Zilch',
        'bnpl_merchant' => 'Amazon',
        'bnpl_fee' => 2.50,
        'confidence' => 0.93,
    ]);

    $result = $this->service->parse('Spent £200 on Zilch at Amazon, fee was £2.50', $user->id);

    expect($result)
        ->toBeInstanceOf(\App\DataTransferObjects\Actions\ParsedExpenseDto::class)
        ->and($result->amount)->toBe(200.00)
        ->and($result->paymentType)->toBe('bnpl_purchase')
        ->and($result->bnplProvider)->toBe('zilch')
        ->and($result->bnplMerchant)->toBe('Amazon')
        ->and($result->bnplFee)->toBe(2.50);
});

test('parse detects new BNPL purchase without fee', function () {
    $user = User::factory()->create();

    fakePrismResponse([
        'amount' => 150.00,
        'name' => 'ClearPay purchase at Boots',
        'type' => 'expense',
        'category' => 'Health',
        'date' => Carbon::today()->toDateString(),
        'credit_card' => null,
        'is_credit_card_payment' => false,
        'payment_type' => 'bnpl_purchase',
        'bill' => null,
        'bnpl_purchase' => null,
        'savings_account' => null,
        'transfer_direction' => null,
        'bnpl_provider' => 'ClearPay',
        'bnpl_merchant' => 'Boots',
        'bnpl_fee' => null,
        'confidence' => 0.91,
    ]);

    $result = $this->service->parse('Used ClearPay for £150 at Boots', $user->id);

    expect($result)
        ->toBeInstanceOf(\App\DataTransferObjects\Actions\ParsedExpenseDto::class)
        ->and($result->amount)->toBe(150.00)
        ->and($result->paymentType)->toBe('bnpl_purchase')
        ->and($result->bnplProvider)->toBe('clearpay')
        ->and($result->bnplMerchant)->toBe('Boots')
        ->and($result->bnplFee)->toBeNull();
});

test('parse defaults transfer direction to deposit when invalid', function () {
    $user = User::factory()->create();
    $savingsAccount = \App\Models\SavingsAccount::factory()->create([
        'user_id' => $user->id,
        'name' => 'Holiday Fund',
    ]);

    fakePrismResponse([
        'amount' => 50.00,
        'name' => 'Holiday savings',
        'type' => 'expense',
        'category' => null,
        'date' => Carbon::today()->toDateString(),
        'credit_card' => null,
        'is_credit_card_payment' => false,
        'payment_type' => 'savings_transfer',
        'bill' => null,
        'bnpl_purchase' => null,
        'savings_account' => 'Holiday Fund',
        'transfer_direction' => 'invalid_direction',
        'bnpl_provider' => null,
        'bnpl_merchant' => null,
        'bnpl_fee' => null,
        'confidence' => 0.85,
    ]);

    $result = $this->service->parse('Put £50 in holiday fund', $user->id);

    expect($result->transferDirection)->toBe('deposit');
});
