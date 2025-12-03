<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class WeeklyBudget extends Component
{
    public string $weekly_budget = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->weekly_budget = (string) (Auth::user()->weekly_budget ?? '0.00');
    }

    /**
     * Update the weekly budget for the currently authenticated user.
     */
    public function updateWeeklyBudget(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'weekly_budget' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        ]);

        $user->fill($validated);
        $user->save();

        $this->dispatch('weekly-budget-updated');
    }

    public function render()
    {
        return view('livewire.settings.weekly-budget');
    }
}
