<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Actions\PennyChallenge\CreatePennyChallengeAction;
use App\DataTransferObjects\Actions\CreatePennyChallengeData;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AddPennyChallenge extends Component
{
    public string $name = '';

    public ?string $start_date = null;

    public ?string $end_date = null;

    public function mount(): void
    {
        // Default to next year Jan 1 - Dec 31
        $year = now()->year + 1;
        $this->name = "{$year} 1p Challenge";
        $this->start_date = Carbon::create($year, 1, 1)->format('Y-m-d');
        $this->end_date = Carbon::create($year, 12, 31)->format('Y-m-d');
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ];
    }

    #[Computed]
    public function preview(): array
    {
        if (! $this->start_date || ! $this->end_date) {
            return ['days' => 0, 'total' => 0];
        }

        try {
            $start = Carbon::parse($this->start_date);
            $end = Carbon::parse($this->end_date);

            if ($end->lte($start)) {
                return ['days' => 0, 'total' => 0];
            }

            $days = (int) $start->diffInDays($end) + 1;
            $total = ($days * ($days + 1) / 2) / 100;

            return [
                'days' => $days,
                'total' => $total,
            ];
        } catch (\Exception) {
            return ['days' => 0, 'total' => 0];
        }
    }

    public function save(CreatePennyChallengeAction $action): void
    {
        $data = $this->validate();

        $action->handle(new CreatePennyChallengeData(
            userId: auth()->id(),
            name: $data['name'],
            startDate: Carbon::parse($data['start_date']),
            endDate: Carbon::parse($data['end_date']),
        ));

        Flux::toast(
            text: 'Challenge created with '.$this->preview['days'].' days',
            heading: 'Challenge Created',
            variant: 'success'
        );

        $this->dispatch('penny-challenge-created');
        Flux::modals()->close('add-penny-challenge');
    }

    public function render(): View
    {
        return view('livewire.components.add-penny-challenge');
    }
}
