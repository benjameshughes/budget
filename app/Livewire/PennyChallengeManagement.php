<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\PennyChallenge\MarkDaysDepositedAction;
use App\Models\PennyChallenge;
use App\Models\PennyChallengeDay;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class PennyChallengeManagement extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    /** @var array<int> */
    public array $selectedDays = [];

    public bool $showDepositModal = false;

    public bool $showDeleteModal = false;

    #[On('penny-challenge-created')]
    public function refresh(): void
    {
        unset($this->challenge);
        unset($this->stats);
        $this->resetPage();
    }

    #[Computed]
    public function challenge(): ?PennyChallenge
    {
        return PennyChallenge::where('user_id', auth()->id())
            ->latest()
            ->first();
    }

    #[Computed]
    public function days(): ?LengthAwarePaginator
    {
        if (! $this->challenge) {
            return null;
        }

        return PennyChallengeDay::with('challenge')
            ->where('penny_challenge_id', $this->challenge->id)
            ->orderByRaw('deposited_at IS NOT NULL ASC')
            ->orderBy('day_number', 'asc')
            ->paginate(50);
    }

    #[Computed]
    public function stats(): array
    {
        if (! $this->challenge) {
            return [
                'totalDeposited' => 0,
                'totalPossible' => 0,
                'totalRemaining' => 0,
                'depositedCount' => 0,
                'totalDays' => 0,
                'progressPercentage' => 0,
            ];
        }

        return [
            'totalDeposited' => $this->challenge->totalDeposited(),
            'totalPossible' => $this->challenge->totalPossible(),
            'totalRemaining' => $this->challenge->totalRemaining(),
            'depositedCount' => $this->challenge->depositedCount(),
            'totalDays' => $this->challenge->totalDays(),
            'progressPercentage' => $this->challenge->progressPercentage(),
        ];
    }

    #[Computed]
    public function selectedTotal(): float
    {
        if (empty($this->selectedDays) || ! $this->challenge) {
            return 0;
        }

        return PennyChallengeDay::whereIn('id', $this->selectedDays)
            ->whereNull('deposited_at')
            ->sum('day_number') / 100;
    }

    public function toggleDay(int $dayId): void
    {
        if (in_array($dayId, $this->selectedDays)) {
            $this->selectedDays = array_values(array_diff($this->selectedDays, [$dayId]));
        } else {
            $this->selectedDays[] = $dayId;
        }

        unset($this->selectedTotal);
    }

    public function selectAllPending(): void
    {
        if (! $this->challenge) {
            return;
        }

        $this->selectedDays = $this->challenge->pendingDays()
            ->pluck('id')
            ->toArray();

        unset($this->selectedTotal);
    }

    public function clearSelection(): void
    {
        $this->selectedDays = [];
        unset($this->selectedTotal);
    }

    public function openDepositModal(): void
    {
        if (empty($this->selectedDays)) {
            return;
        }

        $this->showDepositModal = true;
    }

    public function closeDepositModal(): void
    {
        $this->showDepositModal = false;
    }

    public function openDeleteModal(): void
    {
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
    }

    public function deleteChallenge(): void
    {
        if (! $this->challenge) {
            return;
        }

        $name = $this->challenge->name;
        $this->challenge->days()->delete();
        $this->challenge->delete();

        Flux::toast(
            text: "'{$name}' has been deleted",
            heading: 'Challenge Deleted',
            variant: 'success'
        );

        $this->showDeleteModal = false;
        $this->selectedDays = [];
        unset($this->challenge);
        unset($this->days);
        unset($this->stats);
        unset($this->selectedTotal);
    }

    public function markDeposited(MarkDaysDepositedAction $action): void
    {
        if (empty($this->selectedDays) || ! $this->challenge) {
            return;
        }

        try {
            $transaction = $action->handle($this->challenge, $this->selectedDays);

            $count = count($this->selectedDays);
            $amount = number_format((float) $transaction->amount, 2);

            Flux::toast(
                text: "{$count} days marked as deposited (£{$amount})",
                heading: 'Deposit recorded',
                variant: 'success'
            );

            $this->selectedDays = [];
            $this->showDepositModal = false;
            unset($this->challenge);
            unset($this->days);
            unset($this->stats);
            unset($this->selectedTotal);
        } catch (\Exception $e) {
            Flux::toast(
                text: $e->getMessage(),
                heading: 'Error',
                variant: 'danger'
            );
        }
    }

    public function render()
    {
        return view('livewire.penny-challenge-management');
    }
}
