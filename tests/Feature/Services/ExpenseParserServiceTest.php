<?php

declare(strict_types=1);

use Anthropic\Laravel\Facades\Anthropic;
use Anthropic\Responses\Messages\CreateResponse;
use App\Models\Category;
use App\Models\User;
use App\Services\ExpenseParserService;
use Carbon\Carbon;

beforeEach(function () {
    $this->service = app(ExpenseParserService::class);
});

function fakeAnthropicResponse(array $data): void
{
    Anthropic::fake([
        CreateResponse::fake([
            'content' => [
                ['type' => 'text', 'text' => json_encode($data)],
            ],
        ]),
    ]);
}

test('parse extracts expense data from natural language input', function () {
    $user = User::factory()->create();
    Category::factory()->create([
        'user_id' => $user->id,
        'name' => 'Food & Drink',
    ]);

    fakeAnthropicResponse([
        'amount' => 4.50,
        'name' => 'Starbucks coffee',
        'type' => 'expense',
        'category' => 'Food & Drink',
        'date' => Carbon::today()->toDateString(),
        'confidence' => 0.95,
    ]);

    $result = $this->service->parse('Spent £4.50 at Starbucks', $user->id);

    expect($result)
        ->toBeArray()
        ->and($result['amount'])->toBe(4.50)
        ->and($result['name'])->toBe('Starbucks coffee')
        ->and($result['type'])->toBe('expense')
        ->and($result['category_id'])->not->toBeNull()
        ->and($result['date'])->toBe(Carbon::today()->toDateString())
        ->and($result['confidence'])->toBe(0.95)
        ->and($result['raw_input'])->toBe('Spent £4.50 at Starbucks');
});

test('parse extracts income data from natural language input', function () {
    $user = User::factory()->create();

    fakeAnthropicResponse([
        'amount' => 500.00,
        'name' => 'Freelance payment',
        'type' => 'income',
        'category' => null,
        'date' => Carbon::today()->toDateString(),
        'confidence' => 0.88,
    ]);

    $result = $this->service->parse('Got paid £500 for freelance work', $user->id);

    expect($result)
        ->toBeArray()
        ->and($result['amount'])->toBe(500.00)
        ->and($result['name'])->toBe('Freelance payment')
        ->and($result['type'])->toBe('income')
        ->and($result['category_id'])->toBeNull()
        ->and($result['confidence'])->toBe(0.88);
});

test('parse handles category matching by name', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create([
        'user_id' => $user->id,
        'name' => 'Groceries',
    ]);

    fakeAnthropicResponse([
        'amount' => 45.50,
        'name' => 'Tesco shopping',
        'type' => 'expense',
        'category' => 'Groceries',
        'date' => Carbon::today()->toDateString(),
        'confidence' => 0.92,
    ]);

    $result = $this->service->parse('£45.50 at Tesco', $user->id);

    expect($result['category_id'])->toBe($category->id)
        ->and($result['category_name'])->toBe('Groceries');
});

test('parse sets category_id to null when category does not match', function () {
    $user = User::factory()->create();
    Category::factory()->create([
        'user_id' => $user->id,
        'name' => 'Transport',
    ]);

    fakeAnthropicResponse([
        'amount' => 10.00,
        'name' => 'Coffee',
        'type' => 'expense',
        'category' => 'Food & Drink',
        'date' => Carbon::today()->toDateString(),
        'confidence' => 0.85,
    ]);

    $result = $this->service->parse('£10 at Costa', $user->id);

    expect($result['category_id'])->toBeNull()
        ->and($result['category_name'])->toBe('Food & Drink');
});

test('parse defaults to expense when type is invalid', function () {
    $user = User::factory()->create();

    fakeAnthropicResponse([
        'amount' => 20.00,
        'name' => 'Something',
        'type' => 'invalid_type',
        'category' => null,
        'date' => Carbon::today()->toDateString(),
        'confidence' => 0.60,
    ]);

    $result = $this->service->parse('£20 for something', $user->id);

    expect($result['type'])->toBe('expense');
});

test('parse uses today as fallback date when date is null', function () {
    $user = User::factory()->create();

    fakeAnthropicResponse([
        'amount' => 15.00,
        'name' => 'Lunch',
        'type' => 'expense',
        'category' => null,
        'date' => null,
        'confidence' => 0.75,
    ]);

    $result = $this->service->parse('£15 for lunch', $user->id);

    expect($result['date'])->toBe(Carbon::today()->toDateString());
});

test('parse throws exception when API fails', function () {
    $user = User::factory()->create();

    Anthropic::fake([
        new \Exception('API Error'),
    ]);

    $this->service->parse('£10 at store', $user->id);
})->throws(\Exception::class);
