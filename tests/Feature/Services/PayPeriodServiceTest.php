<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\PayPeriodService;
use Carbon\Carbon;

beforeEach(function () {
    $this->service = new PayPeriodService;
});

it('calculates pay period for Thursday pay day when today is Thursday', function () {
    Carbon::setTestNow(Carbon::parse('2025-12-04')); // Thursday

    $user = User::factory()->create(['pay_day' => 4]); // Thursday

    $period = $this->service->currentPeriod($user);

    expect($period['start']->format('Y-m-d'))->toBe('2025-12-04')
        ->and($period['end']->format('Y-m-d'))->toBe('2025-12-10');
});

it('calculates pay period for Thursday pay day when today is Friday', function () {
    Carbon::setTestNow(Carbon::parse('2025-12-05')); // Friday

    $user = User::factory()->create(['pay_day' => 4]); // Thursday

    $period = $this->service->currentPeriod($user);

    expect($period['start']->format('Y-m-d'))->toBe('2025-12-04') // Last Thursday
        ->and($period['end']->format('Y-m-d'))->toBe('2025-12-10'); // Wednesday
});

it('calculates pay period for Thursday pay day when today is Wednesday', function () {
    Carbon::setTestNow(Carbon::parse('2025-12-03')); // Wednesday

    $user = User::factory()->create(['pay_day' => 4]); // Thursday

    $period = $this->service->currentPeriod($user);

    expect($period['start']->format('Y-m-d'))->toBe('2025-11-27') // Last Thursday
        ->and($period['end']->format('Y-m-d'))->toBe('2025-12-03'); // Today (Wednesday)
});

it('calculates days remaining in period', function () {
    Carbon::setTestNow(Carbon::parse('2025-12-04')); // Thursday (pay day)

    $user = User::factory()->create(['pay_day' => 4]);

    $daysRemaining = $this->service->daysRemaining($user);

    expect($daysRemaining)->toBe(6); // Thu to Wed = 6 days remaining
});

it('calculates days remaining as zero on last day of period', function () {
    Carbon::setTestNow(Carbon::parse('2025-12-10')); // Wednesday (last day before Thursday)

    $user = User::factory()->create(['pay_day' => 4]);

    $daysRemaining = $this->service->daysRemaining($user);

    expect($daysRemaining)->toBe(0);
});

it('calculates current day of period', function () {
    Carbon::setTestNow(Carbon::parse('2025-12-04')); // Thursday

    $user = User::factory()->create(['pay_day' => 4]);

    $dayOfPeriod = $this->service->currentDayOfPeriod($user);

    expect($dayOfPeriod)->toBe(1); // Day 1 of the period
});

it('correctly identifies dates in current period', function () {
    Carbon::setTestNow(Carbon::parse('2025-12-06')); // Saturday

    $user = User::factory()->create(['pay_day' => 4]);

    // This Thursday (start of period)
    expect($this->service->isInCurrentPeriod($user, Carbon::parse('2025-12-04')))->toBeTrue();
    // Sunday (middle of period)
    expect($this->service->isInCurrentPeriod($user, Carbon::parse('2025-12-07')))->toBeTrue();
    // Wednesday (end of period)
    expect($this->service->isInCurrentPeriod($user, Carbon::parse('2025-12-10')))->toBeTrue();
    // Next Thursday (outside period)
    expect($this->service->isInCurrentPeriod($user, Carbon::parse('2025-12-11')))->toBeFalse();
    // Last Wednesday (outside period)
    expect($this->service->isInCurrentPeriod($user, Carbon::parse('2025-12-03')))->toBeFalse();
});

it('returns correct pay day name', function () {
    $user = User::factory()->create(['pay_day' => 4]);

    expect($this->service->payDayName($user))->toBe('Thursday');
});

it('handles Friday pay day correctly', function () {
    Carbon::setTestNow(Carbon::parse('2025-12-06')); // Saturday

    $user = User::factory()->create(['pay_day' => 5]); // Friday

    $period = $this->service->currentPeriod($user);

    expect($period['start']->format('Y-m-d'))->toBe('2025-12-05') // Last Friday
        ->and($period['end']->format('Y-m-d'))->toBe('2025-12-11'); // Thursday
});

it('handles Monday pay day correctly', function () {
    Carbon::setTestNow(Carbon::parse('2025-12-03')); // Wednesday

    $user = User::factory()->create(['pay_day' => 1]); // Monday

    $period = $this->service->currentPeriod($user);

    expect($period['start']->format('Y-m-d'))->toBe('2025-12-01') // Monday
        ->and($period['end']->format('Y-m-d'))->toBe('2025-12-07'); // Sunday
});

it('uses default Thursday when pay_day is null', function () {
    Carbon::setTestNow(Carbon::parse('2025-12-06')); // Saturday

    $user = User::factory()->create(['pay_day' => null]);

    $period = $this->service->currentPeriod($user);

    // Should default to Thursday
    expect($period['start']->format('Y-m-d'))->toBe('2025-12-04');
});

afterEach(function () {
    Carbon::setTestNow(); // Reset
});
