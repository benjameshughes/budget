<?php

declare(strict_types=1);

use App\Actions\PennyChallenge\CreatePennyChallengeAction;
use App\DataTransferObjects\Actions\CreatePennyChallengeData;
use App\Models\PennyChallenge;
use App\Models\User;
use Carbon\Carbon;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('it creates a penny challenge with correct attributes', function () {
    $action = app(CreatePennyChallengeAction::class);

    $data = new CreatePennyChallengeData(
        userId: $this->user->id,
        name: '2026 1p Challenge',
        startDate: Carbon::create(2026, 1, 1),
        endDate: Carbon::create(2026, 12, 31),
    );

    $challenge = $action->handle($data);

    expect($challenge)->toBeInstanceOf(PennyChallenge::class)
        ->and($challenge->user_id)->toBe($this->user->id)
        ->and($challenge->name)->toBe('2026 1p Challenge')
        ->and($challenge->start_date->format('Y-m-d'))->toBe('2026-01-01')
        ->and($challenge->end_date->format('Y-m-d'))->toBe('2026-12-31');
});

test('it generates all days for a 365 day challenge', function () {
    $action = app(CreatePennyChallengeAction::class);

    $data = new CreatePennyChallengeData(
        userId: $this->user->id,
        name: '2026 1p Challenge',
        startDate: Carbon::create(2026, 1, 1),
        endDate: Carbon::create(2026, 12, 31),
    );

    $challenge = $action->handle($data);

    expect($challenge->days()->count())->toBe(365)
        ->and($challenge->days()->min('day_number'))->toBe(1)
        ->and($challenge->days()->max('day_number'))->toBe(365);
});

test('it generates all days for a leap year challenge', function () {
    $action = app(CreatePennyChallengeAction::class);

    $data = new CreatePennyChallengeData(
        userId: $this->user->id,
        name: '2024 1p Challenge',
        startDate: Carbon::create(2024, 1, 1),
        endDate: Carbon::create(2024, 12, 31),
    );

    $challenge = $action->handle($data);

    expect($challenge->days()->count())->toBe(366);
});

test('it creates days in pending state', function () {
    $action = app(CreatePennyChallengeAction::class);

    $data = new CreatePennyChallengeData(
        userId: $this->user->id,
        name: 'Test Challenge',
        startDate: Carbon::create(2026, 1, 1),
        endDate: Carbon::create(2026, 1, 10),
    );

    $challenge = $action->handle($data);

    expect($challenge->days()->whereNull('deposited_at')->count())->toBe(10)
        ->and($challenge->days()->whereNotNull('deposited_at')->count())->toBe(0)
        ->and($challenge->days()->whereNotNull('transaction_id')->count())->toBe(0);
});

test('it calculates total possible amount correctly', function () {
    $action = app(CreatePennyChallengeAction::class);

    // 10 days: 1+2+3+4+5+6+7+8+9+10 = 55 pence = £0.55
    $data = new CreatePennyChallengeData(
        userId: $this->user->id,
        name: 'Test Challenge',
        startDate: Carbon::create(2026, 1, 1),
        endDate: Carbon::create(2026, 1, 10),
    );

    $challenge = $action->handle($data);

    expect($challenge->totalPossible())->toBe(0.55)
        ->and($challenge->totalDays())->toBe(10);
});

test('it calculates total for 365 days correctly', function () {
    $action = app(CreatePennyChallengeAction::class);

    $data = new CreatePennyChallengeData(
        userId: $this->user->id,
        name: '2026 1p Challenge',
        startDate: Carbon::create(2026, 1, 1),
        endDate: Carbon::create(2026, 12, 31),
    );

    $challenge = $action->handle($data);

    // 365 * 366 / 2 = 66795 pence = £667.95
    expect($challenge->totalPossible())->toBe(667.95);
});
