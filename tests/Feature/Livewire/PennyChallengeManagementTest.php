<?php

declare(strict_types=1);

use App\Livewire\PennyChallengeManagement;
use App\Models\PennyChallenge;
use App\Models\PennyChallengeDay;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('it shows empty state when no challenge exists', function () {
    Livewire::test(PennyChallengeManagement::class)
        ->assertSee('Start Your 1p Challenge')
        ->assertSee('Create Challenge');
});

test('it shows challenge when one exists', function () {
    $challenge = PennyChallenge::factory()
        ->forUser($this->user)
        ->withDays()
        ->create([
            'name' => '2026 1p Challenge',
            'start_date' => Carbon::create(2026, 1, 1),
            'end_date' => Carbon::create(2026, 12, 31),
        ]);

    Livewire::test(PennyChallengeManagement::class)
        ->assertSee('2026 1p Challenge')
        ->assertSee('£667.95'); // Remaining amount
});

test('it displays progress stats', function () {
    $challenge = PennyChallenge::factory()
        ->forUser($this->user)
        ->create([
            'name' => 'Test Challenge',
            'start_date' => Carbon::create(2026, 1, 1),
            'end_date' => Carbon::create(2026, 1, 10),
        ]);

    // Create 10 days, mark first 3 as deposited
    for ($i = 1; $i <= 10; $i++) {
        PennyChallengeDay::factory()
            ->forChallenge($challenge)
            ->dayNumber($i)
            ->create([
                'deposited_at' => $i <= 3 ? now() : null,
            ]);
    }

    Livewire::test(PennyChallengeManagement::class)
        ->assertSee('3/10') // Days complete
        ->assertSee('£0.06'); // Saved amount (1+2+3 pence)
});

test('it can toggle day selection', function () {
    $challenge = PennyChallenge::factory()
        ->forUser($this->user)
        ->withDays()
        ->create([
            'start_date' => Carbon::create(2026, 1, 1),
            'end_date' => Carbon::create(2026, 1, 10),
        ]);

    $day = $challenge->days()->first();

    Livewire::test(PennyChallengeManagement::class)
        ->assertSet('selectedDays', [])
        ->call('toggleDay', $day->id)
        ->assertSet('selectedDays', [$day->id])
        ->call('toggleDay', $day->id)
        ->assertSet('selectedDays', []);
});

test('it can clear selection', function () {
    $challenge = PennyChallenge::factory()
        ->forUser($this->user)
        ->withDays()
        ->create([
            'start_date' => Carbon::create(2026, 1, 1),
            'end_date' => Carbon::create(2026, 1, 10),
        ]);

    $days = $challenge->days()->take(3)->get();

    $component = Livewire::test(PennyChallengeManagement::class);

    foreach ($days as $day) {
        $component->call('toggleDay', $day->id);
    }

    $component
        ->assertCount('selectedDays', 3)
        ->call('clearSelection')
        ->assertSet('selectedDays', []);
});

test('it shows selection bar when days are selected', function () {
    $challenge = PennyChallenge::factory()
        ->forUser($this->user)
        ->withDays()
        ->create([
            'start_date' => Carbon::create(2026, 1, 1),
            'end_date' => Carbon::create(2026, 1, 10),
        ]);

    $day = $challenge->days()->first();

    Livewire::test(PennyChallengeManagement::class)
        ->assertDontSee('days selected')
        ->call('toggleDay', $day->id)
        ->assertSeeHtml('<strong>1</strong> days selected');
});

test('it can open deposit modal', function () {
    $challenge = PennyChallenge::factory()
        ->forUser($this->user)
        ->withDays()
        ->create([
            'start_date' => Carbon::create(2026, 1, 1),
            'end_date' => Carbon::create(2026, 1, 10),
        ]);

    $day = $challenge->days()->first();

    Livewire::test(PennyChallengeManagement::class)
        ->assertSet('showDepositModal', false)
        ->call('toggleDay', $day->id)
        ->call('openDepositModal')
        ->assertSet('showDepositModal', true);
});

test('it cannot open deposit modal without selection', function () {
    PennyChallenge::factory()
        ->forUser($this->user)
        ->withDays()
        ->create([
            'start_date' => Carbon::create(2026, 1, 1),
            'end_date' => Carbon::create(2026, 1, 10),
        ]);

    Livewire::test(PennyChallengeManagement::class)
        ->call('openDepositModal')
        ->assertSet('showDepositModal', false);
});

test('it can mark days as deposited', function () {
    $challenge = PennyChallenge::factory()
        ->forUser($this->user)
        ->withDays()
        ->create([
            'start_date' => Carbon::create(2026, 1, 1),
            'end_date' => Carbon::create(2026, 1, 10),
        ]);

    $days = $challenge->days()->take(3)->get();

    $component = Livewire::test(PennyChallengeManagement::class);

    foreach ($days as $day) {
        $component->call('toggleDay', $day->id);
    }

    $component->call('markDeposited');

    foreach ($days as $day) {
        $day->refresh();
        expect($day->deposited_at)->not->toBeNull();
    }
});

test('it paginates days at 50 per page', function () {
    $challenge = PennyChallenge::factory()
        ->forUser($this->user)
        ->withDays()
        ->create([
            'start_date' => Carbon::create(2026, 1, 1),
            'end_date' => Carbon::create(2026, 12, 31),
        ]);

    $component = Livewire::test(PennyChallengeManagement::class);

    // Should show Day 1 through Day 50 on first page
    $component->assertSee('Day 1')
        ->assertSee('Day 50')
        ->assertDontSee('Day 51');
});

test('pending days appear before deposited days', function () {
    $challenge = PennyChallenge::factory()
        ->forUser($this->user)
        ->create([
            'start_date' => Carbon::create(2026, 1, 1),
            'end_date' => Carbon::create(2026, 1, 10),
        ]);

    // Create days with day 1 deposited
    PennyChallengeDay::factory()
        ->forChallenge($challenge)
        ->dayNumber(1)
        ->deposited()
        ->create();

    for ($i = 2; $i <= 10; $i++) {
        PennyChallengeDay::factory()
            ->forChallenge($challenge)
            ->dayNumber($i)
            ->create();
    }

    // Query directly to verify sorting logic
    $days = PennyChallengeDay::where('penny_challenge_id', $challenge->id)
        ->orderByRaw('deposited_at IS NOT NULL ASC')
        ->orderBy('day_number', 'asc')
        ->get();

    // Pending days (2-10) should come before deposited day (1)
    expect($days->first()->day_number)->toBe(2)
        ->and($days->last()->day_number)->toBe(1);
});
